<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin(['artista']);
require_once __DIR__ . '/../header.php';

$project_base = '/poliart/';

/* Datos de prueba retirados; las obras se cargan desde la base de datos.
$obras_dibujo = [
    [
        'id' => 1,
        'titulo' => 'Bodegón Flor de Mayo',
        'artista' => 'Jeffrey Ramos (@lulo)',
        'avatar' => 'https://ui-avatars.com/api/?name=Jeffrey+Ramos&background=f97316&color=fff',
        'imagen' => 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?auto=format&fit=crop&w=600&q=80',
        'likes' => 142,
        'precio' => '150',
        'ubicacion' => 'Medellín, Colombia',
        'fecha' => 'Hace 1 día',
        'descripcion' => 'Estudio clásico de bodegón al óleo enfocado en las orquídeas nativas de la región y juego de sombras.'
    ],
    [
        'id' => 2,
        'titulo' => 'Evolución del Trazo',
        'artista' => 'Sofia R.',
        'avatar' => 'https://ui-avatars.com/api/?name=Sofia+R&background=ec4899&color=fff',
        'imagen' => 'https://images.unsplash.com/photo-1541701494587-cb58502866ab?auto=format&fit=crop&w=600&q=80',
        'likes' => 89,
        'precio' => '120',
        'ubicacion' => 'El Poblado, Medellín',
        'fecha' => 'Hace 3 días',
        'descripcion' => 'Exploración de técnicas mixtas combinando tinta china y capas de acuarela sobre papel de alto gramaje.'
    ],
    [
        'id' => 3,
        'titulo' => 'Arcoíris en Acrílico',
        'artista' => 'Carlos Mendoza',
        'avatar' => 'https://ui-avatars.com/api/?name=Carlos+Mendoza&background=0284c7&color=fff',
        'imagen' => 'https://images.unsplash.com/photo-1578328819058-b69f3a3b0f6b?auto=format&fit=crop&w=600&q=80',
        'likes' => 94,
        'precio' => '180',
        'ubicacion' => 'Laureles, Medellín',
        'fecha' => 'Hace 5 días',
        'descripcion' => 'Obra abstracta a gran formato que explora la degradación del espectro visible mediante espátula.'
    ],
    [
        'id' => 4,
        'titulo' => 'Pinceladas de Fuego',
        'artista' => 'Valentina Gómez',
        'avatar' => 'https://ui-avatars.com/api/?name=Valentina+Gomez&background=f43f5e&color=fff',
        'imagen' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=600&q=80',
        'likes' => 178,
        'precio' => '200',
        'ubicacion' => 'Centro, Medellín',
        'fecha' => 'Hace 1 semana',
        'descripcion' => 'Expresionismo vibrante con tonos cálidos e intensidad cromática en acrílico sobre lienzo.'
    ],
    [
        'id' => 5,
        'titulo' => 'Control & Acceso Digital',
        'artista' => 'Mateo Ríos',
        'avatar' => 'https://ui-avatars.com/api/?name=Mateo+Rios&background=10b981&color=fff',
        'imagen' => 'https://images.unsplash.com/photo-1543857778-c4a1a3e0b2eb?auto=format&fit=crop&w=600&q=80',
        'likes' => 210,
        'precio' => '90',
        'ubicacion' => 'Envigado, Antioquia',
        'fecha' => 'Hace 2 semanas',
        'descripcion' => 'Ilustración digital sobre tablet gráfica conceptualizando la ciberseguridad y la identidad virtual.'
    ],
    [
        'id' => 6,
        'titulo' => 'Retrato Felino al Óleo',
        'artista' => 'Jeffrey Ramos (@lulo)',
        'avatar' => 'https://ui-avatars.com/api/?name=Jeffrey+Ramos&background=f97316&color=fff',
        'imagen' => 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=600&q=80',
        'likes' => 67,
        'precio' => '130',
        'ubicacion' => 'Medellín, Colombia',
        'fecha' => 'Hace 3 semanas',
        'descripcion' => 'Detalle realista capturando el brillo en la mirada y la textura del pelaje felino.'
    ]
];

*/
require_once __DIR__ . '/../../includes/catalogo_obras.php';
try {
    $obras_dibujo = array_map(static function (array $obra): array {
        return [
            'id' => (int)$obra['id'],
            'id_artista' => (int)$obra['artista_id'],
            'id_usuario' => (int)$obra['id_usuario'],
            'titulo' => $obra['titulo'],
            'artista' => $obra['artista'],
            'avatar' => $obra['avatar_path'] ?? '',
            'imagen' => $obra['ruta_imagen'] ?? '',
            'likes' => $obra['total_likes'],
            'liked' => $obra['liked'],
            'precio' => number_format((float)$obra['precio'], 0, ',', '.'),
            'ubicacion' => $obra['zona'] ?? 'Medellín',
            'fecha' => date('d/m/Y', strtotime($obra['publicada_en'] ?? $obra['creado_en'])),
            'descripcion' => $obra['descripcion'] ?? '',
        ];
    }, obtenerObrasPorCategoria('ilustracion-pintura', getUserId()));
} catch (PDOException $e) {
    error_log('Error al cargar obras de dibujo y pintura: ' . $e->getMessage());
    $obras_dibujo = [];
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
    display: flex;
    align-items: center;
    gap: 0.3rem;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
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

.pinterest-title {
    font-size: 1rem;
    font-weight: 800;
    margin: 0;
    color: var(--text-main);
}

.pinterest-author {
    font-size: 0.8rem;
    color: var(--brand-orange);
    font-weight: 700;
    margin-top: 0.25rem;
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

    <div id="scroll-main-wrapper" style="flex-grow: 1; height: 100%; overflow-y: auto; padding: 2.5rem; box-sizing: border-box;">
        
        <div style="max-width: 1250px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.75rem;">

            <!-- HEADER BANNER -->
            <div class="header-banner">
                <div>
                    <h1 style="font-size: 1.875rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.6rem;">
                        🎨 Dibujo y Pintura
                    </h1>
                    <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.3rem;">
                        Ilustraciones digitales, pinturas al óleo, acuarelas y bocetos artísticos.
                    </p>
                </div>

                <!-- BOTÓN REGRESAR A LA GALERÍA -->
                <button id="btn-volver-grid" onclick="mostrarCuadricula()" style="display: none; background: var(--hover-item); border: 1px solid var(--border-subtle); color: var(--text-main); padding: 0.65rem 1.25rem; border-radius: 0.6rem; font-weight: 700; font-size: 0.875rem; cursor: pointer;">
                    ⊞ Volver a la Galería
                </button>
            </div>

            <!-- 1. VISTA CUADRÍCULA ESTILO PINTEREST -->
            <div class="pinterest-grid" id="grid-obras-container">
                <?php foreach ($obras_dibujo as $obra): ?>
                    <div class="pinterest-card" onclick="mostrarFeedEnObra(<?= $obra['id'] ?>)">
                        
                        <span class="like-badge">❤️ <?= $obra['likes'] ?></span>

<img src="<?= $obra['imagen'] ?>" alt="<?= htmlspecialchars($obra['titulo']) ?>" loading="lazy" width="600" height="400">

                        <div class="pinterest-overlay">
                            <h3 class="pinterest-title"><?= htmlspecialchars($obra['titulo']) ?></h3>
                            <span class="pinterest-author"><?= htmlspecialchars($obra['artista']) ?></span>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 2. VISTA FEED VERTICAL CONTINUO (ESTILO INSTAGRAM) -->
            <div class="feed-stream-container" id="feed-obras-container">
                <?php foreach ($obras_dibujo as $obra): ?>
                    <article class="feed-post-card" id="post-card-<?= $obra['id'] ?>">
                        
                        <!-- ENCABEZADO CON AUTOR Y BOTÓN DE COLABORACIÓN -->
                        <div style="padding: 1.25rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 0.85rem;">
                                <img src="<?= $obra['avatar'] ?>" alt="Avatar" style="width: 2.75rem; height: 2.75rem; border-radius: 9999px; box-shadow: 0 2px 8px rgba(249, 115, 22, 0.2); object-fit: cover;">
                                <div>
                                    <h3 style="color: var(--text-main); font-size: 1rem; font-weight: 700; margin: 0;">
                                        <?= htmlspecialchars($obra['artista']) ?> <span style="font-weight: 500; color: var(--text-muted); font-size: 0.85rem;">· <?= $obra['ubicacion'] ?></span>
                                    </h3>
                                    <span style="color: var(--text-muted); font-size: 0.78rem;"><?= $obra['fecha'] ?> · 🌐 Público</span>
                                </div>
                            </div>

                            <!-- BOTÓN DE COLABORACIÓN -->
                            <?php if ($obra['id_usuario'] !== getUserId()): ?>
                                <button type="button"
                                    onclick="abrirModalColaboracion(this.dataset.titulo, this.dataset.artista, this.dataset.idArtista)"
                                    data-titulo="<?= htmlspecialchars($obra['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-artista="<?= htmlspecialchars($obra['artista'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-id-artista="<?= (int)$obra['id_artista'] ?>"
                                    class="btn btn-outline btn-sm">
                                    🤝 Proponer colaboración
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- DESCRIPCIÓN Y TÍTULO DE LA OBRA -->
                        <div style="padding: 0 1.25rem 1rem 1.25rem;">
                            <h4 style="color: var(--text-main); font-size: 1.05rem; font-weight: 800; margin: 0 0 0.35rem 0;">
                                <?= htmlspecialchars($obra['titulo']) ?>
                            </h4>
                            <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.5; margin: 0;">
                                <?= htmlspecialchars($obra['descripcion']) ?> 🎨✨
                            </p>
                        </div>

                        <!-- IMAGEN DE LA OBRA -->
                        <div class="feed-image-box">
                            <img src="<?= $obra['imagen'] ?>" alt="<?= htmlspecialchars($obra['titulo']) ?>">
                        </div>

                        <!-- PIE DE TARJETA CON LOS 3 BOTONES -->
                        <div style="padding: 1rem 1.25rem; border-top: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                            <button class="btn-like" data-obra="<?= (int)$obra['id'] ?>" aria-pressed="<?= $obra['liked'] ? 'true' : 'false' ?>" style="background: var(--hover-item); border: 1px solid var(--brand-orange); color: var(--brand-orange); font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 0.5rem; transition: all 0.2s;">
                                <span class="heart-icon"><?= $obra['liked'] ? '❤️' : '🤍' ?></span> <span class="like-text"><?= $obra['likes'] ?> Me gusta</span>
                            </button>

                            <div style="display: flex; gap: 0.6rem;">
                                <?php if ($obra['id_usuario'] !== getUserId()): ?>
                                    <button class="btn-carrito" data-obra="<?= (int)$obra['id'] ?>" style="background-color: var(--bg-surface); color: var(--text-main); border: 1px solid var(--border-subtle); padding: 0.5rem 0.9rem; border-radius: 0.5rem; font-size: 0.825rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.3rem;">
                                        🛒 Añadir al carrito
                                    </button>
                                <?php endif; ?>
                                <button onclick="abrirModalReporteCliente(<?= (int)$obra['id'] ?>)" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 0.5rem 0.9rem; border-radius: 0.5rem; font-size: 0.825rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.35rem;">
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
<script src="..//poliart/public/assets/js/cliente.js"></script>

