<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['administrador']);
require_once __DIR__ . '/../admin/header.php';

$project_base = '/poliart/';

$errorActividad = '';
try {
    $historial_actividades = getDB()->query(
        "SELECT tipo, icono, texto, zona, fecha FROM (
            SELECT 'Obra' AS tipo, '🖼️' AS icono,
                   CONCAT('Obra \"', o.titulo, '\" publicada por @', u.nombre_usuario) AS texto,
                   COALESCE(z.nombre, 'Sin zona') AS zona, COALESCE(o.publicada_en, o.creado_en) AS fecha
            FROM obra o
            INNER JOIN artista a ON a.id_artista = o.id_artista
            INNER JOIN usuario u ON u.id_usuario = a.id_usuario
            LEFT JOIN zona z ON z.id_zona = o.id_zona
            WHERE o.estado_publicacion = 'publicada'
            UNION ALL
            SELECT CASE u.rol WHEN 'cliente' THEN 'Cliente' ELSE 'Artista' END,
                   CASE u.rol WHEN 'cliente' THEN '👤' ELSE '🎨' END,
                   CONCAT('Nueva cuenta registrada: @', u.nombre_usuario),
                   COALESCE(z.nombre, 'Sin zona'), u.creado_en
            FROM usuario u
            LEFT JOIN zona z ON z.id_zona = u.id_zona
            WHERE u.rol IN ('cliente', 'artista')
            UNION ALL
            SELECT 'Reporte', '⚠️',
                   CONCAT('Reporte #', r.id_reporte, ' (', r.estado, '): ', r.motivo, ' — ', o.titulo),
                   COALESCE(z.nombre, 'Sin zona'), r.creado_en
            FROM reporte_contenido r
            INNER JOIN obra o ON o.id_obra = r.id_obra
            LEFT JOIN zona z ON z.id_zona = o.id_zona
            UNION ALL
            SELECT 'Sistema', '🛡️',
                   COALESCE(NULLIF(l.descripcion, ''), l.accion),
                   'Plataforma Global', l.creado_en
            FROM administrador_log l
         ) actividades
         ORDER BY fecha DESC
         LIMIT 100"
    )->fetchAll();
    foreach ($historial_actividades as &$actividad) {
        $timestamp = strtotime($actividad['fecha']);
        $actividad['fecha'] = date('d/m/Y H:i', $timestamp);
        $minutos = max(0, (int)floor((time() - $timestamp) / 60));
        if ($minutos < 60) {
            $actividad['tiempo'] = $minutos < 1 ? 'Ahora' : 'Hace ' . $minutos . ' min';
        } elseif ($minutos < 1440) {
            $actividad['tiempo'] = 'Hace ' . (int)floor($minutos / 60) . ' h';
        } else {
            $actividad['tiempo'] = 'Hace ' . (int)floor($minutos / 1440) . ' días';
        }
    }
    unset($actividad);
} catch (PDOException $e) {
    error_log('Error al cargar actividad administrativa: ' . $e->getMessage());
    $historial_actividades = [];
    $errorActividad = 'No se pudo cargar el historial de actividad.';
}
?>

<style>
.activities-wrapper {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.5rem;
    box-sizing: border-box;
}

.activities-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.activities-card {
    background-color: var(--bg-surface) !important;
    border: 1px solid var(--border-subtle) !important;
    border-radius: 1.25rem;
    padding: 1.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(12px);
}

.search-input-box {
    display: flex;
    align-items: center;
    background-color: var(--hover-item);
    border: 1px solid var(--border-subtle);
    border-radius: 9999px;
    padding: 0.5rem 1.25rem;
    width: 320px;
}

.search-input-box input {
    background: transparent;
    border: none;
    outline: none;
    color: var(--text-main);
    width: 100%;
    font-size: 0.85rem;
}

.filter-pills-row {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
}

.filter-pill-btn {
    background: var(--hover-item);
    border: 1px solid var(--border-subtle);
    color: var(--text-muted);
    padding: 0.35rem 0.85rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-pill-btn:hover, .filter-pill-btn.active {
    background: var(--brand-orange);
    color: #ffffff;
    border-color: var(--brand-orange);
}

.activity-full-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-subtle);
    transition: background-color 0.2s ease;
}

.activity-full-row:hover {
    background-color: var(--hover-item);
    padding-left: 0.5rem;
    padding-right: 0.5rem;
    border-radius: 0.5rem;
}

.activity-full-row:last-child {
    border-bottom: none;
}

.badge-tipo {
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
}

