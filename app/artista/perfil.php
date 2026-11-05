<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);
$project_base = '/poliart/';
$flash_ok = $_SESSION['flash_ok'] ?? null;
unset($_SESSION['flash_ok']);

$mis_proyectos = [
    [
        'id' => 1,
        'titulo' => 'Ilustración Neón sobre la Niebla',
        'precio' => '$220 USD',
        'imagen' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
        'tipo' => 'galeria',
        'categoria' => 'Digital',
        'tiempo' => 'Hace 20 minutos',
        'descripcion' => 'Boceto inicial para un mural de arte urbano digital. ¿A alguien le interesa colaborar en los trazos o la paleta de colores?',
        'ubicacion' => 'El Poblado',
        'likes' => 42,
        'estado' => 'disponible'
    ],
    [
        'id' => 2,
        'titulo' => 'Retrato Canino al Óleo',
        'precio' => '$180 USD',
        'imagen' => 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=800&q=80',
        'tipo' => 'foto',
        'categoria' => 'Lienzos',
        'tiempo' => 'Hace 2 horas',
        'descripcion' => 'Estudio de textura sobre pelaje canino utilizando paleta de colores cálidos y pinceladas sueltas.',
        'ubicacion' => 'Laureles',
        'likes' => 88,
        'estado' => 'disponible'
    ],
    [
        'id' => 3,
        'titulo' => 'Mural Castillo y Niebla',
        'precio' => '$280 USD',
        'imagen' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=800&q=80',
        'tipo' => 'foto',
        'categoria' => 'Arte Urbano',
        'tiempo' => 'Ayer',
        'descripcion' => 'Propuesta conceptual de muralismo urbano inspirado en la arquitectura mística y las montañas antioqueñas.',
        'ubicacion' => 'Santa Elena',
        'likes' => 64,
        'estado' => 'reservada'
    ],
    [
        'id' => 4,
        'titulo' => 'Mural Montañas Verdes',
        'precio' => '$310 USD',
        'imagen' => 'https://images.unsplash.com/photo-1438786657495-640937046d18?auto=format&fit=crop&w=800&q=80',
        'tipo' => 'foto',
        'categoria' => 'Arte Urbano',
        'tiempo' => 'Hace 3 días',
        'descripcion' => 'Composición panorámica rindiendo homenaje a la biodiversidad y cuencas hidrográficas de Medellín.',
        'ubicacion' => 'San Javier',
        'likes' => 120,
        'estado' => 'vendida'
    ],
    [
        'id' => 5,
        'titulo' => 'Explosión de Color (Fluidos Neón)',
        'precio' => '$190 USD',
        'imagen' => 'https://images.unsplash.com/photo-1541701494587-cb58502866ab?auto=format&fit=crop&w=800&q=80',
        'tipo' => 'video',
        'categoria' => 'Colaboraciones',
        'tiempo' => 'Hace 5 días',
        'descripcion' => 'Experimento de mezcla de tintas de pigmento orgánico en movimiento macro. Trabajo colaborativo.',
        'ubicacion' => 'Centro',
        'likes' => 95,
        'estado' => 'disponible'
    ],
    [
        'id' => 6,
        'titulo' => 'Fiesta de Luces Digital',
        'precio' => '$150 USD',
        'imagen' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=800&q=80',
        'tipo' => 'galeria',
        'categoria' => 'Digital',
        'tiempo' => 'Hace 1 semana',
        'descripcion' => 'Secuencia de partículas luminosas vectoriales creadas para proyecciones mapping en eventos locales.',
        'ubicacion' => 'Belén',
        'likes' => 150,
        'estado' => 'oculta'
    ]
];

require_once __DIR__ . '/../includes/catalogo_obras.php';
$pdo = getDB();
$idUsuario = getUserId();

