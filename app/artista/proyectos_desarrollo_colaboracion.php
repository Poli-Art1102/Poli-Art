<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);
require_once __DIR__ . '/../artista/header.php';
include_once __DIR__ . '/../artista/sidebar.php';

$project_base = '/poliart/';

$proyectos_colaborativos = [];
$errorProyectos = '';
try {
    $stmtArtista = getDB()->prepare('SELECT id_artista FROM artista WHERE id_usuario = :id_usuario');
    $stmtArtista->execute([':id_usuario' => getUserId()]);
    $idArtista = (int)$stmtArtista->fetchColumn();
    $stmtProyectos = getDB()->prepare(
        "SELECT p.id_proyecto AS id, p.titulo, p.progreso, p.estado,
                COALESCE(NULLIF(otro.nombre_artistico, ''), CONCAT(u.nombre, ' ', u.apellido)) AS colaborador,
                u.avatar_path
         FROM proyecto_colaborativo p
         INNER JOIN participante_colaboracion propia
            ON propia.id_proyecto = p.id_proyecto
           AND propia.id_artista = :id_artista
           AND propia.estado_invitacion = 'aceptada'
           AND propia.activa = 1
         INNER JOIN (
             SELECT id_proyecto, MIN(id_artista) AS id_artista
             FROM participante_colaboracion
             WHERE id_artista <> :id_artista_otro
               AND estado_invitacion = 'aceptada'
               AND activa = 1
             GROUP BY id_proyecto
         ) pc ON pc.id_proyecto = p.id_proyecto
         INNER JOIN artista otro ON otro.id_artista = pc.id_artista
         INNER JOIN usuario u ON u.id_usuario = otro.id_usuario
         WHERE p.estado = 'activo'
         ORDER BY p.actualizado_en DESC, p.id_proyecto DESC"
    );
    $stmtProyectos->execute([':id_artista' => $idArtista, ':id_artista_otro' => $idArtista]);
    $proyectos_colaborativos = array_map(static function (array $project): array {
        $project['avatar'] = is_string($project['avatar_path'])
            && strpos($project['avatar_path'], '/poliart/uploads/avatars/') === 0
            ? $project['avatar_path']
            : '';
        $project['inicial'] = mb_strtoupper(
            mb_substr($project['colaborador'] !== '' ? $project['colaborador'] : 'A', 0, 1, 'UTF-8'),
            'UTF-8'
        );
        $project['estado'] = $project['estado'] === 'completado' ? 'Completado' : 'En desarrollo';
        $project['avance'] = min(100, max(0, (int)$project['progreso']));
        return $project;
    }, $stmtProyectos->fetchAll());
} catch (PDOException $e) {
    error_log('Error al cargar proyectos colaborativos: ' . $e->getMessage());
    $errorProyectos = 'No se pudieron cargar los proyectos colaborativos.';
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

.project-card {
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1rem;
    padding: 1.75rem 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    backdrop-filter: blur(8px);
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

.progress-bar-bg {
    width: 100%;
    height: 0.65rem;
    background-color: var(--hover-item);
    border: 1px solid var(--border-subtle);
    border-radius: 9999px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #ea580c 0%, var(--brand-orange) 100%);
    border-radius: 9999px;
}

.btn-abrir-mesa {
    display: block;
    width: 100%;
    text-align: center;
    padding: 0.75rem;
    background-color: var(--hover-item);
    border: 1px solid var(--border-subtle);
    border-radius: 0.5rem;
    color: var(--text-main);
    font-size: 0.9rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.btn-abrir-mesa:hover {
    background-color: var(--bg-body);
    border-color: var(--brand-orange);
    color: var(--brand-orange);
}

.collaborations-page {
    display:flex;
    width:100%;
    min-width:0;
    height:calc(100vh - 4.25rem);
    overflow:hidden;
}

.collaborations-content {
    flex:1;
    min-width:0;
    height:100%;
    overflow-y:auto;
    padding:2.5rem;
    box-sizing:border-box;
}

@media (max-width:640px) {
    .collaborations-content { padding:1rem; }
    .project-card { padding:1.1rem; }
}
</style>

<div class="collaborations-page">

    <div class="collaborations-content">
        
        <div style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.75rem;">

            <div>
                <h1 style="font-size: 1.875rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    🤝 Proyectos Activos en Conjunto
                </h1>
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.3rem;">
                    Gestiona las mesas de trabajo y avances de tus obras compartidas.
                </p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <?php if ($errorProyectos !== ''): ?>
                    <p role="alert" style="color:#dc2626;"><?= htmlspecialchars($errorProyectos, ENT_QUOTES, 'UTF-8') ?></p>
                <?php elseif (!$proyectos_colaborativos): ?>
                    <p style="color:var(--text-muted);">No tienes proyectos colaborativos activos.</p>
                <?php endif; ?>
                <?php foreach ($proyectos_colaborativos as $p): ?>
                    <div class="project-card">
                        
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <?php if ($p['avatar'] !== ''): ?>
                                    <img src="<?= htmlspecialchars($p['avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($p['colaborador'], ENT_QUOTES, 'UTF-8') ?>" style="width: 3rem; height: 3rem; border-radius: 9999px; object-fit: cover; border: 2px solid var(--brand-orange);">
                                <?php else: ?>
                                    <span aria-hidden="true" style="display:grid;place-items:center;width:3rem;height:3rem;flex:none;border-radius:9999px;background:linear-gradient(135deg,var(--brand-orange),#ea580c);color:white;font-weight:800;border:2px solid var(--brand-orange);">
                                        <?= htmlspecialchars($p['inicial'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endif; ?>
                                <div>
                                    <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--text-main); margin: 0;">
                                        <?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?>
                                    </h3>
                                    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.1rem 0 0 0;">
                                        Con <strong style="color: var(--text-main);"><?= htmlspecialchars($p['colaborador'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    </p>
                                </div>
                            </div>

                            <span style="background-color: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); padding: 0.3rem 0.8rem; border-radius: 9999px; font-size: 0.775rem; font-weight: 700;">
                                <?= $p['estado'] ?>
                            </span>
                        </div>

                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.4rem;">
                                <span>Avance del proyecto</span>
                                <span style="color: var(--brand-orange);"><?= $p['avance'] ?>%</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width: <?= $p['avance'] ?>%;"></div>
                            </div>
                        </div>

                        <a href="mesa_trabajo.php?id=<?= $p['id'] ?>" class="btn-abrir-mesa">
                            Abrir Mesa de Trabajo
                        </a>

                    </div>
                <?php endforeach; ?>
            </div>

        </div>

    </div>
</div>


