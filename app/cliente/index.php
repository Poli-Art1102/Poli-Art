<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['cliente', 'artista']);
require_once __DIR__ . '/header.php';
include_once __DIR__ . '/sidebar.php';

$project_base = '/poliart/';
require_once __DIR__ . '/../includes/catalogo_obras.php';
$obrasFeed = [];
$errorFeed = false;
try {
    $obrasFeed = obtenerObrasPorCategoria(null, getUserId(), 30);
} catch (PDOException $e) {
    error_log('Error al cargar el feed del cliente: ' . $e->getMessage());
    $errorFeed = true;
}
?>

<style>
html, body {
    margin: 0;
    padding: 0;
}

.client-home-content {
    flex: 1 0 auto;
    min-width: 0;
    min-height: calc(100vh - 4.25rem);
    padding: 2.5rem 2rem;
    box-sizing: border-box;
}

.client-home-wrap {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    max-width: 680px;
    margin: 0 auto;
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

.client-follow-artist {
    margin-left: .4rem;
    padding: .3rem .65rem;
    border: 1px solid rgba(237, 119, 69, .42);
    border-radius: .55rem;
    background: rgba(237, 119, 69, .13);
    color: #ff9a6b;
    font-size: .74rem;
    font-weight: 700;
    cursor: pointer;
    vertical-align: middle;
    transition: background-color .2s ease, border-color .2s ease;
}

.client-follow-artist:hover {
    border-color: rgba(237, 119, 69, .7);
    background: rgba(237, 119, 69, .22);
}

.client-follow-artist[data-following="true"] {
    border-color: rgba(148, 163, 184, .35);
    background: rgba(148, 163, 184, .12);
    color: var(--text-muted);
}

.client-work-status {
    display: inline-flex;
    align-items: center;
    padding: .2rem .55rem;
    border: 1px solid currentColor;
    border-radius: 9999px;
    font-size: .7rem;
    font-weight: 700;
    line-height: 1.3;
    vertical-align: middle;
}

.client-work-status[data-status="disponible"] {
    color: #15803d;
    background: rgba(34, 197, 94, .15);
}

.client-work-status[data-status="reservada"] {
    color: #a16207;
    background: rgba(234, 179, 8, .15);
}

.client-work-status[data-status="vendida"] {
    color: #b91c1c;
    background: rgba(239, 68, 68, .15);
}

.client-work-status[data-status="no_disponible"] {
    color: #6b7280;
    background: rgba(156, 163, 175, .15);
}

.client-header-report {
    flex: 0 0 auto;
}

.client-feed-actions {
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--border-subtle);
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: center;
    gap: .75rem;
}

.client-feed-actions > div {
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: .6rem;
}

.client-feed-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    padding: .5rem .9rem;
    border: 1px solid rgba(148, 163, 184, .3);
    border-radius: .6rem;
    color: var(--text-main);
    background: rgba(148, 163, 184, .1);
    font-size: .825rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: background-color .2s ease, border-color .2s ease, color .2s ease;
}

.client-feed-action:hover {
    border-color: rgba(148, 163, 184, .5);
    background: rgba(148, 163, 184, .17);
}

.client-feed-action:active,
.client-follow-artist:active {
    transform: translateY(0) scale(.98);
}

.client-feed-action:focus-visible,
.client-follow-artist:focus-visible {
    outline: 3px solid rgba(237, 119, 69, .4);
    outline-offset: 3px;
}

.client-feed-action.btn-seguir-artista-feed[data-following="false"] {
    border-color: rgba(237, 119, 69, .42);
    background: rgba(237, 119, 69, .13);
    color: #ff9a6b;
}

.client-feed-action.client-cart-action {
    border-color: rgba(34, 197, 94, .38);
    background: rgba(34, 197, 94, .12);
    color: #4ade80;
}

.client-feed-action.client-report-action {
    border-color: rgba(239, 68, 68, .38);
    background: rgba(239, 68, 68, .13);
    color: #ef4444;
}

.client-details-action {
    border-color: rgba(56, 189, 248, .38);
    background: rgba(56, 189, 248, .12);
    color: #38bdf8;
}

.client-feed-action.client-cart-action:hover {
    border-color: rgba(34, 197, 94, .65);
    background: rgba(34, 197, 94, .2);
}

.client-feed-action.client-report-action:hover {
    border-color: rgba(239, 68, 68, .65);
    background: rgba(239, 68, 68, .21);
}

.client-details-action:hover {
    border-color: rgba(56, 189, 248, .65);
    background: rgba(56, 189, 248, .2);
}

