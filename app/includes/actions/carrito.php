<?php
/**
 * includes/actions/carrito.php
 * Acciones de carrito via POST:
 *   ?accion=agregar  → agrega obra al carrito activo
 *   ?accion=quitar   → elimina un detalle del carrito
 *   ?accion=checkout → convierte el carrito en pedido
 *   ?accion=ver      → GET devuelve items del carrito (JSON)
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../notificaciones.php';
require_once __DIR__ . '/../catalogo_obras.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Debes iniciar sesión.']);
    exit;
}

$accion = $_REQUEST['accion'] ?? 'ver';
$pdo    = getDB();
$uid    = getUserId();

// ── Helper: obtener o crear carrito activo ────────────────────
if (false && ($accion === 'ver' || $_SERVER['REQUEST_METHOD'] === 'GET')) {
    $items = $pdo->prepare("SELECT cd.id_carrito_detalle, cd.cantidad, cd.precio_unitario, cd.moneda, o.id_obra, o.titulo, o.ruta_imagen, o.estado_publicacion, o.estado_venta, categori.nombre AS categoria, a.id_artista, u.id_usuario AS id_usuario_artista, u.nombre_usuario, a.nombre_artistico FROM carrito_detalle cd INNER JOIN carrito c ON c.id_carrito = cd.id_carrito INNER JOIN obra o ON o.id_obra = cd.id_obra LEFT JOIN categoria_obra categori ON categori.id_categoria = o.id_categoria INNER JOIN artista a ON a.id_artista = o.id_artista INNER JOIN usuario u ON u.id_usuario = a.id_usuario WHERE c.id_usuario = ? AND c.estado = 'activo' ORDER BY cd.id_carrito_detalle DESC");
    $items->execute([$uid]);
    $filas = $items->fetchAll();
    foreach ($filas as &$fila) {
        $fila['ruta_imagen'] = normalizarRutaPublica($fila['ruta_imagen'], 'obras');
    }
    unset($fila);
    $totales = [];
    foreach ($filas as $fila) { $moneda = $fila['moneda']; $totales[$moneda] = ($totales[$moneda] ?? 0.0) + (float)$fila['precio_unitario'] * (int)$fila['cantidad']; }
    foreach ($totales as $moneda => $total) $totales[$moneda] = number_format($total, 2, '.', '');
    echo json_encode(['items' => $filas, 'totales' => $totales], JSON_UNESCAPED_UNICODE);
    exit;
}

 if (false && $accion === 'checkout') {
     // El checkout no debe cambiar el estado de la obra; ese cambio lo hace el artista.
     $idsCarrito = $pdo->prepare("SELECT cd.id_obra FROM carrito_detalle cd INNER JOIN carrito c ON c.id_carrito = cd.id_carrito WHERE c.id_usuario = ? AND c.estado = 'activo'");
     $idsCarrito->execute([$uid]);
     $obrasDelCarrito = array_map('intval', $idsCarrito->fetchAll(PDO::FETCH_COLUMN));
     register_shutdown_function(static function () use ($pdo, $obrasDelCarrito): void {
         if (!$obrasDelCarrito) return;
         $marcadores = implode(',', array_fill(0, count($obrasDelCarrito), '?'));
         $restaurar = $pdo->prepare("UPDATE obra SET estado_venta = 'disponible' WHERE id_obra IN ($marcadores) AND estado_venta = 'reservada'");
         $restaurar->execute($obrasDelCarrito);
     });
 }
 function getCarritoActivo(PDO $pdo, int $uid): int {
    $stmt = $pdo->prepare("SELECT id_carrito FROM carrito WHERE id_usuario = ? AND estado = 'activo' LIMIT 1");
    $stmt->execute([$uid]);
    $row = $stmt->fetch();
    if ($row) return (int)$row['id_carrito'];

    $ins = $pdo->prepare("INSERT INTO carrito (id_usuario, estado) VALUES (?, 'activo')");
    $ins->execute([$uid]);
    return (int)$pdo->lastInsertId();
}

try {
    // ── VER ───────────────────────────────────────────────────
    if ($accion === 'ver' || $_SERVER['REQUEST_METHOD'] === 'GET') {
        // Una reserva válida nace de un pedido pendiente/confirmado. Si otro
        // cliente tenía la obra en su carrito, se retira al consultar el carrito.
        $reservasPropias = $pdo->prepare(
            "SELECT DISTINCT o.id_obra
             FROM obra o
             WHERE o.reservada_por = ? AND o.estado_venta = 'reservada'
             UNION
             SELECT DISTINCT dp.id_obra
             FROM detalle_pedido dp
             INNER JOIN pedido p ON p.id_pedido = dp.id_pedido
             INNER JOIN obra o ON o.id_obra = dp.id_obra
             WHERE p.id_usuario_cliente = ?
               AND p.estado_pedido IN ('pendiente', 'confirmado')
               AND p.estado_pago NOT IN ('fallido', 'reembolsado')
               AND o.estado_venta = 'reservada'"
        );
        $reservasPropias->execute([$uid, $uid]);
        $idsReservasPropias = array_map('intval', $reservasPropias->fetchAll(PDO::FETCH_COLUMN));

        $reservadasEnCarrito = $pdo->prepare(
            "SELECT cd.id_carrito_detalle, cd.id_obra, o.titulo
             FROM carrito_detalle cd
             INNER JOIN carrito c ON c.id_carrito = cd.id_carrito
             INNER JOIN obra o ON o.id_obra = cd.id_obra
             WHERE c.id_usuario = ? AND c.estado = 'activo' AND o.estado_venta = 'reservada'"
        );
        $reservadasEnCarrito->execute([$uid]);
        $removidas = [];
        $eliminarReservadas = $pdo->prepare('DELETE FROM carrito_detalle WHERE id_carrito_detalle = ?');
        foreach ($reservadasEnCarrito->fetchAll() as $reservada) {
            if (!in_array((int)$reservada['id_obra'], $idsReservasPropias, true)) {
                $eliminarReservadas->execute([(int)$reservada['id_carrito_detalle']]);
                $removidas[] = $reservada['titulo'];
            }
        }

        $stmt = $pdo->prepare("SELECT id_carrito FROM carrito WHERE id_usuario = ? AND estado = 'activo' LIMIT 1");
        $stmt->execute([$uid]);
        $carr = $stmt->fetch();

        if (!$carr) {
            echo json_encode(['items' => [], 'totales' => [], 'reservas_removidas' => $removidas], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $items = $pdo->prepare(
            "SELECT cd.id_carrito_detalle, cd.cantidad, cd.precio_unitario, cd.moneda,
                    o.id_obra, o.titulo, o.ruta_imagen, o.estado_publicacion, o.estado_venta,
                    a.id_artista, u.id_usuario AS id_usuario_artista,
                    u.nombre_usuario, a.nombre_artistico
             FROM carrito_detalle cd
             INNER JOIN carrito c ON c.id_carrito = cd.id_carrito
             JOIN obra o    ON o.id_obra    = cd.id_obra
             JOIN artista a ON a.id_artista = o.id_artista
             JOIN usuario u ON u.id_usuario = a.id_usuario
             WHERE cd.id_carrito = ? AND c.id_usuario = ? AND c.estado = 'activo'"
        );
        $items->execute([$carr['id_carrito'], $uid]);
        $filas = $items->fetchAll();
        foreach ($filas as &$fila) {
            $fila['ruta_imagen'] = normalizarRutaPublica($fila['ruta_imagen'], 'obras');
        }
        unset($fila);
        $totales = [];
        foreach ($filas as $fila) {
            $moneda = $fila['moneda'];
            $totales[$moneda] = ($totales[$moneda] ?? 0.0)
                + (float)$fila['precio_unitario'] * (int)$fila['cantidad'];
        }
        foreach ($totales as $moneda => $total) {
            $totales[$moneda] = number_format($total, 2, '.', '');
        }
        foreach ($filas as &$fila) {
            $fila['reserva_propia'] = in_array((int)$fila['id_obra'], $idsReservasPropias, true);
        }
        unset($fila);
        echo json_encode(['items' => $filas, 'totales' => $totales, 'reservas_removidas' => $removidas], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido.']);
        exit;
    }

    // ── AGREGAR ───────────────────────────────────────────────
    if ($accion === 'agregar') {
        $id_obra = intval($_POST['id_obra'] ?? 0);
        if (!$id_obra) { http_response_code(422); echo json_encode(['error' => 'id_obra requerido.']); exit; }

        // Verificar obra disponible
        $stmt = $pdo->prepare(
            "SELECT o.precio, o.moneda, o.estado_venta, a.id_usuario
             FROM obra o
             INNER JOIN artista a ON a.id_artista = o.id_artista
             WHERE o.id_obra = ? AND o.estado_publicacion = 'publicada'
             LIMIT 1"
        );
        $stmt->execute([$id_obra]);
        $obra = $stmt->fetch();
        if (!$obra || $obra['estado_venta'] !== 'disponible') {
            http_response_code(409);
            echo json_encode(['error' => 'La obra no está disponible.']);
            exit;
        }
        if ((int)$obra['id_usuario'] === $uid) {
            http_response_code(409);
            echo json_encode(['error' => 'No puedes agregar tu propia obra al carrito.']);
            exit;
        }

        $carritoId = getCarritoActivo($pdo, $uid);

        $stmtMonedas = $pdo->prepare('SELECT DISTINCT moneda FROM carrito_detalle WHERE id_carrito = ?');
        $stmtMonedas->execute([$carritoId]);
        $monedasExistentes = $stmtMonedas->fetchAll(PDO::FETCH_COLUMN);
        if ($monedasExistentes && !in_array($obra['moneda'], $monedasExistentes, true)) {
            http_response_code(409);
            echo json_encode(['error' => 'No se pueden mezclar monedas distintas en un mismo carrito.']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT 1 FROM carrito_detalle WHERE id_carrito = ? AND id_obra = ?');
        $stmt->execute([$carritoId, $id_obra]);
        if ($stmt->fetch()) {
            echo json_encode(['ok' => true, 'msg' => 'Ya estaba en el carrito.']);
            exit;
        }

        $pdo->prepare(
            'INSERT INTO carrito_detalle (id_carrito, id_obra, cantidad, precio_unitario, moneda) VALUES (?, ?, 1, ?, ?)'
        )->execute([$carritoId, $id_obra, $obra['precio'], $obra['moneda']]);

        echo json_encode(['ok' => true]);
        exit;
    }

    // ── QUITAR ────────────────────────────────────────────────
    if ($accion === 'quitar') {
        $id_detalle = intval($_POST['id_carrito_detalle'] ?? 0);
        $stmt = $pdo->prepare("SELECT id_carrito FROM carrito WHERE id_usuario = ? AND estado = 'activo' LIMIT 1");
        $stmt->execute([$uid]);
        $carr = $stmt->fetch();
        if (!$carr) { echo json_encode(['ok' => true]); exit; }

        $pdo->prepare('DELETE FROM carrito_detalle WHERE id_carrito_detalle = ? AND id_carrito = ?')
            ->execute([$id_detalle, $carr['id_carrito']]);

        echo json_encode(['ok' => true]);
        exit;
    }

    // ── CHECKOUT ──────────────────────────────────────────────
    if ($accion === 'checkout') {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT id_carrito FROM carrito WHERE id_usuario = ? AND estado = 'activo' LIMIT 1");
        $stmt->execute([$uid]);
        $carr = $stmt->fetch();
        if (!$carr) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['error' => 'Carrito vacío.']);
            exit;
        }
        $carritoId = $carr['id_carrito'];

        $items = $pdo->prepare(
            "SELECT cd.*, o.estado_venta, o.estado_publicacion, o.id_artista AS artId,
                    o.titulo, a.id_usuario AS id_usuario_artista
             FROM carrito_detalle cd
             JOIN obra o ON o.id_obra = cd.id_obra
             JOIN artista a ON a.id_artista = o.id_artista
             WHERE cd.id_carrito = ? FOR UPDATE"
        );
        $items->execute([$carritoId]);
        $filas = $items->fetchAll();

        if (!$filas) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['error' => 'El carrito está vacío.']);
            exit;
        }

        foreach ($filas as $item) {
            if ($item['estado_venta'] !== 'disponible' || $item['estado_publicacion'] !== 'publicada') {
                $pdo->rollBack();
                http_response_code(409);
                echo json_encode(['error' => "La obra #{$item['id_obra']} ya no está publicada o disponible."]);
                exit;
            }
        }

        $monedaPedido = $filas[0]['moneda'];
        foreach ($filas as $item) {
            if ($item['moneda'] !== $monedaPedido) {
                $pdo->rollBack();
                http_response_code(409);
                echo json_encode(['error' => 'El carrito contiene monedas distintas. Retira los artículos que no coincidan.']);
                exit;
            }
        }

        $cfg = $pdo->query('SELECT porcentaje_comision FROM configuracion_sistema LIMIT 1')->fetch();
        $pct = floatval($cfg['porcentaje_comision'] ?? 10);

        $subtotal = 0; $totalCom = 0;
        $detalles = [];
        foreach ($filas as $item) {
            $linea = floatval($item['precio_unitario']) * $item['cantidad'];
            $com   = $linea * ($pct / 100);
            $subtotal += $linea; $totalCom += $com;
            $detalles[] = [...$item, 'linea' => $linea, 'com' => $com];
        }

        $ins = $pdo->prepare(
            "INSERT INTO pedido (id_usuario_cliente, estado_pedido, estado_pago, moneda, subtotal, total_comision, total)
             VALUES (?, 'pendiente', 'pendiente', ?, ?, ?, ?)"
        );
        $ins->execute([$uid, $monedaPedido, number_format($subtotal,2,'.',''), number_format($totalCom,2,'.',''), number_format($subtotal+$totalCom,2,'.','')]);
        $pedidoId = (int)$pdo->lastInsertId();

        foreach ($detalles as $d) {
            $pdo->prepare(
                "INSERT INTO detalle_pedido (id_pedido, id_obra, id_artista, cantidad, precio_unitario, moneda, porcentaje_comision, valor_comision, subtotal_linea)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([$pedidoId, $d['id_obra'], $d['artId'], $d['cantidad'], $d['precio_unitario'],
                        $d['moneda'], $pct, number_format($d['com'],2,'.',''), number_format($d['linea'],2,'.','')]);
            $actualizarObra = $pdo->prepare(
                "UPDATE obra SET estado_venta = 'reservada', reservada_por = ?
                 WHERE id_obra = ? AND estado_venta = 'disponible' AND estado_publicacion = 'publicada'"
            );
            $actualizarObra->execute([$uid, $d['id_obra']]);
            if ($actualizarObra->rowCount() !== 1) {
                $pdo->rollBack();
                http_response_code(409);
                echo json_encode(['error' => "La obra #{$d['id_obra']} dejó de estar disponible."]);
                exit;
            }
            crearNotificacion(
                $pdo,
                (int)$d['id_usuario_artista'],
                'pedido',
                'Pedido recibido',
                'Tu obra «' . $d['titulo'] . '» fue incluida en un pedido pendiente de pago.',
                $uid,
                (int)$d['id_obra'],
                '/poliart/app/artista/proyectos.php'
            );
        }

        // Mantener los detalles en el carrito del cliente que reservó. Al
        // consultar, se conservan solo para ese cliente y se eliminan de los
        // demás carritos.
        $pdo->commit();

        echo json_encode([
            'ok'       => true,
            'id_pedido'=> $pedidoId,
            'total'    => number_format($subtotal + $totalCom, 2, '.', ''),
            'moneda'   => $monedaPedido
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Acción desconocida.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor.']);
}
