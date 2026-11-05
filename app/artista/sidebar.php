<?php
// Determinar ruta relativa y archivo actual
$path_prefix = strpos($_SERVER['PHP_SELF'] ?? '', '/includes/') !== false
    ? '../artista/'
    : (file_exists('../subir_obra.php') ? '../' : '');
$pagina_actual = basename($_SERVER['PHP_SELF']);

// Detectar si estamos en alguna página de artes o colaboraciones para dejarlas abiertas por defecto
$en_artes = strpos($_SERVER['REQUEST_URI'], '/artes/') !== false || in_array($pagina_actual, ['artes_escenicas.php', 'dibujo_pintura.php', 'escritura.php', 'escultura.php', 'musica.php']);
$en_colaboraciones_default = in_array($pagina_actual, [
    'invitaciones_recibidas.php', 
    'enviadas_colaboraciones.php', 
    'proyectos_desarrollo_colaboracion.php'
]);
$stmtNotificacionesPendientes = getDB()->prepare(
    'SELECT COUNT(*) FROM notificacion
     WHERE id_destinatario = :id_usuario AND leido_en IS NULL'
);
$stmtNotificacionesPendientes->execute([':id_usuario' => getUserId()]);
$notificacionesPendientes = (int)$stmtNotificacionesPendientes->fetchColumn();
$errorMensajesPendientes = false;
try {
    $stmtMensajesPendientes = getDB()->prepare(
        'SELECT COUNT(*) FROM mensaje
         WHERE id_receptor = :id_usuario AND leido_en IS NULL'
    );
    $stmtMensajesPendientes->execute([':id_usuario' => getUserId()]);
    $mensajesPendientes = (int)$stmtMensajesPendientes->fetchColumn();
} catch (PDOException $e) {
    error_log('Error al contar mensajes no leídos del artista: ' . $e->getMessage());
    $mensajesPendientes = 0;
    $errorMensajesPendientes = true;
}
?>

