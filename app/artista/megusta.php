<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);
require_once __DIR__ . '/../artista/header.php';
include_once __DIR__ . '/../artista/sidebar.php';

$project_base = '/poliart/';

// Obras favoritas con imágenes reales y conteo de likes (según la segunda imagen)
$favoritos = [
    [
        'id' => 1,
        'titulo' => 'Naturaleza Muerta Clásica',
        'artista' => 'Rachel Ruysch',
        'imagen' => 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=800&q=80',
        'likes' => 142,
        'vertical' => true,
        'descripcion' => 'Bodegón clásico al óleo con flores exóticas y mariposas sobre fondo oscuro.'
    ],
    [
        'id' => 2,
        'titulo' => 'Explosión de Pigmentos',
        'artista' => 'Sofia R.',
        'imagen' => 'https://images.unsplash.com/photo-1541701494587-cb58502866ab?auto=format&fit=crop&w=800&q=80',
        'likes' => 89,
        'vertical' => false,
        'descripcion' => 'Mezcla de tintas digitales fluidas en azul neón y naranja cálido.'
    ],
    [
        'id' => 3,
        'titulo' => 'Trazo Primario',
        'artista' => 'Jeffrey Ramos',
        'imagen' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=800&q=80',
        'likes' => 178,
        'vertical' => false,
        'descripcion' => 'Textura acrílica con pinceles saturados en rojo y amarillo sobre óleo.'
    ],
    [
        'id' => 4,
        'titulo' => 'Error 404 - Arte Conceptual',
        'artista' => 'Carlos M.',
        'imagen' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=800&q=80',
        'likes' => 94,
        'vertical' => false,
        'descripcion' => 'Instalación de arte contemporáneo con cerradura inteligente y número 404.'
    ],
    [
        'id' => 5,
        'titulo' => 'Arcoíris Texturizado',
        'artista' => 'Lucía V.',
        'imagen' => 'https://images.unsplash.com/photo-1500462818027-6197b6907d0c?auto=format&fit=crop&w=800&q=80',
        'likes' => 210,
        'vertical' => false,
        'descripcion' => 'Lienzo vertical con barras de color degradadas en acrílico pesado.'
    ],
    [
        'id' => 6,
        'titulo' => 'Flores Barrocas',
        'artista' => 'Rachel Ruysch',
        'imagen' => 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=800&q=80',
        'likes' => 67,
        'vertical' => true,
        'descripcion' => 'Segunda pieza de la serie barroca de bodegones al óleo.'
    ]
];

require_once __DIR__ . '/../includes/catalogo_obras.php';
try {
    $favoritos = obtenerObrasFavoritas(getUserId());
} catch (PDOException $e) {
    error_log('Error al cargar los me gusta del artista: ' . $e->getMessage());
    $favoritos = [];
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

/* MASONRY / GRID DE FOTOS EXACTO DE LA IMAGEN 2 */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    grid-auto-rows: 210px;
    gap: 1.25rem;
    width: 100%;
}

@media (max-width: 1200px) {
    .gallery-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.gallery-card {
    position: relative;
    border-radius: 1.25rem;
    overflow: hidden;
    cursor: pointer;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.18);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease;
    border: 1px solid var(--border-subtle);
    background-color: var(--bg-surface);
}

.gallery-card.row-span-2 {
    grid-row: span 2;
}

.gallery-card:hover {
    transform: translateY(-4px) scale(1.01);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.28);
}

.gallery-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.4s ease;
}

.gallery-card:hover img {
    transform: scale(1.05);
}

/* BADGE FLOTANTE DE LIKES EN LA ESQUINA SUPERIOR DERECHA */
.likes-badge {
    position: absolute;
    top: 0.85rem;
    right: 0.85rem;
    background: rgba(22, 18, 16, 0.75);
    backdrop-filter: blur(8px);
    color: #ffffff;
    font-size: 0.825rem;
    font-weight: 800;
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    border: 1px solid rgba(255, 255, 255, 0.2);
    z-index: 10;
}

/* OVERLAY CON INFORMACIÓN AL PASAR EL CURSOR */
.gallery-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.2) 60%, rgba(0,0,0,0) 100%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 1.25rem;
    color: #ffffff;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.gallery-card:hover .gallery-overlay {
    opacity: 1;
}

@keyframes modalFadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>

