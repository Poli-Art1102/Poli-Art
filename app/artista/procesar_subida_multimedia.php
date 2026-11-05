<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);

const MAX_SIZE_MULTIMEDIA = 50 * 1024 * 1024;
const UPLOAD_URL_MULTIMEDIA = '/poliart/uploads/obras/';

function volverConErrorMultimedia(string $mensaje): never
{
    $_SESSION['flash_error'] = $mensaje;
    header('Location: /poliart/app/artista/proyectos.php');
    exit;
}

function volverCorrectoMultimedia(string $mensaje): never
{
    $_SESSION['flash_ok'] = $mensaje;
    header('Location: /poliart/app/artista/proyectos.php');
    exit;
}

function archivosDelCampo(string $campo): array
{
    if (!isset($_FILES[$campo]['name'])) return [];
    $campoArchivo = $_FILES[$campo];
    if (!is_array($campoArchivo['name'])) return [$campoArchivo];
    $salida = [];
    foreach ($campoArchivo['name'] as $i => $nombre) {
        $salida[] = [
            'name' => $nombre,
            'type' => $campoArchivo['type'][$i] ?? '',
            'tmp_name' => $campoArchivo['tmp_name'][$i] ?? '',
            'error' => $campoArchivo['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $campoArchivo['size'][$i] ?? 0,
        ];
    }
    return $salida;
}

function guardarArchivoMultimedia(array $archivo, array $extensiones, int $idArtista, int &$secuencia): array
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
        throw new RuntimeException('Uno de los archivos no se pudo recibir correctamente.');
    }
    if ((int)$archivo['size'] > MAX_SIZE_MULTIMEDIA) throw new RuntimeException('Un archivo supera el límite de 50 MB.');
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $extensiones, true)) throw new RuntimeException('El formato de uno de los archivos no corresponde a la categoría.');

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']) ?: '';
    $mimes = [
        'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'],
        'gif' => ['image/gif'], 'webp' => ['image/webp'], 'svg' => ['image/svg+xml'],
        'mp3' => ['audio/mpeg', 'audio/mp3'], 'wav' => ['audio/wav', 'audio/x-wav', 'audio/wave'],
        'mp4' => ['video/mp4'], 'pdf' => ['application/pdf'],
    ];
    if (!isset($mimes[$extension]) || !in_array($mime, $mimes[$extension], true)) {
        throw new RuntimeException('El contenido de uno de los archivos no coincide con su extensión.');
    }

    $directorio = dirname(__DIR__, 2) . '/uploads/obras/';
    if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
        throw new RuntimeException('No se pudo preparar la carpeta de archivos.');
    }
    $secuencia++;
    $nombre = 'obra_' . $idArtista . '_' . time() . '_' . $secuencia . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destino = $directorio . $nombre;
    if (!move_uploaded_file($archivo['tmp_name'], $destino)) throw new RuntimeException('No se pudo guardar uno de los archivos.');
    return ['ruta' => UPLOAD_URL_MULTIMEDIA . $nombre, 'mime' => $mime, 'absoluta' => $destino];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') header('Location: /poliart/app/artista/index.php');

$pdo = getDB();
$stmt = $pdo->prepare('SELECT id_artista FROM artista WHERE id_usuario = ? LIMIT 1');
$stmt->execute([getUserId()]);
$artista = $stmt->fetch();
if (!$artista) volverConErrorMultimedia('Tu cuenta no tiene perfil de artista.');
$idArtista = (int)$artista['id_artista'];

$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$precioRaw = trim((string)($_POST['precio'] ?? ''));
$precioRaw = str_replace(',', '.', $precioRaw);
$precioRaw = $precioRaw === '' ? '0' : $precioRaw;
$categoria = trim($_POST['categoria'] ?? '');
$ubicacionRaw = trim($_POST['ubicacion'] ?? '');
$tipoArte = trim($_POST['tipo_arte'] ?? 'dibujo');
if (mb_strlen($titulo, 'UTF-8') < 2) volverConErrorMultimedia('El título es obligatorio.');
if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $precioRaw)) volverConErrorMultimedia('El precio no es válido.');

$categorias = [
    'Ilustración & Pintura' => ['jpg','jpeg','png','gif','webp','svg'],
    'Escultura & 3D' => ['jpg','jpeg','png','gif','webp','svg'],
    'Música & Audio' => ['mp3','wav','mp4'],
    'Artes Escénicas' => ['mp4'],
    'Literatura & Escritura' => ['jpg','jpeg','png','gif','webp','svg','pdf'],
];
if (!isset($categorias[$categoria])) volverConErrorMultimedia('Selecciona una categoría válida.');

