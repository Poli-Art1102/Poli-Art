<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);
require_once __DIR__ . '/../artista/header.php';
include_once __DIR__ . '/../artista/sidebar.php';

$project_base = '/poliart/';

$invitaciones = [];
$errorInvitaciones = '';
try {
    $stmtArtista = getDB()->prepare('SELECT id_artista FROM artista WHERE id_usuario = :id_usuario');
    $stmtArtista->execute([':id_usuario' => getUserId()]);
    $idArtista = (int)$stmtArtista->fetchColumn();
    $stmtInvitaciones = getDB()->prepare(
        "SELECT p.id_proyecto AS id, p.titulo, p.descripcion, p.creado_en,
                COALESCE(NULLIF(a.nombre_artistico, ''), CONCAT(u.nombre, ' ', u.apellido)) AS invitador,
                a.especialidad, u.avatar_path, z.nombre AS ubicacion
         FROM participante_colaboracion pc
         INNER JOIN proyecto_colaborativo p ON p.id_proyecto = pc.id_proyecto
         INNER JOIN artista a ON a.id_artista = p.id_artista_creador
         INNER JOIN usuario u ON u.id_usuario = a.id_usuario
         LEFT JOIN zona z ON z.id_zona = p.id_zona
         WHERE pc.id_artista = :id_artista AND pc.estado_invitacion = 'pendiente' AND pc.activa = 1
         ORDER BY pc.invitado_en DESC"
    );
    $stmtInvitaciones->execute([':id_artista' => $idArtista]);
    $invitaciones = array_map(static function (array $inv): array {
        $inv['avatar'] = is_string($inv['avatar_path'])
            && strpos($inv['avatar_path'], '/poliart/uploads/avatars/') === 0
            ? $inv['avatar_path']
            : '';
        $inv['especialidad'] = $inv['especialidad'] ?: 'Artista';
        $inv['ubicacion'] = $inv['ubicacion'] ?: 'Sin zona';
        $inv['tiempo'] = date('d/m/Y', strtotime($inv['creado_en']));
        return $inv;
    }, $stmtInvitaciones->fetchAll());
} catch (PDOException $e) {
    error_log('Error al cargar invitaciones de colaboración: ' . $e->getMessage());
    $errorInvitaciones = 'No se pudieron cargar las invitaciones.';
}
?>

<style>
body {
    background-image: url('<?= $project_base ?>app/includes/madera.jpg') !important;
    background-size: cover !important;
    background-position: center !important;
    background-attachment: fixed !important;
    background-repeat: no-repeat !important;
}

.card-invitacion {
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    backdrop-filter: blur(8px);
    transition: transform 0.2s, background-color 0.3s ease, border-color 0.3s ease;
}

.card-invitacion:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 35px rgba(237, 119, 69, 0.15);
}

.btn-aceptar {
    background-color: #10b981;
    color: #ffffff;
    border: none;
    padding: 0.6rem 1.25rem;
    border-radius: 0.5rem;
    font-weight: 700;
    font-size: 0.875rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-aceptar:hover {
    background-color: #059669;
}

.btn-rechazar {
    background-color: var(--hover-item);
    color: var(--text-main);
    border: 1px solid var(--border-subtle);
    padding: 0.6rem 1.25rem;
    border-radius: 0.5rem;
    font-weight: 700;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-rechazar:hover {
    background-color: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    border-color: #fca5a5;
}
</style>

<div style="display: flex; width: 100%; min-height: calc(100vh - 4.25rem); background: transparent;">

    <div style="flex-grow: 1; padding: 2.5rem; overflow-y: auto;">
        
        <div style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">

            <!-- ENCABEZADO -->
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.6rem;">
                    📩 Invitaciones Recibidas
                    <span style="background-color: var(--brand-orange); color: #ffffff; font-size: 0.85rem; padding: 0.2rem 0.65rem; border-radius: 9999px; font-weight: 800;">
                        <?= count($invitaciones) ?>
                    </span>
                </h1>
            </div>

            <!-- LISTADO DE INVITACIONES -->
            <?php if ($errorInvitaciones !== ''): ?>
                <p role="alert" style="color:#dc2626;"><?= htmlspecialchars($errorInvitaciones, ENT_QUOTES, 'UTF-8') ?></p>
            <?php elseif (!$invitaciones): ?>
                <p style="color:var(--text-muted);">No tienes invitaciones pendientes.</p>
            <?php endif; ?>
            <?php foreach ($invitaciones as $inv): ?>
                <div class="card-invitacion">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        
                        <div style="display: flex; gap: 1rem; flex: 1; min-width: 280px;">
                            <img src="<?= htmlspecialchars($inv['avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($inv['invitador'], ENT_QUOTES, 'UTF-8') ?>" style="width: 3.5rem; height: 3.5rem; border-radius: 9999px; object-fit: cover; border: 2px solid var(--brand-orange);">
                            
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.2rem;">
                                    <span style="background-color: var(--hover-item); color: var(--brand-orange); border: 1px solid var(--border-subtle); padding: 0.15rem 0.5rem; border-radius: 0.375rem; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">
                                        Invitación Pendiente
                                    </span>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?= $inv['tiempo'] ?></span>
                                </div>

                                <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-main); margin: 0.2rem 0;">
                                    <?= htmlspecialchars($inv['titulo'], ENT_QUOTES, 'UTF-8') ?>
                                </h2>

                                <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0;">
                                    Invitado por <strong style="color: var(--text-main);"><?= htmlspecialchars($inv['invitador'], ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($inv['especialidad'], ENT_QUOTES, 'UTF-8') ?>) &bull; 📍 <span style="color: var(--brand-orange); font-weight: 700;"><?= htmlspecialchars($inv['ubicacion'], ENT_QUOTES, 'UTF-8') ?></span>
                                </p>
                            </div>
                        </div>

                        <!-- ACCIONES -->
                        <div style="display: flex; gap: 0.6rem;">
                            <button class="btn-aceptar" onclick="responderInvitacion(<?= (int)$inv['id'] ?>, 'aceptar')">
                                ✅ Aceptar
                            </button>
                            <button class="btn-rechazar" onclick="responderInvitacion(<?= (int)$inv['id'] ?>, 'rechazar')">
                                ❌ Rechazar
                            </button>
                        </div>

                    </div>

                    <!-- MENSAJE DE LA INVITACIÓN -->
                    <div style="margin-top: 1.25rem; background-color: var(--hover-item); border: 1px solid var(--border-subtle); border-radius: 0.5rem; padding: 1rem; font-size: 0.9rem; color: var(--text-main); font-style: italic;">
                        "<?= htmlspecialchars($inv['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    </div>
                </div>
            <?php endforeach; ?>

        </div>

    </div>
</div>

<script>
async function responderInvitacion(id, respuesta) {
    if (respuesta === 'rechazar' && !window.confirm('¿Rechazar esta invitación?')) return;
    const data = new FormData();
    data.set('accion', 'responder');
    data.set('id_proyecto', id);
    data.set('respuesta', respuesta);
    try {
        const response = await fetch('../includes/actions/gestion_colaboracion.php', { method: 'POST', body: data });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.error || 'No se pudo actualizar la invitación.');
        window.location.reload();
    } catch (error) {
        window.alert(error.message || 'No se pudo actualizar la invitación.');
    }
}
</script>