<div style="display: flex; width: calc(100vw - 260px); height: calc(100vh - 4.25rem); overflow: hidden; background: transparent;">

    <div style="flex-grow: 1; height: 100%; overflow-y: auto; padding: 2rem 2.5rem; box-sizing: border-box;">
        
        <div style="max-width: 1250px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">

            <!-- ENCABEZADO -->
            <div style="background-color: var(--bg-surface); padding: 1.25rem 1.75rem; border-radius: 1rem; border: 1px solid var(--border-subtle); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: space-between; transition: background-color 0.3s ease, border-color 0.3s ease;">
                <div>
                    <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        ❤️ Obras que te gustan
                    </h1>
                    <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
                        Tus creaciones e inspiraciones guardadas en favoritos.
                    </p>
                </div>
                <span style="background: var(--hover-item); color: var(--brand-orange); border: 1px solid var(--border-subtle); font-size: 0.85rem; font-weight: 800; padding: 0.4rem 0.9rem; border-radius: 9999px;">
                    <?= count($favoritos) ?> Guardadas
                </span>
            </div>

            <!-- GALERÍA DE FOTOS ESTILO MASONRY -->
            <div class="gallery-grid">
                <?php foreach ($favoritos as $fav): ?>
                    <div class="gallery-card <?= $fav['vertical'] ? 'row-span-2' : '' ?>" onclick="abrirModalObra('<?= htmlspecialchars($fav['titulo'], ENT_QUOTES) ?>', '<?= htmlspecialchars($fav['artista'], ENT_QUOTES) ?>', '<?= $fav['imagen'] ?>', '<?= htmlspecialchars($fav['descripcion'], ENT_QUOTES) ?>', '<?= $fav['likes'] ?>')">
                        
                        <!-- BADGE CON CORAZÓN Y CONTEO -->
                        <div class="likes-badge">
                            <span style="color: #ff4757;">❤️</span> <?= $fav['likes'] ?>
                        </div>

                        <!-- IMAGEN DE LA OBRA -->
                        <img src="<?= $fav['imagen'] ?>" alt="<?= htmlspecialchars($fav['titulo']) ?>">

                        <!-- OVERLAY INFO AL HOVER -->
                        <div class="gallery-overlay">
                            <h3 style="font-size: 1.05rem; font-weight: 800; margin: 0 0 0.2rem 0; color: #ffffff;">
                                <?= htmlspecialchars($fav['titulo']) ?>
                            </h3>
                            <span style="font-size: 0.8rem; color: #fca5a5; font-weight: 600;">
                                Por <?= htmlspecialchars($fav['artista']) ?>
                            </span>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

        </div>

    </div>
</div>

<!-- MODAL PARA VER OBRA COMPLETA CON FOTO REAL -->
<div id="modal-ver-obra" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center;">
    
    <div style="background: var(--bg-surface); border: 1px solid var(--border-subtle); width: 90%; max-width: 520px; border-radius: 1.25rem; padding: 1.5rem; box-shadow: 0 25px 50px rgba(0,0,0,0.4); display: flex; flex-direction: column; gap: 1rem; animation: modalFadeIn 0.25s ease;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.75rem;">
            <h2 id="modal-obra-titulo" style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 0;">
                Título de la Obra
            </h2>
            <button onclick="cerrarModalObra()" aria-label="Cerrar" style="background: transparent; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">×</button>
        </div>

        <div style="width: 100%; max-height: 320px; border-radius: 0.85rem; overflow: hidden; background-color: var(--bg-body); display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-subtle);">
            <img id="modal-obra-img" src="" alt="Obra vista" style="width: 100%; height: 100%; object-fit: cover;">
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.4rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span id="modal-obra-artista" style="font-size: 0.875rem; color: var(--brand-orange); font-weight: 800;">Artista</span>
                <span id="modal-obra-likes" style="font-size: 0.85rem; color: var(--text-main); font-weight: 700; background: var(--hover-item); padding: 0.25rem 0.65rem; border-radius: 9999px; border: 1px solid var(--border-subtle);">❤️ 0</span>
            </div>
            <p id="modal-obra-desc" style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5; margin: 0.4rem 0 0 0;">
                Descripción detallada de la obra...
            </p>
        </div>

    </div>

</div>

<script>
function abrirModalObra(titulo, artista, imagen, descripcion, likes) {
    document.getElementById('modal-obra-titulo').textContent = titulo;
    document.getElementById('modal-obra-img').src = imagen;
    document.getElementById('modal-obra-artista').textContent = 'Artista: ' + artista;
    document.getElementById('modal-obra-likes').textContent = '❤️ ' + likes;
    document.getElementById('modal-obra-desc').textContent = descripcion;
    
    document.getElementById('modal-ver-obra').style.display = 'flex';
}

function cerrarModalObra() {
    document.getElementById('modal-ver-obra').style.display = 'none';
}
</script>

