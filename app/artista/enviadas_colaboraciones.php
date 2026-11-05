<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);
require_once 'header.php';

$project_base = '/poliart/';

$colaboraciones_enviadas = [];
$errorColaboraciones = '';
try {
    $stmtArtista = getDB()->prepare('SELECT id_artista FROM artista WHERE id_usuario = :id_usuario');
    $stmtArtista->execute([':id_usuario' => getUserId()]);
    $idArtista = (int)$stmtArtista->fetchColumn();
    $stmtEnviadas = getDB()->prepare(
        "SELECT p.id_proyecto AS id, p.titulo, p.descripcion, p.creado_en,
                pc.estado_invitacion, COALESCE(NULLIF(a.nombre_artistico, ''), CONCAT(u.nombre, ' ', u.apellido)) AS destinatario,
                u.avatar_path
         FROM proyecto_colaborativo p
         INNER JOIN participante_colaboracion pc ON pc.id_proyecto = p.id_proyecto
         INNER JOIN artista a ON a.id_artista = pc.id_artista
         INNER JOIN usuario u ON u.id_usuario = a.id_usuario
         WHERE p.id_artista_creador = :id_artista AND pc.id_artista <> :id_invitador
         ORDER BY p.creado_en DESC"
    );
    $stmtEnviadas->execute([':id_artista' => $idArtista, ':id_invitador' => $idArtista]);
    $colaboraciones_enviadas = array_map(static function (array $collab): array {
        $colors = ['pendiente' => '#f59e0b', 'aceptada' => '#16a34a', 'rechazada' => '#dc2626'];
        $labels = ['pendiente' => 'Pendiente', 'aceptada' => 'Aceptada', 'rechazada' => 'Rechazada'];
        $collab['estado'] = $labels[$collab['estado_invitacion']] ?? 'Desconocido';
        $collab['estado_color'] = $colors[$collab['estado_invitacion']] ?? '#64748b';
        $collab['fecha'] = date('d/m/Y', strtotime($collab['creado_en']));
        $collab['avatar_destino'] = is_string($collab['avatar_path'])
            && strpos($collab['avatar_path'], '/poliart/uploads/avatars/') === 0
            ? $collab['avatar_path']
            : '';
        return $collab;
    }, $stmtEnviadas->fetchAll());
} catch (PDOException $e) {
    error_log('Error al cargar propuestas enviadas: ' . $e->getMessage());
    $errorColaboraciones = 'No se pudieron cargar las propuestas enviadas.';
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

.collab-grid {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    width: 100%;
}

.collab-card {
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    display: flex;
    flex-direction: column;
    gap: 1rem;
    transition: transform 0.2s ease, background-color 0.3s ease, border-color 0.3s ease;
}

.collab-card:hover {
    transform: translateY(-2px);
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
    transition: background-color 0.3s ease, border-color 0.3s ease;
}
</style>

<div style="display: flex; width: 100vw; height: calc(100vh - 4.25rem); overflow: hidden; background: transparent;">

    <?php include_once 'sidebar.php'; ?>

    <div style="flex-grow: 1; height: 100%; overflow-y: auto; padding: 2.5rem; box-sizing: border-box;">
        
        <div style="max-width: 1000px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.75rem;">

            <div class="header-banner">
                <div>
                    <h1 style="font-size: 1.875rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.6rem;">
                        📤 Colaboraciones Enviadas
                    </h1>
                    <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.3rem;">
                        Gestiona, revisa el estado y haz seguimiento de las propuestas artísticas enviadas a otros creadores.
                    </p>
                </div>
                
            </div>

            <div class="collab-grid">
                <?php if ($errorColaboraciones !== ''): ?>
                    <p role="alert" style="color:#dc2626;"><?= htmlspecialchars($errorColaboraciones, ENT_QUOTES, 'UTF-8') ?></p>
                <?php elseif (!$colaboraciones_enviadas): ?>
                    <p style="color:var(--text-muted);">Aún no has enviado propuestas de colaboración.</p>
                <?php endif; ?>
                <?php foreach ($colaboraciones_enviadas as $c): ?>
                    <div class="collab-card">
                        
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
                            <div style="display: flex; flex-direction: column; gap: 0.3rem;">
                                <span style="font-size: 0.775rem; font-weight: 700; color: var(--text-muted);">
                                    <?= htmlspecialchars($c['fecha'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin: 0;">
                                    <?= htmlspecialchars($c['titulo'], ENT_QUOTES, 'UTF-8') ?>
                                </h3>
                            </div>

                            <span style="background-color: <?= $c['estado_color'] ?>20; color: <?= $c['estado_color'] ?>; border: 1px solid <?= $c['estado_color'] ?>40; padding: 0.3rem 0.8rem; border-radius: 9999px; font-size: 0.775rem; font-weight: 800;">
                                ? <?= $c['estado'] ?>
                            </span>
                        </div>

                        <p style="font-size: 0.9rem; color: var(--text-muted); margin: 0; line-height: 1.4;">
                            <?= htmlspecialchars($c['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--border-subtle); padding-top: 1rem; margin-top: 0.25rem; flex-wrap: wrap; gap: 1rem;">
                            
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <img src="<?= htmlspecialchars($c['avatar_destino'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($c['destinatario'], ENT_QUOTES, 'UTF-8') ?>" style="width: 32px; height: 32px; border-radius: 9999px; object-fit: cover; border: 2px solid var(--border-subtle);">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-size: 0.7rem; color: var(--text-muted);">Destinatario</span>
                                    <span style="font-size: 0.85rem; font-weight: 700; color: var(--text-main);">
                                        <?= htmlspecialchars($c['destinatario'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                            </div>

                            <button type="button" onclick="window.location.href='proyectos_desarrollo_colaboracion.php?id=<?= (int)$c['id'] ?>'" style="background: var(--hover-item); border: 1px solid var(--border-subtle); color: var(--text-main); padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 700; cursor: pointer; transition: background-color 0.2s ease;">
                                Ver Detalles ?
                            </button>

                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

        </div>

    </div>
</div>