try {
    $stmtPerfil = $pdo->prepare(
        'SELECT u.nombre, u.apellido, u.nombre_usuario, u.avatar_path, u.portada_path, z.nombre AS zona,
                a.id_artista, a.nombre_artistico, a.especialidad
         FROM usuario u
         INNER JOIN artista a ON a.id_usuario = u.id_usuario
         LEFT JOIN zona z ON z.id_zona = u.id_zona
         WHERE u.id_usuario = :id_usuario AND u.rol = \'artista\'
         LIMIT 1'
    );
    $stmtPerfil->execute([':id_usuario' => $idUsuario]);
    $perfil = $stmtPerfil->fetch();

    if (!$perfil) {
        header('Location: /poliart/app/includes/actions/logout.php');
        exit;
    }

    $nombre_usuario = $perfil['nombre'];
    $apellido_usuario = $perfil['apellido'];
    $seudonimo = $perfil['nombre_artistico'] ?: $perfil['nombre_usuario'];
    $zona_usuario = $perfil['zona'] ?: 'Sin zona registrada';
    $especialidad = $perfil['especialidad'] ?: 'Sin especialidad registrada';
    $avatar_path = is_string($perfil['avatar_path'])
        && strpos($perfil['avatar_path'], '/poliart/uploads/avatars/') === 0
        ? $perfil['avatar_path']
        : '';
    $avatar_url = $avatar_path !== ''
        ? $avatar_path
        : 'https://ui-avatars.com/api/?name=' . urlencode($nombre_usuario . ' ' . $apellido_usuario) . '&background=f97316&color=fff&size=128';
    $portada_path = is_string($perfil['portada_path'])
        && strpos($perfil['portada_path'], '/poliart/uploads/portadas/') === 0
        ? $perfil['portada_path']
        : '';
    $portada_style = $portada_path !== ''
        ? "background-image: url('" . htmlspecialchars($portada_path, ENT_QUOTES, 'UTF-8') . "');"
        : '';
    $iniciales = strtoupper(substr($nombre_usuario, 0, 1) . substr($apellido_usuario, 0, 1));

    $mis_proyectos = obtenerObrasDelArtista($idUsuario);

    $stmtEstadisticas = $pdo->prepare(
        'SELECT
            (SELECT COUNT(*) FROM seguimiento WHERE id_seguido = :id_usuario) AS seguidores,
            (SELECT COUNT(*) FROM me_gusta mg
             INNER JOIN obra o ON o.id_obra = mg.id_obra
             INNER JOIN artista a ON a.id_artista = o.id_artista
             WHERE a.id_usuario = :id_likes) AS likes,
            (SELECT COUNT(DISTINCT o.id_obra)
             FROM obra o
             WHERE o.estado_publicacion = \'publicada\'
               AND EXISTS (
                   SELECT 1 FROM obra_colaborador oc
                   WHERE oc.id_obra = o.id_obra
               )
               AND (
                   o.id_artista = :id_artista_creador
                   OR EXISTS (
                       SELECT 1 FROM obra_colaborador oc
                       WHERE oc.id_obra = o.id_obra
                         AND oc.id_artista = :id_artista_colaborador
                   )
               )) AS colaboraciones'
    );
    $stmtEstadisticas->execute([
        ':id_usuario' => $idUsuario,
        ':id_likes' => $idUsuario,
        ':id_artista_creador' => $perfil['id_artista'],
        ':id_artista_colaborador' => $perfil['id_artista'],
    ]);
    $estadisticas = $stmtEstadisticas->fetch();
} catch (PDOException $e) {
    error_log('Error al cargar el perfil del artista: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo cargar el perfil del artista. Intenta de nuevo más tarde.');
}

require_once __DIR__ . '/../artista/header.php';
include_once __DIR__ . '/../artista/sidebar.php';
?>

<style>
html, body {
    height: 100% !important;
    max-height: 100vh !important;
    overflow: hidden !important;
    margin: 0;
    padding: 0;
}

/* En el perfil, el botón volver queda debajo del navbar sin empujar el sidebar ni el contenido. */
.global-back-row {
    position: absolute !important;
    top: 4.25rem !important;
    left: 273px !important;
    z-index: 60 !important;
    margin-top: 0 !important;
    padding: .55rem 2rem 0 !important;
}

