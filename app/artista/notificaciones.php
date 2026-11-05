<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$pdo = getDB();
$idUsuario = getUserId();
$stmtLeidas = $pdo->prepare(
    'UPDATE notificacion SET leido_en = NOW()
     WHERE id_destinatario = :id_usuario AND leido_en IS NULL'
);
$stmtLeidas->execute([':id_usuario' => $idUsuario]);

$stmtNotificaciones = $pdo->prepare(
    'SELECT n.id_notificacion, n.id_actor, n.id_obra, n.tipo, n.titulo, n.mensaje,
            n.enlace, n.creado_en, n.leido_en,
            u.avatar_path AS actor_avatar,
            CASE WHEN n.id_actor IS NULL THEN NULL
                 ELSE COALESCE(NULLIF(a.nombre_artistico, \'\'), CONCAT(u.nombre, \' \', u.apellido))
            END AS actor_nombre,
            o.titulo AS obra_titulo
     FROM notificacion n
     LEFT JOIN usuario u ON u.id_usuario = n.id_actor
     LEFT JOIN artista a ON a.id_usuario = u.id_usuario
     LEFT JOIN obra o ON o.id_obra = n.id_obra
     WHERE n.id_destinatario = :id_usuario
     ORDER BY n.creado_en DESC, n.id_notificacion DESC
     LIMIT 100'
);
$stmtNotificaciones->execute([':id_usuario' => $idUsuario]);
$notificaciones = $stmtNotificaciones->fetchAll();
require_once __DIR__ . '/../artista/header.php';
include_once __DIR__ . '/../artista/sidebar.php';
?>

<div style="flex:1;min-width:0;padding:2rem;box-sizing:border-box;">
    <section style="max-width:1000px;margin:0 auto;">
        <header style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;">
            <div>
                <h1 style="margin:0;color:var(--text-main);font-size:1.7rem;">🔔 Notificaciones</h1>
                <p style="margin:.35rem 0 0;color:var(--text-muted);">Actividad relacionada con tu cuenta y tus obras.</p>
            </div>
        </header>

        <?php if (!$notificaciones): ?>
            <p style="padding:2rem;text-align:center;color:var(--text-muted);background:var(--bg-surface);border:1px solid var(--border-subtle);border-radius:1rem;">
                Todavía no tienes notificaciones.
            </p>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:.75rem;">
                <?php foreach ($notificaciones as $notificacion): ?>
                    <a href="<?= htmlspecialchars($notificacion['enlace'], ENT_QUOTES, 'UTF-8') ?>"
                       style="display:flex;align-items:flex-start;gap:.85rem;padding:1rem 1.2rem;text-decoration:none;color:var(--text-main);background:var(--bg-surface);border:1px solid var(--border-subtle);border-radius:.85rem;">
                        <?php
                        $avatarActor = is_string($notificacion['actor_avatar'])
                            && strpos($notificacion['actor_avatar'], '/poliart/uploads/avatars/') === 0
                            ? $notificacion['actor_avatar']
                            : '';
                        $inicialesActor = $notificacion['actor_nombre'] !== null
                            ? mb_strtoupper(mb_substr((string)$notificacion['actor_nombre'], 0, 1, 'UTF-8'), 'UTF-8')
                            : '';
                        if ($notificacion['actor_nombre'] !== null && strpos((string)$notificacion['actor_nombre'], ' ') !== false) {
                            $partesNombre = preg_split('/\s+/u', trim((string)$notificacion['actor_nombre']));
                            $inicialesActor .= mb_strtoupper(mb_substr((string)end($partesNombre), 0, 1, 'UTF-8'), 'UTF-8');
                        }
                        ?>
                        <?php if ($notificacion['actor_nombre'] !== null && $avatarActor !== ''): ?>
                            <img src="<?= htmlspecialchars($avatarActor, ENT_QUOTES, 'UTF-8') ?>"
                                 alt="Foto de <?= htmlspecialchars($notificacion['actor_nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                 loading="lazy"
                                 style="width:2.75rem;height:2.75rem;flex:0 0 2.75rem;border-radius:9999px;object-fit:cover;border:2px solid var(--brand-orange);">
                        <?php else: ?>
                            <span aria-hidden="true" style="width:2.75rem;height:2.75rem;flex:0 0 2.75rem;border-radius:9999px;display:grid;place-items:center;background:<?= $notificacion['actor_nombre'] !== null ? 'linear-gradient(135deg,var(--brand-orange),#ea580c)' : 'var(--hover-item)' ?>;color:#fff;font-size:.9rem;font-weight:800;border:2px solid var(--brand-orange);">
                                <?= $notificacion['actor_nombre'] !== null ? htmlspecialchars($inicialesActor, ENT_QUOTES, 'UTF-8') : '🔔' ?>
                            </span>
                        <?php endif; ?>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;">
                                <strong><?= htmlspecialchars($notificacion['titulo'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <time datetime="<?= htmlspecialchars($notificacion['creado_en'], ENT_QUOTES, 'UTF-8') ?>" style="flex:none;color:var(--text-muted);font-size:.78rem;">
                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime($notificacion['creado_en'])), ENT_QUOTES, 'UTF-8') ?>
                                </time>
                            </div>
                            <p style="margin:.45rem 0 0;color:var(--text-muted);line-height:1.5;">
                                <?php if ($notificacion['actor_nombre'] !== null): ?>
                                    <strong style="color:var(--text-main);"><?= htmlspecialchars($notificacion['actor_nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?= htmlspecialchars($notificacion['mensaje'], ENT_QUOTES, 'UTF-8') ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($notificacion['mensaje'], ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                                <?php if ($notificacion['obra_titulo'] !== null): ?>
                                    <span> · Obra: <?= htmlspecialchars($notificacion['obra_titulo'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>


