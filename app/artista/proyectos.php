<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);
require_once __DIR__ . '/../artista/header.php';
include_once __DIR__ . '/../artista/sidebar.php';

$project_base = '/poliart/';

// Mensajes flash
$flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
$flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

$user = getCurrentUser();
$nombre_usuario    = $user['nombre']        ?? 'Artista';
$apellido_usuario  = $user['apellido']      ?? '';
$iniciales = strtoupper(substr($nombre_usuario, 0, 1) . substr($apellido_usuario, 0, 1));

require_once __DIR__ . '/../includes/catalogo_obras.php';

$zona_usuario = 'Sin zona'; // valor por defecto si la BD falla
try {
    $stmtZona = getDB()->prepare(
        'SELECT z.nombre
         FROM usuario u
         LEFT JOIN zona z ON z.id_zona = u.id_zona
         WHERE u.id_usuario = :id_usuario'
    );
    $stmtZona->execute([':id_usuario' => getUserId()]);
    $zona_usuario = $stmtZona->fetchColumn() ?: 'Sin zona';
    $mis_proyectos = obtenerObrasDelArtista(getUserId());
} catch (PDOException $e) {
    error_log('Error al cargar las obras del artista: ' . $e->getMessage());
    $mis_proyectos = [];
    $flash_error = 'No fue posible cargar tus obras.';
}
?>

<style>
html, body {
    height: 100% !important;
    max-height: 100vh !important;
    overflow: hidden !important;
    margin: 0;
    padding: 0;
}

body {
    background-image: url('<?= $project_base ?>app/includes/madera.jpg') !important;
    background-size: cover !important;
    background-position: center !important;
    background-attachment: fixed !important;
    background-repeat: no-repeat !important;
}

.section-card {
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1rem;
    padding: 1.75rem 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    backdrop-filter: blur(8px);
}

.projects-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    width: 100%;
}

@media (max-width: 1024px) {
    .projects-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.project-item {
    position: relative;
    width: 100%;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    background-color: var(--border-subtle);
    cursor: pointer;
    border-radius: 0.75rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border-subtle);
    transition: transform 0.25s ease;
}

.project-item:hover {
    transform: translateY(-4px);
    border-color: var(--brand-orange);
}

.project-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.project-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.1) 100%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 1.25rem;
    box-sizing: border-box;
    opacity: 0;
    transition: opacity 0.25s ease;
}

.project-item:hover .project-overlay {
    opacity: 1;
}

/* VISTA FEED ESTILO INSTAGRAM */
.feed-stream-container {
    display: none;
    flex-direction: column;
    gap: 2rem;
    max-width: 680px;
    margin: 0 auto;
    width: 100%;
}

.feed-stream-container.active {
    display: flex;
}

.feed-post-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1.25rem;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(8px);
}

.feed-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-subtle);
}