$stmt = $pdo->prepare('SELECT id_categoria FROM categoria_obra WHERE nombre = ? AND activa = 1 AND id_categoria_padre IS NULL LIMIT 1');
$stmt->execute([$categoria]);
$idCategoria = (int)($stmt->fetchColumn() ?: 0);
if (!$idCategoria) volverConErrorMultimedia('Selecciona una categoría válida.');

$idZona = null;
if ($ubicacionRaw !== '') {
    if (!ctype_digit($ubicacionRaw)) volverConErrorMultimedia('Selecciona una zona válida.');
    $stmt = $pdo->prepare('SELECT id_zona FROM zona WHERE id_zona = ? LIMIT 1');
    $stmt->execute([(int)$ubicacionRaw]);
    $idZona = $stmt->fetchColumn();
    if (!$idZona) volverConErrorMultimedia('Selecciona una zona válida.');
}

$archivosObra = archivosDelCampo('archivo_obra');
$portadas = archivosDelCampo('portada_obra');
$esAudio = $tipoArte === 'musica' || $categoria === 'Música & Audio';
if (!$archivosObra) volverConErrorMultimedia('Selecciona el archivo de la obra.');
if ($esAudio && count($portadas) !== 1) volverConErrorMultimedia('Para publicar un audio debes adjuntar exactamente una imagen de portada.');
if (!$esAudio && count($archivosObra) > 1 && !in_array($categoria, ['Ilustración & Pintura', 'Escultura & 3D'], true)) {
    volverConErrorMultimedia('Esta categoría no permite varios archivos de imagen.');
}

$guardados = [];
try {
    $secuencia = 0;
    $archivosGuardados = [];
    foreach ($archivosObra as $archivo) {
        $guardados[] = guardarArchivoMultimedia($archivo, $categorias[$categoria], $idArtista, $secuencia);
        $archivosGuardados[] = end($guardados);
    }
    $portadaGuardada = null;
    if ($esAudio) {
        $portadaGuardada = guardarArchivoMultimedia($portadas[0], ['jpg','jpeg','png','gif','webp','svg'], $idArtista, $secuencia);
        $guardados[] = $portadaGuardada;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS obra_media (
        id_media INT AUTO_INCREMENT PRIMARY KEY,
        id_obra INT UNSIGNED NOT NULL,
        ruta VARCHAR(255) NOT NULL,
        tipo_mime VARCHAR(100) NOT NULL,
        orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_obra_media_obra_orden (id_obra, orden),
        CONSTRAINT fk_obra_media_obra FOREIGN KEY (id_obra) REFERENCES obra(id_obra) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO obra (id_artista, id_categoria, id_zona, titulo, descripcion, ruta_imagen, precio, moneda, estado_publicacion, estado_venta, publicada_en) VALUES (?, ?, ?, ?, ?, ?, ?, "COP", "publicada", "disponible", NOW())');
    $stmt->execute([$idArtista, $idCategoria, $idZona, $titulo, $descripcion ?: null, $archivosGuardados[0]['ruta'], (float)$precioRaw]);
    $idObra = (int)$pdo->lastInsertId();
    $media = $pdo->prepare('INSERT INTO obra_media (id_obra, ruta, tipo_mime, orden) VALUES (?, ?, ?, ?)');
    foreach ($archivosGuardados as $orden => $mediaArchivo) $media->execute([$idObra, $mediaArchivo['ruta'], $mediaArchivo['mime'], $orden]);
    if ($portadaGuardada) $media->execute([$idObra, $portadaGuardada['ruta'], $portadaGuardada['mime'], count($archivosGuardados)]);
    if (!empty($_POST['colaboradores'])) {
        $tags = preg_split('/[\s,]+/', trim($_POST['colaboradores']));
        foreach ($tags as $tag) {
            $tag = ltrim($tag, '@');
            if ($tag === '') continue;
            $stmtCol = $pdo->prepare('SELECT a.id_artista FROM artista a JOIN usuario u ON u.id_usuario = a.id_usuario WHERE u.nombre_usuario = ? LIMIT 1');
            $stmtCol->execute([$tag]);
            $idColaborador = (int)($stmtCol->fetchColumn() ?: 0);
            if ($idColaborador && $idColaborador !== $idArtista) {
                $pdo->prepare('INSERT IGNORE INTO obra_colaborador (id_obra, id_artista, rol_colaboracion) VALUES (?, ?, "Colaborador")')->execute([$idObra, $idColaborador]);
            }
        }
    }
    $pdo->commit();
    volverCorrectoMultimedia('¡Obra publicada correctamente! Ya está visible para la comunidad.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    foreach ($guardados as $archivo) if (is_file($archivo['absoluta'])) @unlink($archivo['absoluta']);
    error_log('Error al guardar obra multimedia: ' . $e->getMessage());
    volverConErrorMultimedia('Error al guardar la obra. Verifica los archivos e inténtalo nuevamente.');
}
