<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin(['artista']);
require_once __DIR__ . '/../header.php';

$project_base = '/poliart/';

/* Datos de prueba retirados; las obras se cargan desde la base de datos.
$obras_escenicas = [
    [
        'id' => 1,
        'titulo' => 'Obra Teatral: Sombras y Luces del San Javier',
        'duracion' => '1:15:00',
        'miniatura' => 'https://images.unsplash.com/photo-1507676184212-d03ab07a01bf?auto=format&fit=crop&w=800&q=80',
        'video_url' => 'https://file-examples.com/storage/fe884a4413663a8a9134f55/2017/04/file_example_MP4_700_1MG.mp4',
        'director' => 'Compañía Teatral El Mago',
        'avatar' => 'https://ui-avatars.com/api/?name=Teatro+Mago&background=7c3aed&color=fff',
        'vistas' => '1.2 K reproducciones',
        'fecha' => 'Presentado hace 3 días',
        'ubicacion' => 'San Javier, Medellín',
        'precio' => '120',
        'likes' => 34,
        'descripcion' => 'Una profunda reflexión sobre las luces y sombras de la comarca, interpretada en vivo por la Compañía Teatral El Mago.'
    ],
    [
        'id' => 2,
        'titulo' => 'Performance de Danza Contemporánea en El Poblado',
        'duracion' => '24:40',
        'miniatura' => 'https://images.unsplash.com/photo-1508700115892-45ecd05ae2ad?auto=format&fit=crop&w=800&q=80',
        'video_url' => 'https://file-examples.com/storage/fe884a4413663a8a9134f55/2017/04/file_example_MP4_700_1MG.mp4',
        'director' => 'Valentina Gómez',
        'avatar' => 'https://ui-avatars.com/api/?name=Valentina+Gomez&background=f43f5e&color=fff',
        'vistas' => '4.5 K reproducciones',
        'fecha' => 'Hace 1 semana',
        'ubicacion' => 'El Poblado, Medellín',
        'precio' => '95',
        'likes' => 88,
        'descripcion' => 'Exploración de movimiento corporal, espacio e interacción con la arquitectura moderna urbana.'
    ],
    [
        'id' => 3,
        'titulo' => 'Monólogo Dramático: Memorias de un Artista',
        'duracion' => '32:15',
        'miniatura' => 'https://images.unsplash.com/photo-1469488865564-c2de10f69f96?auto=format&fit=crop&w=800&q=80',
        'video_url' => 'https://file-examples.com/storage/fe884a4413663a8a9134f55/2017/04/file_example_MP4_700_1MG.mp4',
        'director' => 'Carlos Mendoza',
        'avatar' => 'https://ui-avatars.com/api/?name=Carlos+Mendoza&background=0284c7&color=fff',
        'vistas' => '890 reproducciones',
        'fecha' => 'Hace 2 semanas',
        'ubicacion' => 'Laureles, Medellín',
        'precio' => '110',
        'likes' => 42,
        'descripcion' => 'Un viaje introspectivo a través de los desafíos y triunfos del proceso creativo contemporáneo.'
    ],
    [
        'id' => 4,
        'titulo' => 'Muestra de Circo Urbano & Malabares con Fuego',
        'duracion' => '18:50',
        'miniatura' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=800&q=80',
        'video_url' => 'https://file-examples.com/storage/fe884a4413663a8a9134f55/2017/04/file_example_MP4_700_1MG.mp4',
        'director' => 'Colectivo Fuego & Arte',
        'avatar' => 'https://ui-avatars.com/api/?name=Fuego+Arte&background=ea580c&color=fff',
        'vistas' => '15.3 K reproducciones',
        'fecha' => 'Hace 1 mes',
        'ubicacion' => 'Centro, Medellín',
        'precio' => '75',
        'likes' => 156,
        'descripcion' => 'Espectáculo callejero lleno de energía, acrobacias nocturnas y dinámicas de fuego controlado.'
    ]
];

*/
require_once __DIR__ . '/../../includes/catalogo_obras.php';
try {
    $obras_escenicas = array_map(static function (array $obra): array {
        $fecha = strtotime($obra['publicada_en'] ?? $obra['creado_en']);
        return [
            'id' => (int)$obra['id'],
            'id_artista' => (int)$obra['artista_id'],
            'id_usuario' => (int)$obra['id_usuario'],
            'titulo' => $obra['titulo'],
            'duracion' => '',
            'miniatura' => $obra['ruta_imagen'] ?? '',
            'video_url' => $obra['ruta_imagen'] ?? '',
            'director' => $obra['artista'],
            'avatar' => $obra['avatar_path'] ?? '',
            'vistas' => number_format($obra['total_vistas']) . ' reproducciones',
            'fecha' => date('d/m/Y', $fecha),
            'ubicacion' => $obra['zona'] ?? 'Medellín',
            'precio' => number_format((float)$obra['precio'], 0, ',', '.'),
            'likes' => $obra['total_likes'],
            'liked' => $obra['liked'],
            'descripcion' => $obra['descripcion'] ?? '',
        ];
    }, obtenerObrasPorCategoria('artes-escenicas', getUserId()));
} catch (PDOException $e) {
    error_log('Error al cargar obras escénicas: ' . $e->getMessage());
    $obras_escenicas = [];
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

/* VISTA FEED CONTINUO ESTILO INSTAGRAM (INDEX) */
.feed-stream-container {
    display: none;
    flex-direction: column;
    gap: 1.75rem;
    max-width: 680px;
    margin: 0 auto;
    width: 100%;
}

.feed-stream-container.active {
    display: flex;
}

.feed-post-card {
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    backdrop-filter: blur(8px);
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

.feed-media-box {
    width: 100%;
    max-height: 440px;
    background-color: var(--bg-body);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.feed-media-box video {
    width: 100%;
    max-height: 440px;
    object-fit: contain;
    outline: none;
}

/* ESTILOS DE MODALES EMERGENTES */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(6px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
}

.modal-overlay.active {
    display: flex;
}

.modal-content-box {
    background: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1rem;
    padding: 1rem;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.4);
}

.reason-option {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem;
    border: 1px solid var(--border-subtle);
    border-radius: 0.6rem;
    background: var(--hover-item);
    cursor: pointer;
    transition: border-color 0.2s ease;
}

.reason-option:hover {
    border-color: var(--brand-orange);
}

.form-control-modal {
    width: 100%;
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 0.5rem;
    padding: 0.65rem 0.85rem;
    color: var(--text-main);
    font-size: 0.85rem;
    box-sizing: border-box;
    outline: none;
    transition: border-color 0.2s;
}

.form-control-modal:focus {
    border-color: var(--brand-orange);
}
</style>

<div style="display: flex; width: 100vw; height: calc(100vh - 4.25rem); overflow: hidden; background: transparent;">

    <?php include_once __DIR__ . '/../sidebar.php'; ?>

    <div id="scroll-main-wrapper" style="flex-grow: 1; height: 100%; overflow-y: auto; padding: 2.5rem 2rem; box-sizing: border-box;">
        
        <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.75rem;">

            <!-- HEADER BANNER -->
            <div class="header-banner">
                <div>
                    <h1 style="font-size: 1.875rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.6rem;">
                        🎭 Artes Escénicas y Teatro
                    </h1>
                    <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.3rem;">
                        Explora guiones, presentaciones en vivo, obras teatrales y performances de la comunidad.
                    </p>
                </div>

                <!-- BOTÓN REGRESAR A LA GALERÍA -->
                <button id="btn-volver-grid" onclick="mostrarCuadricula()" style="display: none;" class="btn btn-neutral">
                    ⊞ Volver a la Galería
                </button>
            </div>

            <!-- 1. VISTA CUADRÍCULA DE VIDEOS -->
            <div class="video-grid" id="grid-obras-container">
                <?php foreach ($obras_escenicas as $e): ?>
                    <div class="video-card" onclick="mostrarFeedEnObra(<?= $e['id'] ?>)">
                        
                        <div class="thumbnail-wrapper">
                            <?php renderizarVistaPreviaObra($e['miniatura'], $e['titulo'], '220px'); ?>
                            <?php if ($e['duracion'] !== ''): ?>
                                <span class="duration-badge"><?= htmlspecialchars($e['duracion'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="video-info-wrapper">
                            <img src="<?= $e['avatar'] ?>" alt="<?= htmlspecialchars($e['director']) ?>" class="channel-avatar">
                            
                            <div class="video-details">
                                <h3 class="video-title"><?= htmlspecialchars($e['titulo']) ?></h3>
                                <p class="video-artist"><?= htmlspecialchars($e['director']) ?></p>
                                <p class="video-meta"><?= $e['vistas'] ?> &bull; <?= $e['fecha'] ?></p>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 2. VISTA FEED VERTICAL CONTINUO (IGUAL A INDEX) -->
            <div class="feed-stream-container" id="feed-obras-container">
                <?php foreach ($obras_escenicas as $e): ?>
                    <article class="feed-post-card" id="post-card-<?= $e['id'] ?>">
                        
                        <!-- ENCABEZADO CON AUTOR Y BOTÓN DE COLABORACIÓN -->
                        <div style="padding: 1.25rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 0.85rem;">
                                <img src="<?= $e['avatar'] ?>" alt="Avatar" style="width: 2.75rem; height: 2.75rem; border-radius: 9999px; box-shadow: 0 2px 8px rgba(249, 115, 22, 0.2); object-fit: cover;">
                                <div>
                                    <h3 style="color: var(--text-main); font-size: 1rem; font-weight: 700; margin: 0;">
                                        <?= htmlspecialchars($e['director']) ?> <span style="font-weight: 500; color: var(--text-muted); font-size: 0.85rem;">· <?= $e['ubicacion'] ?></span>
                                    </h3>
                                    <span style="color: var(--text-muted); font-size: 0.78rem;"><?= $e['fecha'] ?> · 🌐 Público</span>
                                </div>
                            </div>

                            <!-- BOTÓN DE COLABORACIÓN -->
                            <?php if ($e['id_usuario'] !== getUserId()): ?>
                                <button type="button"
                                    onclick="abrirModalColaboracion(this.dataset.titulo, this.dataset.artista, this.dataset.idArtista)"
                                    data-titulo="<?= htmlspecialchars($e['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-artista="<?= htmlspecialchars($e['director'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-id-artista="<?= (int)$e['id_artista'] ?>"
                                    class="btn btn-outline btn-sm">
                                    🤝 Proponer colaboración
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- DESCRIPCIÓN Y TÍTULO DE LA OBRA -->
                        <div style="padding: 0 1.25rem 1rem 1.25rem;">
                            <h4 style="color: var(--text-main); font-size: 1.05rem; font-weight: 800; margin: 0 0 0.35rem 0;">
                                <?= htmlspecialchars($e['titulo']) ?>
                            </h4>
                            <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.5; margin: 0;">
                                <?= htmlspecialchars($e['descripcion']) ?> 🎭✨
                            </p>
                        </div>

                        <div class="feed-media-box">
                            <?php renderizarVistaPreviaObra($e['video_url'], $e['titulo'], 'min(70vh, 760px)', 'media-player-' . $e['id']); ?>
                        </div>

                        <!-- PIE DE TARJETA CON LOS 3 BOTONES -->
                        <div style="padding: 1rem 1.25rem; border-top: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                            <button class="btn-like" data-obra="<?= (int)$e['id'] ?>" aria-pressed="<?= $e['liked'] ? 'true' : 'false' ?>" style="background: var(--hover-item); border: 1px solid var(--brand-orange); color: var(--brand-orange); font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 0.5rem; transition: all 0.2s;">
                                <span class="heart-icon"><?= $e['liked'] ? '❤️' : '🤍' ?></span> <span class="like-text"><?= $e['likes'] ?> Me gusta</span>
                            </button>

                            <div style="display: flex; gap: 0.6rem;">
                                <?php if ($e['id_usuario'] !== getUserId()): ?>
                                    <button class="btn-carrito" data-obra="<?= (int)$e['id'] ?>" style="background-color: var(--bg-surface); color: var(--text-main); border: 1px solid var(--border-subtle); padding: 0.5rem 0.9rem; border-radius: 0.5rem; font-size: 0.825rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.3rem;">
                                        🛒 Añadir al carrito
                                    </button>
                                <?php endif; ?>
                                <button onclick="abrirModalReporteCliente(<?= (int)$e['id'] ?>)" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 0.5rem 0.9rem; border-radius: 0.5rem; font-size: 0.825rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.35rem;">
                                    ⚠️ Reportar Obra
                                </button>
                            </div>
                        </div>

                    </article>
                <?php endforeach; ?>
            </div>

        </div>

    </div>
</div>

<!-- MODAL EMERGENTE PARA PROPONER COLABORACIÓN -->
<div id="modalColaboracion" class="modal-overlay">
    <div class="modal-content-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
            <h3 style="margin: 0; font-size: 1.1rem; color: var(--text-main); font-weight: 800;">
                🤝 Proponer Colaboración
            </h3>
            <button type="button" onclick="cerrarModalColaboracion()" style="background: none; border: none; color: var(--text-muted); font-size: 1.1rem; cursor: pointer;">
                ✖
            </button>
        </div>

        <p style="font-size: 0.825rem; color: var(--text-muted); margin-bottom: 1rem; line-height: 1.4;">
            Envía una propuesta directa a <strong id="colabArtistaNombre" style="color: var(--brand-orange);">Artista</strong> para trabajar juntos en esta obra:
        </p>

        <form onsubmit="enviarPropuestaColaboracion(event)">
            <input type="hidden" id="colabObraTitulo" value="">
            <input type="hidden" id="colabArtistaId" value="">
            <p id="estadoColaboracion" role="status" style="display:none;margin:0;color:var(--text-muted);font-size:.85rem;"></p>

            <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-bottom: 1.25rem;">
                <div>
                        <label for="motivoColaboracion" style="display: block; font-size: 0.825rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.4rem;">
                        Motivo de la Colaboración
                    </label>
                    <select id="motivoColaboracion" class="form-control-modal" required>
                        <option value="" disabled selected>Selecciona una opción...</option>
                        <option value="muralismo">🎨 Muralismo / Arte Urbano Conjunto</option>
                        <option value="teatro">🎭 Dirección / Interpretación Teatral</option>
                        <option value="ilustracion">✏️ Ilustración / Boceto Digital</option>
                        <option value="exposicion">🖼️ Exposición / Galería Conjunta</option>
                        <option value="audiovisual">🎵 Proyecto Audiovisual / Música</option>
                        <option value="otro">💡 Otro tipo de proyecto creativo</option>
                    </select>
                </div>

                <div>
                        <label for="mensajeColaboracion" style="display: block; font-size: 0.825rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.4rem;">
                        Mensaje para el Artista
                    </label>
                    <textarea id="mensajeColaboracion" class="form-control-modal" rows="4" placeholder="Describe tu idea, tu rol propuesto o la visión que tienes para trabajar juntos..." required></textarea>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.6rem;">
                <button type="button" onclick="cerrarModalColaboracion()" class="btn btn-neutral btn-sm">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary btn-sm">
                    📨 Enviar Propuesta
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EMERGENTE PARA REPORTAR OBRA -->
<div id="modalReporteCliente" class="modal-overlay">
    <style>#modalReporteCliente .reason-option { display:flex !important; margin:.75rem 0; padding:.9rem 1rem; border:1px solid var(--brand-orange); border-radius:.85rem; background:transparent; } #modalReporteCliente .modal-content-box > div:first-child button, #modalReporteCliente .modal-content-box > div:first-child + p { display:block; } #modalReporteCliente .modal-content-box > div:first-child { margin-bottom:.85rem !important; }</style>
    <div class="modal-content-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
            <h3 style="margin: 0; font-size: 1.1rem; color: var(--text-main); font-weight: 800;">
                ⚠️ Reportar esta Obra
            </h3>
            <button type="button" onclick="cerrarModalReporteCliente()" style="background: none; border: none; color: var(--text-muted); font-size: 1.1rem; cursor: pointer;">
                ✖
            </button>
        </div>

        <p style="font-size: 0.825rem; color: var(--text-muted); margin-bottom: 1rem; line-height: 1.4;">
            Selecciona la razón principal por la que deseas notificar a moderación sobre esta publicación:
        </p>

        <form onsubmit="enviarReporteCliente(event)">
            <input type="hidden" id="reporteClienteObraId" value="">

            <label for="motivoReporteLegacy">Motivo</label>
            <select id="motivoReporteLegacy" name="motivo-legacy" required onchange="document.querySelector('input[name=motivo_cliente][value=' + this.value + ']').click()" style="display:none;width:100%;padding:.65rem;margin:.35rem 0 .75rem;">
                <option value="plagio">Plagio o copia no autorizada</option>
                <option value="inapropiado">Contenido inapropiado</option>
                <option value="spam">Spam o información engañosa</option>
                <option value="otro">Otro motivo</option>
            </select>
            <div style="display:flex; flex-direction: column; gap: 0.65rem; margin-bottom: 1.25rem;">
                <label class="reason-option">
                    <input type="radio" name="motivo_cliente" value="plagio" required onchange="toggleCajaOtroMotivo(false)" style="margin-top: 0.2rem;">
                    <div>
                        <strong style="display: block; font-size: 0.85rem; color: var(--text-main);">Plagio / Copia no autorizada</strong>
                        <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">Derechos de autor pertenecientes a otro creador.</span>
                    </div>
                </label>

                <label class="reason-option">
                    <input type="radio" name="motivo_cliente" value="inapropiado" onchange="toggleCajaOtroMotivo(false)">
                    <div>
                        <strong style="display: block; font-size: 0.85rem; color: var(--text-main);">Contenido Inapropiado u Ofensivo</strong>
                        <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">Imágenes explícitas, violencia o incitación al odio.</span>
                    </div>
                </label>

                <label class="reason-option">
                    <input type="radio" name="motivo_cliente" value="spam" onchange="toggleCajaOtroMotivo(false)">
                    <div>
                        <strong style="display: block; font-size: 0.85rem; color: var(--text-main);">Spam / Información Engañosa</strong>
                        <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">Precio engañoso, fraude o publicación sospechosa.</span>
                    </div>
                </label>

                <label class="reason-option">
                    <input type="radio" name="motivo_cliente" value="otro" id="radioOtroMotivo" onchange="toggleCajaOtroMotivo(true)">
                    <div style="width: 100%;">
                        <strong style="display: block; font-size: 0.85rem; color: var(--text-main);">Otro motivo</strong>
                        <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">Otra infracción a las políticas de Poli-Art.</span>
                        
                        <div id="contenedorOtroMotivo" style="display: none; margin-top: 0.5rem;">
                            <textarea id="descripcionOtroMotivo" class="form-control-modal" rows="3" placeholder="Describe aquí la razón detallada de tu reporte..."></textarea>
                        </div>
                    </div>
                </label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.6rem;">
                <button type="button" onclick="cerrarModalReporteCliente()" class="btn btn-neutral btn-sm">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-danger btn-sm">
                    Enviar Reporte
                </button>
            </div>
        </form>
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

// FUNCIONES PARA MODALES DE COLABORACIÓN Y REPORTE
function abrirModalColaboracion(nombreObra, artista, idArtista) {
    document.getElementById('colabObraTitulo').value = nombreObra;
    document.getElementById('colabArtistaId').value = idArtista;
    document.getElementById('colabArtistaNombre').textContent = artista;
    document.getElementById('estadoColaboracion').style.display = 'none';
    document.getElementById('estadoColaboracion').textContent = '';
    document.getElementById('modalColaboracion').classList.add('active');
}

function cerrarModalColaboracion() {
    document.getElementById('modalColaboracion').classList.remove('active');
}

async function enviarPropuestaColaboracion(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const submit = form.querySelector('button[type="submit"]');
    const estado = document.getElementById('estadoColaboracion');
    const idArtista = document.getElementById('colabArtistaId').value;
    const titulo = document.getElementById('colabObraTitulo').value.trim();
    const motivo = document.getElementById('motivoColaboracion').value;
    const mensaje = document.getElementById('mensajeColaboracion').value.trim();
    if (!idArtista || !titulo || !motivo || !mensaje) {
        estado.textContent = 'Completa el motivo y el mensaje para enviar la propuesta.';
        estado.style.display = 'block';
        return;
    }
    const data = new FormData();
    data.set('accion', 'crear');
    data.set('id_artista', idArtista);
    data.set('titulo', titulo.slice(0, 160));
    data.set('descripcion', 'Motivo: ' + motivo + '\n\n' + mensaje);
    submit.disabled = true;
    try {
        const response = await fetch('../../includes/actions/gestion_colaboracion.php', { method: 'POST', body: data });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.error || 'No se pudo enviar la invitación.');
        cerrarModalColaboracion();
        form.reset();
        window.alert('La invitación fue enviada. El artista podrá verla en Colaboraciones y Notificaciones.');
    } catch (error) {
        estado.textContent = error.message || 'No se pudo enviar la invitación.';
        estado.style.display = 'block';
    } finally {
        submit.disabled = false;
    }
}

function abrirModalReporteCliente(obraId) {
    document.getElementById('reporteClienteObraId').value = obraId;
    document.getElementById('modalReporteCliente').classList.add('active');
}

function cerrarModalReporteCliente() {
    document.getElementById('modalReporteCliente').classList.remove('active');
}

function toggleCajaOtroMotivo(mostrar) {
    const contenedor = document.getElementById('contenedorOtroMotivo');
    if (contenedor) {
        contenedor.style.display = mostrar ? 'block' : 'none';
    }
}

async function enviarReporteCliente(event) {
    event.preventDefault();
    const motivo = document.querySelector('input[name="motivo_cliente"]:checked');
    const detalle = document.getElementById('descripcionOtroMotivo')?.value.trim() ?? '';
    if (!motivo || (motivo.value === 'otro' && detalle === '')) {
        window.alert('Selecciona un motivo y describe el reporte si elegiste "Otro".');
        return;
    }
    const data = new FormData();
    data.set('id_obra', document.getElementById('reporteClienteObraId').value);
    data.set('motivo', motivo.value);
    data.set('detalle', detalle);
    fetch('/poliart/app/includes/actions/reportar.php', { method: 'POST', body: data })
        .then(async response => {
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.error || 'No se pudo enviar el reporte.');
            window.alert(result.msg || 'Reporte enviado. Gracias por ayudar a moderar la comunidad.');
            cerrarModalReporteCliente();
        })
        .catch(error => window.alert(error.message || 'No se pudo enviar el reporte.'));
    return;
    alert('⚠️ Tu reporte ha sido recibido por el equipo de moderación. ¡Gracias por ayudar a mantener segura la comunidad de Poli-Art!');
    cerrarModalReporteCliente();
}
</script>

<script src="<?= $project_base ?>public/assets/js/cliente.js"></script>


