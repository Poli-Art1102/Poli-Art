<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);

$pdo = getDB();
$idProyecto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$idArtistaStmt = $pdo->prepare('SELECT id_artista FROM artista WHERE id_usuario = :usuario LIMIT 1');
$idArtistaStmt->execute([':usuario' => getUserId()]);
$idArtista = (int)$idArtistaStmt->fetchColumn();
$errorMesa = '';
$mensajeMesa = '';
$proyecto = null;
$participantes = [];
$avances = [];
$archivoGuardado = null;
$rutaAbsolutaImagen = null;
$directorioImagenes = dirname(__DIR__, 2) . '/uploads/colaboraciones/';

if (!$idProyecto || $idProyecto < 1 || !$idArtista) {
    http_response_code(400);
    $errorMesa = 'Selecciona un proyecto colaborativo válido.';
} else {
    try {
        $stmtProyecto = $pdo->prepare(
            "SELECT p.id_proyecto, p.titulo, p.descripcion, p.progreso, p.estado, p.creado_en,
                    propia.estado_invitacion
               FROM proyecto_colaborativo p
               INNER JOIN participante_colaboracion propia
                 ON propia.id_proyecto = p.id_proyecto
                AND propia.id_artista = :id_artista
                AND propia.estado_invitacion = 'aceptada'
                AND propia.activa = 1
               WHERE p.id_proyecto = :id_proyecto
               LIMIT 1"
        );
        $stmtProyecto->execute([
            ':id_artista' => $idArtista,
            ':id_proyecto' => $idProyecto,
        ]);
        $proyecto = $stmtProyecto->fetch();
        if (!$proyecto) {
            http_response_code(404);
            $errorMesa = 'No tienes acceso a este proyecto colaborativo.';
        } else {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                $descripcion = trim((string)($_POST['descripcion'] ?? ''));
                $progresoRaw = trim((string)($_POST['progreso'] ?? ''));
                $archivo = $_FILES['imagen'] ?? null;
                $hayImagen = is_array($archivo) && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                $progreso = null;

                if ($progresoRaw !== '') {
                    $progresoValidado = filter_var($progresoRaw, FILTER_VALIDATE_INT);
                    if ($progresoValidado === false || $progresoValidado < 0 || $progresoValidado > 100) {
                        $errorMesa = 'El avance debe ser un número entre 0 y 100.';
                    } else {
                        $progreso = $progresoValidado;
                    }
                }
                if ($descripcion === '' && !$hayImagen && $progreso === null) {
                    $errorMesa = 'Agrega una descripción, una imagen o actualiza el porcentaje de avance.';
                } elseif (mb_strlen($descripcion, 'UTF-8') > 3000) {
                    $errorMesa = 'La descripción del avance no puede superar 3000 caracteres.';
                } elseif ($proyecto['estado'] !== 'activo') {
                    $errorMesa = 'Este proyecto ya no está activo y no admite nuevos avances.';
                }

                if ($errorMesa === '' && $hayImagen) {
                    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
                        || !is_uploaded_file($archivo['tmp_name'] ?? '')
                        || ($archivo['size'] ?? 0) < 1
                        || ($archivo['size'] ?? 0) > 10 * 1024 * 1024) {
                        $errorMesa = 'La imagen debe pesar hasta 10 MB y cargarse correctamente.';
                    } else {
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $tipoImagen = $finfo->file($archivo['tmp_name']);
                        $extensionesImagen = [
                            'image/jpeg' => 'jpg',
                            'image/png' => 'png',
                            'image/gif' => 'gif',
                            'image/webp' => 'webp',
                        ];
                        if (!is_string($tipoImagen) || !isset($extensionesImagen[$tipoImagen])) {
                            $errorMesa = 'Adjunta una imagen JPG, PNG, GIF o WebP.';
                        } else {
                            if (!is_dir($directorioImagenes)
                                && !mkdir($directorioImagenes, 0750, true)
                                && !is_dir($directorioImagenes)) {
                                error_log('No fue posible crear el directorio de imágenes de avances.');
                                $errorMesa = 'No fue posible preparar el almacenamiento de imágenes.';
                            } else {
                                $nombreImagen = bin2hex(random_bytes(24)) . '.' . $extensionesImagen[$tipoImagen];
                                $rutaAbsolutaImagen = $directorioImagenes . $nombreImagen;
                                if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsolutaImagen)) {
                                    error_log('No fue posible guardar una imagen de avance colaborativo.');
                                    $errorMesa = 'No fue posible guardar la imagen seleccionada.';
                                } else {
                                    $nombreOriginal = basename(str_replace('\\', '/', (string)$archivo['name']));
                                    $archivoGuardado = [
                                        'ruta' => $nombreImagen,
                                        'nombre' => mb_substr($nombreOriginal, 0, 255, 'UTF-8'),
                                    ];
                                }
                            }
                        }
                    }
                }

                if ($errorMesa === '') {
                    try {
                        $pdo->beginTransaction();
                        $stmtAvance = $pdo->prepare(
                            'INSERT INTO avance_colaboracion
                                (id_proyecto, id_artista, descripcion, progreso, ruta_imagen, nombre_imagen)
                             VALUES
                                (:proyecto, :artista, :descripcion, :progreso, :ruta, :nombre)'
                        );
                        $stmtAvance->execute([
                            ':proyecto' => $idProyecto,
                            ':artista' => $idArtista,
                            ':descripcion' => $descripcion !== '' ? $descripcion : null,
                            ':progreso' => $progreso,
                            ':ruta' => $archivoGuardado['ruta'] ?? null,
                            ':nombre' => $archivoGuardado['nombre'] ?? null,
                        ]);
                        if ($progreso !== null) {
                            $pdo->prepare(
                                'UPDATE proyecto_colaborativo
                                 SET progreso = :progreso
                                 WHERE id_proyecto = :proyecto AND estado = \'activo\''
                            )->execute([
                                ':progreso' => $progreso,
                                ':proyecto' => $idProyecto,
                            ]);
                        }
                        $pdo->commit();
                        $_SESSION['flash_ok'] = 'Avance compartido con el equipo.';
                        header('Location: mesa_trabajo.php?id=' . $idProyecto);
                        exit;
                    } catch (PDOException $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        if ($rutaAbsolutaImagen !== null && is_file($rutaAbsolutaImagen)) {
                            unlink($rutaAbsolutaImagen);
                        }
                        error_log('Error al guardar avance colaborativo: ' . $e->getMessage());
                        $errorMesa = 'No fue posible guardar el avance. Inténtalo de nuevo.';
                    }
                }
                if ($errorMesa !== '' && $rutaAbsolutaImagen !== null && is_file($rutaAbsolutaImagen)) {
                    unlink($rutaAbsolutaImagen);
                }
            }

            $mensajeMesa = (string)($_SESSION['flash_ok'] ?? '');
            unset($_SESSION['flash_ok']);

            $stmtParticipantes = $pdo->prepare(
                "SELECT a.id_artista,
                        COALESCE(NULLIF(a.nombre_artistico, ''), CONCAT(u.nombre, ' ', u.apellido)) AS nombre,
                        u.avatar_path
                   FROM participante_colaboracion pc
                   INNER JOIN artista a ON a.id_artista = pc.id_artista
                   INNER JOIN usuario u ON u.id_usuario = a.id_usuario
                   WHERE pc.id_proyecto = :proyecto
                     AND pc.estado_invitacion = 'aceptada'
                     AND pc.activa = 1
                   ORDER BY pc.invitado_en"
            );
            $stmtParticipantes->execute([':proyecto' => $idProyecto]);
            $participantes = $stmtParticipantes->fetchAll();

            $stmtAvances = $pdo->prepare(
                'SELECT av.id_avance, av.descripcion, av.progreso, av.ruta_imagen,
                        av.nombre_imagen, av.creado_en,
                        COALESCE(NULLIF(a.nombre_artistico, \'\'), CONCAT(u.nombre, \' \', u.apellido)) AS autor,
                        u.avatar_path
                   FROM avance_colaboracion av
                   INNER JOIN artista a ON a.id_artista = av.id_artista
                   INNER JOIN usuario u ON u.id_usuario = a.id_usuario
                   WHERE av.id_proyecto = :proyecto
                   ORDER BY av.creado_en DESC, av.id_avance DESC'
            );
            $stmtAvances->execute([':proyecto' => $idProyecto]);
            $avances = $stmtAvances->fetchAll();
        }
    } catch (PDOException $e) {
        error_log('Error al cargar mesa de trabajo: ' . $e->getMessage());
        http_response_code(500);
        $errorMesa = 'No fue posible cargar la mesa de trabajo.';
    }
}

