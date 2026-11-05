<?php

function crearNotificacion(
    PDO $pdo,
    int $destinatario,
    string $tipo,
    string $titulo,
    string $mensaje,
    ?int $actor = null,
    ?int $obra = null,
    ?string $enlace = null
): void {
    if ($destinatario < 1 || $tipo === '' || $titulo === '' || $mensaje === '') {
        throw new InvalidArgumentException('Los datos de la notificación son obligatorios.');
    }

    if ($enlace === null) {
        $stmtRol = $pdo->prepare('SELECT rol FROM usuario WHERE id_usuario = :id_usuario LIMIT 1');
        $stmtRol->execute([':id_usuario' => $destinatario]);
        $rol = (string)$stmtRol->fetchColumn();
        $enlace = match ($rol) {
            'administrador' => '/poliart/app/admin/index.php',
            'cliente' => '/poliart/app/cliente/index.php',
            default => '/poliart/app/artista/notificaciones.php',
        };
    }

    $stmt = $pdo->prepare(
        'INSERT INTO notificacion (id_destinatario, id_actor, id_obra, tipo, titulo, mensaje, enlace)
         VALUES (:destinatario, :actor, :obra, :tipo, :titulo, :mensaje, :enlace)'
    );
    $stmt->execute([
        ':destinatario' => $destinatario,
        ':actor' => $actor,
        ':obra' => $obra,
        ':tipo' => $tipo,
        ':titulo' => $titulo,
        ':mensaje' => $mensaje,
        ':enlace' => $enlace,
    ]);
}
