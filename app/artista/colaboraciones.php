<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);
require_once __DIR__ . '/../artista/header.php';
include_once __DIR__ . '/../artista/sidebar.php';

$project_base = '/poliart/';

$invitaciones = $colaboraciones_activas = $artistasDisponibles = [];
$errorColaboraciones = '';
try {
    $pdo = getDB();
    $stmtMiArtista = $pdo->prepare('SELECT id_artista FROM artista WHERE id_usuario = :usuario');
    $stmtMiArtista->execute([':usuario' => getUserId()]);
    $idArtista = (int)$stmtMiArtista->fetchColumn();

    $stmtInvitaciones = $pdo->prepare(
        "SELECT p.id_proyecto AS id, p.titulo, p.descripcion, p.creado_en,
                a.especialidad, u.nombre, u.apellido, u.avatar_path,
                a.nombre_artistico, z.nombre AS zona
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
        $inv['invitador'] = $inv['nombre_artistico'] ?: trim($inv['nombre'] . ' ' . $inv['apellido']);
        $inv['avatar'] = is_string($inv['avatar_path'])
            && strpos($inv['avatar_path'], '/poliart/uploads/avatars/') === 0
            ? $inv['avatar_path']
            : '';
        $inv['especialidad'] = $inv['especialidad'] ?: 'Artista';
        $inv['zona'] = $inv['zona'] ?: 'Sin zona';
        $inv['fecha'] = date('d/m/Y', strtotime($inv['creado_en']));
        return $inv;
    }, $stmtInvitaciones->fetchAll());

    $stmtActivas = $pdo->prepare(
        "SELECT p.id_proyecto AS id, p.titulo, p.progreso, p.estado,
                otro.nombre_artistico, otroUsuario.nombre, otroUsuario.apellido,
                otroUsuario.avatar_path, z.nombre AS zona
         FROM proyecto_colaborativo p
         INNER JOIN participante_colaboracion propia
            ON propia.id_proyecto = p.id_proyecto AND propia.id_artista = :id_artista AND propia.estado_invitacion = 'aceptada'
         INNER JOIN participante_colaboracion pc
            ON pc.id_proyecto = p.id_proyecto AND pc.id_artista <> :id_artista_otro AND pc.estado_invitacion = 'aceptada'
         INNER JOIN artista otro ON otro.id_artista = pc.id_artista
         INNER JOIN usuario otroUsuario ON otroUsuario.id_usuario = otro.id_usuario
         LEFT JOIN zona z ON z.id_zona = p.id_zona
         WHERE p.estado = 'activo'
         ORDER BY p.actualizado_en DESC"
    );
    $stmtActivas->execute([':id_artista' => $idArtista, ':id_artista_otro' => $idArtista]);
    $colaboraciones_activas = array_map(static function (array $project): array {
        $project['socio'] = $project['nombre_artistico'] ?: trim($project['nombre'] . ' ' . $project['apellido']);
        $project['avatar'] = is_string($project['avatar_path'])
            && strpos($project['avatar_path'], '/poliart/uploads/avatars/') === 0
            ? $project['avatar_path']
            : '';
        $project['zona'] = $project['zona'] ?: 'Sin zona';
        return $project;
    }, $stmtActivas->fetchAll());

    $stmtArtistas = $pdo->prepare(
        "SELECT a.id_artista, COALESCE(NULLIF(a.nombre_artistico, ''), CONCAT(u.nombre, ' ', u.apellido)) AS nombre,
                a.especialidad
         FROM artista a INNER JOIN usuario u ON u.id_usuario = a.id_usuario
         WHERE a.id_artista <> :id_artista AND u.estado = 'activo'
         ORDER BY nombre"
    );
    $stmtArtistas->execute([':id_artista' => $idArtista]);
    $artistasDisponibles = $stmtArtistas->fetchAll();
} catch (PDOException $e) {
    error_log('Error al cargar colaboraciones: ' . $e->getMessage());
    $errorColaboraciones = 'No se pudieron cargar los proyectos colaborativos.';
}
?>

<style>
.glass-card {
    background-color: rgba(255, 255, 255, 0.95);
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    backdrop-filter: blur(8px);
}

.btn-aceptar {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    font-weight: 700;
    padding: 0.55rem 1.25rem;
    border-radius: 0.5rem;
    border: none;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.2s;
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.25);
}

.btn-aceptar:hover {
    box-shadow: 0 6px 15px rgba(16, 185, 129, 0.4);
    transform: translateY(-1px);
}

