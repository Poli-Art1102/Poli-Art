<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin(['artista']);
require_once __DIR__ . '/../header.php';
include_once __DIR__ . '/../sidebar.php';

$project_base = '/poliart/';

/* Datos de prueba retirados; las obras se cargan desde la base de datos.
$videos_musica = [
    [
        'id' => 1,
        'titulo' => 'Mix: SENTIMIENTO ORIGINAL (En Vivo Medellín)',
        'duracion' => '1:04:13',
        'miniatura' => 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?auto=format&fit=crop&w=800&q=80',
        'video_url' => 'https://file-examples.com/storage/fe884a4413663a8a9134f55/2017/04/file_example_MP4_700_1MG.mp4',
        'artista' => 'Dread Mar I, Gondwana y más',
        'avatar' => 'https://ui-avatars.com/api/?name=Reggae+Vibes&background=16a34a&color=fff',
        'vistas' => '3.8 M vistas',
        'fecha' => 'Hace 3 meses',
        'descripcion' => 'Compilado especial en vivo con las mejores interpretaciones y vibraciones del género desde Medellín.'
    ],
    [
        'id' => 2,
        'titulo' => 'Sesión de Producción en Vivo: Beats de Rap Urbano',
        'duracion' => '45:12',
        'miniatura' => 'https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?auto=format&fit=crop&w=800&q=80',
        'video_url' => 'https://file-examples.com/storage/fe884a4413663a8a9134f55/2017/04/file_example_MP4_700_1MG.mp4',
        'artista' => 'Jeffrey Ramos (Lulo)',
        'avatar' => 'https://ui-avatars.com/api/?name=Jeffrey+Ramos&background=f97316&color=fff',
        'vistas' => '12.4 K vistas',
        'fecha' => 'Transmitido hace 12 h',
        'descripcion' => 'Proceso de composición, ecualización y mezcla de ritmos urbanos en directo desde el estudio casero.'
    ],
    [
        'id' => 3,
        'titulo' => 'EL SUEÑOOO - Videoclip Oficial en Comuna 13',
        'duracion' => '1:07:29',
        'miniatura' => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?auto=format&fit=crop&w=800&q=80',
        'video_url' => 'https://file-examples.com/storage/fe884a4413663a8a9134f55/2017/04/file_example_MP4_700_1MG.mp4',
        'artista' => 'Yedvid Music',
        'avatar' => 'https://ui-avatars.com/api/?name=Yedvid&background=0284c7&color=fff',
        'vistas' => '322 vistas',
        'fecha' => 'Hace 2 días',
        'descripcion' => 'Producción audiovisual independiente grabada en los miradores y murales icónicos de la Comuna 13.'
    ],
    [
        'id' => 4,
        'titulo' => 'Acústico en la Niebla: Guitarra & Voz',
        'duracion' => '56:35',
        'miniatura' => 'https://images.unsplash.com/photo-1510915361894-db8b60106cb1?auto=format&fit=crop&w=800&q=80',
        'video_url' => 'https://file-examples.com/storage/fe884a4413663a8a9134f55/2017/04/file_example_MP4_700_1MG.mp4',
        'artista' => 'Sofía Rodríguez',
        'avatar' => 'https://ui-avatars.com/api/?name=Sofia+Rod&background=e11d48&color=fff',
        'vistas' => '85.1 K vistas',
        'fecha' => 'Hace 1 semana',
        'descripcion' => 'Sesión íntima en formato acústico interpretando baladas y temas originales rodeada de naturaleza.'
    ],
    [
        'id' => 5,
        'titulo' => 'Sintetizadores & Lo-Fi Beats para Ilustrar',
        'duracion' => '28:10',
        'miniatura' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=800&q=80',
        'video_url' => 'https://file-examples.com/storage/fe884a4413663a8a9134f55/2017/04/file_example_MP4_700_1MG.mp4',
        'artista' => 'PoliArt Sound System',
        'avatar' => 'https://ui-avatars.com/api/?name=Poli+Art&background=8b5cf6&color=fff',
        'vistas' => '45 K vistas',
        'fecha' => 'Hace 2 semanas',
        'descripcion' => 'Música ambiental relajante para acompañar tus bloques de trabajo creativo, dibujo y diseño.'
    ]
];

*/
require_once __DIR__ . '/../../includes/catalogo_obras.php';
try {
    $videos_musica = array_map(static function (array $obra): array {
        $fecha = strtotime($obra['publicada_en'] ?? $obra['creado_en']);
        return [
            'id' => (int)$obra['id'],
            'titulo' => $obra['titulo'],
            'duracion' => '',
            'miniatura' => $obra['ruta_imagen'] ?? '',
            'video_url' => $obra['ruta_imagen'] ?? '',
            'artista' => $obra['artista'],
            'avatar' => $obra['avatar_path'] ?? '',
            'vistas' => number_format($obra['total_vistas']) . ' vistas',
            'fecha' => date('d/m/Y', $fecha),
            'descripcion' => $obra['descripcion'] ?? '',
            'likes' => (int)$obra['total_likes'],
        ];
    }, obtenerObrasPorCategoria('musica-audio', getUserId()));
} catch (PDOException $e) {
    error_log('Error al cargar obras musicales: ' . $e->getMessage());
    $videos_musica = [];
    $errorCarga = true;
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

.video-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    width: 100%;
}

