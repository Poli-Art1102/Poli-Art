<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);
require_once __DIR__ . '/header.php';
include_once __DIR__ . '/sidebar.php';

$project_base = '/poliart/';
$nombre_artista = $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? '';
require_once __DIR__ . '/../includes/catalogo_obras.php';
$obrasFeed = [];
$errorFeed = false;
try {
    $obrasFeed = obtenerObrasPorCategoria(null, getUserId(), 30);
} catch (PDOException $e) {
    error_log('Error al cargar el feed del artista: ' . $e->getMessage());
    $errorFeed = true;
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

/* El feed tiene su propio scroll; evita una segunda barra en el main. */
body > main {
    overflow: hidden !important;
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
    max-height: 520px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #000;
}

.feed-post-header {
    padding: 1rem 1.25rem .5rem;
}

.feed-post-description {
    padding: 0 1.25rem .75rem;
}

.feed-follow-artist {
    margin-left: .45rem;
    padding: .2rem .5rem;
    border: 1px solid var(--brand-orange);
    border-radius: .45rem;
    background: transparent;
    color: var(--brand-orange);
    font-size: .7rem;
    font-weight: 700;
    cursor: pointer;
    vertical-align: middle;
}

.feed-follow-artist[data-following="true"] {
    background: var(--hover-item);
}

@media (max-width: 640px) {
    .feed-post-card [data-feed-actions] {
        align-items: stretch !important;
        flex-direction: column !important;
    }

    .feed-post-card [data-feed-actions] > div {
        flex-wrap: wrap;
    }
}
</style>

<div style="display: flex; width: 100vw; height: calc(100vh - 4.25rem); overflow: hidden; background: transparent;">

    <div id="feed-artista-scroll" style="flex-grow: 1; height: 100%; overflow-y: auto; padding: 2.5rem 2rem; box-sizing: border-box;">
        
        <div style="max-width: 680px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.25rem;">
            <?php require_once __DIR__ . '/../includes/anuncios_globales.php'; renderizarAnunciosGlobales('artistas'); ?>
            <?php if ($errorFeed): ?>
                <p role="alert" style="color:var(--text-main);">No fue posible cargar las publicaciones.</p>
            <?php elseif (!$obrasFeed): ?>
                <p style="padding:2rem;text-align:center;color:var(--text-muted);background:var(--bg-surface);border:1px solid var(--border-subtle);border-radius:1rem;">
                    Aún no hay obras publicadas. Cuando los artistas publiquen, aparecerán aquí.
                </p>
            <?php else: ?>
                <?php foreach ($obrasFeed as $obra): ?>
                    <?php
                    $esOtroArtista = (int)$obra['id_usuario'] !== getUserId();
                    $nombreArtista = (string)$obra['artista'];
                    $tituloObra = (string)$obra['titulo'];
                    $fechaPublicacion = $obra['publicada_en'] ?: $obra['creado_en'];
                    ?>
<article class="feed-post-card" data-estado-venta="<?= escaparCatalogo((string)$obra['estado_venta']) ?>">
                        <div class="feed-post-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;">
                            <div style="display:flex;align-items:center;gap:.85rem;min-width:0;">
                                <a href="/poliart/app/cliente/perfil_artista.php?id=<?= (int)$obra['artista_id'] ?>" aria-label="Ver perfil de <?= escaparCatalogo($nombreArtista) ?>" style="display:block;text-decoration:none;color:inherit;line-height:0;" onclick="event.stopPropagation()">
                                <?php if (!empty($obra['avatar_path'])): ?>
                                <img src="<?= escaparCatalogo($obra['avatar_path']) ?>" alt="" loading="lazy" width="44" height="44" style="width:2.75rem;height:2.75rem;border-radius:9999px;box-shadow:0 2px 8px rgba(249,115,22,.2);object-fit:cover;">
                                <?php else: ?>
                                    <span aria-hidden="true" style="width:2.75rem;height:2.75rem;flex:0 0 2.75rem;border-radius:9999px;background:linear-gradient(135deg,var(--brand-orange),#ea580c);color:#fff;font-weight:800;display:grid;place-items:center;">🎨</span>
                                <?php endif; ?>
                                </a>
                                <div style="min-width:0;">
                                    <h2 style="color:var(--text-main);font-size:1rem;font-weight:700;margin:0;">
                                        <?= escaparCatalogo($nombreArtista) ?>
                                        <?php if ($esOtroArtista): ?>
                                            <button type="button"
                                                class="feed-follow-artist btn-seguir-artista-feed"
                                                data-artista-id="<?= (int)$obra['artista_id'] ?>"
                                                data-following="<?= $obra['following'] ? 'true' : 'false' ?>"
                                                aria-pressed="<?= $obra['following'] ? 'true' : 'false' ?>">
                                                <?= $obra['following'] ? '✓ Siguiendo' : '➕ Seguir artista' ?>
                                            </button>
                                        <?php endif; ?>
                                        <span style="font-weight:500;color:var(--text-muted);font-size:.85rem;">· <?= escaparCatalogo($obra['zona'] ?? 'Sin zona') ?></span>
                                    </h2>
                                    <span style="color:var(--text-muted);font-size:.78rem;">
                                        <?= escaparCatalogo(date('d/m/Y', strtotime((string)$fechaPublicacion))) ?> · <?= escaparCatalogo($obra['categoria']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ($esOtroArtista): ?>
                                <button type="button"
                                    onclick="abrirModalColaboracion(this.dataset.titulo, this.dataset.artista, this.dataset.idArtista)"
                                    data-titulo="<?= escaparCatalogo($tituloObra) ?>"
                                    data-artista="<?= escaparCatalogo($nombreArtista) ?>"
                                    data-id-artista="<?= (int)$obra['artista_id'] ?>"
                                    class="btn btn-primary">
                                    🤝 Proponer colaboración
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="feed-post-description">
                            <h3 style="color:var(--text-main);font-size:1.05rem;font-weight:800;margin:0 0 .35rem;">
                                <?= escaparCatalogo($tituloObra) ?>
                            </h3>
                            <?php if (!empty($obra['descripcion'])): ?>
                                <p style="color:var(--text-main);font-size:.95rem;line-height:1.5;margin:0;">
                                    <?= escaparCatalogo($obra['descripcion']) ?> 🎨✨
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="feed-image-box">
                            <?php renderizarVistaPreviaObra($obra['ruta_imagen'], $tituloObra, 'min(520px, 70vh)', '', 'contain'); ?>
                        </div>

                        <div data-feed-actions style="padding:1rem 1.25rem;border-top:1px solid var(--border-subtle);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem;">
                            <button type="button" class="btn-like" data-obra="<?= (int)$obra['id'] ?>" aria-pressed="<?= $obra['liked'] ? 'true' : 'false' ?>" style="background:var(--hover-item);border:1px solid var(--brand-orange);color:var(--brand-orange);font-weight:700;font-size:.85rem;cursor:pointer;display:flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:.5rem;transition:all .2s;">
                                <span class="heart-icon"><?= $obra['liked'] ? '❤️' : '🤍' ?></span>
                                <span class="like-text"><?= (int)$obra['total_likes'] ?> Me gusta</span>
                            </button>
                            
                            <div style="display:flex;gap:.6rem;">
                                <?php if ($esOtroArtista && $obra['estado_venta'] === 'disponible'): ?>
                                    <button type="button" class="btn-carrito" data-obra="<?= (int)$obra['id'] ?>" style="background-color:var(--bg-surface);color:var(--text-main);border:1px solid var(--border-subtle);padding:.5rem .9rem;border-radius:.5rem;font-size:.825rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:.3rem;">
                                        🛒 Añadir al carrito
                                    </button>
                                <?php endif; ?>
                                <a href="/poliart/app/cliente/ver_obra.php?id=<?= (int)$obra['id'] ?>" class="client-feed-action client-details-action" onclick="guardarEstadoFeedArtista()" style="display:inline-flex;align-items:center;justify-content:center;gap:.35rem;padding:.5rem .9rem;border:1px solid rgba(56,189,248,.38);border-radius:.5rem;background:rgba(56,189,248,.12);color:#38bdf8;font-size:.825rem;font-weight:700;text-decoration:none;cursor:pointer;">
                                    Ver detalles
                                </a>
                                <button type="button" onclick="abrirModalReporteCliente(<?= (int)$obra['id'] ?>)" style="background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3);padding:.5rem .9rem;border-radius:.5rem;font-size:.825rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.35rem;">
                                    ⚠️ Reportar Obra
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>

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
            Envía una propuesta directa a <strong id="colabArtistaNombre" style="color: var(--brand-orange);">Sofia R.</strong> para trabajar juntos en esta obra:
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
// FUNCIONES PARA EL MODAL DE COLABORACIÓN
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
    const tituloObra = document.getElementById('colabObraTitulo').value.trim();
    const motivo = document.getElementById('motivoColaboracion').value;
    const mensaje = document.getElementById('mensajeColaboracion').value.trim();

    if (!idArtista || !tituloObra || !motivo || !mensaje) {
        estado.textContent = 'Completa el motivo y el mensaje para enviar la propuesta.';
        estado.style.display = 'block';
        return;
    }

    const data = new FormData();
    data.set('accion', 'crear');
    data.set('id_artista', idArtista);
    data.set('titulo', tituloObra.slice(0, 160));
    data.set('descripcion', 'Motivo: ' + motivo + '\n\n' + mensaje);
    submit.disabled = true;

    try {
        const response = await fetch('/poliart/app/includes/actions/gestion_colaboracion.php', {
            method: 'POST',
            body: data
        });
        const result = await response.json();
        if (!response.ok || !result.ok) {
            throw new Error(result.error || 'No se pudo enviar la invitación.');
        }

        cerrarModalColaboracion();
        form.reset();
        window.alert('🤝 La invitación fue enviada. El artista podrá verla en Colaboraciones y Notificaciones.');
    } catch (error) {
        estado.textContent = error.message || 'No se pudo enviar la invitación.';
        estado.style.display = 'block';
    } finally {
        submit.disabled = false;
    }
}

// FUNCIONES PARA EL MODAL DE REPORTE
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
    const idObra = document.getElementById('reporteClienteObraId').value;
    const detalle = document.getElementById('descripcionOtroMotivo')?.value.trim() ?? '';
    if (!motivo || (motivo.value === 'otro' && detalle === '')) {
        window.alert('Selecciona un motivo y describe el reporte si elegiste "Otro".');
        return;
    }
    const data = new FormData();
    data.set('id_obra', idObra);
    data.set('motivo', motivo.value);
    data.set('detalle', detalle);
    try {
        const response = await fetch('/poliart/app/includes/actions/reportar.php', { method: 'POST', body: data });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.error || 'No se pudo enviar el reporte.');
        window.alert(result.msg || 'Reporte enviado. Gracias por ayudar a moderar la comunidad.');
        cerrarModalReporteCliente();
    } catch (error) {
        window.alert(error.message || 'No se pudo enviar el reporte.');
    }
}
</script>