.btn-rechazar {
    background-color: #f1f5f9;
    color: #64748b;
    font-weight: 700;
    padding: 0.55rem 1.25rem;
    border-radius: 0.5rem;
    border: 1px solid #cbd5e1;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.2s;
}

.btn-rechazar:hover {
    background-color: #fee2e2;
    color: #dc2626;
    border-color: #fca5a5;
}

.progress-bar-bg {
    background-color: #f1f5f9;
    border-radius: 9999px;
    height: 8px;
    overflow: hidden;
    width: 100%;
}

.progress-bar-fill {
    background: linear-gradient(90deg, #f97316, #ea580c);
    height: 100%;
    border-radius: 9999px;
}
</style>

<div style="display: flex; width: 100%; min-height: calc(100vh - 4.25rem); background: transparent;">

    <div style="flex-grow: 1; padding: 2.5rem; overflow-y: auto;">
        
        <div style="max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.75rem;">

            <!-- ENCABEZADO PRINCIPAL -->
            <div class="glass-card" style="padding: 1.5rem 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 52px; height: 52px; border-radius: 1rem; background: #fff7ed; border: 1px solid #fed7aa; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        🤝
                    </div>
                    <div>
                        <h1 style="font-size: 1.75rem; font-weight: 800; color: #1e293b; margin: 0;">Proyectos y Colaboraciones</h1>
                        <p style="font-size: 0.875rem; color: #64748b; margin: 2px 0 0 0;">Crea sinergias con otros artistas e impulsa el arte local en Medellín</p>
                    </div>
                </div>

                <button type="button" onclick="document.getElementById('formColaboracion').hidden = false; document.getElementById('formColaboracion').scrollIntoView({behavior:'smooth'});" class="btn btn-primary">
                    ➕ Proponer Colaboración
                </button>
            </div>

            <?php if ($errorColaboraciones !== ''): ?>
                <p role="alert" style="color:#dc2626;"><?= htmlspecialchars($errorColaboraciones, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form id="formColaboracion" hidden onsubmit="crearColaboracion(event)" class="glass-card" style="padding:1.5rem;">
                <h2 style="margin-top:0;">Nueva propuesta</h2>
                <label>Título del proyecto
                    <input name="titulo" maxlength="160" required style="display:block;width:100%;padding:.65rem;margin:.35rem 0 1rem;">
                </label>
                <label>Artista a invitar
                    <select name="id_artista" required style="display:block;width:100%;padding:.65rem;margin:.35rem 0 1rem;">
                        <option value="">Selecciona un artista</option>
                        <?php foreach ($artistasDisponibles as $artistaDisponible): ?>
                            <option value="<?= (int)$artistaDisponible['id_artista'] ?>"><?= htmlspecialchars($artistaDisponible['nombre'], ENT_QUOTES, 'UTF-8') ?><?= $artistaDisponible['especialidad'] ? ' — ' . htmlspecialchars($artistaDisponible['especialidad'], ENT_QUOTES, 'UTF-8') : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Descripción
                    <textarea name="descripcion" required rows="3" style="display:block;width:100%;padding:.65rem;margin:.35rem 0 1rem;"></textarea>
                </label>
                <button type="submit" class="btn-aceptar">Enviar invitación</button>
                <button type="button" class="btn-rechazar" onclick="document.getElementById('formColaboracion').hidden = true;">Cancelar</button>
            </form>

            <!-- SECCIÓN 1: INVITACIONES PENDIENTES -->
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <h2 style="font-size: 1.15rem; font-weight: 800; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    📩 Invitaciones Recibidas 
                    <span style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: #ffffff; font-size: 0.75rem; padding: 0.15rem 0.6rem; border-radius: 9999px;">
                        <?= count($invitaciones) ?>
                    </span>
                </h2>

                <?php foreach ($invitaciones as $inv): ?>
                    <div class="glass-card" style="padding: 1.5rem; border-left: 5px solid #f97316;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.25rem;">
                            
                            <div style="display: flex; gap: 1.25rem; align-items: flex-start; max-width: 700px;">
                                <img src="<?= htmlspecialchars($inv['avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" style="width: 56px; height: 56px; border-radius: 9999px; object-fit: cover; border: 2px solid #f97316; box-shadow: 0 4px 10px rgba(249,115,22,0.2);">
                                
                                <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                                        <span style="font-size: 0.7rem; font-weight: 800; color: #ea580c; text-transform: uppercase; background: #fff7ed; padding: 2px 8px; border-radius: 4px; border: 1px solid #fed7aa;">
                                            INVITACIÓN PENDIENTE
                                        </span>
                                        <span style="font-size: 0.75rem; color: #94a3b8;"><?= htmlspecialchars($inv['fecha'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>

                                    <h3 style="font-size: 1.2rem; font-weight: 800; color: #1e293b; margin: 0;">
                                        <?= htmlspecialchars($inv['titulo'], ENT_QUOTES, 'UTF-8') ?>
                                    </h3>

                                    <p style="font-size: 0.875rem; color: #475569; margin: 0;">
                                        Invitado por <strong style="color: #1e293b;"><?= htmlspecialchars($inv['invitador'], ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($inv['especialidad'], ENT_QUOTES, 'UTF-8') ?>) &bull; <span style="color: #ea580c; font-weight: 700;">📍 <?= htmlspecialchars($inv['zona'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </p>

                                    <p style="font-size: 0.85rem; color: #334155; background: #f8fafc; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-top: 0.5rem; border: 1px solid #e2e8f0;">
                                        "<?= htmlspecialchars($inv['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    </p>
                                </div>
                            </div>

                            <div style="display: flex; gap: 0.75rem; align-items: center;">
                                <button type="button" class="btn-aceptar" onclick="responderInvitacion(<?= (int)$inv['id'] ?>, 'aceptar')">
                                    ✅ Aceptar
                                </button>
                                <button type="button" class="btn-rechazar" onclick="responderInvitacion(<?= (int)$inv['id'] ?>, 'rechazar')">
                                    ❌ Rechazar
                                </button>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$invitaciones && $errorColaboraciones === ''): ?>
                    <p style="color:var(--text-muted);">No tienes invitaciones pendientes.</p>
                <?php endif; ?>
            </div>

            <!-- SECCIÓN 2: COLABORACIONES EN CURSO -->
            <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 0.5rem;">
                <h2 style="font-size: 1.15rem; font-weight: 800; color: #1e293b; margin: 0;">
                    🤝 Proyectos Activos en Conjunto
                </h2>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem;">
                    <?php foreach ($colaboraciones_activas as $act): ?>
                        <div class="glass-card" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: 0.85rem;">
                                    <img src="<?= htmlspecialchars($act['avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" style="width: 44px; height: 44px; border-radius: 9999px; object-fit: cover; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                    <div>
                                        <h4 style="font-size: 1rem; font-weight: 800; color: #1e293b; margin: 0;"><?= htmlspecialchars($act['titulo'], ENT_QUOTES, 'UTF-8') ?></h4>
                                        <span style="font-size: 0.8rem; color: #64748b; font-weight: 500;">Con <?= htmlspecialchars($act['socio'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                                <span style="font-size: 0.75rem; color: #15803d; background: #dcfce7; padding: 3px 10px; border-radius: 9999px; font-weight: 700; border: 1px solid #bbf7d0;">En desarrollo</span>
                            </div>

                            <div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #64748b; margin-bottom: 0.4rem; font-weight: 600;">
                                    <span>Avance del proyecto</span>
                                    <strong style="color: #ea580c;"><?= $act['progreso'] ?>%</strong>
                                </div>
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill" style="width: <?= $act['progreso'] ?>%;"></div>
                                </div>
                            </div>

                            <a href="proyectos_desarrollo_colaboracion.php?id=<?= (int)$act['id'] ?>" style="text-align: center; background-color: #f8fafc; color: #1e293b; border: 1px solid #cbd5e1; padding: 0.6rem; border-radius: 0.5rem; font-size: 0.825rem; font-weight: 700; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.backgroundColor='#f1f5f9';" onmouseout="this.style.backgroundColor='#f8fafc';">
                                Abrir Mesa de Trabajo &rarr;
                            </a>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$colaboraciones_activas && $errorColaboraciones === ''): ?>
                        <p style="color:var(--text-muted);">Aún no participas en proyectos colaborativos activos.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
async function crearColaboracion(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const data = new FormData(form);
    data.set('accion', 'crear');
    await enviarAccionColaboracion(data);
}

async function responderInvitacion(id, respuesta) {
    const data = new FormData();
    data.set('accion', 'responder');
    data.set('id_proyecto', id);
    data.set('respuesta', respuesta);
    await enviarAccionColaboracion(data);
}

async function enviarAccionColaboracion(data) {
    try {
        const response = await fetch('../includes/actions/gestion_colaboracion.php', { method: 'POST', body: data });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.error || 'No se pudo guardar la colaboración.');
        window.location.reload();
    } catch (error) {
        window.alert(error.message || 'No se pudo guardar la colaboración.');
    }
}
</script>