.video-card {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.video-card:hover {
    transform: translateY(-4px);
}

.thumbnail-wrapper {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    border-radius: 0.85rem;
    overflow: hidden;
    background-color: var(--bg-surface);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    border: 1px solid var(--border-subtle);
}

.thumbnail-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.video-card:hover .thumbnail-wrapper img {
    transform: scale(1.04);
}

.duration-badge {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background-color: rgba(0, 0, 0, 0.85);
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.2rem 0.45rem;
    border-radius: 0.35rem;
    backdrop-filter: blur(4px);
}

.video-info-wrapper {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
}

.channel-avatar {
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 9999px;
    object-fit: cover;
    flex-shrink: 0;
    border: 2px solid var(--border-subtle);
}

.video-details {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.video-title {
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--text-main);
    line-height: 1.3;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.video-artist {
    font-size: 0.825rem;
    color: var(--text-muted);
    margin: 0;
    font-weight: 600;
}

.video-meta {
    font-size: 0.775rem;
    color: var(--text-muted);
    margin: 0;
    opacity: 0.8;
}

.header-banner {
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1rem;
    padding: 1.5rem 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

/* VISTA FEED CONTINUO ESTILO INSTAGRAM */
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

.feed-media-box {
    width: 100%;
    background: #000;
    max-height: 520px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.feed-media-box video {
    width: 100%;
    max-height: 520px;
    object-fit: contain;
    outline: none;
}

.feed-card-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border-subtle);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
</style>

<div style="display: flex; width: 100vw; height: calc(100vh - 4.25rem); overflow: hidden; background: transparent;">

    <div id="scroll-main-wrapper" style="flex-grow: 1; height: 100%; overflow-y: auto; padding: 2.5rem; box-sizing: border-box;">
        
        <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.75rem;">

            <!-- HEADER BANNER -->
            <div class="header-banner">
                <div>
                    <h1 style="font-size: 1.875rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.6rem;">
                        🎵 Categoría: Música y Audio
                    </h1>
                    <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.3rem;">
                        Explora composiciones musicales, videoclips, sesiones en vivo y maquetas de la comunidad.
                    </p>
                </div>

                <!-- BOTÓN REGRESAR A LA GALERÍA -->
                <button id="btn-volver-grid" onclick="mostrarCuadricula()" style="display: none;" class="btn btn-neutral">
                    ⊞ Volver a la Galería
                </button>
            </div>

            <!-- 1. VISTA CUADRÍCULA DE VIDEOS -->
            <div class="video-grid" id="grid-obras-container">
                <?php foreach ($videos_musica as $v): ?>
                    <div class="video-card" onclick="mostrarFeedEnObra(<?= $v['id'] ?>)">
                        
                        <div class="thumbnail-wrapper">
                            <?php renderizarVistaPreviaObra($v['miniatura'], $v['titulo'], '220px'); ?>
                            <?php if ($v['duracion'] !== ''): ?>
                                <span class="duration-badge"><?= htmlspecialchars($v['duracion'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="video-info-wrapper">
                            <img src="<?= $v['avatar'] ?>" alt="<?= htmlspecialchars($v['artista']) ?>" class="channel-avatar">
                            
                            <div class="video-details">
                                <h3 class="video-title"><?= htmlspecialchars($v['titulo']) ?></h3>
                                <p class="video-artist"><?= htmlspecialchars($v['artista']) ?></p>
                                <p class="video-meta"><?= $v['vistas'] ?> &bull; <?= $v['fecha'] ?></p>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 2. VISTA FEED VERTICAL CONTINUO -->
            <div class="feed-stream-container" id="feed-obras-container">
                <?php foreach ($videos_musica as $v): ?>
                    <div class="feed-post-card" id="post-card-<?= $v['id'] ?>">
                        
                        <div class="feed-card-header">
                            <div style="display: flex; align-items: center; gap: 0.85rem;">
                                <img src="<?= $v['avatar'] ?>" alt="<?= htmlspecialchars($v['artista']) ?>" style="width: 2.75rem; height: 2.75rem; border-radius: 9999px; object-fit: cover;">
                                <div>
                                    <h3 style="color: var(--text-main); font-size: 0.95rem; font-weight: 800; margin: 0;">
                                        <?= htmlspecialchars($v['artista']) ?>
                                    </h3>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem;">
                                        <?= $v['fecha'] ?> &bull; <?= $v['vistas'] ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="padding: 1rem 1.5rem; color: var(--text-main); font-size: 0.95rem;">
                            <strong style="display: block; font-size: 1.05rem; margin-bottom: 0.35rem;"><?= htmlspecialchars($v['titulo']) ?></strong>
                            <?= htmlspecialchars($v['descripcion']) ?>
                        </div>

                        <div class="feed-media-box">
                            <?php renderizarVistaPreviaObra($v['video_url'], $v['titulo'], 'min(70vh, 760px)', 'media-player-' . $v['id']); ?>
                        </div>

                        <div class="feed-card-footer">
                            <span style="color: var(--text-main); font-weight: 700;">❤️ <?= (int)$v['likes'] ?> Me gusta</span>
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
            
            const media = document.getElementById('media-player-' + obraId);
            if (media) {
                media.play().catch(e => console.log('Reproducción automática no disponible:', e));
            }
        }
    }, 50);
}

function mostrarCuadricula() {
    document.querySelectorAll('video, audio').forEach(media => media.pause());

    document.getElementById('feed-obras-container').classList.remove('active');
    document.getElementById('grid-obras-container').style.display = 'grid';
    document.getElementById('btn-volver-grid').style.display = 'none';
    
    const wrapper = document.getElementById('scroll-main-wrapper');
    if (wrapper) wrapper.scrollTop = 0;
}

document.querySelectorAll('.thumbnail-wrapper video, .thumbnail-wrapper audio').forEach(media => {
    media.addEventListener('click', event => event.stopPropagation());
});
</script>

