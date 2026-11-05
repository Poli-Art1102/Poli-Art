<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin(['artista']);
require_once __DIR__ . '/../header.php';

$project_base = '/poliart/';

/* Datos de prueba retirados; las obras se cargan desde la base de datos.
$esculturas = [
    [
        'id' => 1,
        'titulo' => 'Escultura Cerámica Abstracta',
        'artista' => 'Sofía Rodríguez',
        'avatar' => 'https://ui-avatars.com/api/?name=Sofia+Rodriquez&background=ec4899&color=fff',
        'imagen' => 'https://images.unsplash.com/photo-1565193566173-7a0ee3dbe261?auto=format&fit=crop&w=600&q=80',
        'likes' => '320',
        'descripcion' => 'Pieza en cerámica de alta temperatura moldeada a mano con acabados en esmalte mate.'
    ],
    [
        'id' => 2,
        'titulo' => 'Busto Ciberpunk Medellín 2026',
        'artista' => 'Jeffrey Ramos (@lulo)',
        'avatar' => 'https://ui-avatars.com/api/?name=Jeffrey+Ramos&background=f97316&color=fff',
        'imagen' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=600&q=80',
        'likes' => '512',
        'descripcion' => 'Modelado y renderizado 3D futurista explorando la estética ciberpunk y elementos tradicionales.'
    ],
    [
        'id' => 3,
        'titulo' => 'Estructura Resina & LED',
        'artista' => 'Carlos Mendoza',
        'avatar' => 'https://ui-avatars.com/api/?name=Carlos+Mendoza&background=0284c7&color=fff',
        'imagen' => 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?auto=format&fit=crop&w=600&q=80',
        'likes' => '195',
        'descripcion' => 'Escultura lumínica con resina epóxica translúcida e iluminación LED programable interna.'
    ],
    [
        'id' => 4,
        'titulo' => 'Relieve Metal Comuna 13',
        'artista' => 'Colectivo Urbano',
        'avatar' => 'https://ui-avatars.com/api/?name=Colectivo+Urbano&background=10b981&color=fff',
        'imagen' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&w=600&q=80',
        'likes' => '240',
        'descripcion' => 'Trabajo en repujado sobre láminas de cobre reciclando estructuras metálicas locales.'
    ]
];

*/
require_once __DIR__ . '/../../includes/catalogo_obras.php';
try {
    $esculturas = array_map(static function (array $obra): array {
        return [
            'id' => (int)$obra['id'],
            'titulo' => $obra['titulo'],
            'artista' => $obra['artista'],
            'avatar' => $obra['avatar_path'] ?? '',
            'imagen' => $obra['ruta_imagen'] ?? '',
            'likes' => $obra['total_likes'],
            'descripcion' => $obra['descripcion'] ?? '',
        ];
    }, obtenerObrasPorCategoria('escultura-3d', getUserId()));
} catch (PDOException $e) {
    error_log('Error al cargar obras de escultura: ' . $e->getMessage());
    $esculturas = [];
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

/* CUADRÍCULA PINTEREST */
.pinterest-grid {
    column-count: 4;
    column-gap: 1.25rem;
    width: 100%;
}

@media (max-width: 1200px) { .pinterest-grid { column-count: 3; } }
@media (max-width: 800px)  { .pinterest-grid { column-count: 2; } }
@media (max-width: 500px)  { .pinterest-grid { column-count: 1; } }

.pinterest-card {
    break-inside: avoid;
    margin-bottom: 1.25rem;
    position: relative;
    border-radius: 1.25rem;
    overflow: hidden;
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease;
    cursor: pointer;
}

.pinterest-card:hover {
    transform: translateY(-6px) scale(1.02);
    box-shadow: 0 18px 35px rgba(0, 0, 0, 0.25);
}

.pinterest-card img {
    width: 100%;
    height: auto;
    display: block;
    object-fit: cover;
    border-radius: 1.25rem;
}

.like-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background-color: var(--bg-surface);
    backdrop-filter: blur(8px);
    color: var(--text-main);
    font-size: 0.75rem;
    font-weight: 800;
    padding: 0.35rem 0.65rem;
    border-radius: 9999px;
    z-index: 5;
    border: 1px solid var(--border-subtle);
}

