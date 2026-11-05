<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../notificaciones.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

if (!isLoggedIn() || !in_array(getUserRole(), ['cliente', 'artista'], true)) {
    http_response_code(401);
    echo json_encode(['error' => 'Debes iniciar sesión como cliente o artista para reportar.']);
    exit;
}

$idArtista = filter_input(INPUT_POST, 'id_artista', FILTER_VALIDATE_INT);
$motivo = trim((string)($_POST['motivo'] ?? ''));
$detalle = trim((string)($_POST['detalle'] ?? ''));
$motivosValidos = ['acoso', 'fraude', 'contenido_inapropiado', 'suplantacion', 'otro'];

if (!$idArtista || !in_array($motivo, $motivosValidos, true)) {
    http_response_code(422);
    echo json_encode(['error' => 'Selecciona un motivo válido.']);
    exit;
}

if ($detalle === '' && $motivo === 'otro') {
    http_response_code(422);
    echo json_encode(['error' => 'Describe el motivo del reporte.']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        'SELECT a.id_artista, a.id_usuario, COALESCE(NULLIF(a.nombre_artistico, \'\'), u.nombre_usuario) AS nombre
         FROM artista a INNER JOIN usuario u ON u.id_usuario = a.id_usuario
         WHERE a.id_artista = :id_artista AND u.estado = \'activo\' LIMIT 1'
    );
    $stmt->execute([':id_artista' => $idArtista]);
    $artista = $stmt->fetch();
    if (!$artista) {
        http_response_code(404);
        echo json_encode(['error' => 'Artista no encontrado.']);
        exit;
    }

    if ((int)$artista['id_usuario'] === getUserId()) {
        http_response_code(422);
        echo json_encode(['error' => 'No puedes reportar tu propio perfil.']);
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT id_reporte_artista FROM reporte_artista
         WHERE id_reportante = :id_reportante AND id_artista = :id_artista AND estado = 'pendiente' LIMIT 1"
    );
    $stmt->execute([':id_reportante' => getUserId(), ':id_artista' => $idArtista]);
    if ($stmt->fetch()) {
        echo json_encode(['ok' => true, 'msg' => 'Ya habías reportado este perfil.']);
        exit;
    }

    $etiquetas = [
        'acoso' => 'Acoso o comportamiento abusivo',
        'fraude' => 'Fraude o estafa',
        'contenido_inapropiado' => 'Contenido inapropiado',
        'suplantacion' => 'Suplantación de identidad',
        'otro' => 'Otro motivo',
    ];

    $pdo->beginTransaction();
    $pdo->prepare(
        'INSERT INTO reporte_artista (id_reportante, id_artista, motivo, detalle)
         VALUES (:id_reportante, :id_artista, :motivo, :detalle)'
    )->execute([
        ':id_reportante' => getUserId(),
        ':id_artista' => $idArtista,
        ':motivo' => $motivo,
        ':detalle' => $detalle !== '' ? $detalle : null,
    ]);
    crearNotificacion(
        $pdo,
        (int)$artista['id_usuario'],
        'reporte_artista',
        'Perfil reportado',
        'Tu perfil recibió un reporte. Motivo: ' . ($etiquetas[$motivo] ?? $motivo) . '.',
        null,
        null,
        '/poliart/app/artista/perfil.php'
    );
    $pdo->commit();

    echo json_encode(['ok' => true, 'msg' => 'Reporte enviado. Gracias por ayudar a moderar la comunidad.']);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al registrar reporte de artista: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No fue posible registrar el reporte.']);
}
