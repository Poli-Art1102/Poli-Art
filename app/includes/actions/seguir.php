<?php
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

$idArtista = filter_input(INPUT_POST, 'id_artista', FILTER_VALIDATE_INT);
if (!$idArtista || $idArtista < 1) {
    http_response_code(422);
    echo json_encode(['error' => 'El artista indicado no es válido.']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        'SELECT u.id_usuario, u.rol
         FROM artista a INNER JOIN usuario u ON u.id_usuario = a.id_usuario
         WHERE a.id_artista = :id_artista LIMIT 1'
    );
    $stmt->execute([':id_artista' => $idArtista]);
    $perfilSeguido = $stmt->fetch();
    $idSeguido = $perfilSeguido ? (int)$perfilSeguido['id_usuario'] : 0;

    if (!$idSeguido || (int)$idSeguido === getUserId()) {
        http_response_code(422);
        echo json_encode(['error' => 'No puedes seguir este perfil.']);
        exit;
    }

    $idSeguidor = getUserId();
    $stmt = $pdo->prepare(
        'SELECT 1 FROM seguimiento WHERE id_seguidor = :seguidor AND id_seguido = :seguido'
    );
    $stmt->execute([':seguidor' => $idSeguidor, ':seguido' => $idSeguido]);
    $following = (bool)$stmt->fetchColumn();

    if ($following) {
        $pdo->prepare(
            'DELETE FROM seguimiento WHERE id_seguidor = :seguidor AND id_seguido = :seguido'
        )->execute([':seguidor' => $idSeguidor, ':seguido' => $idSeguido]);
        $following = false;
    } else {
        $pdo->beginTransaction();
        $pdo->prepare(
            'INSERT INTO seguimiento (id_seguidor, id_seguido) VALUES (:seguidor, :seguido)'
        )->execute([':seguidor' => $idSeguidor, ':seguido' => $idSeguido]);
        if ($perfilSeguido['rol'] === 'artista') {
            crearNotificacion(
                $pdo,
                $idSeguido,
                'seguidor',
                'Nuevo seguidor',
                'empezó a seguirte.',
                $idSeguidor,
                null,
                '/poliart/app/artista/seguidores.php'
            );
        }
        $pdo->commit();
        $following = true;
    }

    echo json_encode(['ok' => true, 'following' => $following]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al actualizar seguimiento: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No fue posible actualizar el seguimiento.']);
}
