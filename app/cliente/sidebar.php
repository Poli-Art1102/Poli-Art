<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/catalogo_obras.php';
// Determinar ruta relativa según la ubicación
$path_prefix = strpos($_SERVER['PHP_SELF'] ?? '', '/includes/') !== false
    ? '../cliente/'
    : (file_exists('../carrito.php') ? '../' : '');
$usuarioSesion = getCurrentUser() ?? [];
$nombre_cliente = trim(($usuarioSesion['nombre'] ?? '') . ' ' . ($usuarioSesion['apellido'] ?? ''));
$nombre_cliente = $nombre_cliente !== '' ? $nombre_cliente : ($usuarioSesion['nombre_usuario'] ?? 'Usuario');
$artistasSeguidos = [];
$errorSeguimientos = false;
$mensajesPendientes = 0;
$errorMensajesPendientes = false;
$notificacionesPendientes = 0;
try {
    $stmtSeguidos = getDB()->prepare(
        'SELECT a.id_artista,
                COALESCE(NULLIF(a.nombre_artistico, \'\'), CONCAT(u.nombre, \' \', u.apellido)) AS nombre,
                u.avatar_path
         FROM seguimiento s
         INNER JOIN usuario u ON u.id_usuario = s.id_seguido AND u.rol = \'artista\' AND u.estado = \'activo\'
         INNER JOIN artista a ON a.id_usuario = u.id_usuario
         WHERE s.id_seguidor = :id_usuario
         ORDER BY s.creado_en DESC
         LIMIT 5'
    );
    $stmtSeguidos->execute([':id_usuario' => getUserId()]);
    $artistasSeguidos = $stmtSeguidos->fetchAll();
} catch (PDOException $e) {
    error_log('Error al cargar los artistas seguidos: ' . $e->getMessage());
    $errorSeguimientos = true;
}
try {
    $stmtNotificacionesPendientes = getDB()->prepare(
        'SELECT COUNT(*) FROM notificacion
         WHERE id_destinatario = :id_usuario AND leido_en IS NULL'
    );
    $stmtNotificacionesPendientes->execute([':id_usuario' => getUserId()]);
    $notificacionesPendientes = (int)$stmtNotificacionesPendientes->fetchColumn();
} catch (PDOException $e) {
    error_log('Error al contar notificaciones del cliente: ' . $e->getMessage());
}
try {
    $stmtMensajesPendientes = getDB()->prepare(
        'SELECT COUNT(*) FROM mensaje
         WHERE id_receptor = :id_usuario AND leido_en IS NULL'
    );
    $stmtMensajesPendientes->execute([':id_usuario' => getUserId()]);
    $mensajesPendientes = (int)$stmtMensajesPendientes->fetchColumn();
} catch (PDOException $e) {
    error_log('Error al contar mensajes no leídos del cliente: ' . $e->getMessage());
    $errorMensajesPendientes = true;
}
?>

<!-- SIDEBAR ESTÁTICO FIJO CLIENTE -->
<aside class="fixed-sidebar-container">
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- PANEL DE CLIENTE -->
        <div>
            <span class="sidebar-section-title">PORTAL DE CLIENTE</span>
            <nav style="display: flex; flex-direction: column; gap: 0.35rem;">
                <a href="<?= $path_prefix ?>index.php" class="sidebar-link">
                    <span>🏠</span> Inicio
                </a>
                <a href="<?= $path_prefix ?>carrito.php" class="sidebar-link">
                    <span>🛒</span> Carrito de Compras
                </a>
                <a href="<?= $path_prefix ?>mensajes.php" class="sidebar-link" style="justify-content:space-between;">
                    <span><span>💬</span> Mensajes</span>
                    <?php if ($mensajesPendientes > 0): ?>
                        <span aria-label="<?= $mensajesPendientes ?> mensajes sin leer" title="<?= $mensajesPendientes ?> mensajes sin leer" style="min-width:1.35rem;padding:.12rem .35rem;box-sizing:border-box;border-radius:9999px;background:#ef4444;color:#fff;text-align:center;font-size:.7rem;font-weight:800;">
                            <?= $mensajesPendientes > 99 ? '99+' : $mensajesPendientes ?>
                        </span>
                    <?php elseif ($errorMensajesPendientes): ?>
                        <span aria-label="No se pudo consultar el contador de mensajes" title="No se pudo consultar el contador de mensajes" style="min-width:1.35rem;padding:.12rem .35rem;box-sizing:border-box;border-radius:9999px;background:#ef4444;color:#fff;text-align:center;font-size:.7rem;font-weight:800;">!</span>
                    <?php endif; ?>
                </a>
                <a href="<?= $path_prefix ?>notificaciones.php" class="sidebar-link" style="justify-content:space-between;">
                    <span>🔔 Notificaciones</span>
                    <?php if ($notificacionesPendientes > 0): ?>
                        <span aria-label="<?= $notificacionesPendientes ?> notificaciones sin leer" title="<?= $notificacionesPendientes ?> notificaciones sin leer" style="min-width:1.35rem;padding:.12rem .35rem;box-sizing:border-box;border-radius:9999px;background:#ef4444;color:#fff;text-align:center;font-size:.7rem;font-weight:800;">
                            <?= $notificacionesPendientes > 99 ? '99+' : $notificacionesPendientes ?>
                        </span>
                    <?php endif; ?>
                </a>
            </nav>
        </div>

        <!-- ARTISTAS SEGUIDOS -->
        <div>
            <span class="sidebar-section-title">ARTISTAS SEGUIDOS</span>
            <nav style="display: flex; flex-direction: column; gap: 0.35rem;">
                <?php if ($errorSeguimientos): ?>
                    <span style="padding: .5rem .75rem; color: var(--text-muted); font-size: .8rem;">No fue posible cargar los seguimientos.</span>
                <?php elseif (!$artistasSeguidos): ?>
                    <span style="padding: .5rem .75rem; color: var(--text-muted); font-size: .8rem;">Aún no sigues artistas.</span>
                <?php else: ?>
                    <?php foreach ($artistasSeguidos as $artistaSeguido): ?>
                        <?php
                        $avatarSeguido = normalizarRutaPublica($artistaSeguido['avatar_path'] ?? null, 'avatars') ?? '';
                        ?>
                        <a href="<?= $path_prefix ?>perfil_artista.php?id=<?= (int)$artistaSeguido['id_artista'] ?>" class="sidebar-link" style="padding: 0.5rem 0.75rem;">
                            <?php if ($avatarSeguido !== ''): ?>
                                <img src="<?= htmlspecialchars($avatarSeguido, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width: 1.75rem; height: 1.75rem; border-radius: 9999px; object-fit: cover;">
                            <?php endif; ?>
                            <span style="font-size: 0.85rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($artistaSeguido['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </nav>
        </div>

    </div>

    <!-- BOTÓN DE CERRAR SESIÓN EN EL FOOTER DEL SIDEBAR -->
    <a href="<?= $path_prefix ?>../includes/actions/logout.php" class="sidebar-logout-btn" title="Clic para cerrar sesión">
        <div style="width: 2.25rem; height: 2.25rem; background: linear-gradient(135deg, var(--brand-orange), var(--brand-pink)); color: #ffffff; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 800; flex-shrink: 0;">
            <?= htmlspecialchars(mb_strtoupper(mb_substr($nombre_cliente, 0, 2, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div style="display: flex; flex-direction: column; overflow: hidden; flex-grow: 1;">
            <strong style="font-size: 0.85rem; color: var(--text-main); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                <?= htmlspecialchars($nombre_cliente, ENT_QUOTES, 'UTF-8') ?>
            </strong>
            <span style="font-size: 0.725rem; color: var(--brand-orange); font-weight: 700;">
                🚪 Cerrar Sesión
            </span>
        </div>
    </a>
</aside>

<style>
.fixed-sidebar-container {
    width: 260px !important;
    min-width: 260px !important;
    max-width: 260px !important;
    height: calc(100vh - 4.25rem) !important;
    min-height: 0 !important;
    background-color: var(--bg-nav) !important;
    color: var(--text-main) !important;
    padding: 1.5rem 1rem !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: flex-start !important;
    gap: 1rem !important;
    box-shadow: 4px 0 15px rgba(0, 0, 0, 0.15) !important;
    box-sizing: border-box !important;
    flex-shrink: 0 !important;
    position: sticky !important;
    top: 0 !important;
    align-self: flex-start;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    overscroll-behavior: contain;
    scrollbar-width: thin;
    scrollbar-color: color-mix(in srgb, var(--brand-orange) 38%, var(--bg-nav)) transparent;
    z-index: 40 !important;
    border-right: 1px solid var(--border-subtle) !important;
    border-top: 0 !important;
    margin-top: 0 !important;
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}

.fixed-sidebar-container::-webkit-scrollbar {
    width: 7px;
}

.fixed-sidebar-container::-webkit-scrollbar-track {
    background: transparent;
}

.fixed-sidebar-container::-webkit-scrollbar-thumb {
    border: 2px solid transparent;
    border-radius: 999px;
    background: color-mix(in srgb, var(--brand-orange) 38%, var(--bg-nav));
    background-clip: padding-box;
    transition: background-color 0.2s ease;
}

.fixed-sidebar-container::-webkit-scrollbar-thumb:hover {
    background: color-mix(in srgb, var(--brand-orange) 60%, var(--bg-nav));
    background-clip: padding-box;
}

.fixed-sidebar-container > div:first-child {
    flex: 0 0 auto;
}

.fixed-sidebar-container > .sidebar-logout-btn {
    flex: 0 0 auto;
}

.sidebar-section-title {
    font-size: 0.725rem;
    font-weight: 800;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.75rem;
    display: block;
    padding-left: 0.5rem;
}

.sidebar-link {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.6rem 0.85rem;
    color: var(--text-main) !important;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    background: transparent;
    border: none;
    font-family: inherit;
    cursor: pointer;
    text-align: left;
    width: 100%;
    box-sizing: border-box;
}

.sidebar-link:hover {
    background-color: var(--hover-item) !important;
    color: var(--brand-orange) !important;
}

.sidebar-logout-btn {
    border-top: 1px solid var(--border-subtle);
    padding-top: 0.85rem;
    margin-top: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    border-radius: 0.6rem;
    padding: 0.6rem;
    transition: all 0.2s ease;
}

.sidebar-logout-btn:hover {
    background-color: var(--hover-item);
}
</style>
<style>
  .fixed-sidebar-container { justify-content: space-between !important; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const nav = document.querySelector('.fixed-sidebar-container nav');
    if (!nav || nav.querySelector('[data-menu-tipos-arte]')) return;
    const bloque = document.createElement('div');
    bloque.dataset.menuTiposArte = 'true';
    bloque.style.cssText = 'display:flex;flex-direction:column;margin-top:.25rem;';
    bloque.innerHTML = `
        <button type="button" class="sidebar-link" style="justify-content:space-between;">
            <span style="display:flex;align-items:center;gap:.6rem;"><span>🌟</span> Tipos de Arte</span>
            <span style="font-size:.75rem;color:var(--text-muted);">▼</span>
        </button>
        <div class="cliente-submenu-artes" style="display:none;flex-direction:column;gap:.25rem;padding-left:1.25rem;margin-top:.25rem;border-left:2px solid var(--brand-orange);">
            <a href="/poliart/app/cliente/artes/dibujo_pintura.php" class="cliente-sidebar-sublink">🎨 Ilustración &amp; Pintura</a>
            <a href="/poliart/app/cliente/artes/musica.php" class="cliente-sidebar-sublink">🎵 Música &amp; Videoclips</a>
            <a href="/poliart/app/cliente/artes/artes_escenicas.php" class="cliente-sidebar-sublink">🎭 Artes Escénicas</a>
            <a href="/poliart/app/cliente/artes/escritura.php" class="cliente-sidebar-sublink">✍️ Libros &amp; Literatura</a>
            <a href="/poliart/app/cliente/artes/escultura.php" class="cliente-sidebar-sublink">🗿 Escultura &amp; 3D</a>
        </div>`;
    const boton = bloque.querySelector('button');
    const submenu = bloque.querySelector('.cliente-submenu-artes');
    const enArtes = window.location.pathname.includes('/app/cliente/artes/');
    submenu.style.display = enArtes ? 'flex' : 'none';
    boton.addEventListener('click', function () {
        submenu.style.display = submenu.style.display === 'flex' ? 'none' : 'flex';
    });
    const estilo = document.createElement('style');
    estilo.textContent = '.cliente-sidebar-sublink{display:block;padding:.45rem .75rem;color:var(--text-muted)!important;text-decoration:none;font-size:.825rem;font-weight:500;border-radius:.375rem}.cliente-sidebar-sublink:hover{background:var(--hover-item);color:var(--brand-orange)!important;font-weight:700}';
    document.head.appendChild(estilo);
    const panelCliente = nav.parentElement;
    const seccionArtes = document.createElement('div');
    seccionArtes.innerHTML = '<span class="sidebar-section-title">EXPLORA EL ARTE</span>';
    const navArtes = document.createElement('nav');
    navArtes.style.cssText = 'display:flex;flex-direction:column;gap:.35rem;';
    navArtes.appendChild(bloque);
    seccionArtes.appendChild(navArtes);
    if (panelCliente && panelCliente.parentElement) panelCliente.parentElement.insertBefore(seccionArtes, panelCliente.nextElementSibling);
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.setTimeout(function () {
        const submenu = document.querySelector('.cliente-submenu-artes');
        const enlaces = document.querySelectorAll('.cliente-sidebar-sublink');
        if (!submenu || !enlaces.length) return;
        enlaces.forEach(function (enlace) {
            if (new URL(enlace.href, window.location.origin).pathname === window.location.pathname) {
                enlace.classList.add('active-sublink');
                enlace.style.backgroundColor = 'var(--hover-item)';
                enlace.style.color = 'var(--brand-orange)';
                enlace.style.fontWeight = '700';
                submenu.style.display = 'flex';
            }
        });
    }, 0);
});
</script>