.client-like-action {
    padding: .5rem .9rem;
    border: 1px solid rgba(244, 63, 94, .38);
    border-radius: .6rem;
    background: rgba(244, 63, 94, .12);
    color: #fb7185;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: .4rem;
    transition: background-color .2s ease, border-color .2s ease, color .2s ease;
}

.client-like-action[aria-pressed="true"] {
    border-color: rgba(244, 63, 94, .58);
    background: rgba(244, 63, 94, .2);
    color: #fb7185;
}

.client-like-action:hover {
    border-color: rgba(244, 63, 94, .65);
    background: rgba(244, 63, 94, .2);
}

.client-modal-action {
    padding: .5rem .9rem;
    border: 1px solid rgba(148, 163, 184, .3);
    border-radius: .6rem;
    background: rgba(148, 163, 184, .1);
    color: var(--text-muted);
    font-size: .825rem;
    font-weight: 700;
    cursor: pointer;
    transition: background-color .2s ease, border-color .2s ease;
}

.client-modal-action:hover {
    border-color: rgba(148, 163, 184, .5);
    background: rgba(148, 163, 184, .18);
}

.client-modal-action.client-modal-submit {
    border-color: rgba(239, 68, 68, .38);
    background: rgba(239, 68, 68, .13);
    color: #ef4444;
}

.client-modal-action.client-modal-submit:hover {
    border-color: rgba(239, 68, 68, .65);
    background: rgba(239, 68, 68, .21);
}

.client-modal-close {
    border: 1px solid rgba(148, 163, 184, .3) !important;
    border-radius: .5rem;
    background: rgba(148, 163, 184, .1) !important;
    padding: .25rem .4rem;
    transition: background-color .2s ease, border-color .2s ease;
}

.client-modal-close:hover {
    border-color: rgba(148, 163, 184, .5) !important;
    background: rgba(148, 163, 184, .18) !important;
}

@media (max-width: 640px) {
    .client-home-content {
        padding: 1.25rem .85rem;
    }

    .client-feed-actions {
        grid-template-columns: 1fr;
        align-items: stretch;
        padding: .85rem;
    }

    .client-feed-actions > div {
        justify-content: stretch;
    }

    .client-feed-action {
        flex: 1 1 auto;
    }
}

/* MODAL DE REPORTE */
.modal-report-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(5px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 1rem;
    box-sizing: border-box;
}

.modal-report-content {
    background: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1rem;
    padding: 1rem;
    width: 100%;
    max-width: 600px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
}

.reason-option {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--hover-item);
    border: 1px solid var(--border-subtle);
    border-radius: 0.65rem;
    cursor: pointer;
    transition: border-color 0.2s ease;
}

.reason-option:hover {
    border-color: #ef4444;
}

.btn-report-action {
    background-color: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
    padding: 0.5rem 0.9rem;
    border-radius: 0.5rem;
    font-size: 0.825rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    transition: all 0.2s ease;
}

.btn-report-action:hover {
    background-color: #ef4444;
    color: #ffffff;
}

.form-control-modal {
    background-color: var(--hover-item);
    border: 1px solid var(--border-subtle);
    color: var(--text-main);
    padding: 0.75rem;
    border-radius: 0.65rem;
    width: 100%;
    outline: none;
    font-size: 0.85rem;
    box-sizing: border-box;
    margin-top: 0.5rem;
    resize: vertical;
}

.form-control-modal:focus {
    border-color: #ef4444;
}

/* TOAST NOTIFICATION ELEGANTE */
.toast-notification {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: var(--bg-surface);
    border: 1px solid var(--brand-orange);
    color: var(--text-main);
    padding: 0.9rem 1.4rem;
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.88rem;
    font-weight: 700;
    z-index: 10000;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease;
    pointer-events: none;
}

.toast-notification.show {
    opacity: 1;
    transform: translateY(0);
}
</style>

