<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);
require_once __DIR__ . '/../artista/header.php';
include_once __DIR__ . '/../artista/sidebar.php';

$project_base = '/poliart/';

$seguidores = [];

try {
    $stmtSeguidores = getDB()->prepare(
        'SELECT u.id_usuario AS id, u.nombre_usuario AS usuario,
                CONCAT(u.nombre, \' \', u.apellido) AS nombre,
                u.avatar_path AS avatar, z.nombre AS zona, u.rol,
                a.id_artista,
                EXISTS(
                    SELECT 1 FROM seguimiento seg
                    WHERE seg.id_seguidor = :id_actual AND seg.id_seguido = u.id_usuario
                ) AS siguiendo
         FROM seguimiento s
         INNER JOIN usuario u ON u.id_usuario = s.id_seguidor
         LEFT JOIN artista a ON a.id_usuario = u.id_usuario
         LEFT JOIN zona z ON z.id_zona = u.id_zona
         WHERE s.id_seguido = :id_artista
         ORDER BY s.creado_en DESC'
    );
    $stmtSeguidores->execute([
        ':id_actual' => getUserId(),
        ':id_artista' => getUserId(),
    ]);
    $seguidores = array_map(static function (array $seguidor): array {
        $seguidor['avatar'] = is_string($seguidor['avatar'])
            && strpos($seguidor['avatar'], '/poliart/uploads/avatars/') === 0
            ? $seguidor['avatar']
            : '';
        $seguidor['zona'] = $seguidor['zona'] ?? 'Sin zona';
        $seguidor['es_artista'] = $seguidor['rol'] === 'artista';
        $seguidor['siguiendo'] = (bool)$seguidor['siguiendo'];
        $seguidor['tiene_historia'] = false;
        return $seguidor;
    }, $stmtSeguidores->fetchAll());
} catch (PDOException $e) {
    error_log('Error al cargar seguidores del artista: ' . $e->getMessage());
    $seguidores = [];
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

.followers-container {
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1rem;
    width: 100%;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    backdrop-filter: blur(8px);
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

.follower-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.75rem;
    border-bottom: 1px solid var(--border-subtle);
    transition: background-color 0.2s;
}

.follower-row:last-child {
    border-bottom: none;
}

.follower-row:hover {
    background-color: var(--hover-item);
}
.follower-profile-link {
 display: flex;
 align-items: center;
 gap: 1rem;
 min-width: 0;
 margin-right: 1rem;
 color: inherit;
 text-decoration: none;
 border-radius: .75rem;
 }
.follower-profile-link:focus-visible { outline: 3px solid rgba(249,115,22,.45); outline-offset: 4px; }

.avatar-ring {
    padding: 2px;
    border-radius: 9999px;
    background: linear-gradient(135deg, var(--brand-orange), #ea580c, var(--brand-pink));
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.avatar-ring.no-story {
    background: transparent;
    border: 1px solid var(--border-subtle);
}

.avatar-img {
    width: 48px;
    height: 48px;
    border-radius: 9999px;
    object-fit: cover;
    border: 2px solid var(--bg-surface);
}

.btn-follow {
    padding: 0.5rem 1.25rem;
    border-radius: 0.5rem;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.btn-follow.following {
    background-color: var(--hover-item);
    color: var(--text-muted);
    border: 1px solid var(--border-subtle);
}

.btn-follow.following:hover {
    background-color: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    border-color: rgba(239, 68, 68, 0.3);
}

.btn-follow.not-following {
    background: linear-gradient(135deg, var(--brand-orange) 0%, #ea580c 100%);
    color: #ffffff;
    box-shadow: 0 4px 10px rgba(249, 115, 22, 0.25);
}

.btn-follow.not-following:hover {
    background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
}

.badge-cliente {
    background-color: var(--hover-item);
    color: var(--text-muted);
    padding: 0.4rem 0.9rem;
    border-radius: 0.375rem;
    font-size: 0.8rem;
    font-weight: 700;
    border: 1px solid var(--border-subtle);
}
</style>

<div style="display: flex; width: calc(100vw - 260px); height: calc(100vh - 4.25rem); overflow: hidden; background: transparent;">

    <div style="flex-grow: 1; height: 100%; overflow-y: auto; padding: 2rem 2.5rem; box-sizing: border-box;">
        
        <div style="width: 100%; margin: 0 auto;">

            <div class="followers-container">
                
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 1.5rem 1.75rem; border-bottom: 1px solid var(--border-subtle);">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <a href="perfil.php" style="color: var(--text-muted); text-decoration: none; font-size: 1.2rem; font-weight: bold;">&larr;</a>
                        <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 0;">
                            👥 Seguidores (<?= count($seguidores) ?>)
                        </h2>
                    </div>
                    <span style="font-size: 0.8rem; color: var(--brand-orange); font-weight: 700; background: var(--hover-item); padding: 0.3rem 0.8rem; border-radius: 9999px; border: 1px solid var(--border-subtle);">
                        Comunidad de seguidores
                    </span>
                </div>

                <div style="display: flex; flex-direction: column;">
                    <?php if (!$seguidores): ?>
                        <p style="padding: 1.5rem; color: var(--text-muted);">Aún no tienes seguidores.</p>
                    <?php endif; ?>
                    <?php foreach ($seguidores as $s): ?>
<div class="follower-row">
<?php if ($s['es_artista'] && !empty($s['id_artista'])): ?><a class="follower-profile-link" href="../cliente/perfil_artista.php?id=<?= (int)$s['id_artista'] ?>" aria-label="Ver perfil de <?= htmlspecialchars($s['usuario'], ENT_QUOTES, 'UTF-8') ?>">
                            
                            <div style="display: flex; align-items: center; gap: 1rem; overflow: hidden; margin-right: 1rem;">
                                <div class="avatar-ring <?= $s['tiene_historia'] ? '' : 'no-story' ?>">
                                    <img src="<?= htmlspecialchars($s['avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($s['usuario'], ENT_QUOTES, 'UTF-8') ?>" class="avatar-img">
                                </div>

                                <div style="display: flex; flex-direction: column; overflow: hidden;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span style="color: var(--text-main); font-weight: 700; font-size: 0.95rem;">
                                            <?= htmlspecialchars($s['usuario'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
<?php if ($s['es_artista']): ?>
                                            <span style="font-size: 0.7rem; color: var(--brand-orange); background: var(--hover-item); padding: 2px 7px; border-radius: 4px; font-weight: 700; border: 1px solid var(--border-subtle);">🎨 Artista</span>
                                        <?php else: ?>
                                            <span style="font-size: 0.7rem; color: var(--text-muted); background: var(--hover-item); padding: 2px 7px; border-radius: 4px; font-weight: 700; border: 1px solid var(--border-subtle);">🛍️ Cliente</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <span style="color: var(--text-muted); font-size: 0.825rem; margin-top: 0.2rem;">
                                        <?= htmlspecialchars($s['nombre'], ENT_QUOTES, 'UTF-8') ?> &bull; <span style="color: var(--text-main); font-weight: 600;">📍 <?= htmlspecialchars($s['zona'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </span>
                                </div>
                            </div>
                            </a><?php endif; ?>

                            <?php if ($s['es_artista']): ?>
                                <button type="button" data-artista-id="<?= (int)$s['id_artista'] ?>"
                                        class="btn-follow <?= $s['siguiendo'] ? 'following' : 'not-following' ?>" 
                                        onclick="toggleFollow(this)">
                                    <?= $s['siguiendo'] ? 'Siguiendo' : 'Seguir' ?>
                                </button>
                            <?php else: ?>
                                <span class="badge-cliente">Cliente</span>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                </div>

            </div>

        </div>

    </div>
</div>

<script>
async function toggleFollow(btn) {
    btn.disabled = true;
    try {
        const response = await fetch('../includes/actions/seguir.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id_artista: btn.dataset.artistaId })
        });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.error || 'No fue posible actualizar el seguimiento.');
        btn.classList.toggle('following', result.following);
        btn.classList.toggle('not-following', !result.following);
        btn.textContent = result.following ? 'Siguiendo' : 'Seguir';
    } catch (error) {
        window.alert(error.message || 'Error al actualizar el seguimiento.');
    } finally {
        btn.disabled = false;
    }
}
</script>


