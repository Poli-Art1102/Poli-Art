<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../notificaciones.php';
header('Content-Type: application/json; charset=utf-8');
if (!isLoggedIn() || getUserRole() !== 'administrador') {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permiso para moderar reportes.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$idReporte = filter_input(INPUT_POST, 'id_reporte', FILTER_VALIDATE_INT);
$accion = $_POST['accion'] ?? '';
if (!$idReporte || !in_array($accion, ['retirar', 'ignorar'], true)) {
    http_response_code(422);
    echo json_encode(['error' => 'La acción o el reporte no son válidos.']);
    exit;
}

try {
    $pdo = getDB();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT r.id_obra, r.estado, r.id_reportante, a.id_usuario AS id_artista, o.titulo AS titulo_obra
         FROM reporte_contenido r
         INNER JOIN obra o ON o.id_obra = r.id_obra
         INNER JOIN artista a ON a.id_artista = o.id_artista
         WHERE r.id_reporte = :id FOR UPDATE'
    );
    $stmt->execute([':id' => $idReporte]);
    $reporte = $stmt->fetch();
    if (!$reporte || $reporte['estado'] !== 'pendiente') {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'El reporte no existe o ya fue revisado.']);
        exit;
    }

    $estado = $accion === 'retirar' ? 'resuelto' : 'ignorado';
    $pdo->prepare(
        'UPDATE reporte_contenido
         SET estado = :estado, id_revisor = :revisor, revisado_en = NOW()
         WHERE id_reporte = :id'
    )->execute([
        ':estado' => $estado,
        ':revisor' => getUserId(),
        ':id' => $idReporte,
    ]);

    if ($accion === 'retirar') {
        $pdo->prepare(
            'UPDATE obra SET estado_publicacion = \'oculta\' WHERE id_obra = :id_obra'
        )->execute([':id_obra' => $reporte['id_obra']]);
    }

    $pdo->prepare(
        'INSERT INTO administrador_log (id_admin, accion, entidad, id_entidad, descripcion)
         VALUES (:admin, :accion, \'reporte_contenido\', :entidad, :descripcion)'
    )->execute([
        ':admin' => getUserId(),
        ':accion' => $accion,
        ':entidad' => $idReporte,
        ':descripcion' => $accion === 'retirar'
            ? 'Reporte resuelto y obra retirada.'
            : 'Reporte descartado; obra conservada.',
    ]);

    crearNotificacion(
        $pdo,
        (int)$reporte['id_artista'],
        'reporte_revisado',
        $accion === 'retirar' ? 'Obra retirada tras revisión' : 'Reporte revisado',
        $accion === 'retirar'
            ? 'La moderación retiró «' . $reporte['titulo_obra'] . '» después de revisar un reporte.'
            : 'La moderación revisó el reporte de «' . $reporte['titulo_obra'] . '» y decidió conservar la obra.',
        null,
        (int)$reporte['id_obra'],
        '/poliart/app/artista/proyectos.php'
    );

    if ((int)$reporte['id_reportante'] !== (int)$reporte['id_artista']) {
        crearNotificacion(
            $pdo,
            (int)$reporte['id_reportante'],
            'reporte_revisado_cliente',
            $accion === 'retirar' ? 'Reporte atendido' : 'Reporte revisado',
            $accion === 'retirar'
                ? 'La moderación retiró la obra «' . $reporte['titulo_obra'] . '» después de revisar tu reporte.'
                : 'La moderación revisó tu reporte sobre «' . $reporte['titulo_obra'] . '» y decidió conservar la obra.',
            getUserId(),
            (int)$reporte['id_obra'],
            '/poliart/app/cliente/notificaciones.php'
        );
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'estado' => $estado]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al moderar reporte: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No fue posible actualizar el reporte.']);
}
