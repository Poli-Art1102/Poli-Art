<?php
// La auditoría pertenece exclusivamente al módulo administrativo.
// Las funciones transversales se consumen desde el módulo compartido.
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['administrador']);

$project_base = '/poliart/';
$pdo = getDB();

$administradores = [];
$logs_auditoria = [];
$error_administradores = '';
$error_logs = '';

try {
    $stmtAdmins = $pdo->query(
        "SELECT id_usuario, nombre, nombre_usuario, correo, estado, creado_en
         FROM usuario
         WHERE rol = 'administrador'
         ORDER BY creado_en DESC"
    );
    $administradores = $stmtAdmins->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error al consultar administradores: ' . $e->getMessage());
    $error_administradores = 'No fue posible cargar el equipo de administradores.';
}

try {
    $stmtLogs = $pdo->query(
        "SELECT
            r.id_reporte AS id_log,
            u.nombre_usuario AS admin_user,
            r.estado AS accion,
            CONCAT(
                'Reporte #', r.id_reporte, ' revisado: ', r.motivo,
                CASE
                    WHEN r.observaciones_revision IS NOT NULL AND r.observaciones_revision != ''
                    THEN CONCAT(' — ', r.observaciones_revision)
                    ELSE ''
                END
            ) AS descripcion,
            r.revisado_en AS creado_en
         FROM reporte_contenido r
         INNER JOIN usuario u ON u.id_usuario = r.id_revisor
         WHERE r.id_revisor IS NOT NULL
           AND r.revisado_en IS NOT NULL
         ORDER BY r.revisado_en DESC
         LIMIT 15"
    );
    $logs_auditoria = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error al consultar el historial de moderación: ' . $e->getMessage());
    $error_logs = 'No fue posible cargar el historial de moderación.';
}

require_once __DIR__ . '/header.php';
?>

<style>
.container-box {
    background-color: var(--bg-surface) !important;
    border: 1px solid var(--border-subtle) !important;
    border-radius: 1.5rem;
    padding: 2.25rem;
    box-shadow: 0 15px 35px rgba(0,0,0,0.18);
    backdrop-filter: blur(14px);
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    box-sizing: border-box;
}

/* BOTÓN DE CREAR ADMINISTRADOR */
.btn-create-admin {
    background: linear-gradient(135deg, #FF8C5A 0%, #FF6B35 100%) !important;
    color: #ffffff !important;
    padding: 0.6rem 1.25rem;
    border-radius: 0.85rem;
    font-weight: 800;
    font-size: 0.88rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 4px 15px rgba(255, 107, 53, 0.35);
    border: 1px solid rgba(255, 255, 255, 0.25);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.btn-create-admin:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 107, 53, 0.45);
}

.btn-audit-history {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .55rem .9rem;
    border-radius: .75rem;
    background: rgba(56, 189, 248, .14);
    border: 1px solid rgba(56, 189, 248, .55);
    color: #38bdf8;
    font-size: .8rem;
    font-weight: 800;
    text-decoration: none;
}
.btn-audit-history:hover { background: rgba(56, 189, 248, .24); color: #7dd3fc; }

/* TARJETAS DE ADMINISTRADORES */
.admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.25rem;
    margin-top: 1rem;
}

.admin-card {
    background-color: var(--hover-item) !important;
    border: 1.5px solid var(--border-subtle) !important;
    border-radius: 1.15rem;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    position: relative;
    overflow: hidden;
    transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.admin-card:hover {
    transform: translateY(-3px);
    border-color: var(--brand-orange) !important;
    box-shadow: 0 8px 24px rgba(237, 119, 69, 0.2);
}

.admin-card-header {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

.admin-avatar {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--brand-orange), #ea580c);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    font-weight: 800;
    box-shadow: 0 4px 12px rgba(237, 119, 69, 0.3);
}

.badge-pill {
    font-size: 0.72rem;
    font-weight: 800;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.badge-active { background: rgba(34, 197, 94, 0.18); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.35); }
.badge-suspended { background: rgba(239, 68, 68, 0.18); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.35); }

/* LÍNEA DE TIEMPO DE AUDITORÍA */
.timeline-box {
    background-color: var(--hover-item) !important;
    border: 1.5px solid var(--border-subtle) !important;
    border-radius: 1.15rem;
    padding: 1.5rem;
    margin-top: 1rem;
}

.timeline-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-subtle);
    position: relative;
}