<div class="client-home-content">
    <div class="client-home-wrap">
        <?php require_once __DIR__ . '/../includes/anuncios_globales.php'; renderizarAnunciosGlobales('clientes'); ?>
        <?php if ($errorFeed): ?>
            <p role="alert" style="color:var(--text-main);">No fue posible cargar las publicaciones.</p>
        <?php elseif (!$obrasFeed): ?>
            <p style="padding:2rem;text-align:center;color:var(--text-muted);background:var(--bg-surface);border:1px solid var(--border-subtle);border-radius:1rem;">
                Aún no hay obras publicadas. Cuando los artistas publiquen, aparecerán aquí.
            </p>
        <?php else: ?>
            <?php foreach ($obrasFeed as $obra): ?>
                <?php
                $nombreArtista = (string)$obra['artista'];
                $tituloObra = (string)$obra['titulo'];
                $fechaPublicacion = $obra['publicada_en'] ?: $obra['creado_en'];
                $estadosVenta = [
                    'disponible' => 'Disponible para venta',
                    'reservada' => 'Reservada',
                    'vendida' => 'Vendida',
                    'no_disponible' => 'No disponible',
                ];
                $estadoVenta = (string)$obra['estado_venta'];
                $etiquetaEstadoVenta = $estadosVenta[$estadoVenta] ?? 'No disponible';
                ?>
                <article class="feed-post-card">
                    <div class="feed-post-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;">
                        <div style="display:flex;align-items:center;gap:.85rem;min-width:0;">
                            <a href="/poliart/app/cliente/perfil_artista.php?id=<?= (int)$obra['artista_id'] ?>" aria-label="Ver perfil de <?= escaparCatalogo($nombreArtista) ?>" style="display:block;text-decoration:none;color:inherit;line-height:0;" onclick="event.stopPropagation()">
                            <?php if (!empty($obra['avatar_path'])): ?>
                                <img src="<?= escaparCatalogo($obra['avatar_path']) ?>" alt="" loading="lazy" style="width:2.75rem;height:2.75rem;border-radius:9999px;box-shadow:0 2px 8px rgba(249,115,22,.2);object-fit:cover;">
                            <?php else: ?>
                                <span aria-hidden="true" style="width:2.75rem;height:2.75rem;flex:0 0 2.75rem;border-radius:9999px;background:linear-gradient(135deg,var(--brand-orange),#ea580c);color:#fff;font-weight:800;display:grid;place-items:center;">🎨</span>
                            <?php endif; ?>
                            </a>
                            <div style="min-width:0;">
                                <h2 style="color:var(--text-main);font-size:1rem;font-weight:700;margin:0;">
                                    <?= escaparCatalogo($nombreArtista) ?>
                                    <?php if ((int)$obra['id_usuario'] !== getUserId()): ?>
                                        <button type="button" class="client-follow-artist btn-seguir-artista-feed" data-artista-id="<?= (int)$obra['artista_id'] ?>" data-following="<?= $obra['following'] ? 'true' : 'false' ?>" aria-pressed="<?= $obra['following'] ? 'true' : 'false' ?>">
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
                        <?php if ((int)$obra['id_usuario'] !== getUserId()): ?>
                            <button type="button" class="client-feed-action client-report-action client-header-report" onclick="abrirModalReporteCliente(<?= (int)$obra['id'] ?>)">
                                ⚠️ Reportar obra
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="feed-post-description">
                        <h3 style="display:flex;align-items:center;flex-wrap:wrap;gap:.5rem;color:var(--text-main);font-size:1.05rem;font-weight:800;margin:0 0 .35rem;">
                            <?= escaparCatalogo($tituloObra) ?>
                            <span class="client-work-status" data-status="<?= escaparCatalogo($estadoVenta) ?>">
                                <?= escaparCatalogo($etiquetaEstadoVenta) ?>
                            </span>
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

                    <div class="client-feed-actions">
                        <button type="button" class="btn-like client-like-action" data-obra="<?= (int)$obra['id'] ?>" aria-pressed="<?= $obra['liked'] ? 'true' : 'false' ?>">
                            <span class="heart-icon"><?= $obra['liked'] ? '❤️' : '🤍' ?></span>
                            <span class="like-text"><?= (int)$obra['total_likes'] ?> Me gusta</span>
                        </button>

                        <div>
                            <?php if ((int)$obra['id_usuario'] !== getUserId() && $obra['estado_venta'] === 'disponible'): ?>
                                <button type="button" class="btn-carrito client-feed-action client-cart-action" data-obra="<?= (int)$obra['id'] ?>">
                                    🛒 Añadir al carrito
                                </button>
                            <?php endif; ?>
                            <a class="client-feed-action client-details-action" href="/poliart/app/cliente/ver_obra.php?id=<?= (int)$obra['id'] ?>">Ver detalles</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL DE MOTIVO DE REPORTE -->