.badge-obra { background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); }
.badge-reporte { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
.badge-cliente { background: rgba(168, 85, 247, 0.15); color: #a855f7; border: 1px solid rgba(168, 85, 247, 0.3); }
.badge-artista { background: rgba(249, 115, 22, 0.15); color: var(--brand-orange); border: 1px solid rgba(249, 115, 22, 0.3); }
.badge-sistema { background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); }
.badge-reseña { background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); }
</style>

<div class="activities-wrapper">
    
    <!-- ENCABEZADO Y BUSCADOR -->
    <div class="activities-header">
        <div>
            <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; font-weight: 700;">&larr; Volver al Panel General</a>
            <h1 style="font-size: 1.6rem; font-weight: 900; color: var(--text-main); margin: 0.2rem 0 0 0;">
                📜 Historial Completo de Actividades
            </h1>
        </div>

        <div class="search-input-box">
            <span style="margin-right: 0.5rem; color: var(--text-muted);">🔍</span>
            <input type="text" id="activitySearch" placeholder="Buscar por usuario, obra o comuna..." onkeyup="filterActivities()">
        </div>
    </div>

    <!-- TARJETA CONTENEDORA -->
    <div class="activities-card">
        
        <!-- BOTONES DE FILTRADO RÁPIDO -->
        <div class="filter-pills-row">
            <button class="filter-pill-btn active" onclick="setFilterCategory('todos', this)">Todos</button>
            <button class="filter-pill-btn" onclick="setFilterCategory('Obra', this)">Obras</button>
            <button class="filter-pill-btn" onclick="setFilterCategory('Reporte', this)">Reportes</button>
            <button class="filter-pill-btn" onclick="setFilterCategory('Cliente', this)">Clientes</button>
            <button class="filter-pill-btn" onclick="setFilterCategory('Artista', this)">Artistas</button>
            <button class="filter-pill-btn" onclick="setFilterCategory('Sistema', this)">Sistema</button>
        </div>

        <!-- LISTADO DE ACTIVIDADES -->
        <div id="activitiesList">
            <?php if ($errorActividad !== ''): ?>
                <p role="alert" style="color:#dc2626;"><?= htmlspecialchars($errorActividad, ENT_QUOTES, 'UTF-8') ?></p>
            <?php elseif (!$historial_actividades): ?>
                <p style="color:var(--text-muted);">Todavía no hay actividad registrada.</p>
            <?php endif; ?>
            <?php foreach ($historial_actividades as $act): ?>
                <?php 
                    $badge_class = 'badge-sistema';
                    if ($act['tipo'] === 'Obra') $badge_class = 'badge-obra';
                    if ($act['tipo'] === 'Reporte') $badge_class = 'badge-reporte';
                    if ($act['tipo'] === 'Cliente') $badge_class = 'badge-cliente';
                    if ($act['tipo'] === 'Artista') $badge_class = 'badge-artista';
                    if ($act['tipo'] === 'Reseña') $badge_class = 'badge-reseña';
                ?>
                <div class="activity-full-row" 
                     data-type="<?= htmlspecialchars($act['tipo'], ENT_QUOTES, 'UTF-8') ?>"
                     data-text="<?= htmlspecialchars(mb_strtolower($act['texto'] . ' ' . $act['zona'] . ' ' . $act['tipo'], 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span style="font-size: 1.5rem;"><?= $act['icono'] ?></span>
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.2rem;">
                                <span class="badge-tipo <?= $badge_class ?>"><?= htmlspecialchars($act['tipo'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;"><?= htmlspecialchars($act['zona'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-main);">
                                <?= htmlspecialchars($act['texto'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                    </div>
                    <div style="text-align: right; flex-shrink: 0; margin-left: 1rem;">
                        <span style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--brand-orange);"><?= htmlspecialchars($act['tiempo'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span style="font-size: 0.7rem; color: var(--text-muted);"><?= htmlspecialchars($act['fecha'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

</div>

<script>
let currentCategory = 'todos';

function setFilterCategory(category, btnElement) {
    currentCategory = category;
    
    // Cambiar estado activo de los botones
    document.querySelectorAll('.filter-pill-btn').forEach(btn => btn.classList.remove('active'));
    btnElement.classList.add('active');

    filterActivities();
}

function filterActivities() {
    const query = document.getElementById('activitySearch').value.toLowerCase();
    const rows = document.querySelectorAll('.activity-full-row');

    rows.forEach(row => {
        const text = row.getAttribute('data-text');
        const type = row.getAttribute('data-type');

        const matchesQuery = text.includes(query);
        const matchesType = (currentCategory === 'todos' || type === currentCategory);

        if (matchesQuery && matchesType) {
            row.style.display = 'flex';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

