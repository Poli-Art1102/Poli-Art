<?php
require_once __DIR__ . '/../auth.php';
requireLogin(['artista', 'cliente']);

$idMensaje = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$idMensaje || $idMensaje < 1) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

$stmt = getDB()->prepare(
    'SELECT ruta_adjunto, nombre_adjunto, tipo_adjunto, tamano_adjunto
     FROM mensaje
     WHERE id_mensaje = :id
       AND ruta_adjunto IS NOT NULL
       AND (id_emisor = :usuario_emisor OR id_receptor = :usuario_receptor)
     LIMIT 1'
);
$stmt->execute([
    ':id' => $idMensaje,
    ':usuario_emisor' => getUserId(),
    ':usuario_receptor' => getUserId(),
]);
$adjunto = $stmt->fetch();
if (!$adjunto || !preg_match('/\A[a-f0-9]{48}\.(jpg|png|gif|webp|pdf|txt|docx|xlsx|pptx)\z/', $adjunto['ruta_adjunto'])) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

$ruta = dirname(__DIR__, 2) . '/uploads/mensajes/' . $adjunto['ruta_adjunto'];
if (!is_file($ruta) || !is_readable($ruta)) {
    http_response_code(404);
    exit('El archivo ya no está disponible.');
}

$esImagen = in_array($adjunto['tipo_adjunto'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
$inline = $esImagen && ($_GET['inline'] ?? '') === '1';
$nombre = preg_replace('/[\r\n"]/', '_', (string)$adjunto['nombre_adjunto']);
header('Content-Type: ' . $adjunto['tipo_adjunto']);
header('Content-Length: ' . filesize($ruta));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $nombre . '"; filename*=UTF-8\'\'' . rawurlencode((string)$adjunto['nombre_adjunto']));
header('Cache-Control: private, no-store');
readfile($ruta);
