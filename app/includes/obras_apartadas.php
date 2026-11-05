<?php
require_once __DIR__ . '/auth.php';
requireLogin(['cliente', 'artista']);

$esArtista = getUserRole() === 'artista';
$rutaBase = $esArtista ? '/poliart/app/artista/' : '/poliart/app/cliente/';

if ($esArtista) {
    require_once __DIR__ . '/../artista/header.php';
    include_once __DIR__ . '/../artista/sidebar.php';
} else {
    require_once __DIR__ . '/../cliente/header.php';
    include_once __DIR__ . '/../cliente/sidebar.php';
}
require_once __DIR__ . '/catalogo_obras.php';

$obras_apartadas = [];
$errorReservas = false;

try {
    $stmtReservas = getDB()->prepare(
        "SELECT o.id_obra AS id, o.titulo, o.ruta_imagen AS imagen,
                a.id_artista AS artista_id, a.nombre_artistico,
                u.nombre, u.apellido, u.nombre_usuario AS artista_usuario, u.avatar_path,
                dp.precio_unitario, dp.moneda, p.estado_pedido, p.estado_pago, p.creado_en
         FROM pedido p
         INNER JOIN detalle_pedido dp ON dp.id_pedido = p.id_pedido
         INNER JOIN obra o ON o.id_obra = dp.id_obra
         INNER JOIN artista a ON a.id_artista = dp.id_artista
         INNER JOIN usuario u ON u.id_usuario = a.id_usuario
         WHERE p.id_usuario_cliente = :id_usuario
           AND p.estado_pedido IN ('pendiente', 'confirmado')
         ORDER BY p.creado_en DESC"
    );
    $stmtReservas->execute([':id_usuario' => getUserId()]);
    $obras_apartadas = $stmtReservas->fetchAll();

    $stmtCarritoReservado = getDB()->prepare(
        "SELECT o.id_obra AS id, o.titulo, o.ruta_imagen AS imagen,
                a.id_artista AS artista_id, a.nombre_artistico,
                u.nombre, u.apellido, u.nombre_usuario AS artista_usuario, u.avatar_path,
                cd.precio_unitario, cd.moneda, 'pendiente' AS estado_pedido,
                'no_aplica' AS estado_pago, o.actualizado_en AS creado_en
         FROM carrito c
         INNER JOIN carrito_detalle cd ON cd.id_carrito = c.id_carrito
         INNER JOIN obra o ON o.id_obra = cd.id_obra
         INNER JOIN artista a ON a.id_artista = o.id_artista
         INNER JOIN usuario u ON u.id_usuario = a.id_usuario
         WHERE c.id_usuario = :id_usuario AND c.estado = 'activo'
           AND o.estado_venta = 'reservada' AND o.reservada_por = :reservada_por"
    );
    $stmtCarritoReservado->execute([
        ':id_usuario' => getUserId(),
        ':reservada_por' => getUserId(),
    ]);
    foreach ($stmtCarritoReservado->fetchAll() as $reserva) {
        $reserva['estado'] = 'Apartada por el artista';
        $obras_apartadas[] = $reserva;
    }

    foreach ($obras_apartadas as &$reserva) {
        $reserva['imagen'] = normalizarRutaPublica($reserva['imagen'], 'obras') ?? '';
        $reserva['artista_nombre'] = $reserva['nombre_artistico'] ?: trim($reserva['nombre'] . ' ' . $reserva['apellido']);
        $reserva['artista_avatar'] = normalizarRutaPublica($reserva['avatar_path'], 'avatars') ?? '';
        $reserva['precio_total'] = '$ ' . number_format((float)$reserva['precio_unitario'], 2, ',', '.') . ' ' . $reserva['moneda'];
        $reserva['abono'] = ucfirst($reserva['estado_pago']);
        $reserva['estado'] = $reserva['estado'] ?? ($reserva['estado_pedido'] === 'confirmado' ? 'Listo para Entrega' : 'En Proceso');
        $reserva['fecha_reserva'] = date('d/m/Y', strtotime($reserva['creado_en']));
        $reserva['fecha_entrega_est'] = 'Por coordinar';
    }
    unset($reserva);
} catch (PDOException $e) {
    error_log('Error al cargar reservas: ' . $e->getMessage());
    $errorReservas = true;
}
?>