.pinterest-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, var(--bg-surface) 0%, rgba(0, 0, 0, 0) 65%);
    opacity: 0;
    transition: opacity 0.25s ease;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 1.25rem;
    color: var(--text-main);
}

.pinterest-card:hover .pinterest-overlay {
    opacity: 1;
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
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border-subtle);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
</style>

<div style="display: flex; width: 100vw; height: calc(100vh - 4.25rem); overflow: hidden; background: transparent;">

    <?php include_once __DIR__ . '/../sidebar.php'; ?>

    <div id="scroll-main-wrapper" style="flex-grow: 1; height: 100%; overflow-y: auto; padding: 2.5rem; box-sizing: border-box;">
        
        <div style="max-width: 1250px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.75rem;">

            <!-- HEADER BANNER -->
            <div class="header-banner">
                <div>
                    <h1 style="font-size: 1.875rem; font-weight: 800; color: var(--text-main); margin: 0;">
                        🗿 Escultura y Modelado 3D
                    </h1>
                    <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.3rem;">
                        Modelados digitales en 3D, esculturas físicas, piezas en cerámica y arte en relieve.
                    </p>
                </div>

                <!-- BOTÓN REGRESAR A LA GALERÍA -->
                <button id="btn-volver-grid" onclick="mostrarCuadricula()" style="display: none;" class="btn btn-neutral">
                    ⊞ Volver a la Galería
                </button>
            </div>

            <!-- 1. VISTA CUADRÍCULA ESTILO PINTEREST -->
            <div class="pinterest-grid" id="grid-obras-container">
                <?php foreach ($esculturas as $e): ?>
                    <div class="pinterest-card" onclick="mostrarFeedEnObra(<?= $e['id'] ?>)">
                        <span class="like-badge">❤️ <?= $e['likes'] ?></span>
<img src="<?= $e['imagen'] ?>" alt="<?= htmlspecialchars($e['titulo']) ?>" loading="lazy" width="600" height="400">
                        <div class="pinterest-overlay">
                            <h3 style="font-size: 1rem; font-weight: 800; margin: 0; color: var(--text-main);"><?= htmlspecialchars($e['titulo']) ?></h3>
                            <span style="font-size: 0.8rem; color: var(--brand-orange); font-weight: 700; margin-top: 0.25rem;"><?= htmlspecialchars($e['artista']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 2. VISTA FEED VERTICAL CONTINUO (ESTILO INSTAGRAM) -->
            <div class="feed-stream-container" id="feed-obras-container">
                <?php foreach ($esculturas as $e): ?>
                    <div class="feed-post-card" id="post-card-<?= $e['id'] ?>">
                        
                        <div class="feed-card-header">
                            <div style="display: flex; align-items: center; gap: 0.85rem;">
                                <img src="<?= $e['avatar'] ?>" alt="<?= htmlspecialchars($e['artista']) ?>" style="width: 2.75rem; height: 2.75rem; border-radius: 9999px; object-fit: cover;">
                                <div>
                                    <h3 style="color: var(--text-main); font-size: 0.95rem; font-weight: 800; margin: 0;">
                                        <?= htmlspecialchars($e['artista']) ?>
                                    </h3>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem;">
                                        🌐 Publicación de la Galería
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="padding: 1rem 1.5rem; color: var(--text-main); font-size: 0.95rem;">
                            <strong style="display: block; font-size: 1.05rem; margin-bottom: 0.35rem;"><?= htmlspecialchars($e['titulo']) ?></strong>
                            <?= htmlspecialchars($e['descripcion']) ?>
                        </div>

                        <div class="feed-image-box">
                            <img src="<?= $e['imagen'] ?>" alt="<?= htmlspecialchars($e['titulo']) ?>">
                        </div>

                        <div class="feed-card-footer">
                            <button style="background: var(--hover-item); border: 1px solid var(--border-subtle); color: var(--text-main); padding: 0.6rem 1rem; border-radius: 0.65rem; font-weight: 700; font-size: 0.85rem; cursor: pointer;">
                                ❤️ <?= $e['likes'] ?> Me gusta
                            </button>
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
    document.getElementById('grid-obras-container').style.display = 'block';
    document.getElementById('btn-volver-grid').style.display = 'none';
    
    const wrapper = document.getElementById('scroll-main-wrapper');
    if (wrapper) wrapper.scrollTop = 0;
}
</script>