.feed-avatar {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 9999px;
    background: linear-gradient(135deg, var(--brand-orange) 0%, #ea580c 100%);
    color: #ffffff;
    font-weight: 800;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.feed-image-box {
    width: 100%;
    background: #000;
    max-height: 520px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.feed-image-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.feed-card-footer {
    padding: 1.25rem 1.5rem;
    border-top: 1px solid var(--border-subtle);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* TOAST FLOTANTE */
.toast-container {
    position: fixed !important;
    top: 5.5rem !important;
    bottom: auto !important;
    left: 50% !important;
    right: auto !important;
    z-index: 10000 !important;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    pointer-events: none;
}

.toast-message {
    background-color: var(--bg-surface);
    color: var(--text-main);
    border: 1px solid var(--border-subtle);
    border-left: 4px solid var(--brand-orange);
    padding: 0.85rem 1.25rem;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    font-weight: 700;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
    max-width: min(32rem, 90vw);
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease;
    pointer-events: auto;
}

.toast-message.show {
    opacity: 1;
    transform: translateY(0);
}
.project-inline-alert {
    width: 100%;
    box-sizing: border-box;
    padding: .9rem 1.1rem;
    border: 1px solid rgba(34, 197, 94, .45);
    border-left: 4px solid #22c55e;
    border-radius: .75rem;
    background: rgba(34, 197, 94, .12);
    color: var(--text-main);
    font-weight: 700;
    box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
}
.project-inline-alert.error {
    border-color: rgba(239, 68, 68, .5);
    border-left-color: #ef4444;
    background: rgba(239, 68, 68, .12);
}
@media (max-width: 640px) {
    .toast-container { top: 5rem !important; width: calc(100vw - 2rem); }
    .toast-message { max-width: 100%; box-sizing: border-box; }
}
</style>

<div style="display: flex; width: 100vw; height: calc(100vh - 4.25rem); overflow: hidden; background: transparent;">

    <div id="scroll-main-wrapper" style="flex-grow: 1; height: 100%; overflow-y: auto; padding: 2rem 2.5rem; box-sizing: border-box;">
        
        <div style="max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">

            <?php if ($flash_ok): ?>
                <div class="project-inline-alert" role="status">
                    <?= htmlspecialchars($flash_ok, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php elseif ($flash_error): ?>
                <div class="project-inline-alert error" role="alert">
                    <?= htmlspecialchars($flash_error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <!-- SECCIÓN ENCABEZADO "MIS PROYECTOS Y OBRAS" -->
            <div class="section-card" style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h1 style="color: var(--text-main); font-size: 1.75rem; font-weight: 800; margin: 0;">
                        Mis Proyectos y Obras
                    </h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0.3rem 0 0 0;">
                        <?= count($mis_proyectos) ?> obras en tu galería personal
                    </p>
                </div>

                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <!-- BOTÓN VOLVER A CUADRÍCULA -->
                    <button id="btn-volver-grid" onclick="mostrarCuadricula()" class="btn btn-neutral" style="display: none;">
                        ⊞ Volver a la Galería
                    </button>

                    <a href="subir_obra.php" class="btn btn-primary">
                        ➕ Nueva obra
                    </a>
                </div>
            </div>

            <!-- 1. VISTA CUADRÍCULA DE OBRAS -->
            <div class="projects-grid" id="grid-obras-container">
                <?php if (!$mis_proyectos): ?>
                    <p style="grid-column: 1 / -1; color: var(--text-muted); text-align: center; padding: 2rem;">
                        Aún no has subido obras.
                    </p>
                <?php endif; ?>
                <?php foreach ($mis_proyectos as $obra): ?>
                    <?php $estadoVisual = presentarEstadoObraArtista($obra); ?>
                    <div class="project-item" onclick="mostrarFeedEnObra(<?= $obra['id'] ?>)">
                        <?php renderizarVistaPreviaObra($obra['imagen'], $obra['titulo'], '100%'); ?>
                        
                        <div class="project-overlay">
                            <strong style="color: #ffffff; font-size: 1rem; font-weight: 800; display: block;">
                                <?= htmlspecialchars($obra['titulo']) ?>
                            </strong>
                            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-top: 0.4rem;">
                                <span style="color: var(--brand-orange); font-weight: 800; font-size: 0.88rem;"><?= $obra['precio'] ?></span>
                                <span style="font-size: 0.78rem; color: #fed7aa; font-weight: 700;">❤️ <?= $obra['likes'] ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 2. VISTA FEED CONTINUO TIPO INSTAGRAM -->
            <div class="feed-stream-container" id="feed-obras-container">
                <?php foreach ($mis_proyectos as $obra): ?>
                    <?php $estadoVisual = presentarEstadoObraArtista($obra); ?>
                    <div class="feed-post-card" id="post-card-<?= $obra['id'] ?>">
                        
                        <!-- ENCABEZADO CON EL BADGE DE ESTADO DE LA OBRA -->
                        <div class="feed-card-header">
                            <div style="display: flex; align-items: center; gap: 0.85rem;">
                                <div class="feed-avatar"><?= htmlspecialchars($iniciales, ENT_QUOTES, 'UTF-8') ?></div>
                                <div>
                                    <h3 style="color: var(--text-main); font-size: 0.95rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 0.4rem;">
                                        <?= htmlspecialchars($nombre_usuario . ' ' . $apellido_usuario) ?>
                                        <span style="font-weight: 400; color: var(--text-muted); font-size: 0.825rem;">• <?= htmlspecialchars($zona_usuario) ?></span>
                                    </h3>
                                    <div style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem;">
                                        <?= htmlspecialchars($obra['tiempo'], ENT_QUOTES, 'UTF-8') ?>
                                        &bull; <?= $estadoVisual['visible'] ? 'Visible para todos' : 'Solo visible para ti' ?>
                                    </div>
                                </div>
                            </div>

                            <!-- BADGE DE ESTADO -->
                            <span style="background-color: <?= htmlspecialchars($estadoVisual['fondo'], ENT_QUOTES, 'UTF-8') ?>; color: <?= htmlspecialchars($estadoVisual['color'], ENT_QUOTES, 'UTF-8') ?>; border: 1px solid <?= htmlspecialchars($estadoVisual['color'], ENT_QUOTES, 'UTF-8') ?>; padding: 0.3rem 0.75rem; border-radius: 9999px; font-size: 0.78rem; font-weight: 800; display: inline-flex; align-items: center; gap: 0.35rem;">
                                <?= htmlspecialchars($estadoVisual['etiqueta'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                        <div style="padding: 1rem 1.5rem; color: var(--text-main); font-size: 0.925rem; line-height: 1.5;">
                            <?= htmlspecialchars($obra['descripcion']) ?>
                        </div>

                        <div class="feed-image-box">
                            <?php renderizarVistaPreviaObra($obra['imagen'], $obra['titulo'], 'min(520px, 70vh)'); ?>
                        </div>

                        <div class="feed-card-footer">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span style="background: var(--hover-item); border: 1px solid var(--border-subtle); color: var(--text-main); padding: 0.6rem 1rem; border-radius: 0.65rem; font-weight: 700; font-size: 0.85rem;">
                                    ❤️ <?= (int)$obra['likes'] ?> Me gusta
                                </span>
                                <span style="background: rgba(249, 115, 22, 0.15); color: var(--brand-orange); border: 1px solid rgba(249, 115, 22, 0.3); padding: 0.5rem 0.85rem; border-radius: 0.65rem; font-weight: 800; font-size: 0.85rem;">
                                    <?= $obra['precio'] ?>
                                </span>
                            </div>

                            <a href="modificar_obra.php?id=<?= $obra['id'] ?>&amp;origen=proyectos" class="btn btn-primary btn-sm">
                                ✏️ Editar Obra
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

        </div>

    </div>
</div>

<script>
function mostrarFeedEnObra(obraId) {
    document.getElementById('grid-obras-container').style.display = 'none';
    const feedContainer = document.getElementById('feed-obras-container');
    feedContainer.classList.add('active');
    document.getElementById('btn-volver-grid').style.display = 'block';

    setTimeout(() => {
        const postCard = document.getElementById('post-card-' + obraId);
        if (postCard) {
            postCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, 50);
}

function mostrarCuadricula() {
    document.getElementById('feed-obras-container').classList.remove('active');
    document.getElementById('grid-obras-container').style.display = 'grid';
    document.getElementById('btn-volver-grid').style.display = 'none';
    
    document.getElementById('scroll-main-wrapper').scrollTop = 0;
}

</script>

<script src="/poliart/public/assets/js/artista.js"></script>