<!-- SIDEBAR ESTÁTICO FIJO SIN SCROLL DE PÁGINA -->
<aside class="fixed-sidebar-container">
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- PANEL DE ARTISTA -->
        <div>
            <span class="sidebar-section-title">PANEL DE ARTISTA</span>
            <nav style="display: flex; flex-direction: column; gap: 0.35rem;">
                <a href="<?= $path_prefix ?>index.php" class="sidebar-link <?= ($pagina_actual == 'index.php') ? 'active-link' : '' ?>">
                    <span>🏠</span> Inicio
                </a>
            </nav>
        </div>

        <!-- GESTIÓN ARTÍSTICA -->
        <div>
            <span class="sidebar-section-title">GESTIÓN ARTÍSTICA</span>
            <nav style="display: flex; flex-direction: column; gap: 0.35rem;">
                
                <button type="button" onclick="abrirModalSubirObraSidebar()" class="sidebar-link">
                    <span>🎨</span> Subir Nueva Obra
                </button>

                <!-- DESPLEGABLE TIPOS DE ARTE (APUNTA A LA CARPETA ARTES) -->
                <div style="display: flex; flex-direction: column; margin-top: 0.25rem;">
                    <button type="button" onclick="toggleMenuArtes()" class="sidebar-link" style="justify-content: space-between;">
                        <span style="display: flex; align-items: center; gap: 0.6rem;">
                            <span>🌟</span> Tipos de Arte
                        </span>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">▼</span>
                    </button>

                    <div id="submenu-artes" style="display: <?= $en_artes ? 'flex' : 'none' ?>; flex-direction: column; gap: 0.25rem; padding-left: 1.25rem; margin-top: 0.25rem; border-left: 2px solid var(--brand-orange);">
                        <a href="<?= $path_prefix ?>artes/dibujo_pintura.php" class="sidebar-sublink <?= ($pagina_actual == 'dibujo_pintura.php') ? 'active-sublink' : '' ?>">🎨 Ilustración & Pintura</a>
                        <a href="<?= $path_prefix ?>artes/musica.php" class="sidebar-sublink <?= ($pagina_actual == 'musica.php') ? 'active-sublink' : '' ?>">🎵 Música & Videoclips</a>
                        <a href="<?= $path_prefix ?>artes/artes_escenicas.php" class="sidebar-sublink <?= ($pagina_actual == 'artes_escenicas.php') ? 'active-sublink' : '' ?>">🎭 Artes Escénicas</a>
                        <a href="<?= $path_prefix ?>artes/escritura.php" class="sidebar-sublink <?= ($pagina_actual == 'escritura.php') ? 'active-sublink' : '' ?>">✍️ Libros & Literatura</a>
                        <a href="<?= $path_prefix ?>artes/escultura.php" class="sidebar-sublink <?= ($pagina_actual == 'escultura.php') ? 'active-sublink' : '' ?>">🗿 Escultura & 3D</a>
                    </div>
                </div>

                <a href="<?= $path_prefix ?>proyectos.php" class="sidebar-link <?= ($pagina_actual == 'proyectos.php') ? 'active-link' : '' ?>">
                    <span>📁</span> Mis Proyectos
                </a>

                <a href="<?= $path_prefix ?>borradores.php" class="sidebar-link <?= ($pagina_actual == 'borradores.php') ? 'active-link' : '' ?>">
                    <span>📝</span> Borradores
                </a>

                <a href="<?= $path_prefix ?>megusta.php" class="sidebar-link <?= ($pagina_actual == 'megusta.php') ? 'active-link' : '' ?>">
                    <span>❤️</span> Me gusta
                </a>

                <a href="<?= $path_prefix ?>notificaciones.php" class="sidebar-link <?= ($pagina_actual == 'notificaciones.php') ? 'active-link' : '' ?>" style="justify-content:space-between;">
                    <span><span>🔔</span> Notificaciones</span>
                    <?php if ($notificacionesPendientes > 0): ?>
                        <span aria-label="<?= $notificacionesPendientes ?> sin leer" style="min-width:1.35rem;padding:.12rem .35rem;box-sizing:border-box;border-radius:9999px;background:#ef4444;color:#fff;text-align:center;font-size:.7rem;font-weight:800;">
                            <?= $notificacionesPendientes > 99 ? '99+' : $notificacionesPendientes ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- DESPLEGABLE COLABORACIONES -->
                <div style="display: flex; flex-direction: column; margin-top: 0.25rem;">
                    <button type="button" onclick="toggleMenuColaboraciones()" class="sidebar-link" style="justify-content: space-between;">
                        <span style="display: flex; align-items: center; gap: 0.6rem;">
                            <span>🤝</span> Colaboraciones
                        </span>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">▼</span>
                    </button>

                    <div id="submenu-colaboraciones" style="display: <?= $en_colaboraciones_default ? 'flex' : 'none' ?>; flex-direction: column; gap: 0.25rem; padding-left: 1.25rem; margin-top: 0.25rem; border-left: 2px solid var(--brand-cyan);">
                        <a href="<?= $path_prefix ?>invitaciones_recibidas.php" class="sidebar-sublink <?= ($pagina_actual == 'invitaciones_recibidas.php') ? 'active-sublink' : '' ?>">
                            📬 Recibidas
                        </a>
                        <a href="<?= $path_prefix ?>enviadas_colaboraciones.php" class="sidebar-sublink <?= ($pagina_actual == 'enviadas_colaboraciones.php') ? 'active-sublink' : '' ?>">
                            📤 Enviadas
                        </a>
                        <a href="<?= $path_prefix ?>proyectos_desarrollo_colaboracion.php" class="sidebar-sublink <?= ($pagina_actual == 'proyectos_desarrollo_colaboracion.php') ? 'active-sublink' : '' ?>">
                            🚀 Proyectos Activos
                        </a>
                    </div>
                </div>

                <!-- BOTÓN DE MENSAJES -->
                <a href="<?= $path_prefix ?>mensajes.php" class="sidebar-link <?= ($pagina_actual == 'mensajes.php') ? 'active-link' : '' ?>" style="justify-content:space-between;">
                    <span><span>💬</span> Mensajes</span>
                    <?php if ($mensajesPendientes > 0): ?>
                        <span aria-label="<?= $mensajesPendientes ?> mensajes sin leer" title="<?= $mensajesPendientes ?> mensajes sin leer" style="min-width:1.35rem;padding:.12rem .35rem;box-sizing:border-box;border-radius:9999px;background:#ef4444;color:#fff;text-align:center;font-size:.7rem;font-weight:800;">
                            <?= $mensajesPendientes > 99 ? '99+' : $mensajesPendientes ?>
                        </span>
                    <?php elseif ($errorMensajesPendientes): ?>
                        <span aria-label="No se pudo consultar el contador de mensajes" title="No se pudo consultar el contador de mensajes" style="min-width:1.35rem;padding:.12rem .35rem;box-sizing:border-box;border-radius:9999px;background:#ef4444;color:#fff;text-align:center;font-size:.7rem;font-weight:800;">!</span>
                    <?php endif; ?>
                </a>

                <a href="<?= $path_prefix ?>carrito.php" class="sidebar-link <?= ($pagina_actual == 'carrito.php') ? 'active-link' : '' ?>">
                    <span>🛒</span> Carrito de Compras
                </a>

            </nav>
        </div>

    </div>

    <!-- BOTÓN DE CERRAR SESIÓN EN EL FOOTER DEL SIDEBAR -->
    <a href="<?= $path_prefix ?>../includes/actions/logout.php" class="sidebar-logout-btn" title="Clic para cerrar sesión">
        <div style="width: 2.25rem; height: 2.25rem; background: linear-gradient(135deg, var(--brand-orange), var(--brand-pink)); color: #ffffff; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 800; flex-shrink: 0;">
            AP
        </div>
        <div style="display: flex; flex-direction: column; overflow: hidden; flex-grow: 1;">
            <strong style="font-size: 0.85rem; color: var(--text-main); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                <?= htmlspecialchars($nombre_user ?? 'Artista Poli-Art') ?>
            </strong>
            <span style="font-size: 0.725rem; color: var(--brand-orange); font-weight: 700;">
                🚪 Cerrar Sesión
            </span>
        </div>
    </a>
