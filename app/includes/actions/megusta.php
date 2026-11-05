<?php
/**
 * includes/actions/megusta.php
 * POST: toggle me gusta en una obra.
 * Responde JSON { ok, liked, total }
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
    echo json_encode(['error' => 'Debes iniciar sesión.']);
    exit;
}

$id_obra = intval($_POST['id_obra'] ?? 0);
if (!$id_obra) {
    http_response_code(422);
    echo json_encode(['error' => 'id_obra requerido.']);
    exit;
}

try {
    $pdo = getDB();
    $uid = getUserId();

    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'SELECT a.id_usuario
         FROM obra o INNER JOIN artista a ON a.id_artista = o.id_artista
         WHERE o.id_obra = ? AND o.estado_publicacion = \'publicada\'
         LIMIT 1'
    );
    $stmt->execute([$id_obra]);
    $idArtista = $stmt->fetchColumn();
    if (!$idArtista) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'La obra no está disponible.']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT 1 FROM me_gusta WHERE id_usuario = ? AND id_obra = ? FOR UPDATE');
    $stmt->execute([$uid, $id_obra]);
    $yaLiked = (bool)$stmt->fetchColumn();

    if ($yaLiked) {
        $pdo->prepare('DELETE FROM me_gusta WHERE id_usuario = ? AND id_obra = ?')
            ->execute([$uid, $id_obra]);
        $liked = false;
    } else {
        $pdo->prepare('INSERT INTO me_gusta (id_usuario, id_obra) VALUES (?, ?)')
            ->execute([$uid, $id_obra]);
        $liked = true;
        if ((int)$idArtista !== $uid) {
            crearNotificacion(
                $pdo,
                (int)$idArtista,
                'me_gusta',
                'Nuevo me gusta',
                'le dio Me gusta a una de tus obras.',
                $uid,
                $id_obra,
                '/poliart/app/artista/proyectos.php'
            );
        }
    }

    $stmtT = $pdo->prepare('SELECT COUNT(*) FROM me_gusta WHERE id_obra = ?');
    $stmtT->execute([$id_obra]);
    $total = (int)$stmtT->fetchColumn();

    $pdo->commit();
    echo json_encode(['ok' => true, 'liked' => $liked, 'total' => $total]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al actualizar me gusta: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al procesar.']);
}