<div id="modalReporteCliente" class="modal-report-overlay">
    <style>#modalReporteCliente .reason-option { display:flex !important; margin:.75rem 0; padding:.9rem 1rem; border:1px solid var(--brand-orange); border-radius:.85rem; background:transparent; } #modalReporteCliente .modal-report-content > div:first-child button, #modalReporteCliente .modal-report-content > div:first-child + p { display:block; } #modalReporteCliente .modal-report-content > div:first-child { margin-bottom:.85rem !important; }</style>
    <div class="modal-report-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
            <h3 style="margin: 0; font-size: 1.1rem; color: var(--text-main); font-weight: 800;">
                ⚠️ Reportar esta Obra
            </h3>
            <button type="button" class="client-modal-action client-modal-close" aria-label="Cerrar reporte" onclick="cerrarModalReporteCliente()" style="color: var(--text-muted); font-size: 1.1rem; cursor: pointer;">
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
                        
                        <!-- CAJA DE TEXTO DINÁMICA -->
                        <div id="contenedorOtroMotivo" style="display: none; margin-top: 0.5rem;">
                            <textarea id="descripcionOtroMotivo" class="form-control-modal" rows="3" placeholder="Describe aquí la razón detallada de tu reporte..."></textarea>
                        </div>
                    </div>
                </label>

            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.6rem;">
                <button type="button" class="client-modal-action" onclick="cerrarModalReporteCliente()">
                    Cancelar
                </button>
                <button type="submit" class="client-modal-action client-modal-submit">
                    Enviar Reporte
                </button>
            </div>
        </form>
    </div>
</div>

<!-- COMPONENTE TOAST DE NOTIFICACIÓN SUSTITUTO DE POPUPS -->
<div id="toastNotification" class="toast-notification">
    <span id="toastIcon">🛒</span>
    <span id="toastMessage">Obra agregada al carrito con éxito</span>
</div>

<script src="<?= $project_base ?>public/assets/js/cliente.js"></script>

<script>
function mostrarToast(mensaje, icono = '🛒') {
    const toast = document.getElementById('toastNotification');
    document.getElementById('toastIcon').textContent = icono;
    document.getElementById('toastMessage').textContent = mensaje;
    
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3200);
}

function agregarAlCarrito(nombreObra) {
    mostrarToast(`"${nombreObra}" agregada a tu carrito de compras.`, '🛒');
}

function abrirModalReporteCliente(obraId) {
    document.getElementById('reporteClienteObraId').value = obraId;
    document.getElementById('modalReporteCliente').style.display = 'flex';
}

function cerrarModalReporteCliente() {
    document.getElementById('modalReporteCliente').style.display = 'none';
    toggleCajaOtroMotivo(false);
}

function toggleCajaOtroMotivo(mostrar) {
    const contenedor = document.getElementById('contenedorOtroMotivo');
    const textarea = document.getElementById('descripcionOtroMotivo');
    
    if (mostrar) {
        contenedor.style.display = 'block';
        textarea.focus();
    } else {
        contenedor.style.display = 'none';
        textarea.value = '';
    }
}

async function enviarReporteCliente(e) {
    e.preventDefault();
    const radioOtro = document.getElementById('radioOtroMotivo');
    const descripcionText = document.getElementById('descripcionOtroMotivo').value.trim();
    const motivo = document.querySelector('input[name="motivo_cliente"]:checked');
    if (!motivo || (radioOtro.checked && descripcionText === '')) {
        mostrarToast('Por favor describe brevemente el motivo del reporte.', '⚠️');
        return;
    }
    const data = new FormData();
    data.set('id_obra', document.getElementById('reporteClienteObraId').value);
    data.set('motivo', motivo.value);
    data.set('detalle', descripcionText);
    try {
        const response = await fetch('/poliart/app/includes/actions/reportar.php', { method: 'POST', body: data });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.error || 'No se pudo enviar el reporte.');
        mostrarToast(result.msg || 'Reporte enviado. Nuestro equipo revisará el caso.', '🛡️');
        cerrarModalReporteCliente();
    } catch (error) {
        mostrarToast(error.message || 'No se pudo enviar el reporte.', '⚠️');
    }
}
</script>

<style>
  .client-home-wrap { max-width: 900px; }
  .feed-image-box { max-height: 650px; }
  .feed-image-box { max-height: none; overflow: visible; }
  .feed-image-box > div { height: auto !important; max-height: calc(100vh - 15rem); }
  .feed-image-box img { display: block; width: 100%; height: auto !important; max-height: calc(100vh - 15rem); object-fit: contain; }
  @media (min-width: 1400px) { .client-home-wrap { max-width: 980px; } }
</style>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.feed-image-box video, .feed-image-box audio').forEach(function (media) {
      media.setAttribute('controlsList', 'nodownload');
      media.setAttribute('disablepictureinpicture', '');
    });
  });
</script>