.timeline-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.timeline-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.75rem;
    background: rgba(255, 140, 90, 0.15);
    border: 1px solid rgba(255, 140, 90, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.action-tag {
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.15rem 0.5rem;
    border-radius: 0.4rem;
    background: rgba(237, 119, 69, 0.2);
    color: var(--brand-orange);
}

.data-error {
    padding: 0.9rem 1rem;
    margin-bottom: 1rem;
    border: 1px solid #ef4444;
    border-radius: 0.75rem;
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    font-weight: 700;
}
</style>

<div style="width: 100%; padding: 0 2rem; box-sizing: border-box;">
    <div class="container-box">

        <!-- ENCABEZADO DE LA SECCIÓN -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2.25rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <span style="font-size: 1.8rem;">🛡️</span>
                    <h1 style="font-size: 1.65rem; font-weight: 800; color: var(--text-main); margin: 0;">
                        Administradores e Historial de Moderación
                    </h1>
                </div>
                <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0.35rem 0 0 2.4rem;">
                    Gestión del equipo y revisiones de reportes registradas en el sistema
                </p>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <a href="/poliart/app/admin/crear_administrador.php" class="btn-create-admin">
                    <span>➕</span><span>Crear Administrador</span>
                </a>
                <a href="/poliart/app/admin/index.php" style="color: var(--brand-orange); font-size: 0.88rem; font-weight: 700; text-decoration: none;">
                    ← Volver al Panel
                </a>
            </div>
        </div>

        <?php if ($error_administradores !== ''): ?>
            <div class="data-error" role="alert"><?= htmlspecialchars($error_administradores, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($error_logs !== ''): ?>
            <div class="data-error" role="alert"><?= htmlspecialchars($error_logs, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- SECCIÓN 1: ADMINISTRADORES REGISTRADOS EN CARDS -->
        <section style="margin-bottom: 2.5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <h2 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <span>👥</span> Equipo de Administradores
                </h2>
                <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
                    <a href="/poliart/app/admin/auditoria.php" class="btn-audit-history">📋 Historial de Moderación</a>
                    <span style="font-size: 0.8rem; font-weight: 800; color: var(--brand-orange); background: rgba(255,140,90,0.15); padding: 0.25rem 0.75rem; border-radius: 9999px; border: 1px solid var(--border-subtle);">
                        <?= count($administradores) ?> Registrados
                    </span>
                </div>
            </div>

            <?php if (empty($administradores)): ?>
                <div class="timeline-box" style="text-align: center; padding: 2.5rem;">
                    <span style="font-size: 2.5rem; display: block; margin-bottom: 0.5rem;">🛡️</span>
                    <p style="color: var(--text-muted); margin: 0; font-weight: 700;">No se encontraron cuentas de administrador en la base de datos.</p>
                </div>
            <?php else: ?>
                <div class="admin-grid">
                    <?php foreach ($administradores as $admin): ?>
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <div class="admin-avatar">
                                    <?= htmlspecialchars(strtoupper(substr($admin['nombre'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div style="flex: 1; overflow: hidden;">
                                    <h3 style="font-size: 0.95rem; font-weight: 800; color: var(--text-main); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($admin['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    </h3>
                                    <span style="font-size: 0.8rem; color: var(--brand-orange); font-weight: 700;">
                                        @<?= htmlspecialchars($admin['nombre_usuario'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                            </div>

                            <div style="font-size: 0.82rem; color: var(--text-muted); display: flex; flex-direction: column; gap: 0.35rem; border-top: 1px solid var(--border-subtle); padding-top: 0.75rem;">
                                <div>✉️ <span style="color: var(--text-main); font-weight: 600;"><?= htmlspecialchars($admin['correo'], ENT_QUOTES, 'UTF-8') ?></span></div>
                                <div>📅 <span style="color: var(--text-muted);">Registro: <?= date('d/m/Y', strtotime($admin['creado_en'])) ?></span></div>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.25rem;">
                                <?php if ($admin['estado'] === 'activo'): ?>
                                    <span class="badge-pill badge-active">🟢 Activo</span>
                                <?php elseif ($admin['estado'] === 'suspendido'): ?>
                                    <span class="badge-pill badge-suspended">🔴 Suspendido</span>
                                <?php else: ?>
                                    <span class="badge-pill badge-suspended">⚪ Inactivo</span>
                                <?php endif; ?>

                                <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase;">
                                    Poli-Art Admin
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- SECCIÓN 2: HISTORIAL Y LOGS DE AUDITORÍA -->
        <section style="display:none;">
            <h2 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>📋</span> Historial de Moderación y Auditoría
            </h2>

            <div class="timeline-box">
                <?php if (empty($logs_auditoria)): ?>
                    <div style="text-align: center; padding: 2rem 1rem;">
                        <span style="font-size: 2.5rem; display: block; margin-bottom: 0.5rem;">🔍</span>
                        <h4 style="margin: 0 0 0.35rem 0; font-size: 1rem; color: var(--text-main); font-weight: 800;">Sin eventos registrados</h4>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
                            Las acciones realizadas por los administradores (bloqueo de usuarios, aprobación de obras, etc.) aparecerán reflejadas en esta línea de tiempo.
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($logs_auditoria as $log): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                ⚖️
                            </div>
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; margin-bottom: 0.25rem;">
                                    <span class="action-tag"><?= htmlspecialchars($log['accion'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span style="font-size: 0.85rem; font-weight: 800; color: var(--brand-orange);">
                                        por @<?= htmlspecialchars($log['admin_user'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                                <p style="font-size: 0.88rem; color: var(--text-main); margin: 0.25rem 0 0.35rem 0; line-height: 1.4; font-weight: 600;">
                                    <?= htmlspecialchars($log['descripcion'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">
                                    🕒 <?= date('d/m/Y \a \l\a\s H:i', strtotime($log['creado_en'])) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

    </div>
</div>

