<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['cliente', 'artista']);
require_once __DIR__ . '/../cliente/header.php';
include_once __DIR__ . '/../cliente/sidebar.php';

$project_base = '/poliart/';

// Artistas seguidos por el cliente en Medellín
/* Datos de prueba retirados; los artistas seguidos se cargan desde la base de datos.
$seguidos = [
    [
        'id' => 1,
        'usuario' => 'jeffrey_ramos',
        'nombre' => 'Jeffrey Ramos (@Lulo)',
        'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80',
        'zona' => 'Comuna 14 - El Poblado',
        'especialidad' => 'Pintura & Óleo',
        'siguiendo' => true,
        'tiene_historia' => true
    ],
    [
        'id' => 2,
        'usuario' => 'sofia_rodriguez',
        'nombre' => 'Sofía Rodríguez',
        'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80',
        'zona' => 'Comuna 13 - San Javier',
        'especialidad' => 'Escultura & Cerámica',
        'siguiendo' => true,
        'tiene_historia' => true
    ],
    [
        'id' => 3,
        'usuario' => 'carlos_mendoza',
        'nombre' => 'Carlos Mendoza',
        'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=150&q=80',
        'zona' => 'Comuna 10 - La Candelaria',
        'especialidad' => 'Ilustración Digital',
        'siguiendo' => true,
        'tiene_historia' => false
    ],
    [
        'id' => 4,
        'usuario' => 'arantxalobato',
        'nombre' => 'Arantxa Lobato • Ilustradora',
        'avatar' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=150&q=80',
        'zona' => 'Comuna 8 - Villa Hermosa',
        'especialidad' => 'Muralismo',
        'siguiendo' => true,
        'tiene_historia' => false
    ],
    [
        'id' => 5,
        'usuario' => 'malokasunset',
        'nombre' => 'Maloka Sunset Studio',
        'avatar' => 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=150&q=80',
        'zona' => 'Comuna 11 - Laureles',
        'especialidad' => 'Fotografía Urbana',
        'siguiendo' => true,
        'tiene_historia' => true
    ]
];

*/
try {
    $stmtSeguidos = getDB()->prepare(
        'SELECT a.id_artista AS id, u.nombre_usuario AS usuario,
                CONCAT(u.nombre, \' \', u.apellido) AS nombre,
                u.avatar_path AS avatar, z.nombre AS zona,
                a.especialidad,
                EXISTS(
                    SELECT 1 FROM seguimiento s
                    WHERE s.id_seguidor = :id_usuario AND s.id_seguido = u.id_usuario
                ) AS siguiendo
         FROM seguimiento s
         INNER JOIN usuario u ON u.id_usuario = s.id_seguido AND u.rol = \'artista\'
         INNER JOIN artista a ON a.id_usuario = u.id_usuario
         LEFT JOIN zona z ON z.id_zona = u.id_zona
         WHERE s.id_seguidor = :id_seguidor
         ORDER BY u.nombre, u.apellido'
    );
    $stmtSeguidos->execute([
        ':id_usuario' => getUserId(),
        ':id_seguidor' => getUserId(),
    ]);
    $seguidos = array_map(static function (array $artista): array {
        $artista['avatar'] = is_string($artista['avatar'])
            && strpos($artista['avatar'], '/poliart/uploads/avatars/') === 0
            ? $artista['avatar']
            : '';
        $artista['zona'] = $artista['zona'] ?? 'Sin zona';
        $artista['especialidad'] = $artista['especialidad'] ?? 'Artista Poli-Art';
        $artista['siguiendo'] = (bool)$artista['siguiendo'];
        $artista['tiene_historia'] = false;
        return $artista;
    }, $stmtSeguidos->fetchAll());
} catch (PDOException $e) {
    error_log('Error al cargar artistas seguidos: ' . $e->getMessage());
    $seguidos = [];
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

.artist-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    overflow: hidden;
    margin-right: 1rem;
    text-decoration: none;
    color: inherit;
    flex: 1;
}

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
    display: inline-flex;
    align-items: center;
    justify-content: center;
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
</style>

<div style="display: flex; width: 100vw; height: calc(100vh - 4.25rem); overflow: hidden; background: transparent;">

    <div style="flex-grow: 1; height: 100%; overflow-y: auto; padding: 2rem 2.5rem; box-sizing: border-box;">
        
        <div style="width: 100%; max-width: 1100px; margin: 0 auto;">

            <div class="followers-container">
                
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 1.5rem 1.75rem; border-bottom: 1px solid var(--border-subtle);">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 1.2rem; font-weight: bold;">&larr;</a>
                        <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 0;">
                            ⭐ Artistas que sigues (<?= count($seguidos) ?>)
                        </h2>
                    </div>
                    <span style="font-size: 0.8rem; color: var(--brand-orange); font-weight: 700; background: var(--hover-item); padding: 0.3rem 0.8rem; border-radius: 9999px; border: 1px solid var(--border-subtle);">
                        Comunidad Medellín
                    </span>
                </div>

                <div style="display: flex; flex-direction: column;">
                    <?php foreach ($seguidos as $s): ?>
                        <div class="follower-row">
                            
                            <!-- Redirige directamente a la página del perfil del artista -->
                            <a href="perfil_artista.php?id=<?= $s['id'] ?>" class="artist-link">
                                <div class="avatar-ring <?= $s['tiene_historia'] ? '' : 'no-story' ?>">
                                    <img src="<?= $s['avatar'] ?>" alt="<?= htmlspecialchars($s['usuario']) ?>" class="avatar-img">
                                </div>

                                <div style="display: flex; flex-direction: column; overflow: hidden;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span style="color: var(--text-main); font-weight: 700; font-size: 0.95rem;">
                                            <?= htmlspecialchars($s['usuario']) ?>
                                        </span>
                                        <span style="font-size: 0.7rem; color: var(--brand-orange); background: var(--hover-item); padding: 2px 7px; border-radius: 4px; font-weight: 700; border: 1px solid var(--border-subtle);">🎨 <?= htmlspecialchars($s['especialidad']) ?></span>
                                    </div>
                                    
                                    <span style="color: var(--text-muted); font-size: 0.825rem; margin-top: 0.2rem;">
                                        <?= htmlspecialchars($s['nombre']) ?> &bull; <span style="color: var(--text-main); font-weight: 600;">📍 <?= $s['zona'] ?></span>
                                    </span>
                                </div>
                            </a>

                            <button type="button" data-artista-id="<?= (int)$s['id'] ?>"
                                    class="btn-follow <?= $s['siguiendo'] ? 'following' : 'not-following' ?>" 
                                    onclick="toggleFollow(this)">
                                <?= $s['siguiendo'] ? 'Siguiendo' : 'Seguir' ?>
                            </button>

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