require_once __DIR__ . '/../artista/header.php';
include_once __DIR__ . '/../artista/sidebar.php';
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
    background-image: url('<?= $project_base ?? "/poliart/" ?>app/includes/madera.jpg') !important;
    background-size: cover !important;
    background-position: center !important;
    background-attachment: fixed !important;
    background-repeat: no-repeat !important;
}

.workroom-page {
    position: fixed;
    top: 4.25rem;
    left: 260px;
    width: calc(100% - 260px);
    height: calc(100vh - 4.25rem);
    box-sizing: border-box;
    overflow-y: auto;
    padding: 2rem 2.5rem;
}

.workroom-wrap { max-width:1280px; margin:0 auto; display:flex; flex-direction:column; gap:1.5rem; }
.workroom-hero { position:relative; overflow:hidden; padding:clamp(1.4rem,3vw,2.2rem); border:1px solid rgba(255,255,255,.18); border-radius:1.25rem; color:#fff; background:linear-gradient(120deg,#4b1e12 0%,#7a3119 48%,#c45127 100%); box-shadow:0 18px 45px rgba(80,31,13,.2); }
.workroom-hero:after { content:""; position:absolute; width:22rem; height:22rem; right:-7rem; top:-12rem; border:1px solid rgba(255,255,255,.16); border-radius:50%; box-shadow:0 0 0 2rem rgba(255,255,255,.035),0 0 0 4rem rgba(255,255,255,.025); pointer-events:none; }
.workroom-back { position:relative; z-index:1; color:#ffe0d1; font-size:.85rem; font-weight:750; text-decoration:none; }
.workroom-back:hover { color:#fff; }
.workroom-hero-main { position:relative; z-index:1; display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; margin-top:1.2rem; }
.workroom-eyebrow { margin:0 0 .45rem; color:#ffd4be; font-size:.72rem; font-weight:850; letter-spacing:.14em; text-transform:uppercase; }
.workroom-title { margin:0; color:#fff; font-size:clamp(1.6rem,4vw,2.2rem); line-height:1.12; font-weight:900; letter-spacing:-.035em; }
.workroom-summary { max-width:720px; margin:.7rem 0 0; color:rgba(255,255,255,.78); line-height:1.6; font-size:0.925rem; }
.workroom-status { flex:none; padding:.42rem .8rem; border:1px solid rgba(255,255,255,.28); border-radius:999px; color:#fff; background:rgba(255,255,255,.12); font-size:.8rem; font-weight:800; }
.workroom-hero-bottom { position:relative; z-index:1; display:grid; grid-template-columns:minmax(180px,1fr) auto; align-items:end; gap:2rem; margin-top:1.5rem; }
.workroom-progress-label { display:flex; justify-content:space-between; gap:1rem; color:#ffe4d7; font-size:.8rem; font-weight:750; }
.workroom-progress { width:100%; height:.6rem; margin-top:.6rem; overflow:hidden; border:1px solid rgba(255,255,255,.25); border-radius:999px; background:rgba(0,0,0,.2); }
.workroom-progress span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#ffd369,#fff0b8); box-shadow:0 0 14px rgba(255,211,105,.5); }
.workroom-team { display:flex; align-items:center; flex-wrap:wrap; justify-content:flex-end; gap:.55rem; }
.workroom-member { display:flex; align-items:center; gap:.45rem; padding:.28rem .65rem .28rem .28rem; border:1px solid rgba(255,255,255,.2); border-radius:999px; color:#fff; background:rgba(255,255,255,.1); font-size:.78rem; font-weight:700; }
.workroom-avatar { display:grid; flex:none; width:2.15rem; height:2.15rem; place-items:center; overflow:hidden; border:2px solid rgba(255,255,255,.55); border-radius:50%; background:linear-gradient(135deg,#ffd369,#ed7745); color:#51200f; font-size:.75rem; font-weight:850; }
.workroom-avatar img { width:100%; height:100%; object-fit:cover; }

/* DISEÑO ARMÓNICO DE LAYOUT Y TARJETAS */
.workroom-layout { display:grid; grid-template-columns:minmax(0,1fr) 360px; align-items:start; gap:1.5rem; }
.workroom-feed { min-width:0; }
.workroom-card { padding:1.5rem; border:1px solid var(--border-subtle); border-radius:1.25rem; background:var(--bg-surface); box-shadow:0 12px 35px rgba(0,0,0,.15); backdrop-filter:blur(8px); }
.workroom-section-heading { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; border-bottom:1px solid var(--border-subtle); padding-bottom:.85rem; }
.workroom-section-heading h2 { margin:0; color:var(--text-main); font-size:1.15rem; font-weight:850; }
.workroom-count { padding:.24rem .65rem; border-radius:999px; color:var(--text-muted); background:var(--hover-item); font-size:.75rem; font-weight:800; border:1px solid var(--border-subtle); }
.workroom-muted { margin:.45rem 0 0; color:var(--text-muted); line-height:1.55; }

/* FORMULARIO ESTILIZADO E INTEGRADO */
.workroom-composer { position:sticky; top:1.5rem; }
.workroom-composer-intro { display:flex; align-items:center; gap:.75rem; margin-bottom:1.25rem; padding-bottom:.85rem; border-bottom:1px solid var(--border-subtle); }
.workroom-composer-intro .workroom-avatar { width:2.65rem; height:2.65rem; border-color:var(--border-subtle); }
.workroom-composer-intro strong { display:block; color:var(--text-main); font-size:.95rem; }
.workroom-composer-intro span { display:block; margin-top:.15rem; color:var(--text-muted); font-size:.78rem; }
.workroom-form { display:flex; flex-direction:column; gap:1rem; }
.workroom-form textarea,.workroom-form input[type=number] { box-sizing:border-box; width:100%; padding:.75rem .9rem; border:1px solid var(--border-subtle); border-radius:.75rem; background:var(--bg-body); color:var(--text-main); font:inherit; transition:border-color .2s,box-shadow .2s; }
.workroom-form textarea { min-height:120px; resize:vertical; line-height:1.5; }
.workroom-form textarea:focus,.workroom-form input:focus { outline:none; border-color:var(--brand-orange); box-shadow:0 0 0 3px rgba(237,119,69,.15); }
.workroom-form label { display:block; margin-bottom:.4rem; color:var(--text-main); font-size:.82rem; font-weight:800; }
.workroom-image-picker { padding:.9rem; border:2px dashed var(--border-subtle); border-radius:.75rem; background:var(--bg-body); text-align:center; cursor:pointer; transition:border-color .2s; }
.workroom-image-picker:hover { border-color:var(--brand-orange); }
.workroom-image-picker input { width:100%; color:var(--text-muted); font-size:.78rem; cursor:pointer; }
.workroom-form button { display:flex; align-items:center; justify-content:center; gap:.5rem; width:100%; padding:.85rem 1rem; border:0; border-radius:.75rem; background:linear-gradient(135deg,var(--brand-orange),#ea580c); color:#fff; font:inherit; font-weight:850; cursor:pointer; box-shadow:0 8px 20px rgba(237,119,69,.25); transition:transform .18s,filter .18s; }
.workroom-form button:hover { filter:brightness(1.06); transform:translateY(-1px); }

/* ACTUALIZACIONES DEL EQUIPO */
.workroom-update { position:relative; display:flex; flex-direction:column; gap:.85rem; padding:1.25rem 0; border-bottom:1px solid var(--border-subtle); }
.workroom-update:first-of-type { padding-top:0; }
.workroom-update:last-child { border-bottom:0; padding-bottom:0; }
.workroom-update-head { display:flex; align-items:center; gap:.75rem; }
.workroom-update-head .workroom-avatar { width:2.45rem; height:2.45rem; border-color:var(--border-subtle); background:var(--hover-item); color:var(--brand-orange); }
.workroom-update-head strong { color:var(--text-main); font-size:.9rem; }
.workroom-update-head time { margin-left:auto; color:var(--text-muted); font-size:.75rem; }
.workroom-update p { margin:0; color:var(--text-main); line-height:1.65; white-space:pre-wrap; overflow-wrap:anywhere; font-size:.925rem; }
.workroom-update img { display:block; width:100%; max-height:450px; object-fit:contain; border:1px solid var(--border-subtle); border-radius:.85rem; background:var(--bg-body); margin-top:.25rem; }
.workroom-update-progress { align-self:flex-start; padding:.3rem .7rem; border-radius:999px; color:var(--brand-orange); background:rgba(237,119,69,.12); font-size:.75rem; font-weight:850; border:1px solid rgba(237,119,69,.3); }
.workroom-empty { display:flex; flex-direction:column; align-items:center; padding:3rem 1rem; color:var(--text-muted); text-align:center; }
.workroom-empty-icon { display:grid; width:3.5rem; height:3.5rem; margin-bottom:.85rem; place-items:center; border-radius:1.1rem; background:rgba(237,119,69,.13); font-size:1.6rem; color:var(--brand-orange); }
.workroom-empty strong { color:var(--text-main); font-size:1rem; }
.workroom-alert { padding:.85rem 1rem; border:1px solid var(--border-subtle); border-radius:.75rem; color:var(--text-main); background:var(--bg-surface); }
.workroom-alert.error { border-color:#ef4444; color:#b91c1c; background:rgba(239,68,68,.08); }

@media(max-width:1100px) { .workroom-layout { grid-template-columns:1fr; } .workroom-composer { position:static; } }
@media(max-width:540px) { .workroom-page { padding:1rem; } .workroom-hero { padding:1.2rem; } .workroom-hero-main { flex-direction:column; } .workroom-hero-bottom { grid-template-columns:1fr; gap:1rem; } .workroom-team { justify-content:flex-start; } }
</style>

<div class="workroom-page">
    <div class="workroom-wrap">
        <?php if ($errorMesa !== ''): ?>
            <section class="workroom-card">
                <h1 class="workroom-title" style="color:var(--text-main);">Mesa de trabajo</h1>
                <p class="workroom-alert error" role="alert"><?= htmlspecialchars($errorMesa, ENT_QUOTES, 'UTF-8') ?></p>
                <a href="proyectos_desarrollo_colaboracion.php" style="color:var(--brand-orange);font-weight:700;display:inline-block;margin-top:1rem;">← Volver a proyectos colaborativos</a>
            </section>
        <?php elseif ($proyecto): ?>
            <section class="workroom-hero">
                <a class="workroom-back" href="proyectos_desarrollo_colaboracion.php">← Volver a proyectos</a>
                <div class="workroom-hero-main">
                    <div>
                        <p class="workroom-eyebrow">Espacio de colaboración</p>
                        <h1 class="workroom-title"><?= htmlspecialchars($proyecto['titulo'], ENT_QUOTES, 'UTF-8') ?></h1>
                        <p class="workroom-summary"><?= htmlspecialchars($proyecto['descripcion'] ?? 'Un espacio compartido para organizar el trabajo y mostrar cada avance.', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <span class="workroom-status"><?= htmlspecialchars(ucfirst($proyecto['estado']), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="workroom-hero-bottom">
                    <div>
                        <div class="workroom-progress-label">
                            <span>Progreso del proyecto</span>
                            <span><?= (int)$proyecto['progreso'] ?>%</span>
                        </div>
                        <div class="workroom-progress" role="progressbar" aria-label="Progreso del proyecto" aria-valuenow="<?= (int)$proyecto['progreso'] ?>" aria-valuemin="0" aria-valuemax="100">
                            <span style="width:<?= min(100, max(0, (int)$proyecto['progreso'])) ?>%;"></span>
                        </div>
                    </div>
                    <div class="workroom-team" aria-label="Participantes del proyecto">
                        <?php foreach ($participantes as $participante): ?>
                            <?php
                            $avatarParticipante = is_string($participante['avatar_path'])
                                && strpos($participante['avatar_path'], '/poliart/uploads/avatars/') === 0
                                ? $participante['avatar_path']
                                : '';
                            ?>
                            <span class="workroom-member">
                                <span class="workroom-avatar">
                                    <?php if ($avatarParticipante !== ''): ?>
                                        <img src="<?= htmlspecialchars($avatarParticipante, ENT_QUOTES, 'UTF-8') ?>" alt="">
                                    <?php else: ?>
                                        <?= htmlspecialchars(mb_strtoupper(mb_substr($participante['nombre'], 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </span>
                                <?= htmlspecialchars($participante['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <?php if ($mensajeMesa !== ''): ?>
                <p class="workroom-alert" role="status" style="border-color:var(--brand-orange);color:var(--brand-orange);font-weight:700;"><?= htmlspecialchars($mensajeMesa, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <div class="workroom-layout">
                <!-- ACTIVIDAD DEL EQUIPO (FEED) -->
                <section class="workroom-card workroom-feed">
                    <div class="workroom-section-heading">
                        <h2>Actividad del equipo</h2>
                        <span class="workroom-count"><?= count($avances) ?> <?= count($avances) === 1 ? 'actualización' : 'actualizaciones' ?></span>
                    </div>
                    <?php if (!$avances): ?>
                        <div class="workroom-empty">
                            <span class="workroom-empty-icon" aria-hidden="true">✦</span>
                            <strong>El proyecto empieza aquí</strong>
                            <p class="workroom-muted">Cuando alguien comparta un avance, aparecerá en esta bitácora para todo el equipo.</p>
                        </div>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column; gap:1.25rem;">
                            <?php foreach ($avances as $avance): ?>
                                <?php
                                $avatarAutor = is_string($avance['avatar_path'])
                                    && strpos($avance['avatar_path'], '/poliart/uploads/avatars/') === 0
                                    ? $avance['avatar_path']
                                    : '';
                                ?>
                                <article class="workroom-update">
                                    <div class="workroom-update-head">
                                        <span class="workroom-avatar">
                                            <?php if ($avatarAutor !== ''): ?>
                                                <img src="<?= htmlspecialchars($avatarAutor, ENT_QUOTES, 'UTF-8') ?>" alt="">
                                            <?php else: ?>
                                                <?= htmlspecialchars(mb_strtoupper(mb_substr($avance['autor'], 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                                            <?php endif; ?>
                                        </span>
                                        <div>
                                            <strong><?= htmlspecialchars($avance['autor'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                        <time datetime="<?= htmlspecialchars($avance['creado_en'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(date('d/m/Y · H:i', strtotime($avance['creado_en'])), ENT_QUOTES, 'UTF-8') ?></time>
                                    </div>
                                    <?php if ($avance['progreso'] !== null): ?>
                                        <span class="workroom-update-progress">Proyecto al <?= (int)$avance['progreso'] ?>%</span>
                                    <?php endif; ?>
                                    <?php if ($avance['descripcion'] !== null && $avance['descripcion'] !== ''): ?>
                                        <p><?= htmlspecialchars($avance['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php endif; ?>
                                    <?php if ($avance['ruta_imagen']): ?>
                                        <img src="../includes/actions/imagen_avance_colaboracion.php?id=<?= (int)$avance['id_avance'] ?>" alt="<?= htmlspecialchars($avance['nombre_imagen'] ?? 'Imagen de avance', ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- FORMULARIO / PANEL DE PUBLICACIÓN -->
                <?php if ($proyecto['estado'] === 'activo'): ?>
                    <aside class="workroom-card workroom-composer">
                        <div class="workroom-composer-intro">
                            <?php
                            $avatarPropio = '';
                            $nombrePropio = 'Comparte con tu equipo';
                            foreach ($participantes as $participante) {
                                if ((int)$participante['id_artista'] === $idArtista) {
                                    $nombrePropio = $participante['nombre'];
                                    $avatarPropio = is_string($participante['avatar_path'])
                                        && strpos($participante['avatar_path'], '/poliart/uploads/avatars/') === 0
                                        ? $participante['avatar_path']
                                        : '';
                                    break;
                                }
                            }
                            ?>
                            <span class="workroom-avatar">
                                <?php if ($avatarPropio !== ''): ?>
                                    <img src="<?= htmlspecialchars($avatarPropio, ENT_QUOTES, 'UTF-8') ?>" alt="">
                                <?php else: ?>
                                    <?= htmlspecialchars(mb_strtoupper(mb_substr($nombrePropio, 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </span>
                            <div>
                                <strong>Comparte un avance</strong>
                                <span>Visible para el equipo</span>
                            </div>
                        </div>
                        <form class="workroom-form" method="POST" enctype="multipart/form-data">
                            <div>
                                <label for="avance-descripcion">Cuéntale al equipo qué estás haciendo</label>
                                <textarea id="avance-descripcion" name="descripcion" rows="3" maxlength="3000" placeholder="Comparte una idea, una decisión o cómo va el trabajo..."></textarea>
                            </div>
                            <div>
                                <label for="avance-progreso">Progreso del proyecto (%)</label>
                                <input id="avance-progreso" type="number" name="progreso" min="0" max="100" placeholder="<?= (int)$proyecto['progreso'] ?>">
                            </div>
                            <div class="workroom-image-picker">
                                <label for="avance-imagen" style="cursor:pointer;margin:0;">
                                    <span style="font-size:1.25rem;display:block;margin-bottom:.2rem;">📷</span>
                                    <span style="font-weight:700;color:var(--text-main);font-size:.85rem;">Añade una imagen del proceso</span>
                                    <span style="display:block;margin-top:.25rem;color:var(--text-muted);font-size:.72rem;">JPG, PNG, GIF o WebP · hasta 10 MB</span>
                                </label>
                                <input id="avance-imagen" type="file" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" onchange="this.previousElementSibling.querySelector('span:nth-child(2)').textContent = this.files[0] ? this.files[0].name : 'Añade una imagen del proceso'">
                            </div>
                            <button type="submit"><span aria-hidden="true">＋</span> Publicar en la bitácora</button>
                        </form>
                    </aside>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