</aside>

<!-- MODAL PARA ELEGIR TIPO DE ARTE AL SUBIR OBRA -->
<div id="modal-sidebar-tipo-arte" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(6px); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: var(--bg-surface); width: 90%; max-width: 520px; border-radius: 1rem; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.3); display: flex; flex-direction: column; gap: 1.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.75rem;">
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-main); margin: 0;">🚀 Publicar Nueva Obra</h2>
            <button onclick="cerrarModalSubirObraSidebar()" style="background: transparent; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">✕</button>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            <a href="<?= $path_prefix ?>subir_obra.php?tipo=dibujo" class="option-sidebar-art-btn">
                <span style="font-size: 1.5rem;">🎨</span>
                <div>
                    <strong style="display: block; color: var(--text-main); font-size: 0.95rem;">Ilustración, Pintura & Arte Visual</strong>
                    <span style="font-size: 0.775rem; color: var(--text-muted);">Lienzos, arte digital, acuarelas y bocetos.</span>
                </div>
            </a>
            <a href="<?= $path_prefix ?>subir_obra.php?tipo=musica" class="option-sidebar-art-btn">
                <span style="font-size: 1.5rem;">🎵</span>
                <div>
                    <strong style="display: block; color: var(--text-main); font-size: 0.95rem;">Música, Pistas & Videoclips</strong>
                    <span style="font-size: 0.775rem; color: var(--text-muted);">Composiciones, canciones, maquetas y videos.</span>
                </div>
            </a>
            <a href="<?= $path_prefix ?>subir_obra.php?tipo=escenicas" class="option-sidebar-art-btn">
                <span style="font-size: 1.5rem;">🎭</span>
                <div>
                    <strong style="display: block; color: var(--text-main); font-size: 0.95rem;">Artes Escénicas & Teatro</strong>
                    <span style="font-size: 0.775rem; color: var(--text-muted);">Obras de teatro, performances y danza en vivo.</span>
                </div>
            </a>
            <a href="<?= $path_prefix ?>subir_obra.php?tipo=escritura" class="option-sidebar-art-btn">
                <span style="font-size: 1.5rem;">✍️</span>
                <div>
                    <strong style="display: block; color: var(--text-main); font-size: 0.95rem;">Libros & Literatura</strong>
                    <span style="font-size: 0.775rem; color: var(--text-muted);">Novelas, cuentos, poemarios y ensayos.</span>
                </div>
            </a>
            <a href="<?= $path_prefix ?>subir_obra.php?tipo=escultura" class="option-sidebar-art-btn">
                <span style="font-size: 1.5rem;">🗿</span>
                <div>
                    <strong style="display: block; color: var(--text-main); font-size: 0.95rem;">Escultura & Modelo 3D</strong>
                    <span style="font-size: 0.775rem; color: var(--text-muted);">Modelado, piezas 3D y piezas artesanales.</span>
                </div>
            </a>
        </div>
    </div>
</div>

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
    top: 4.25rem;
    align-self: flex-start;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    overscroll-behavior: contain;
    scrollbar-width: thin;
    scrollbar-color: color-mix(in srgb, var(--brand-orange) 38%, var(--bg-nav)) transparent;
    z-index: 40 !important;
    border-right: 1px solid var(--border-subtle) !important;
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
}

.sidebar-link:hover, .sidebar-link.active-link {
    background-color: var(--hover-item) !important;
    color: var(--brand-orange) !important;
}

.sidebar-sublink {
    display: block;
    padding: 0.45rem 0.75rem;
    color: var(--text-muted) !important;
    text-decoration: none;
    font-size: 0.825rem;
    font-weight: 500;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
}

.sidebar-sublink:hover, .sidebar-sublink.active-sublink {
    background-color: var(--hover-item) !important;
    color: var(--brand-orange) !important;
    font-weight: 700;
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

.option-sidebar-art-btn {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.85rem 1rem;
    background-color: var(--bg-body);
    border: 1px solid var(--border-subtle);
    border-radius: 0.75rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.option-sidebar-art-btn:hover {
    background-color: var(--hover-item);
    border-color: var(--brand-orange);
    transform: translateX(4px);
}
</style>

<script>
function toggleMenuArtes() {
    const submenu = document.getElementById('submenu-artes');
    submenu.style.display = (submenu.style.display === 'none' || submenu.style.display === '') ? 'flex' : 'none';
}

function toggleMenuColaboraciones() {
    const submenu = document.getElementById('submenu-colaboraciones');
    submenu.style.display = (submenu.style.display === 'none' || submenu.style.display === '') ? 'flex' : 'none';
}

function abrirModalSubirObraSidebar() {
    document.getElementById('modal-sidebar-tipo-arte').style.display = 'flex';
}

function cerrarModalSubirObraSidebar() {
    document.getElementById('modal-sidebar-tipo-arte').style.display = 'none';
}
</script>
