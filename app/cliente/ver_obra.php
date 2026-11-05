<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/catalogo_obras.php';

$pdo = getDB();
$idObra = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$obra = null;
$errorCarga = false;

if ($idObra) {
    try {
        $stmt = $pdo->prepare(
            'SELECT o.id_obra, o.id_artista, o.titulo, o.descripcion, o.ruta_imagen,
                    o.precio, o.moneda, o.estado_venta, c.nombre AS categoria,
                    z.nombre AS zona,
                    COALESCE(NULLIF(a.nombre_artistico, \'\'), CONCAT(u.nombre, \' \', u.apellido)) AS artista,
                    u.nombre_usuario, u.avatar_path,
                    (SELECT COUNT(*) FROM me_gusta mg WHERE mg.id_obra = o.id_obra) AS total_likes,
                    EXISTS(
                        SELECT 1 FROM me_gusta mg
                        WHERE mg.id_obra = o.id_obra AND mg.id_usuario = :id_usuario
                    ) AS liked
             FROM obra o
             INNER JOIN artista a ON a.id_artista = o.id_artista
             INNER JOIN usuario u ON u.id_usuario = a.id_usuario
             INNER JOIN categoria_obra c ON c.id_categoria = o.id_categoria
             LEFT JOIN zona z ON z.id_zona = COALESCE(o.id_zona, u.id_zona)
             WHERE o.id_obra = :id_obra AND o.estado_publicacion = \'publicada\'
             LIMIT 1'
        );
        $stmt->execute([
            ':id_usuario' => getUserId(),
            ':id_obra' => $idObra,
        ]);
        $obra = $stmt->fetch();

        if ($obra) {
            $pdo->prepare(
                'INSERT INTO obra_visita (id_obra, id_usuario) VALUES (:id_obra, :id_usuario)'
            )->execute([
                ':id_obra' => $idObra,
                ':id_usuario' => getUserId(),
            ]);
            $obra['ruta_imagen'] = normalizarRutaPublica($obra['ruta_imagen'], 'obras');
        }
    } catch (PDOException $e) {
        error_log('Error al cargar la obra: ' . $e->getMessage());
        $errorCarga = true;
    }
}

require_once __DIR__ . '/../cliente/header.php';
include_once __DIR__ . '/../cliente/sidebar.php';
?>
<style>
.contact-artist-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    margin-top: 1rem;
    padding: .5rem .9rem;
    border: 1px solid rgba(56, 189, 248, .38);
    border-radius: .65rem;
    background: rgba(56, 189, 248, .12);
    color: #38bdf8;
    font-size: .875rem;
    font-weight: 700;
    text-decoration: none;
    transition: background-color .2s ease, border-color .2s ease, transform .2s ease;
}

.contact-artist-action:hover {
    transform: translateY(-1px);
    border-color: rgba(56, 189, 248, .65);
    background: rgba(56, 189, 248, .2);
}

.contact-artist-action:focus-visible {
    outline: 2px solid #38bdf8;
    outline-offset: 2px;
}
</style>
<div class="obra-detail-page" style="max-width: 1100px; width: 100%; margin: 0 auto; padding: 2rem 1.5rem; box-sizing: border-box;">
    <?php if ($errorCarga): ?>
        <p role="alert" style="padding: 1.5rem; background: var(--bg-surface); color: var(--text-main); border-radius: 1rem;">
            No fue posible cargar esta obra. Intenta de nuevo más tarde.
        </p>
    <?php elseif (!$obra): ?>
        <p style="padding: 1.5rem; background: var(--bg-surface); color: var(--text-main); border-radius: 1rem;">
            La obra no existe o ya no está publicada.
        </p>
    <?php else: ?>
        <article style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(280px, 1fr); gap: 2rem; padding: 1.5rem; background: var(--bg-surface); color: var(--text-main); border: 1px solid var(--border-subtle); border-radius: 1rem;">
            <div style="min-height: 300px; background: var(--hover-item); border-radius: .75rem; display: grid; place-items: center; overflow: hidden;">
                <?php renderizarVistaPreviaObra($obra['ruta_imagen'], $obra['titulo'], 'min(650px, 75vh)'); ?>
            </div>
            <div>
                <p style="color: var(--brand-orange); font-weight: 700;"><?= htmlspecialchars($obra['categoria'], ENT_QUOTES, 'UTF-8') ?></p>
                <h1><?= htmlspecialchars($obra['titulo'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p>Por <a href="perfil_artista.php?id=<?= (int)$obra['id_artista'] ?>"><?= htmlspecialchars($obra['artista'], ENT_QUOTES, 'UTF-8') ?></a></p>
                <?php if ($obra['zona']): ?>
                    <p>📍 <?= htmlspecialchars($obra['zona'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <p style="white-space: pre-line; line-height: 1.6;"><?= htmlspecialchars($obra['descripcion'] ?? 'Sin descripción.', ENT_QUOTES, 'UTF-8') ?></p>
                <strong style="font-size: 1.5rem;"><?= htmlspecialchars(number_format((float)$obra['precio'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($obra['moneda'], ENT_QUOTES, 'UTF-8') ?></strong>
                <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;margin:1rem 0;">
                    <?php if (isLoggedIn()): ?>
                        <button type="button" id="btn-me-gusta" data-obra-id="<?= (int)$obra['id_obra'] ?>" aria-pressed="<?= $obra['liked'] ? 'true' : 'false' ?>" class="btn btn-rose">
                            <span id="icono-me-gusta"><?= $obra['liked'] ? '❤️' : '🤍' ?></span>
                            <span id="total-me-gusta"><?= (int)$obra['total_likes'] ?></span> Me gusta
                        </button>
                    <?php else: ?>
                        <a href="/poliart/app/auth/login_cliente.php?redirect=<?= urlencode('/poliart/app/cliente/ver_obra.php?id=' . (int)$obra['id_obra']) ?>">❤️ <?= (int)$obra['total_likes'] ?> Me gusta · Inicia sesión para participar</a>
                    <?php endif; ?>

                    <?php
                    $esObraPropia = getUserRole() === 'artista'
                        && (int)($_SESSION['user']['id_artista'] ?? 0) === (int)$obra['id_artista'];
                    // Mensaje predeterminado para contactar al artista
                    $msgContacto = rawurlencode('Hola! Me interesa la obra "' . $obra['titulo'] . '" (precio: ' . number_format((float)$obra['precio'], 2, ',', '.') . ' ' . $obra['moneda'] . '). ¿Está disponible? Me gustaría conocer más detalles.');
                    ?>
                    <?php if (isLoggedIn() && !$esObraPropia && in_array($obra['estado_venta'], ['disponible','reservada'])): ?>
                        <a href="mensajes.php?artista_id=<?= (int)$obra['id_artista'] ?>&msg=<?= $msgContacto ?>" class="btn btn-primary">
                            💬 Contactar artista
                        </a>
                    <?php elseif (!isLoggedIn() && in_array($obra['estado_venta'], ['disponible','reservada'])): ?>
                        <a href="/poliart/app/auth/login_cliente.php?redirect=<?= urlencode('/poliart/app/cliente/ver_obra.php?id=' . (int)$obra['id_obra']) ?>">
                            💬 Inicia sesión para contactar al artista
                        </a>
                    <?php endif; ?>

                    <?php if (isLoggedIn() && !$esObraPropia): ?>
                        <button type="button" id="btn-reportar-obra" class="btn btn-danger btn-sm">⚠️ Reportar obra</button>
                    <?php endif; ?>
                </div>
                <p id="mensaje-acciones-obra" role="status" aria-live="polite" style="min-height:1.25rem;"></p>
                <?php if ($esObraPropia): ?>
                    <p>Esta es tu obra publicada.</p>
                <?php endif; ?>
                <a class="contact-artist-action" href="mensajes.php?artista_id=<?= (int)$obra['id_artista'] ?>">
                    <span aria-hidden="true">💬</span> Contactar al artista
                </a>
            </div>
        </article>
        <?php if (isLoggedIn() && !$esObraPropia): ?>
            <style>
                .reporte-modal__form { border: 1px solid var(--brand-orange); box-shadow: 0 20px 60px rgba(0,0,0,.45); max-height: calc(100vh - 2rem); overflow-y: auto; }
                .reporte-modal__close { float: right; width: 2.75rem; height: 2.75rem; border: 1px solid var(--border-subtle); border-radius: 50%; background: transparent; color: var(--text-main); font-size: 1.5rem; font-weight: 800; cursor: pointer; }
                .reporte-modal__intro { margin: .9rem 0 1.25rem; color: var(--text-muted); line-height: 1.45; }
                .reporte-option { display: block; margin: .75rem 0; padding: .9rem 1rem; border: 1px solid var(--brand-orange); border-radius: .85rem; cursor: pointer; }
                .reporte-option input { accent-color: var(--brand-orange); margin-right: .75rem; }
                .reporte-option strong, .reporte-option span { display: block; margin-left: 1.55rem; }
                .reporte-option strong { margin-top: -1.35rem; color: var(--text-main); }
                .reporte-option span { margin-top: .3rem; color: var(--text-muted); font-size: .9rem; }
                .reporte-option:has(input:checked) { background: rgba(237,119,69,.12); box-shadow: inset 0 0 0 1px var(--brand-orange); }
                .reporte-modal__actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.25rem; }
                .reporte-modal__actions button { padding: .65rem 1rem; border-radius: .7rem; border: 1px solid var(--border-subtle); background: var(--bg-surface); color: var(--text-main); font-weight: 700; cursor: pointer; }
                .reporte-modal__actions button[type="submit"] { border-color: #b91c1c; background: rgba(185,28,28,.18); color: #f87171; }
            </style>
            <div id="modal-reporte-obra" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-reporte" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,.65);align-items:center;justify-content:center;padding:1rem;box-sizing:border-box;">
                <form id="form-reporte-obra" class="reporte-modal__form" style="width:100%;max-width:600px;padding:1.75rem;border-radius:1rem;background:var(--bg-surface);color:var(--text-main);">
                    <button type="button" class="reporte-modal__close" id="cerrar-reporte-obra" aria-label="Cerrar">×</button>
                    <h2 id="titulo-modal-reporte" style="margin-top:0;">⚠️ Reportar esta obra</h2>
                    <p class="reporte-modal__intro">Selecciona la razón principal por la que deseas notificar a moderación sobre esta publicación:</p>
                    <div class="reporte-options" role="radiogroup" aria-label="Motivo del reporte">
                        <label class="reporte-option"><input type="radio" name="motivo-visual" value="plagio" checked><strong>Plagio / Copia no autorizada</strong><span>Derechos de autor pertenecientes a otro creador.</span></label>
                        <label class="reporte-option"><input type="radio" name="motivo-visual" value="inapropiado"><strong>Contenido Inapropiado u Ofensivo</strong><span>Imágenes explícitas, violencia o incitación al odio.</span></label>
                        <label class="reporte-option"><input type="radio" name="motivo-visual" value="spam"><strong>Spam / Información Engañosa</strong><span>Precio engañoso, fraude o publicación sospechosa.</span></label>
                        <label class="reporte-option"><input type="radio" name="motivo-visual" value="otro"><strong>Otro motivo</strong><span>Otra infracción a las políticas de Poli-Art.</span></label>
                    </div>
                    <select id="motivo-reporte-obra" name="motivo" required style="display:none;">
                        <option value="plagio">Plagio o copia no autorizada</option>
                        <option value="inapropiado">Contenido inapropiado</option>
                        <option value="spam">Spam o información engañosa</option>
                        <option value="otro">Otro motivo</option>
                    </select>
                    <label for="detalle-reporte-obra" id="etiqueta-detalle-reporte" style="display:none;">Detalles del reporte</label>
                    <textarea id="detalle-reporte-obra" name="detalle" rows="3" maxlength="1000" style="display:none;width:100%;box-sizing:border-box;padding:.65rem;margin:.35rem 0 .75rem;background:var(--bg-surface);color:var(--text-main);border:1px solid var(--border-subtle);border-radius:.5rem;"></textarea>
                    <p id="estado-reporte-obra" role="status" aria-live="polite"></p>
                    <div class="reporte-modal__actions">
                        <button type="button" id="cancelar-reporte-obra">Cancelar</button>
                        <button type="submit">Enviar Reporte</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script>
const obraActionMessage = document.getElementById('mensaje-acciones-obra');
const likeButton = document.getElementById('btn-me-gusta');
if (likeButton) {
    likeButton.addEventListener('click', async () => {
        likeButton.disabled = true;
        try {
            const response = await fetch('../includes/actions/megusta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ id_obra: likeButton.dataset.obraId })
            });
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.error || 'No fue posible actualizar el me gusta.');
            likeButton.setAttribute('aria-pressed', result.liked ? 'true' : 'false');
            document.getElementById('icono-me-gusta').textContent = result.liked ? '❤️' : '🤍';
            document.getElementById('total-me-gusta').textContent = result.total;
        } catch (error) {
            obraActionMessage.textContent = error.message || 'No fue posible actualizar el me gusta.';
        } finally {
            likeButton.disabled = false;
        }
    });
}

const reportButton = document.getElementById('btn-reportar-obra');
const reportModal = document.getElementById('modal-reporte-obra');
const reportForm = document.getElementById('form-reporte-obra');
if (reportButton && reportModal && reportForm) {
    const closeReportButton = document.getElementById('cerrar-reporte-obra');
    const motiveSelect = document.getElementById('motivo-reporte-obra');
    const detailInput = document.getElementById('detalle-reporte-obra');
    const detailLabel = document.getElementById('etiqueta-detalle-reporte');
    const visualMotives = Array.from(document.querySelectorAll('input[name="motivo-visual"]'));
    const syncReportMotive = () => {
        const selected = visualMotives.find(input => input.checked);
        const motive = selected ? selected.value : 'plagio';
        motiveSelect.value = motive;
        const needsDetails = motive === 'otro';
        detailInput.style.display = needsDetails ? 'block' : 'none';
        detailLabel.style.display = needsDetails ? 'block' : 'none';
        detailInput.required = needsDetails;
    };

    const closeReportModal = () => {
        reportModal.style.display = 'none';
        reportForm.reset();
        visualMotives[0].checked = true;
        syncReportMotive();
        document.getElementById('estado-reporte-obra').textContent = '';
    };

    reportButton.addEventListener('click', () => {
        reportModal.style.display = 'flex';
        syncReportMotive();
        visualMotives[0].focus();
    });
    closeReportButton.addEventListener('click', closeReportModal);
    document.getElementById('cancelar-reporte-obra').addEventListener('click', closeReportModal);
    visualMotives.forEach(input => input.addEventListener('change', syncReportMotive));
    reportModal.addEventListener('click', event => {
        if (event.target === reportModal) closeReportModal();
    });
    reportForm.addEventListener('submit', async event => {
        event.preventDefault();
        const motivo = document.getElementById('motivo-reporte-obra').value;
        const detalle = document.getElementById('detalle-reporte-obra').value.trim();
        const status = document.getElementById('estado-reporte-obra');
        if (motivo === 'otro' && detalle === '') {
            status.textContent = 'Describe el motivo del reporte.';
            return;
        }

        const submitButton = reportForm.querySelector('[type="submit"]');
        submitButton.disabled = true;
        status.textContent = 'Enviando reporte...';
        try {
            const response = await fetch('../includes/actions/reportar.php', {
                method: 'POST',
                body: new URLSearchParams({
                    id_obra: <?= (int)($obra['id_obra'] ?? 0) ?>,
                    motivo,
                    detalle
                })
            });
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.error || 'No fue posible enviar el reporte.');
            status.textContent = result.msg || 'Reporte enviado. Gracias por ayudar a moderar la comunidad.';
            reportButton.disabled = true;
            reportButton.textContent = '✅ Obra reportada';
            window.setTimeout(closeReportModal, 1200);
        } catch (error) {
            status.textContent = error.message || 'No fue posible enviar el reporte.';
        } finally {
            submitButton.disabled = false;
        }
    });
}
</script>