body > main,
.global-back-row + main {
    margin-top: 4.25rem !important;
    height: calc(100vh - 4.25rem) !important;
    min-height: 0 !important;
}

#scroll-main-wrapper {
    padding-top: 5rem !important;
}

@media (max-width: 700px) {
    .global-back-row {
        left: 0 !important;
        padding-left: 1rem !important;
    }
}

body {
    background-image: url('<?= $project_base ?>app/includes/madera.jpg') !important;
    background-size: cover !important;
    background-position: center !important;
    background-attachment: fixed !important;
    background-repeat: no-repeat !important;
}

.stat-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem 1rem;
    background: var(--hover-item);
    border: 1px solid var(--border-subtle);
    border-radius: 0.5rem;
}

.stat-number {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--brand-orange);
}

.stat-label {
    font-size: 0.7rem;
    color: var(--text-muted);
    text-transform: uppercase;
    font-weight: 700;
}

.projects-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    width: 100%;
}

@media (min-width: 1400px) {
    .projects-grid {
        grid-template-columns: repeat(4, 1fr);
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
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    z-index: 10000;
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
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease;
    pointer-events: auto;
}

.toast-message.show {
    opacity: 1;
    transform: translateY(0);
}
.profile-inline-alert {
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
</style>

<div style="display: flex; width: 100vw; height: calc(100vh - 4.25rem); overflow: hidden; background: transparent;">

    <div id="scroll-main-wrapper" style="flex-grow: 1; height: 100%; overflow-y: auto; padding: 2rem 2.5rem; box-sizing: border-box;">
        
        <div style="max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">

            <?php if ($flash_ok): ?>
                <div class="profile-inline-alert" role="status">
                    <?= htmlspecialchars($flash_ok, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <!-- TARJETA CABECERA DE PERFIL -->
            <div style="background-color: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: 1rem; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); backdrop-filter: blur(8px);">
                <div style="width: 100%; height: 180px; background: linear-gradient(135deg, var(--brand-orange) 0%, #fb923c 50%, #f43f5e 100%); background-size: cover; background-position: center; <?= $portada_style ?>"></div>

                <div style="padding: 0 2rem 1.25rem 2rem; position: relative;">
                    <div style="display: flex; align-items: flex-end; justify-content: space-between; margin-top: -3.5rem; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                        
                        <div style="display: flex; align-items: flex-end; gap: 1.25rem;">
                            <img src="<?= htmlspecialchars($avatar_url, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" style="width: 6.5rem; height: 6.5rem; border-radius: 9999px; border: 4px solid var(--bg-surface); object-fit: cover; background-color: var(--brand-orange);">
                            
                            <div style="margin-bottom: 0.25rem;">
                                <div style="display: flex; align-items: center; gap: 0.6rem;">
                                    <h1 style="color: var(--text-main); font-size: 1.75rem; font-weight: 800; margin: 0;">
                                        <?= htmlspecialchars($nombre_usuario . ' ' . $apellido_usuario, ENT_QUOTES, 'UTF-8') ?>
                                    </h1>
                                    <span style="background-color: var(--hover-item); color: var(--text-muted); border: 1px solid var(--border-subtle); padding: 0.2rem 0.65rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700;">
                                        Perfil de artista
                                    </span>
                                </div>
                                <div style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.2rem;">
                                    @<?= strtolower(htmlspecialchars($seudonimo, ENT_QUOTES, 'UTF-8')) ?> &bull; <span style="color: var(--brand-orange); font-weight: 700;"><?= htmlspecialchars($especialidad, ENT_QUOTES, 'UTF-8') ?></span> &bull; 📍 <?= htmlspecialchars($zona_usuario, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 1.25rem;">
                            <div style="display: flex; gap: 0.6rem;">
                                <div class="stat-box">
                                    <span class="stat-number"><?= count($mis_proyectos) ?></span>
                                    <span class="stat-label">Obras</span>
                                </div>
                                <div class="stat-box">
                                    <span class="stat-number"><?= (int)$estadisticas['likes'] ?></span>
                                    <span class="stat-label">Likes</span>
                                </div>
                                <div class="stat-box">
                                    <span class="stat-number"><?= (int)$estadisticas['colaboraciones'] ?></span>
                                    <span class="stat-label">Colabs</span>
                                </div>
                            </div>

                            <a href="editar_perfil.php" style="background: linear-gradient(135deg, var(--brand-orange) 0%, #ea580c 100%); color: #ffffff; padding: 0.65rem 1.25rem; border-radius: 0.5rem; font-weight: 700; font-size: 0.875rem; text-decoration: none;">
                                ✏️ Editar perfil
                            </a>
                        </div>
                    </div>

                    <!-- BARRA DE NAVEGACIÓN VISTAS Y SELECTOR -->
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-subtle); padding-top: 0.75rem; margin-top: 1rem;">
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="perfil.php" style="color: var(--brand-orange); border-bottom: 2px solid var(--brand-orange); padding: 0.75rem 1.25rem; font-weight: 700; text-decoration: none;">🖼️ Publicaciones</a>
                            <a href="borradores.php" style="color: var(--text-muted); padding: 0.75rem 1.25rem; font-weight: 600; text-decoration: none;">📝 Bocetos</a>
                            <a href="megusta.php" style="color: var(--text-muted); padding: 0.75rem 1.25rem; font-weight: 600; text-decoration: none;">❤️ Me gusta</a>
                            <a href="seguidores.php" style="color: var(--text-muted); padding: 0.75rem 1.25rem; font-weight: 600; text-decoration: none;">👥 Seguidores (<?= (int)$estadisticas['seguidores'] ?>)</a>
                            <a href="../includes/obras_apartadas.php" style="color: var(--text-muted); padding: 0.75rem 1.25rem; font-weight: 600; text-decoration: none; white-space: nowrap;">🎨 Obras Compradas / Apartadas</a>
                        </div>

                        <!-- BOTÓN VOLVER A CUADRÍCULA -->
                        <button id="btn-volver-grid" onclick="mostrarCuadricula()" style="display: none;" class="btn btn-neutral">
                            ← Volver a la Cuadrícula
                        </button>
                    </div>
                </div>
            </div>

            <!-- 1. VISTA CUADRÍCULA DE OBRAS -->
            <div class="projects-grid" id="grid-obras-container">
                <?php if (!$mis_proyectos): ?>
                    <p style="grid-column: 1 / -1; color: var(--text-muted); text-align: center; padding: 2rem;">
                        Todavía no has subido obras.
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

            <!-- 2. VISTA FEED ESTILO INSTAGRAM -->
            <div class="feed-stream-container" id="feed-obras-container">
                <?php foreach ($mis_proyectos as $obra): ?>
                    <?php $estadoVisual = presentarEstadoObraArtista($obra); ?>
                    <div class="feed-post-card" id="post-card-<?= $obra['id'] ?>">
                        
                        <!-- ENCABEZADO DE TARJETA CON ESTADO DE LA OBRA -->
                        <div class="feed-card-header">
                            <div style="display: flex; align-items: center; gap: 0.85rem;">
                                <div class="feed-avatar"><?= htmlspecialchars($iniciales, ENT_QUOTES, 'UTF-8') ?></div>
                                <div>
                                    <h3 style="color: var(--text-main); font-size: 0.95rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 0.4rem;">
                                        <?= htmlspecialchars($nombre_usuario . ' ' . $apellido_usuario, ENT_QUOTES, 'UTF-8') ?>
                                        <span style="font-weight: 400; color: var(--text-muted); font-size: 0.825rem;">📍 <?= htmlspecialchars($zona_usuario, ENT_QUOTES, 'UTF-8') ?></span>
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

                            <a href="modificar_obra.php?id=<?= $obra['id'] ?>&amp;origen=perfil" class="btn btn-primary btn-sm">
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


