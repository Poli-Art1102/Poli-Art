<?php
require_once __DIR__ . '/../auth.php';
requireLogin(['artista']);

$idAvance = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$idAvance || $idAvance < 1) {
    http_response_code(404);
    exit('Imagen no encontrada.');
}

$stmt = getDB()->prepare(
    "SELECT av.ruta_imagen
     FROM avance_colaboracion av
     INNER JOIN participante_colaboracion pc
        ON pc.id_proyecto = av.id_proyecto
       AND pc.estado_invitacion = 'aceptada'
       AND pc.activa = 1
     INNER JOIN artista a ON a.id_artista = pc.id_artista
     WHERE av.id_avance = :id_avance
       AND av.ruta_imagen IS NOT NULL
       AND a.id_usuario = :id_usuario
     LIMIT 1"
);
$stmt->execute([
    ':id_avance' => $idAvance,
    ':id_usuario' => getUserId(),
]);
$nombreArchivo = $stmt->fetchColumn();
if (!is_string($nombreArchivo)
    || !preg_match('/\A[a-f0-9]{48}\.(jpg|png|gif|webp)\z/', $nombreArchivo)) {
    http_response_code(404);
    exit('Imagen no encontrada.');
}

$ruta = dirname(__DIR__, 2) . '/uploads/colaboraciones/' . $nombreArchivo;
if (!is_file($ruta) || !is_readable($ruta)) {
    http_response_code(404);
    exit('La imagen ya no está disponible.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$tipo = $finfo->file($ruta);
if (!in_array($tipo, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
    http_response_code(415);
    exit('El archivo no es una imagen permitida.');
}

header('Content-Type: ' . $tipo);
header('Content-Length: ' . filesize($ruta));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($ruta);
