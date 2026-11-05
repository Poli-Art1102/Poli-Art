<?php
/**
 * includes/actions/reportar.php
 * POST: registra un reporte de contenido sobre una obra.
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../notificaciones.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Debes iniciar sesión para reportar.']);
    exit;
}

$id_obra = intval($_POST['id_obra'] ?? 0);
$motivo  = trim($_POST['motivo']    ?? '');
$detalle = trim($_POST['detalle']   ?? '');

if (!$id_obra || !$motivo) {
    http_response_code(422);
    echo json_encode(['error' => 'Faltan campos obligatorios.']);
    exit;
}

try {
    $pdo = getDB();

    // Verificar que la obra existe
    $stmt = $pdo->prepare(
        'SELECT o.id_obra, a.id_usuario AS id_artista
         FROM obra o INNER JOIN artista a ON a.id_artista = o.id_artista
         WHERE o.id_obra = ? LIMIT 1'
    );
    $stmt->execute([$id_obra]);
    $obra = $stmt->fetch();
    if (!$obra) {
        http_response_code(404);
        echo json_encode(['error' => 'Obra no encontrada.']);
        exit;
    }

    // Evitar reportes duplicados del mismo usuario sobre la misma obra
    $stmt = $pdo->prepare(
        "SELECT id_reporte FROM reporte_contenido
         WHERE id_reportante = ? AND id_obra = ? AND estado = 'pendiente' LIMIT 1"
    );
    $stmt->execute([getUserId(), $id_obra]);
    if ($stmt->fetch()) {
        echo json_encode(['ok' => true, 'msg' => 'Ya habías reportado esta obra.']);
        exit;
    }

    $pdo->beginTransaction();
    $pdo->prepare(
        'INSERT INTO reporte_contenido (id_reportante, id_obra, motivo, detalle) VALUES (?, ?, ?, ?)'
    )->execute([getUserId(), $id_obra, $motivo, $detalle ?: null]);

    if ((int)$obra['id_artista'] !== getUserId()) {
        $motivos = [
            'plagio' => 'Plagio o copia no autorizada',
            'inapropiado' => 'Contenido inapropiado u ofensivo',
            'spam' => 'Spam o información engañosa',
            'otro' => 'Otro motivo',
        ];
        $motivoVisible = $motivos[$motivo] ?? $motivo;
        crearNotificacion(
            $pdo,
            (int)$obra['id_artista'],
            'reporte',
            'Obra reportada',
            'Recibiste un reporte anónimo. Motivo: ' . $motivoVisible . '.',
            null,
            $id_obra,
            '/poliart/app/artista/proyectos.php'
        );
    }
    $pdo->commit();

    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al registrar reporte: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al registrar el reporte.']);
}
