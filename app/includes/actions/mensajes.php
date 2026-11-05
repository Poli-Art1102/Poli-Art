<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../notificaciones.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isLoggedIn()) {
    http_response_code($_SERVER['REQUEST_METHOD'] !== 'POST' ? 405 : 401);
    echo json_encode(['error' => 'Debes iniciar sesión para enviar un mensaje.']);
    exit;
}

$idReceptor = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
$contenido = trim((string)($_POST['contenido'] ?? ''));
$archivo = $_FILES['archivo'] ?? null;
$hayArchivo = is_array($archivo) && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
if (!$idReceptor || $idReceptor < 1 || ($contenido === '' && !$hayArchivo) || mb_strlen($contenido, 'UTF-8') > 5000) {
    http_response_code(422);
    echo json_encode(['error' => 'Escribe un mensaje o selecciona un archivo. El mensaje admite hasta 5000 caracteres.']);
    exit;
}

if ($idReceptor === getUserId()) {
    http_response_code(422);
    echo json_encode(['error' => 'No puedes enviarte mensajes a ti mismo.']);
    exit;
}

$archivoGuardado = null;
$rutaAbsolutaAdjunto = null;
$directorioAdjuntos = dirname(__DIR__, 2) . '/uploads/mensajes/';
$tiposPermitidos = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
    'text/plain' => 'txt',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
];
if ($hayArchivo) {
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        || !is_uploaded_file($archivo['tmp_name'] ?? '')
        || ($archivo['size'] ?? 0) < 1
        || $archivo['size'] > 10 * 1024 * 1024) {
        http_response_code(422);
        echo json_encode(['error' => 'El archivo debe pesar hasta 10 MB y haberse cargado correctamente.']);
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $tipoAdjunto = $finfo->file($archivo['tmp_name']);
    if (!is_string($tipoAdjunto) || !isset($tiposPermitidos[$tipoAdjunto])) {
        http_response_code(422);
        echo json_encode(['error' => 'Tipo de archivo no permitido. Adjunta imágenes, PDF, TXT o documentos Office.']);
        exit;
    }

    $nombreOriginal = basename(str_replace('\\', '/', (string)$archivo['name']));
    $nombreOriginal = mb_substr($nombreOriginal, 0, 255, 'UTF-8');
    if ($nombreOriginal === '' || $nombreOriginal === '.' || $nombreOriginal === '..') {
        $nombreOriginal = 'archivo.' . $tiposPermitidos[$tipoAdjunto];
    }
    $nombreAlmacenado = bin2hex(random_bytes(24)) . '.' . $tiposPermitidos[$tipoAdjunto];
    if (!is_dir($directorioAdjuntos)
        && !mkdir($directorioAdjuntos, 0750, true)
        && !is_dir($directorioAdjuntos)) {
        error_log('No fue posible crear el directorio de adjuntos de mensajes.');
        http_response_code(500);
        echo json_encode(['error' => 'No fue posible guardar el archivo adjunto.']);
        exit;
    }
    $rutaAbsolutaAdjunto = $directorioAdjuntos . $nombreAlmacenado;
    if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsolutaAdjunto)) {
        error_log('No fue posible mover el archivo adjunto del mensaje.');
        http_response_code(500);
        echo json_encode(['error' => 'No fue posible guardar el archivo adjunto.']);
        exit;
    }
    $archivoGuardado = [
        'ruta' => $nombreAlmacenado,
        'nombre' => $nombreOriginal,
        'tipo' => $tipoAdjunto,
        'tamano' => (int)$archivo['size'],
    ];
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        "SELECT id_usuario, rol FROM usuario
         WHERE id_usuario = :id_usuario AND estado = 'activo'
           AND rol IN ('cliente', 'artista')
         LIMIT 1"
    );
    $stmt->execute([':id_usuario' => $idReceptor]);
    $destinatario = $stmt->fetch();
    if (!$destinatario) {
        if ($rutaAbsolutaAdjunto !== null && is_file($rutaAbsolutaAdjunto)) {
            unlink($rutaAbsolutaAdjunto);
        }
        http_response_code(404);
        echo json_encode(['error' => 'El destinatario no está disponible.']);
        exit;
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'INSERT INTO mensaje
            (id_emisor, id_receptor, contenido, ruta_adjunto, nombre_adjunto, tipo_adjunto, tamano_adjunto)
         VALUES
            (:emisor, :receptor, :contenido, :ruta_adjunto, :nombre_adjunto, :tipo_adjunto, :tamano_adjunto)'
    );
    $stmt->execute([
        ':emisor' => getUserId(),
        ':receptor' => $idReceptor,
        ':contenido' => $contenido,
        ':ruta_adjunto' => $archivoGuardado['ruta'] ?? null,
        ':nombre_adjunto' => $archivoGuardado['nombre'] ?? null,
        ':tipo_adjunto' => $archivoGuardado['tipo'] ?? null,
        ':tamano_adjunto' => $archivoGuardado['tamano'] ?? null,
    ]);
    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'id' => (int)$pdo->lastInsertId(),
        'contenido' => $contenido,
        'adjunto' => $archivoGuardado,
        'creado_en' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($rutaAbsolutaAdjunto !== null && is_file($rutaAbsolutaAdjunto)) {
        unlink($rutaAbsolutaAdjunto);
    }
    error_log('Error al guardar mensaje: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No fue posible enviar el mensaje.']);
}
