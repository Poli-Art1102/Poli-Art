<?php
/**
 * artista/procesar_subida.php
 * Procesa el formulario de subida de obra (POST multipart/form-data).
 * Guarda el archivo en /uploads/obras/ e inserta la obra en la BD.
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);

// ─── Constantes de upload ─────────────────────────────────────
define('UPLOAD_DIR',  dirname(__DIR__, 2) . '/uploads/obras/');
define('UPLOAD_URL',  '/poliart/uploads/obras/');
define('MAX_SIZE',    50 * 1024 * 1024);   // 50 MB
define('ALLOWED_EXT', ['jpg','jpeg','png','gif','webp','mp4','mp3','wav','pdf']);

// ─── Helpers ──────────────────────────────────────────────────
function redirigirConError(string $msg): void {
    $_SESSION['flash_error'] = $msg;
    header('Location: /poliart/app/artista/proyectos.php');
    exit;
}

function redirigirOk(string $msg): void {
    $_SESSION['flash_ok'] = $msg;
    header('Location: /poliart/app/artista/proyectos.php');
    exit;
}

// ─── Solo POST ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /poliart/app/artista/index.php');
    exit;
}

// ─── Obtener id_artista del usuario logueado ──────────────────
$pdo = getDB();
$stmt = $pdo->prepare('SELECT id_artista FROM artista WHERE id_usuario = ? LIMIT 1');
$stmt->execute([getUserId()]);
$artista = $stmt->fetch();

if (!$artista) {
    redirigirConError('Tu cuenta no tiene perfil de artista.');
}
$id_artista = (int)$artista['id_artista'];

// ─── Campos del formulario ────────────────────────────────────
$titulo      = trim($_POST['titulo']      ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$precio_raw  = trim($_POST['precio']      ?? '0');
$tipo_arte   = trim($_POST['tipo_arte']   ?? 'dibujo');
$slug_cat    = trim($_POST['categoria']   ?? '');
$ubicacion_raw = trim($_POST['ubicacion'] ?? '');

// Validaciones
if (mb_strlen($titulo, 'UTF-8') < 2 || mb_strlen($titulo, 'UTF-8') > 160) {
    redirigirConError('El título es obligatorio (mínimo 2 caracteres).');
}
if ($precio_raw === '') {
    $precio_raw = '0';
}
if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $precio_raw)) {
    redirigirConError('El precio debe ser un número positivo con hasta dos decimales.');
}
$precio = (float)$precio_raw;
if ($precio > 9999999999.99) {
    redirigirConError('El precio supera el máximo permitido.');
}
if ($ubicacion_raw !== '' && !ctype_digit($ubicacion_raw)) {
    redirigirConError('Selecciona una zona válida.');
}
$id_zona_raw = $ubicacion_raw === '' ? 0 : (int)$ubicacion_raw;

// ─── Buscar categoría seleccionada ────────────────────────────
$stmtCat = $pdo->prepare(
    'SELECT id_categoria FROM categoria_obra
     WHERE nombre = ? AND activa = 1 AND id_categoria_padre IS NULL
     LIMIT 1'
);
$stmtCat->execute([$slug_cat]);
$catRow = $stmtCat->fetch();
if (!$catRow) {
    redirigirConError('Selecciona una categoría válida antes de enviar la obra.');
}
$id_categoria = (int)$catRow['id_categoria'];
$extensionesImagen = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$extensionesPorCategoria = [
    'Ilustración & Pintura' => $extensionesImagen,
    'Escultura & 3D' => $extensionesImagen,
    'Música & Audio' => ['mp4', 'mp3', 'wav'],
    'Artes Escénicas' => ['mp4'],
    'Literatura & Escritura' => array_merge($extensionesImagen, ['pdf']),
];
if (!isset($extensionesPorCategoria[$slug_cat])) {
    redirigirConError('La categoría seleccionada no admite publicaciones.');
}

// ─── Zona ─────────────────────────────────────────────────────
$id_zona = null;
if ($id_zona_raw > 0) {
    $stmtZona = $pdo->prepare('SELECT id_zona FROM zona WHERE id_zona = ? LIMIT 1');
    $stmtZona->execute([$id_zona_raw]);
    $zonaRow = $stmtZona->fetch();
    if (!$zonaRow) {
        redirigirConError('Selecciona una zona válida.');
    }
    $id_zona = (int)$zonaRow['id_zona'];
}

// ─── Subida del archivo ───────────────────────────────────────
$rutaImagen = null;

if (!isset($_FILES['archivo_obra']) || $_FILES['archivo_obra']['error'] === UPLOAD_ERR_NO_FILE) {
    redirigirConError('Selecciona el archivo que deseas adjuntar a la obra.');
}

if ($_FILES['archivo_obra']['error'] !== UPLOAD_ERR_OK) {
    redirigirConError('No se pudo recibir el archivo. Intenta subirlo nuevamente.');
}

$file     = $_FILES['archivo_obra'];
$origName = basename($file['name']);
$ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

if (!in_array($ext, ALLOWED_EXT, true)) {
    redirigirConError("Tipo de archivo no permitido: .$ext");
}
if (!in_array($ext, $extensionesPorCategoria[$slug_cat], true)) {
    redirigirConError('El formato del archivo no corresponde a la categoría seleccionada.');
}
if ($file['size'] > MAX_SIZE) {
    redirigirConError('El archivo supera el límite de 50 MB.');
}

// Verificar MIME real
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
$allowedMimes = [
    'image/jpeg','image/png','image/gif','image/webp',
    'video/mp4','audio/mpeg','audio/wav','audio/x-wav','audio/wave','application/pdf'
];
if (!in_array($mime, $allowedMimes, true)) {
    redirigirConError('El tipo MIME del archivo no está permitido.');
}
$mimesPorExtension = [
    'jpg' => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png' => ['image/png'],
    'gif' => ['image/gif'],
    'webp' => ['image/webp'],
    'mp4' => ['video/mp4'],
    'mp3' => ['audio/mpeg'],
    'wav' => ['audio/wav', 'audio/x-wav', 'audio/wave'],
    'pdf' => ['application/pdf'],
];
if (!in_array($mime, $mimesPorExtension[$ext], true)) {
    redirigirConError('El contenido del archivo no coincide con su extensión.');
}

if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true) && !is_dir(UPLOAD_DIR)) {
    redirigirConError('No se pudo preparar la carpeta uploads/obras/.');
}

$newName = 'obra_' . $id_artista . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$destino = UPLOAD_DIR . $newName;

if (!move_uploaded_file($file['tmp_name'], $destino)) {
    redirigirConError('Error al guardar el archivo. Revisa los permisos de la carpeta uploads/.');
}

$rutaImagen = UPLOAD_URL . $newName;

// ─── Insertar obra en la BD ───────────────────────────────────
try {
    $pdo->beginTransaction();
    $stmtIns = $pdo->prepare(
        'INSERT INTO obra
            (id_artista, id_categoria, id_zona, titulo, descripcion,
             ruta_imagen, precio, moneda, estado_publicacion, estado_venta, publicada_en)
         VALUES (?, ?, ?, ?, ?, ?, ?, "COP", "publicada", "disponible", NOW())'
    );
    $stmtIns->execute([
        $id_artista, $id_categoria, $id_zona,
        $titulo, $descripcion ?: null,
        $rutaImagen, $precio
    ]);
    $id_obra = (int)$pdo->lastInsertId();

    // ─── Colaboradores (campo texto @usuario1, @usuario2) ────
    if (!empty($_POST['colaboradores'])) {
        $tags = preg_split('/[\s,]+/', trim($_POST['colaboradores']));
        foreach ($tags as $tag) {
            $tag = ltrim($tag, '@');
            if (!$tag) continue;
            $stmtColU = $pdo->prepare(
                'SELECT a.id_artista FROM artista a
                 JOIN usuario u ON u.id_usuario = a.id_usuario
                 WHERE u.nombre_usuario = ? LIMIT 1'
            );
            $stmtColU->execute([$tag]);
            $colRow = $stmtColU->fetch();
            if ($colRow && (int)$colRow['id_artista'] !== $id_artista) {
                $pdo->prepare(
                    'INSERT IGNORE INTO obra_colaborador (id_obra, id_artista, rol_colaboracion) VALUES (?, ?, "Colaborador")'
                )->execute([$id_obra, $colRow['id_artista']]);
            }
        }
    }

    $pdo->commit();
    redirigirOk('¡Obra publicada correctamente! Ya está visible para la comunidad.');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($rutaImagen !== null) {
        $rutaAbsoluta = dirname(__DIR__) . str_replace('/poliart', '', $rutaImagen);
        if (is_file($rutaAbsoluta)) {
            unlink($rutaAbsoluta);
        }
    }
    error_log('Error al guardar obra del artista: ' . $e->getMessage());
    redirigirConError('Error de base de datos al guardar la obra.');
}
