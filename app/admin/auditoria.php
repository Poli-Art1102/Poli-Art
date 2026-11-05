<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['administrador']);
require_once __DIR__ . '/../includes/catalogo_obras.php';

$project_base = '/poliart/';
$logs_auditoria = [];
$error_logs = '';

try {
    $stmtLogs = getDB()->query(
        "SELECT r.id_reporte AS id_log, r.id_obra,
                u.nombre_usuario AS admin_user,
                o.titulo AS obra_titulo, o.ruta_imagen AS obra_imagen,
                r.estado AS accion,
                CONCAT('Reporte #', r.id_reporte, ' revisado: ', r.motivo,
                    CASE WHEN r.observaciones_revision IS NOT NULL AND r.observaciones_revision != ''
                         THEN CONCAT(' — ', r.observaciones_revision) ELSE '' END) AS descripcion,
                r.revisado_en AS creado_en
         FROM reporte_contenido r
         INNER JOIN obra o ON o.id_obra = r.id_obra
         INNER JOIN usuario u ON u.id_usuario = r.id_revisor
         WHERE r.id_revisor IS NOT NULL AND r.revisado_en IS NOT NULL
         ORDER BY r.revisado_en DESC LIMIT 50"
    );
    $logs_auditoria = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
    foreach ($logs_auditoria as &$log) {
        $log['obra_imagen'] = normalizarRutaPublica($log['obra_imagen'], 'obras') ?? '';
    }
    unset($log);
} catch (PDOException $e) {
    error_log('Error al consultar el historial de moderación: ' . $e->getMessage());
    $error_logs = 'No fue posible cargar el historial de moderación.';
}

require_once __DIR__ . '/header.php';
?>

<style>
.audit-page { width:100%; padding:2.5rem 2rem 0; box-sizing:border-box; }
.audit-box { background:var(--bg-surface); border:1px solid var(--border-subtle); border-radius:1.5rem; padding:2.25rem; max-width:1200px; margin:0 auto; box-sizing:border-box; }
.audit-title { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem; }
.audit-title h1 { margin:0; color:var(--text-main); font-size:1.65rem; font-weight:800; }
.audit-back { color:var(--brand-orange); font-size:.88rem; font-weight:700; text-decoration:none; }
.timeline-box { background:var(--hover-item); border:1px solid var(--border-subtle); border-radius:1.15rem; padding:1.5rem; }
.timeline-item { display:grid; grid-template-columns:260px minmax(0, 1fr); align-items:center; gap:1.5rem; padding:1rem 0; border-bottom:1px solid var(--border-subtle); }
.timeline-item:last-child { border-bottom:0; padding-bottom:0; }
.obra-audit { display:flex; flex-direction:column; align-items:flex-start; gap:.65rem; margin:0; min-width:0; text-decoration:none; cursor:pointer; }
.obra-audit img { width:100%; height:190px; object-fit:cover; border-radius:.85rem; border:1px solid var(--border-subtle); background:var(--bg-surface); }
.obra-audit-title { color:var(--text-main); font-size:.88rem; font-weight:800; text-decoration:none; }
.obra-audit-title:hover { color:var(--brand-orange); text-decoration:underline; }
.audit-info { display:flex; flex-direction:column; gap:.85rem; min-width:0; }
.audit-info p { margin:0 !important; max-width:34rem; }
.audit-info > span { white-space:nowrap; }
@media (max-width: 900px) {
    .timeline-item { grid-template-columns:200px minmax(0, 1fr); }
    .obra-audit img { height:150px; }
}
@media (max-width: 560px) {
    .timeline-item { grid-template-columns:1fr; }
    .obra-audit img { height:220px; }
}
.timeline-icon { width:2.5rem; height:2.5rem; border-radius:.75rem; background:rgba(255,140,90,.15); border:1px solid rgba(255,140,90,.3); display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
.action-tag { font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; padding:.15rem .5rem; border-radius:.4rem; background:rgba(237,119,69,.2); color:var(--brand-orange); }
.data-error { padding:.9rem 1rem; border:1px solid #ef4444; border-radius:.75rem; background:rgba(239,68,68,.15); color:#ef4444; font-weight:700; }
</style>

<div class="audit-page">
  <div class="audit-box">
    <div class="audit-title">
      <h1>📋 Historial de Moderación y Auditoría</h1>
      <a class="audit-back" href="<?= $project_base ?>app/admin/administradores_log.php">← Volver a Administradores</a>
    </div>

    <?php if ($error_logs !== ''): ?>
      <div class="data-error" role="alert"><?= htmlspecialchars($error_logs, ENT_QUOTES, 'UTF-8') ?></div>
    <?php elseif (empty($logs_auditoria)): ?>
      <div class="timeline-box" style="text-align:center;padding:2.5rem 1rem;">
        <span style="font-size:2.5rem;display:block;margin-bottom:.5rem;">🔍</span>
        <h2 style="margin:0 0 .35rem;color:var(--text-main);font-size:1rem;">Sin eventos registrados</h2>
        <p style="color:var(--text-muted);font-size:.85rem;margin:0;">Las acciones realizadas por los administradores aparecerán aquí.</p>
      </div>
    <?php else: ?>
      <div class="timeline-box">
        <?php foreach ($logs_auditoria as $log): ?>
          <div class="timeline-item">
            <a class="obra-audit" href="<?= $project_base ?>app/cliente/ver_obra.php?id=<?= (int)$log['id_obra'] ?>" title="Ver obra">
              <?php if ($log['obra_imagen'] !== ''): ?>
                <img src="<?= htmlspecialchars($log['obra_imagen'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($log['obra_titulo'], ENT_QUOTES, 'UTF-8') ?>">
              <?php endif; ?>
            </a>
            <div class="audit-info">
              <a class="obra-audit-title" href="<?= $project_base ?>app/cliente/ver_obra.php?id=<?= (int)$log['id_obra'] ?>">🎨 <?= htmlspecialchars($log['obra_titulo'], ENT_QUOTES, 'UTF-8') ?></a>
              <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;margin-bottom:.25rem;">
                <span class="action-tag"><?= htmlspecialchars($log['accion'], ENT_QUOTES, 'UTF-8') ?></span>
                <span style="font-size:.85rem;font-weight:800;color:var(--brand-orange);">por @<?= htmlspecialchars($log['admin_user'], ENT_QUOTES, 'UTF-8') ?></span>
              </div>
              <p style="font-size:.88rem;color:var(--text-main);margin:.25rem 0 .35rem;line-height:1.4;font-weight:600;"><?= htmlspecialchars($log['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
              <span style="font-size:.75rem;color:var(--text-muted);font-weight:700;">🕒 <?= date('d/m/Y \a \l\a\s H:i', strtotime($log['creado_en'])) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