<style>
html, body { height: 100% !important; max-height: 100vh !important; overflow: hidden !important; }
body { background-image: url('/poliart/app/includes/madera.jpg') !important; background-size: cover !important; background-position: center !important; background-attachment: fixed !important; }
.reserved-container { background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: 1.25rem; width: 100%; max-width: 1100px; margin: 0 auto; overflow: hidden; }
.header-bar { display: flex; align-items: center; justify-content: space-between; padding: 1.5rem 2rem; border-bottom: 1px solid var(--border-subtle); }
.reserved-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; padding: 2rem; }
.reserved-card { background: var(--hover-item); border: 1px solid var(--border-subtle); border-radius: 1rem; overflow: hidden; display: flex; flex-direction: column; }
.card-img-wrapper { position: relative; width: 100%; height: 200px; overflow: hidden; }
.card-img { width: 100%; height: 100%; object-fit: cover; }
.badge-status { position: absolute; top: .75rem; right: .75rem; padding: .3rem .75rem; border-radius: .85rem; font-size: .75rem; font-weight: 800; background: rgba(56, 189, 248, .14); border: 1px solid rgba(56, 189, 248, .55); color: #1e3a8a; }
.badge-status.status-process, .badge-status.status-reserved { background: rgba(56, 189, 248, .14); border-color: rgba(56, 189, 248, .55); color: #1e3a8a; }
.card-body { padding: 1.25rem; display: flex; flex-direction: column; gap: .8rem; flex-grow: 1; }
.artist-info { display: flex; align-items: center; gap: .6rem; text-decoration: none; color: inherit; }
.artist-avatar { width: 32px; height: 32px; border-radius: 9999px; object-fit: cover; border: 1px solid var(--brand-orange); }
.price-box { background: var(--bg-surface); border: 1px solid var(--border-subtle); padding: .75rem; border-radius: .5rem; display: flex; justify-content: space-between; align-items: center; }
.btn-chat-artist { background: linear-gradient(135deg, var(--brand-orange), #ea580c); color: #fff; text-decoration: none; padding: .65rem; border-radius: .5rem; font-size: .85rem; font-weight: 700; display: flex; justify-content: center; margin-top: auto; }
</style>

<div style="display:flex;width:100%;height:calc(100vh - 4.25rem);overflow:hidden;background:transparent;">
  <div style="flex-grow:1;height:100%;overflow-y:auto;padding:2rem 2.5rem;box-sizing:border-box;">
    <div class="reserved-container">
      <div class="header-bar">
        <div style="display:flex;align-items:center;gap:.75rem;">
          <a href="<?= $rutaBase ?>index.php" style="color:var(--text-muted);text-decoration:none;font-size:1.2rem;font-weight:bold;">&larr;</a>
          <h2 style="font-size:1.25rem;font-weight:800;color:var(--text-main);margin:0;">🎨 Obras Apartadas (<?= count($obras_apartadas) ?>)</h2>
        </div>
        <span style="font-size:.8rem;color:var(--brand-orange);font-weight:700;background:var(--hover-item);padding:.3rem .8rem;border-radius:9999px;">Reservas Activas</span>
      </div>
      <div class="reserved-grid">
        <?php if ($errorReservas): ?>
          <p role="alert" style="grid-column:1/-1;color:#dc2626;">No se pudieron cargar tus reservas. Inténtalo de nuevo más tarde.</p>
        <?php elseif (!$obras_apartadas): ?>
          <p style="grid-column:1/-1;color:var(--text-muted);">No tienes obras reservadas por el momento.</p>
        <?php endif; ?>
        <?php foreach ($obras_apartadas as $obra): ?>
          <div class="reserved-card">
            <div class="card-img-wrapper"><img src="<?= htmlspecialchars($obra['imagen'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($obra['titulo'], ENT_QUOTES, 'UTF-8') ?>" class="card-img"><span class="badge-status <?= $obra['estado'] === 'Apartada por el artista' ? 'status-reserved' : 'status-process' ?>"><?= htmlspecialchars($obra['estado'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="card-body">
              <h3 style="font-size:1rem;font-weight:800;color:var(--text-main);margin:0;"><?= htmlspecialchars($obra['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
              <a href="/poliart/app/cliente/perfil_artista.php?id=<?= (int)$obra['artista_id'] ?>" class="artist-info"><img src="<?= htmlspecialchars($obra['artista_avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($obra['artista_nombre'], ENT_QUOTES, 'UTF-8') ?>" class="artist-avatar"><span style="font-size:.85rem;font-weight:700;color:var(--text-muted);"><?= htmlspecialchars($obra['artista_nombre'], ENT_QUOTES, 'UTF-8') ?> (@<?= htmlspecialchars($obra['artista_usuario'], ENT_QUOTES, 'UTF-8') ?>)</span></a>
              <div style="display:flex;justify-content:space-between;font-size:.75rem;color:var(--text-muted);"><span>📅 Reserva: <?= htmlspecialchars($obra['fecha_reserva'], ENT_QUOTES, 'UTF-8') ?></span><span>🚚 Entrega: <?= htmlspecialchars($obra['fecha_entrega_est'], ENT_QUOTES, 'UTF-8') ?></span></div>
              <a href="<?= $rutaBase ?>mensajes.php?artista_id=<?= (int)$obra['artista_id'] ?>" class="btn-chat-artist">💬 Coordinar con Artista</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
