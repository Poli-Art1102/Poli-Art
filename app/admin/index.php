<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['administrador']);
require_once __DIR__ . '/header.php';

$project_base = '/poliart/';
$pdo = getDB();

try {
    $stats = [
        'clientes_registrados' => (int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE rol='cliente'")->fetchColumn(),
        'artistas_verificados' => (int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE rol='artista' AND estado='activo'")->fetchColumn(),
        'reportes_activos'     => (int)$pdo->query("SELECT COUNT(*) FROM reporte_contenido WHERE estado='pendiente'")->fetchColumn(),
        'comunas_cubiertas'    => (int)$pdo->query("SELECT COUNT(DISTINCT id_zona) FROM obra WHERE id_zona IS NOT NULL")->fetchColumn(),
        'usuarios_bloqueados'  => (int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE estado='suspendido'")->fetchColumn(),
        'total_obras'          => (int)$pdo->query("SELECT COUNT(*) FROM obra WHERE estado_publicacion='publicada'")->fetchColumn(),
        'total_ventas'         => (int)$pdo->query("SELECT COUNT(*) FROM pedido WHERE estado_pedido='completado'")->fetchColumn(),
        'ingreso_comisiones'   => (float)$pdo->query("SELECT COALESCE(SUM(total_comision),0) FROM pedido WHERE estado_pedido='completado'")->fetchColumn(),
    ];

    $ultimas_actividades = [];

    $stmt = $pdo->query(
        "SELECT CONCAT('Nueva obra \"', o.titulo, '\" de @', u.nombre_usuario) AS texto, o.publicada_en AS ts
         FROM obra o JOIN artista a ON a.id_artista = o.id_artista JOIN usuario u ON u.id_usuario = a.id_usuario
         WHERE o.estado_publicacion = 'publicada' ORDER BY o.publicada_en DESC LIMIT 3"
    );
    foreach ($stmt->fetchAll() as $row) {
        $diff = $row['ts'] ? time() - strtotime($row['ts']) : 0;
        $tiempo = $diff < 3600 ? 'Hace '.round($diff/60).' min' : ($diff < 86400 ? 'Hace '.round($diff/3600).' h' : 'Hace '.round($diff/86400).' día(s)');
        $ultimas_actividades[] = ['icono' => '🖼️', 'texto' => $row['texto'], 'tiempo' => $tiempo];
    }

    $stmt = $pdo->query(
        "SELECT CONCAT('Obra reportada: \"', o.titulo, '\" por @', u.nombre_usuario) AS texto, r.creado_en AS ts
         FROM reporte_contenido r JOIN obra o ON o.id_obra = r.id_obra JOIN usuario u ON u.id_usuario = r.id_reportante
         WHERE r.estado = 'pendiente' ORDER BY r.creado_en DESC LIMIT 2"
    );
    foreach ($stmt->fetchAll() as $row) {
        $diff = time() - strtotime($row['ts']);
        $tiempo = $diff < 3600 ? 'Hace '.round($diff/60).' min' : ($diff < 86400 ? 'Hace '.round($diff/3600).' h' : 'Hace '.round($diff/86400).' día(s)');
        $ultimas_actividades[] = ['icono' => '⚠️', 'texto' => $row['texto'], 'tiempo' => $tiempo];
    }

    $stmt = $pdo->query(
        "SELECT nombre_usuario, rol, creado_en AS ts FROM usuario WHERE rol != 'administrador' ORDER BY creado_en DESC LIMIT 3"
    );
    foreach ($stmt->fetchAll() as $row) {
        $icono = $row['rol'] === 'artista' ? '🎨' : '👤';
        $diff  = time() - strtotime($row['ts']);
        $tiempo = $diff < 3600 ? 'Hace '.round($diff/60).' min' : ($diff < 86400 ? 'Hace '.round($diff/3600).' h' : 'Hace '.round($diff/86400).' día(s)');
        $ultimas_actividades[] = ['icono' => $icono, 'texto' => "Nuevo {$row['rol']}: @{$row['nombre_usuario']}", 'tiempo' => $tiempo];
    }

    usort($ultimas_actividades, fn($a,$b) => strcmp($a['tiempo'], $b['tiempo']));

} catch (PDOException $e) {
    $stats = array_fill_keys(['clientes_registrados','artistas_verificados','reportes_activos',
        'comunas_cubiertas','usuarios_bloqueados','total_obras',
        'total_ventas','ingreso_comisiones'], 0);
    $ultimas_actividades = [];
}
?>

<style>
.dashboard-container {
    background-color: var(--bg-surface) !important;
    border: 1px solid var(--border-subtle) !important;
    border-radius: 1.25rem;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    backdrop-filter: blur(12px);
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    box-sizing: border-box;
}
.stat-card-link { text-decoration: none; display: flex; flex-direction: column; }
.stat-card {
    background-color: var(--hover-item) !important;
    border: 1px solid var(--border-subtle) !important;
    border-radius: 1rem;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.2s ease, border-color 0.2s ease;
    height: 100%;
    box-sizing: border-box;
}
.stat-card:hover { transform: translateY(-3px); border-color: var(--brand-orange) !important; }
.quick-action-btn {
    background-color: var(--hover-item) !important;
    border: 1px solid var(--border-subtle) !important;
    padding: 0.95rem 1.15rem;
    border-radius: 0.85rem;
    text-decoration: none;
    color: var(--text-main) !important;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 700;
    font-size: 0.88rem;
    transition: all 0.2s ease;
}
.quick-action-btn:hover {
    background: linear-gradient(135deg, var(--brand-orange) 0%, #ea580c 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 15px rgba(249,115,22,0.3);
}

/* BOTÓN DE CREAR ADMINISTRADOR CON NARANJA CÁLIDO Y SUAVE */
.btn-create-admin {
    background: linear-gradient(135deg, #FF8C5A 0%, #FF6B35 100%) !important;
    color: #ffffff !important;
    padding: 0.55rem 1.15rem;
    border-radius: 0.75rem;
    font-weight: 800;
    font-size: 0.85rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    box-shadow: 0 4px 14px rgba(255, 107, 53, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.25);
    transition: transform 0.2s ease, opacity 0.2s ease, box-shadow 0.2s ease;
}
.btn-create-admin:hover {
    transform: translateY(-2px);
    opacity: 0.95;
    box-shadow: 0 6px 18px rgba(255, 107, 53, 0.42);
}

.activity-box {
    background-color: var(--hover-item) !important;
    border: 1px solid var(--border-subtle) !important;
    padding: 1.5rem;
    border-radius: 1rem;
}
.activity-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.85rem 0;
    border-bottom: 1px solid var(--border-subtle);
}
.activity-item:last-child { border-bottom: none; }
</style>

<div style="width: 100%; padding: 0 2rem; box-sizing: border-box;">
    <div class="dashboard-container">

        <!-- ENCABEZADO -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin: 0;">
                    🛡️ Panel de Administración General
                </h1>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0.25rem 0 0 0;">
                    Centro de analítica y operaciones de Poli-Art Medellín
                </p>
            </div>
            
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <a href="/poliart/app/admin/crear_administrador.php" class="btn-create-admin">
                    <span>➕</span><span>Crear Nuevo Administrador</span>
                </a>
                <span style="font-size: 0.8rem; color: var(--brand-orange); font-weight: 700; background: var(--hover-item); padding: 0.45rem 0.9rem; border-radius: 9999px; border: 1px solid var(--border-subtle);">
                    🟢 Sistema Operativo
                </span>
            </div>
        </div>

        <!-- TARJETAS DE ESTADÍSTICAS -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">

            <a href="/poliart/app/admin/dashboard_clientes.php" class="stat-card-link">
                <div class="stat-card">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Clientes</span>
                        <span style="font-size: 1.2rem;">🛍️</span>
                    </div>
                    <span style="font-size: 1.75rem; font-weight: 800; color: var(--text-main); margin-top: 0.5rem; display: block;"><?= $stats['clientes_registrados'] ?></span>
                    <span style="font-size: 0.75rem; color: var(--brand-orange); font-weight: 700; margin-top: 0.25rem;">Compradores activos →</span>
                </div>
            </a>

            <a href="/poliart/app/admin/dashboard_artistas.php" class="stat-card-link">
                <div class="stat-card">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Artistas</span>
                        <span style="font-size: 1.2rem;">🎨</span>
                    </div>
                    <span style="font-size: 1.75rem; font-weight: 800; color: var(--brand-orange); margin-top: 0.5rem; display: block;"><?= $stats['artistas_verificados'] ?></span>
                    <span style="font-size: 0.75rem; color: var(--brand-orange); font-weight: 700; margin-top: 0.25rem;">Creadores en Medellín →</span>
                </div>
            </a>

            <a href="/poliart/app/admin/dashboard_reportes.php" class="stat-card-link">
                <div class="stat-card">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Reportes Activos</span>
                        <span style="font-size: 1.2rem;">⚠️</span>
                    </div>
                    <span style="font-size: 1.75rem; font-weight: 800; color: #ef4444; margin-top: 0.5rem; display: block;"><?= $stats['reportes_activos'] ?></span>
                    <span style="font-size: 0.75rem; color: #ef4444; font-weight: 700; margin-top: 0.25rem;">Dashboard de Reportes →</span>
                </div>
            </a>

            <a href="/poliart/app/admin/dashboard_zonas.php" class="stat-card-link">
                <div class="stat-card">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Zonas / Comunas</span>
                        <span style="font-size: 1.2rem;">📍</span>
                    </div>
                    <span style="font-size: 1.75rem; font-weight: 800; color: #06b6d4; margin-top: 0.5rem; display: block;"><?= $stats['comunas_cubiertas'] ?></span>
                    <span style="font-size: 0.75rem; color: #06b6d4; font-weight: 700; margin-top: 0.25rem;">Cobertura en Medellín →</span>
                </div>
            </a>

        </div>

        <!-- ACCIONES RÁPIDAS Y ACTIVIDAD RECIENTE -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">

            <!-- ACCIONES RÁPIDAS -->
            <div class="activity-box">
                <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin: 0 0 1rem 0;">⚡ Acciones Rápidas</h3>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">

                    <a href="/poliart/app/admin/administradores_log.php" class="quick-action-btn btn">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span>🛡️</span><span>Administradores y Logs de Auditoría</span>
                        </div>
                        <span style="color: var(--text-muted); font-size: 0.8rem;">Ver acciones →</span>
                    </a>

                    <a href="/poliart/app/admin/obras_reportadas.php" class="quick-action-btn btn">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span>⚠️</span><span>Revisar Obras Reportadas</span>
                        </div>
                        <span style="background: rgba(239,68,68,0.2); color: #ef4444; padding: 0.15rem 0.55rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 800;"><?= $stats['reportes_activos'] ?></span>
                    </a>

                    <a href="/poliart/app/admin/usuarios_bloqueados.php" class="quick-action-btn btn">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span>🚫</span><span>Usuarios Bloqueados / Sancionados</span>
                        </div>
                        <span style="color: var(--text-muted); font-size: 0.8rem;"><?= $stats['usuarios_bloqueados'] ?></span>
                    </a>

                    <a href="/poliart/app/admin/anuncio_global.php" class="quick-action-btn btn">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span>📢</span><span>Publicar Anuncio Global</span>
                        </div>
                        <span style="color: var(--text-muted); font-size: 0.8rem;">→</span>
                    </a>

                    <a href="/poliart/app/admin/usuarios.php" class="quick-action-btn btn">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span>👥</span><span>Gestionar Usuarios</span>
                        </div>
                        <span style="color: var(--text-muted); font-size: 0.8rem;">→</span>
                    </a>

                </div>
            </div>

            <!-- ACTIVIDAD RECIENTE -->
            <div class="activity-box">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin: 0;">🔔 Actividad Reciente</h3>
                    <a href="/poliart/app/admin/actividad.php" style="font-size: 0.78rem; font-weight: 700; color: var(--brand-orange); text-decoration: none;">Ver todo →</a>
                </div>

                <?php if (empty($ultimas_actividades)): ?>
                    <p style="color: var(--text-muted); font-size: 0.875rem; text-align: center; padding: 1rem 0;">Sin actividad reciente.</p>
                <?php else: ?>
                    <?php foreach ($ultimas_actividades as $act): ?>
                        <div class="activity-item">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span style="font-size: 1.2rem;"><?= $act['icono'] ?></span>
                                <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($act['texto']) ?></span>
                            </div>
                            <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; white-space: nowrap;"><?= $act['tiempo'] ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

    </div>
</div>


