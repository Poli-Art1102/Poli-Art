<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../notificaciones.php';
requireLogin(['artista']);
header('Content-Type: application/json; charset=utf-8');

function responderColaboracion(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderColaboracion(['ok' => false, 'error' => 'Método no permitido.'], 405);
}

try {
    $pdo = getDB();
    $stmtArtista = $pdo->prepare('SELECT id_artista FROM artista WHERE id_usuario = :id_usuario LIMIT 1');
    $stmtArtista->execute([':id_usuario' => getUserId()]);
    $idArtista = (int)$stmtArtista->fetchColumn();
    if ($idArtista < 1) {
        responderColaboracion(['ok' => false, 'error' => 'No se encontró el perfil de artista.'], 403);
    }

    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        $idInvitado = filter_var($_POST['id_artista'] ?? null, FILTER_VALIDATE_INT);
        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if (!$idInvitado || $idInvitado < 1 || $idInvitado === $idArtista) {
            responderColaboracion(['ok' => false, 'error' => 'Selecciona otro artista para invitar.'], 422);
        }
        if ($titulo === '' || mb_strlen($titulo) > 160 || $descripcion === '') {
            responderColaboracion(['ok' => false, 'error' => 'Completa un título de hasta 160 caracteres y una descripción.'], 422);
        }

        $stmtInvitado = $pdo->prepare(
            "SELECT id_artista FROM artista a
             INNER JOIN usuario u ON u.id_usuario = a.id_usuario
             WHERE a.id_artista = :id_artista AND u.estado = 'activo'"
        );
        $stmtInvitado->execute([':id_artista' => $idInvitado]);
        if (!$stmtInvitado->fetchColumn()) {
            responderColaboracion(['ok' => false, 'error' => 'El artista seleccionado no está disponible.'], 422);
        }

        $pdo->beginTransaction();
        $stmtProyecto = $pdo->prepare(
            'INSERT INTO proyecto_colaborativo (id_artista_creador, id_zona, titulo, descripcion)
             SELECT :id_artista, u.id_zona, :titulo, :descripcion
             FROM artista a INNER JOIN usuario u ON u.id_usuario = a.id_usuario
             WHERE a.id_artista = :id_artista_select'
        );
        $stmtProyecto->execute([
            ':id_artista' => $idArtista,
            ':titulo' => $titulo,
            ':descripcion' => $descripcion,
            ':id_artista_select' => $idArtista,
        ]);
        $idProyecto = (int)$pdo->lastInsertId();
        $stmtParticipante = $pdo->prepare(
            'INSERT INTO participante_colaboracion (id_proyecto, id_artista, estado_invitacion, activa)
             VALUES (:id_proyecto, :id_artista, :estado, 1)'
        );
        $stmtParticipante->execute([
            ':id_proyecto' => $idProyecto,
            ':id_artista' => $idArtista,
            ':estado' => 'aceptada',
        ]);
        $stmtParticipante->execute([
            ':id_proyecto' => $idProyecto,
            ':id_artista' => $idInvitado,
            ':estado' => 'pendiente',
        ]);
        $stmtUsuarioInvitado = $pdo->prepare(
            'SELECT id_usuario FROM artista WHERE id_artista = :id_artista LIMIT 1'
        );
        $stmtUsuarioInvitado->execute([':id_artista' => $idInvitado]);
        crearNotificacion(
            $pdo,
            (int)$stmtUsuarioInvitado->fetchColumn(),
            'colaboracion',
            'Nueva invitación de colaboración',
            'te invitó a participar en «' . $titulo . '».',
            getUserId(),
            null,
            '/poliart/app/artista/invitaciones_recibidas.php'
        );
        $pdo->commit();
        responderColaboracion(['ok' => true, 'id_proyecto' => $idProyecto]);
    }

    if ($accion === 'responder') {
        $idProyecto = filter_var($_POST['id_proyecto'] ?? null, FILTER_VALIDATE_INT);
        $respuesta = $_POST['respuesta'] ?? '';
        if (!$idProyecto || $idProyecto < 1 || !in_array($respuesta, ['aceptar', 'rechazar'], true)) {
            responderColaboracion(['ok' => false, 'error' => 'La respuesta indicada no es válida.'], 422);
        }

        $estado = $respuesta === 'aceptar' ? 'aceptada' : 'rechazada';
        $pdo->beginTransaction();
        $stmtRespuesta = $pdo->prepare(
            "UPDATE participante_colaboracion
             SET estado_invitacion = :estado, activa = :activa, respondido_en = NOW()
             WHERE id_proyecto = :id_proyecto AND id_artista = :id_artista
               AND estado_invitacion = 'pendiente' AND activa = 1"
        );
        $stmtRespuesta->execute([
            ':estado' => $estado,
            ':activa' => $respuesta === 'aceptar' ? 1 : 0,
            ':id_proyecto' => $idProyecto,
            ':id_artista' => $idArtista,
        ]);
        if ($stmtRespuesta->rowCount() !== 1) {
            $pdo->rollBack();
            responderColaboracion(['ok' => false, 'error' => 'La invitación ya no está pendiente o no te pertenece.'], 404);
        }
        if ($respuesta === 'aceptar') {
            $pdo->prepare(
                "UPDATE proyecto_colaborativo SET estado = 'activo'
                 WHERE id_proyecto = :id_proyecto AND estado = 'abierto'"
            )->execute([':id_proyecto' => $idProyecto]);
        }
        $stmtProyecto = $pdo->prepare(
            'SELECT p.titulo, creador.id_usuario AS id_usuario_creador
             FROM proyecto_colaborativo p
             INNER JOIN artista creador ON creador.id_artista = p.id_artista_creador
             WHERE p.id_proyecto = :id_proyecto LIMIT 1'
        );
        $stmtProyecto->execute([':id_proyecto' => $idProyecto]);
        $proyecto = $stmtProyecto->fetch();
        if ($proyecto && (int)$proyecto['id_usuario_creador'] !== (int)getUserId()) {
            crearNotificacion(
                $pdo,
                (int)$proyecto['id_usuario_creador'],
                'colaboracion_respuesta',
                $respuesta === 'aceptar' ? 'Invitación aceptada' : 'Invitación rechazada',
                'respondió a tu invitación para «' . $proyecto['titulo'] . '».',
                (int)getUserId(),
                null,
                '/poliart/app/artista/enviadas_colaboraciones.php'
            );
        }
        $pdo->commit();
        responderColaboracion(['ok' => true, 'estado' => $estado]);
    }

    responderColaboracion(['ok' => false, 'error' => 'Acción no reconocida.'], 422);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al gestionar colaboración: ' . $e->getMessage());
    responderColaboracion(['ok' => false, 'error' => 'No fue posible guardar la colaboración.'], 500);
}