<script>
function guardarEstadoFeedArtista() {
    const contenedor = document.getElementById('feed-artista-scroll');
    if (!contenedor) return;
    sessionStorage.setItem('poliart_artista_feed_scroll', String(contenedor.scrollTop));
}

document.addEventListener('DOMContentLoaded', function () {
    const contenedor = document.getElementById('feed-artista-scroll');
    const scrollGuardado = sessionStorage.getItem('poliart_artista_feed_scroll');
    if (!contenedor || scrollGuardado === null) return;
    requestAnimationFrame(function () {
        contenedor.scrollTop = Number(scrollGuardado) || 0;
        sessionStorage.removeItem('poliart_artista_feed_scroll');
    });
});
</script>

<script src="<?= $project_base ?>public/assets/js/cliente.js"></script>

<style>
  [style*="max-width: 680px"] { max-width: 900px !important; }
  .feed-image-box { max-height: 650px; }
  .feed-image-box { max-height: none; overflow: visible; }
  .feed-image-box > div { height: auto !important; max-height: calc(100vh - 15rem); }
  .feed-image-box img { display: block; width: 100%; height: auto !important; max-height: calc(100vh - 15rem); object-fit: contain; }
  @media (min-width: 1400px) { [style*="max-width: 680px"] { max-width: 980px !important; } }
</style>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.feed-image-box video, .feed-image-box audio').forEach(function (media) {
      media.setAttribute('controlsList', 'nodownload');
      media.setAttribute('disablepictureinpicture', '');
    });
  });
</script>

