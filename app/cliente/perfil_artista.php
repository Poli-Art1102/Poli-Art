<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['cliente', 'artista']);
require_once __DIR__ . '/../cliente/header.php';
include_once __DIR__ . '/../cliente/sidebar.php';

$project_base = '/poliart/';

// Obtener el ID solicitado o, si es el perfil propio, el de la sesión.
$artista_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($artista_id < 1 && getUserRole() === 'artista') {
    $artista_id = (int)($_SESSION['user']['id_artista'] ?? 0);
}

require_once __DIR__ . '/../includes/catalogo_obras.php';
try {
    $pdo = getDB();
    $stmtArtista = $pdo->prepare(
        'SELECT a.id_artista, a.id_usuario, a.nombre_artistico, a.especialidad, a.biografia,
                u.nombre, u.apellido, u.nombre_usuario, u.avatar_path,
                z.nombre AS zona
         FROM artista a
         INNER JOIN usuario u ON u.id_usuario = a.id_usuario
         LEFT JOIN zona z ON z.id_zona = u.id_zona
         WHERE a.id_artista = :id_artista AND u.estado = \'activo\'
         LIMIT 1'
    );
    $stmtArtista->execute([':id_artista' => $artista_id]);
    $filaArtista = $stmtArtista->fetch();
    if (!$filaArtista) {
        http_response_code(404);
        exit('No se encontró el perfil de artista solicitado.');
    }

    $stmtObras = $pdo->prepare(
        'SELECT o.id_obra AS id, o.titulo, o.descripcion, o.ruta_imagen,
                o.precio, o.moneda, o.estado_venta, o.publicada_en, o.creado_en, c.nombre AS categoria,
                (SELECT COUNT(*) FROM me_gusta mg WHERE mg.id_obra = o.id_obra) AS likes
                , EXISTS(
                    SELECT 1 FROM me_gusta mg_usuario
                    WHERE mg_usuario.id_obra = o.id_obra AND mg_usuario.id_usuario = :id_usuario
                ) AS liked
         FROM obra o
         INNER JOIN categoria_obra c ON c.id_categoria = o.id_categoria
         WHERE o.id_artista = :id_artista AND o.estado_publicacion = \'publicada\'
         ORDER BY COALESCE(o.publicada_en, o.creado_en) DESC, o.id_obra DESC'
    );
    $stmtObras->execute([':id_artista' => $artista_id, ':id_usuario' => getUserId()]);
    $obrasArtista = $stmtObras->fetchAll();
    $stmtLikes = $pdo->prepare(
        'SELECT COUNT(*)
         FROM me_gusta mg
         INNER JOIN obra o ON o.id_obra = mg.id_obra
         WHERE o.id_artista = :id_artista AND o.estado_publicacion = \'publicada\''
    );
    $stmtLikes->execute([':id_artista' => $artista_id]);
    $stmtColaboraciones = $pdo->prepare(
        'SELECT COUNT(DISTINCT o.id_obra)
         FROM obra o
         WHERE o.estado_publicacion = \'publicada\'
           AND EXISTS (
               SELECT 1 FROM obra_colaborador oc
               WHERE oc.id_obra = o.id_obra
           )
           AND (
               o.id_artista = :id_artista_creador
               OR EXISTS (
                   SELECT 1 FROM obra_colaborador oc
                   WHERE oc.id_obra = o.id_obra
                     AND oc.id_artista = :id_artista_colaborador
               )
           )'
    );
    $stmtColaboraciones->execute([
        ':id_artista_creador' => $artista_id,
        ':id_artista_colaborador' => $artista_id,
    ]);
    $stmtSiguiendo = $pdo->prepare(
        'SELECT EXISTS(
             SELECT 1 FROM seguimiento
             WHERE id_seguidor = :id_seguidor AND id_seguido = :id_seguido
         )'
    );
    $stmtSiguiendo->execute([
        ':id_seguidor' => getUserId(),
        ':id_seguido' => (int)$filaArtista['id_usuario'],
    ]);

    $nombreArtista = trim((string)($filaArtista['nombre_artistico'] ?? ''));
    if ($nombreArtista === '') {
        $nombreArtista = trim($filaArtista['nombre'] . ' ' . $filaArtista['apellido']);
    }
    $avatar = normalizarRutaPublica($filaArtista['avatar_path'], 'avatars') ?? '';
    $esPerfilPropio = (int)$filaArtista['id_usuario'] === getUserId();
    $artista = [
        'nombre' => $nombreArtista,
        'usuario' => '@' . $filaArtista['nombre_usuario'],
        'especialidad' => $filaArtista['especialidad'] ?: 'Artista Poli-Art',
        'zona' => $filaArtista['zona'] ?? 'Sin zona',
        'avatar' => $avatar,
        'iniciales' => mb_strtoupper(mb_substr($nombreArtista, 0, 2, 'UTF-8'), 'UTF-8'),
        'disponible' => false,
        'obras_count' => count($obrasArtista),
        'likes_count' => (int)$stmtLikes->fetchColumn(),
        'colabs_count' => (int)$stmtColaboraciones->fetchColumn(),
        'siguiendo' => (bool)$stmtSiguiendo->fetchColumn(),
        'id_usuario' => (int)$filaArtista['id_usuario'],
        'es_perfil_propio' => $esPerfilPropio,
        'obras' => array_map(static function (array $obra): array {
            $imagen = normalizarRutaPublica($obra['ruta_imagen'], 'obras') ?? '';
            return [
                'id' => (int)$obra['id'],
                'titulo' => $obra['titulo'],
                'precio' => number_format((float)$obra['precio'], 2, ',', '.') . ' ' . $obra['moneda'],
                'estado_venta' => $obra['estado_venta'],
                'likes' => (int)$obra['likes'],
                'categoria' => $obra['categoria'],
                'descripcion' => $obra['descripcion'] ?? '',
                'img' => $imagen,
            ];
        }, $obrasArtista),
    ];
    $obrasFeedArtista = array_map(static function (array $obra) use ($artista, $filaArtista): array {
        return [
            'id' => (int)$obra['id'],
            'titulo' => $obra['titulo'],
            'descripcion' => $obra['descripcion'] ?? '',
            'ruta_imagen' => normalizarRutaPublica($obra['ruta_imagen'], 'obras'),
            'precio' => (float)$obra['precio'],
            'moneda' => $obra['moneda'],
            'estado_venta' => $obra['estado_venta'],
            'publicada_en' => $obra['publicada_en'] ?? $obra['creado_en'],
            'creado_en' => $obra['creado_en'],
            'categoria' => $obra['categoria'],
            'zona' => $artista['zona'],
            'artista' => $artista['nombre'],
            'avatar_path' => $artista['avatar'],
            'total_likes' => (int)$obra['likes'],
            'liked' => (bool)$obra['liked'],
            'id_usuario' => (int)$filaArtista['id_usuario'],
        ];
    }, $obrasArtista);
} catch (PDOException $e) {
    error_log('Error al cargar el perfil público del artista: ' . $e->getMessage());
    http_response_code(500);
    exit('No fue posible cargar el perfil de artista.');
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

.profile-main-container {
    display: flex;
    width: calc(100vw - 260px);
    height: calc(100vh - 4.25rem);
    overflow: hidden;
    background: transparent;
}

.profile-scroll-area {
    flex-grow: 1;
    height: 100%;
    overflow-y: auto;
    padding: 2rem 2.5rem;
    box-sizing: border-box;
}

/* BANNER Y TARJETA DEL ARTISTA */
.artist-header-card {
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1.25rem;
    overflow: hidden;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(12px);
    max-width: 1100px;
    margin: 0 auto 2rem auto;
}

.artist-banner {
    width: 100%;
    height: 160px;
    background: linear-gradient(135deg, #f97316 0%, #fb923c 40%, #f43f5e 100%);
}

.artist-body {
    padding: 0 2rem 1.5rem 2rem;
    position: relative;
}

.artist-avatar-badge {
    width: 6.5rem;
    height: 6.5rem;
    border-radius: 9999px;
    background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
    color: #ffffff;
    font-size: 2.25rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4px solid var(--bg-surface);
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    margin-top: -3.5rem;
}

.stat-pill {
    background: var(--hover-item);
    border: 1px solid var(--border-subtle);
    border-radius: 0.75rem;
    padding: 0.5rem 1.25rem;
    text-align: center;
}

.stat-pill .number {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--text-main);
    display: block;
}

.stat-pill .label {
    font-size: 0.68rem;
    color: var(--text-muted);
    font-weight: 800;
    text-transform: uppercase;
}

.btn-message-artist {
    background: linear-gradient(135deg, var(--brand-orange) 0%, #ea580c 100%);
    color: #ffffff;
    text-decoration: none;
    padding: 0.65rem 1.35rem;
    border-radius: 0.75rem;
    font-size: 0.88rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 4px 15px rgba(249, 115, 22, 0.35);
    transition: transform 0.2s ease;
}

.btn-message-artist:hover {
    transform: scale(1.03);
}

.btn-following-pill {
    background: var(--hover-item);
    border: 1px solid var(--border-subtle);
    color: var(--text-main);
    padding: 0.65rem 1.25rem;
    border-radius: 0.75rem;
    font-size: 0.88rem;
    font-weight: 700;
    text-decoration: none;
}

.btn-follow-artist {
    border: 1px solid var(--brand-orange);
    background: linear-gradient(135deg, var(--brand-orange), #ea580c);
    color: #fff;
    padding: 0.65rem 1.25rem;
    border-radius: 0.75rem;
    font-size: 0.88rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(249, 115, 22, 0.25);
    transition: transform .2s ease, background .2s ease, color .2s ease;
}

.btn-follow-artist:hover,
.artist-work-action:hover {
    transform: translateY(-1px);
}

.btn-follow-artist[data-following="true"] {
    background: var(--hover-item);
    color: var(--text-main);
    box-shadow: none;
}

.btn-report-artist {
    border: 1px solid rgba(239, 68, 68, .45);
    background: rgba(239, 68, 68, .12);
    color: #ef4444;
    padding: .65rem 1rem;
    border-radius: .75rem;
    font: inherit;
    font-size: .82rem;
    font-weight: 800;
    cursor: pointer;
}
.btn-report-artist:hover { background: rgba(239, 68, 68, .22); }

/* GRID DE OBRAS REDONDEADAS TIPO BENTO */
.artwork-grid-bento {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.25rem;
    max-width: 1100px;
    margin: 0 auto;
}

@media (max-width: 1200px) {
    .artwork-grid-bento {
        grid-template-columns: repeat(2, 1fr);
    }
}

.artwork-bento-card {
    position: relative;
    width: 100%;
    border-radius: 1.25rem;
    overflow: hidden;
    background: var(--bg-surface);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    border: 1px solid var(--border-subtle);
    transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}

.artwork-bento-image {
    position: relative;
    display: block;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    text-decoration: none;
}

.artwork-bento-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.artwork-bento-card:hover {
    transform: translateY(-5px);
    border-color: var(--brand-orange);
    box-shadow: 0 12px 28px rgba(249, 115, 22, 0.25);
}

.artwork-bento-card:hover .artwork-bento-image img {
    transform: scale(1.06);
}

.artwork-overlay-hover {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.2) 60%, rgba(0,0,0,0) 100%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 1.25rem;
    box-sizing: border-box;
    opacity: 0;
    transition: opacity 0.25s ease;
}

.artwork-bento-image:hover .artwork-overlay-hover,
.artwork-bento-image:focus-visible .artwork-overlay-hover {
    opacity: 1;
}

.artwork-bento-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .6rem;
    flex-wrap: wrap;
    padding: .85rem;
    background: var(--bg-surface);
    border-top: 1px solid var(--border-subtle);
}

.artist-work-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .35rem;
    min-height: 2.4rem;
    padding: .5rem .75rem;
    border: 1px solid var(--border-subtle);
    border-radius: .6rem;
    background: var(--hover-item);
    color: var(--text-main);
    font: inherit;
    font-size: .78rem;
    font-weight: 750;
    text-decoration: none;
    cursor: pointer;
    transition: transform .2s ease, border-color .2s ease, background .2s ease;
}

.artist-work-action:hover {
    border-color: var(--brand-orange);
}

.artist-work-action.cart {
    border-color: var(--brand-orange);
    background: linear-gradient(135deg, var(--brand-orange), #ea580c);
    color: #fff;
}

.artist-work-action.report {
    border-color: rgba(239, 68, 68, .35);
    background: rgba(239, 68, 68, .12);
    color: #dc2626;
}

.artwork-action-status {
    flex: 1 0 100%;
    min-height: 1rem;
    margin: 0;
    color: var(--text-muted);
    font-size: .75rem;
}

.artist-report-overlay {
    position: fixed;
    inset: 0;
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    box-sizing: border-box;
    background: rgba(0, 0, 0, .7);
    backdrop-filter: blur(5px);
}

.artist-report-overlay.active {
    display: flex;
}

.artist-report-form {
    width: 100%;
    max-width: 600px;
    padding: 1rem;
    border: 1px solid var(--border-subtle);
    border-radius: 1rem;
    background: var(--bg-surface);
    color: var(--text-main);
    box-shadow: 0 20px 40px rgba(0, 0, 0, .3);
}

.artist-report-form select,
.artist-report-form textarea {
    display: block;
    width: 100%;
    box-sizing: border-box;
    margin: .4rem 0 1rem;
    padding: .7rem;
    border: 1px solid var(--border-subtle);
    border-radius: .6rem;
    background: var(--hover-item);
    color: var(--text-main);
    font: inherit;
}

/* --- MODAL VISOR DE OBRA AMPLIA --- */
.artwork-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(10px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    box-sizing: border-box;
}

.artwork-modal-backdrop.active {
    display: flex;
}

.artwork-modal-container {
    background: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1.25rem;
    max-width: 950px;
    width: 100%;
    max-height: 90vh;
    display: flex;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
    position: relative;
}

@media (max-width: 768px) {
    .artwork-modal-container {
        flex-direction: column;
        max-height: 95vh;
        overflow-y: auto;
    }
}

.artwork-modal-img-wrapper {
    flex: 1.2;
    background: #000;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.artwork-modal-img-wrapper img {
    width: 100%;
    height: 100%;
    max-height: 80vh;
    object-fit: contain;
}

.artwork-modal-info {
    flex: 1;
    padding: 2rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-sizing: border-box;
}

.close-modal-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(0,0,0,0.5);
    color: #fff;
    border: none;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 9999px;
    font-size: 1.2rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    transition: background 0.2s;
}

.close-modal-btn:hover {
    background: var(--brand-orange);
}

/* CONTENEDOR TOAST FLOTANTE (REEMPLAZA ALERTS NATIVOS) */
.toast-container {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    z-index: 10000;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    pointer-events: none;
}

.toast-message {
    background-color: var(--bg-surface);
    color: var(--text-main);
    border: 1px solid var(--border-subtle);
    border-left: 4px solid var(--brand-orange);
    padding: 0.85rem 1.25rem;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    font-weight: 700;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
    display: flex;
    align-items: center;
    gap: 0.6rem;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease;
    pointer-events: auto;
}

.toast-message.show {
    opacity: 1;
    transform: translateY(0);
}
</style>

<div class="profile-main-container">
    <div class="profile-scroll-area">
        
        <!-- CABECERA DEL PERFIL DE ARTISTA -->
        <div class="artist-header-card">
            <div class="artist-banner"></div>
            
            <div class="artist-body">
                <div style="display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
                    
                    <div style="display: flex; align-items: flex-end; gap: 1.25rem;">
                        <div class="artist-avatar-badge">
                            <?php if ($artista['avatar'] !== ''): ?>
                                <img src="<?= htmlspecialchars($artista['avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="Avatar de <?= htmlspecialchars($artista['nombre'], ENT_QUOTES, 'UTF-8') ?>" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
                            <?php else: ?>
                                <?= htmlspecialchars($artista['iniciales'], ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </div>
                        <div style="margin-bottom: 0.25rem;">
                            <div style="display: flex; align-items: center; gap: 0.6rem;">
                                <h1 style="color: var(--text-main); font-size: 1.75rem; font-weight: 800; margin: 0;">
                                    <?= htmlspecialchars($artista['nombre']) ?>
                                </h1>
                                <?php if ($artista['disponible']): ?>
                                    <span style="background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); padding: 0.15rem 0.6rem; border-radius: 9999px; font-size: 0.725rem; font-weight: 700;">
                                        🟢 Disponible
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.2rem;">
                                <?= htmlspecialchars($artista['usuario']) ?> &bull; <strong style="color: var(--brand-orange);"><?= htmlspecialchars($artista['especialidad']) ?></strong> &bull; 📍 <?= htmlspecialchars($artista['zona']) ?>
                            </div>
                        </div>
                    </div>

                    <!-- ESTADÍSTICAS Y ACCIONES -->
                    <div style="display: flex; align-items: center; gap: 0.85rem;">
                        <div class="stat-pill">
                            <span class="number"><?= $artista['obras_count'] ?></span>
                            <span class="label">Obras</span>
                        </div>
                        <div class="stat-pill">
                            <span class="number"><?= $artista['likes_count'] ?></span>
                            <span class="label">Likes</span>
                        </div>
                        <div class="stat-pill">
                            <span class="number"><?= $artista['colabs_count'] ?></span>
                            <span class="label">Colabs</span>
                        </div>

                        <a href="mensajes.php?artista_id=<?= $artista_id ?>&nombre=<?= urlencode($artista['nombre']) ?>&zona=<?= urlencode($artista['zona']) ?>&avatar=<?= urlencode($artista['avatar']) ?>" class="btn-message-artist">
                            💬 Enviar Mensaje
                        </a>
                        <?php if ($artista['es_perfil_propio']): ?>
                            <span class="btn-following-pill">Tu perfil</span>
                        <?php else: ?>
                            <button type="button" id="btn-seguir-artista" class="btn-follow-artist" data-artist-id="<?= $artista_id ?>" data-following="<?= $artista['siguiendo'] ? 'true' : 'false' ?>" aria-pressed="<?= $artista['siguiendo'] ? 'true' : 'false' ?>" onclick="alternarSeguimientoArtista()">
                                <?= $artista['siguiendo'] ? 'Siguiendo' : 'Seguir artista' ?>
                            </button>
                            <button type="button" id="btn-reportar-artista" class="btn-report-artist">⚠️ Reportar artista</button>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- SECCIÓN DE NAVEGACIÓN DENTRO DEL PERFIL -->
                <div style="border-top: 1px solid var(--border-subtle); padding-top: 0.85rem; margin-top: 1rem; display: flex; gap: 1rem;">
                    <span style="color: var(--brand-orange); font-weight: 800; font-size: 0.9rem; border-bottom: 2px solid var(--brand-orange); padding-bottom: 0.4rem;">
                        🖼️ Publicaciones del Artista
                    </span>
                </div>
            </div>
        </div>

        <!-- GRID DE OBRAS PUBLICADAS POR EL ARTISTA -->
        <div class="artwork-grid-bento" id="grid-obras-artista">
            <?php if (!$artista['obras']): ?>
                <p style="grid-column:1/-1;padding:1.5rem;text-align:center;background:var(--bg-surface);color:var(--text-muted);border:1px solid var(--border-subtle);border-radius:1rem;">
                    Este artista todavía no tiene obras publicadas.
                </p>
            <?php else: ?>
                <?php foreach ($artista['obras'] as $obra): ?>
                    <article class="artwork-bento-card" onclick="mostrarFeedPerfilArtista(<?= (int)$obra['id'] ?>)" style="cursor:pointer;">
                        <a class="artwork-bento-image" href="#feed-obras-artista" onclick="event.preventDefault();event.stopPropagation();mostrarFeedPerfilArtista(<?= (int)$obra['id'] ?>)" aria-label="Ver <?= htmlspecialchars($obra['titulo'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php renderizarVistaPreviaObra($obra['img'], $obra['titulo'], '280px'); ?>
                            <div class="artwork-overlay-hover">
                                <strong style="color: #ffffff; font-size: 1rem; font-weight: 800; display: block;">
                                    <?= htmlspecialchars($obra['titulo'], ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.4rem;">
                                    <span style="color: var(--brand-orange); font-weight: 800; font-size: 0.9rem;"><?= htmlspecialchars($obra['precio'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span style="color: #ffffff; font-size: 0.8rem; font-weight: 700;">❤️ <?= (int)$obra['likes'] ?></span>
                                </div>
                            </div>
                        </a>
                        <?php if (!$artista['es_perfil_propio']): ?>
                            <div class="artwork-bento-actions">
                                <a class="artist-work-action" href="ver_obra.php?id=<?= (int)$obra['id'] ?>">❤️ <?= (int)$obra['likes'] ?> Me gusta</a>
                                <?php if ($obra['estado_venta'] === 'disponible'): ?>
                                    <button type="button" class="artist-work-action cart" data-obra="<?= (int)$obra['id'] ?>">🛒 Añadir al carrito</button>
                                <?php endif; ?>
                                <button type="button" class="artist-work-action report" data-report-obra="<?= (int)$obra['id'] ?>">⚠️ Reportar obra</button>
                                <p class="artwork-action-status" role="status" aria-live="polite"></p>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if ($artista['obras']): ?>
            <button type="button" id="btn-volver-obras-artista" onclick="mostrarGaleriaPerfilArtista()" style="display:none;margin:1rem 0;padding:.65rem 1.25rem;border:1px solid var(--border-subtle);border-radius:.6rem;background:var(--hover-item);color:var(--text-main);font-weight:700;cursor:pointer;">← Volver a las obras</button>
            <style>
       #feed-obras-artista .client-work-status{display:inline-flex;align-items:center;margin-left:.55rem;padding:.2rem .55rem;border:1px solid rgba(148,163,184,.35);border-radius:999px;font-size:.72rem;font-weight:700;vertical-align:middle}
       #feed-obras-artista .client-work-status[data-status="disponible"]{color:#22c55e;border-color:rgba(34,197,94,.5);background:rgba(34,197,94,.12)}
       #feed-obras-artista .client-work-status[data-status="reservada"]{color:#f59e0b;border-color:rgba(245,158,11,.5);background:rgba(245,158,11,.12)}
       #feed-obras-artista .client-work-status[data-status="vendida"]{color:#f87171;border-color:rgba(248,113,113,.5);background:rgba(248,113,113,.12)}
       #feed-obras-artista .client-work-status[data-status="no_disponible"]{color:#cbd5e1;border-color:rgba(148,163,184,.45);background:rgba(148,163,184,.12)}
       #feed-obras-artista .client-feed-action{display:inline-flex;align-items:center;justify-content:center;gap:.45rem;padding:.5rem .9rem;border:1px solid rgba(148,163,184,.3);border-radius:.6rem;color:var(--text-main);background:rgba(148,163,184,.1);font-size:.825rem;font-weight:700;text-decoration:none;cursor:pointer;transition:all .2s ease}
       #feed-obras-artista .client-details-action{border-color:rgba(56,189,248,.38);background:rgba(56,189,248,.12);color:#38bdf8}
       #feed-obras-artista .client-details-action:hover{border-color:rgba(56,189,248,.65);background:rgba(56,189,248,.2)}
       #feed-obras-artista{display:none;flex-direction:column;gap:1.25rem;width:100%;max-width:680px;margin:0 auto}
                #feed-obras-artista>article{width:100%;max-width:680px}
                #feed-obras-artista .feed-post-card{background:var(--bg-surface);border:1px solid var(--border-subtle);border-radius:1.25rem;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,.15);backdrop-filter:blur(8px)}
                #feed-obras-artista .feed-image-box{width:100%;max-height:520px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#000}
                #feed-obras-artista .client-feed-post-card{display:flex;flex-direction:column}
                #feed-obras-artista .client-feed-post-card>div:nth-child(1){order:1}
                #feed-obras-artista .client-feed-post-card>div:nth-child(2){order:3}
                #feed-obras-artista .client-feed-post-card>div:nth-child(3){order:2}
                #feed-obras-artista .client-feed-post-card>div:nth-child(4){order:4}
            </style>
            <div id="feed-obras-artista">
                <?php renderizarFeedObras($obrasFeedArtista, '/poliart/app/cliente/ver_obra.php'); ?>
            </div>
            <script>
            function mostrarFeedPerfilArtista(obraId) {
                var grid = document.getElementById('grid-obras-artista');
                var feed = document.getElementById('feed-obras-artista');
                var volver = document.getElementById('btn-volver-obras-artista');
                if (!grid || !feed) return;
                grid.style.display = 'none';
                feed.style.display = 'flex';
                if (volver) volver.style.display = 'inline-block';
                var obra = feed.querySelector('[data-obra-id="' + obraId + '"]');
                if (obra) obra.scrollIntoView({behavior:'smooth', block:'start'});
            }
            function mostrarGaleriaPerfilArtista() {
                var grid = document.getElementById('grid-obras-artista');
                var feed = document.getElementById('feed-obras-artista');
                var volver = document.getElementById('btn-volver-obras-artista');
                if (grid) grid.style.display = 'grid';
                if (feed) feed.style.display = 'none';
                if (volver) volver.style.display = 'none';
            }
            document.querySelectorAll('#feed-obras-artista .client-feed-post-card').forEach(function(card){
                var report=card.querySelector('button[onclick^="abrirReporteFeed"]');
                var header=card.children[0];
                if(report&&header){report.style.marginLeft='auto';report.style.padding='.5rem .9rem';report.style.border='1px solid rgba(239,68,68,.35)';report.style.borderRadius='.6rem';report.style.background='rgba(239,68,68,.15)';report.style.color='#ef4444';report.style.fontWeight='700';header.appendChild(report);}
            });
            document.querySelectorAll('#grid-obras-artista .artwork-bento-actions').forEach(function(actions){actions.addEventListener('click',function(event){event.stopPropagation();});});
            </script>
        <?php endif; ?>

    </div>
</div>

<?php if (!$artista['es_perfil_propio']): ?>
    <style>
        .reporte-option-global { display:block; margin:.75rem 0; padding:.9rem 1rem; border:1px solid var(--brand-orange); border-radius:.85rem; cursor:pointer; }
        .reporte-option-global input { accent-color:var(--brand-orange); margin-right:.75rem; }
        .reporte-option-global strong, .reporte-option-global span { display:block; margin-left:1.55rem; }
        .reporte-option-global strong { margin-top:-1.35rem; color:var(--text-main); }
        .reporte-option-global span { margin-top:.3rem; color:var(--text-muted); font-size:.9rem; }
    </style>
    <div id="modal-reporte-perfil-artista" class="artist-report-overlay" role="dialog" aria-modal="true" aria-labelledby="titulo-reporte-perfil-artista">
        <form id="form-reporte-perfil-artista" class="artist-report-form">
            <button type="button" id="cerrar-reporte-perfil-artista" aria-label="Cerrar" style="float:right;width:2.75rem;height:2.75rem;border:1px solid var(--border-subtle);border-radius:50%;background:transparent;color:var(--text-main);font-size:1.5rem;font-weight:800;cursor:pointer;">×</button>
            <h2 id="titulo-reporte-perfil-artista" style="margin:0 0 1rem;">⚠️ Reportar esta obra</h2>
            <input type="hidden" name="id_obra" id="reporte-perfil-artista-obra">
            <label for="reporte-perfil-artista-motivo">Motivo</label>
            <div class="reporte-options-global" role="radiogroup" aria-label="Motivo del reporte">
                <label class="reporte-option-global"><input type="radio" name="motivo-perfil-visual" value="plagio" checked><strong>Plagio / Copia no autorizada</strong><span>Derechos de autor pertenecientes a otro creador.</span></label>
                <label class="reporte-option-global"><input type="radio" name="motivo-perfil-visual" value="inapropiado"><strong>Contenido Inapropiado u Ofensivo</strong><span>Imágenes explícitas, violencia o incitación al odio.</span></label>
                <label class="reporte-option-global"><input type="radio" name="motivo-perfil-visual" value="spam"><strong>Spam / Información Engañosa</strong><span>Precio engañoso, fraude o publicación sospechosa.</span></label>
                <label class="reporte-option-global"><input type="radio" name="motivo-perfil-visual" value="otro"><strong>Otro motivo</strong><span>Otra infracción a las políticas de Poli-Art.</span></label>
            </div>
            <select id="reporte-perfil-artista-motivo" name="motivo" required style="display:none;">
                <option value="plagio">Plagio o copia no autorizada</option>
                <option value="inapropiado">Contenido inapropiado</option>
                <option value="spam">Spam o información engañosa</option>
                <option value="otro">Otro motivo</option>
            </select>
            <label for="reporte-perfil-artista-detalle" id="etiqueta-detalle-perfil" style="display:none;">Detalles del reporte</label>
            <textarea id="reporte-perfil-artista-detalle" name="detalle" rows="3" maxlength="1000" style="display:none;"></textarea>
            <p id="estado-reporte-perfil-artista" role="status" aria-live="polite"></p>
            <div style="display:flex;justify-content:flex-end;gap:.6rem;">
                <button type="button" class="artist-work-action" id="cancelar-reporte-perfil-artista">Cancelar</button>
                <button type="submit" class="artist-work-action report">Enviar reporte</button>
            </div>
        </form>
    </div>
    <div id="modal-reporte-artista" class="artist-report-overlay" role="dialog" aria-modal="true" aria-labelledby="titulo-reporte-artista">
        <form id="form-reporte-artista" class="artist-report-form">
            <button type="button" id="cerrar-reporte-artista" aria-label="Cerrar" style="float:right;width:2.75rem;height:2.75rem;border:1px solid var(--border-subtle);border-radius:50%;background:transparent;color:var(--text-main);font-size:1.5rem;font-weight:800;cursor:pointer;">×</button>
            <h2 id="titulo-reporte-artista" style="margin:0 0 1rem;">⚠️ Reportar artista</h2>
            <p style="color:var(--text-muted);margin:0 0 1rem;">Ayúdanos a mantener segura la comunidad.</p>
            <label for="reporte-artista-motivo">Motivo</label>
            <select id="reporte-artista-motivo" name="motivo" required style="width:100%;margin:.5rem 0 1rem;padding:.7rem;border:1px solid var(--border-subtle);border-radius:.6rem;background:var(--bg-surface);color:var(--text-main);">
                <option value="acoso">Acoso o comportamiento abusivo</option>
                <option value="fraude">Fraude o estafa</option>
                <option value="contenido_inapropiado">Contenido inapropiado</option>
                <option value="suplantacion">Suplantación de identidad</option>
                <option value="otro">Otro motivo</option>
            </select>
            <label for="reporte-artista-detalle">Detalles</label>
            <textarea id="reporte-artista-detalle" name="detalle" rows="4" maxlength="1000" placeholder="Cuéntanos qué ocurrió..."></textarea>
            <p id="estado-reporte-artista" role="status" aria-live="polite"></p>
            <div style="display:flex;justify-content:flex-end;gap:.6rem;">
                <button type="button" class="artist-work-action" id="cancelar-reporte-artista">Cancelar</button>
                <button type="submit" class="artist-work-action report">Enviar reporte</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<script>
async function alternarSeguimientoArtista() {
    const button = document.getElementById('btn-seguir-artista');
    if (!button || button.disabled) return;

    button.disabled = true;
    try {
        const response = await fetch('../includes/actions/seguir.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id_artista: button.dataset.artistId })
        });
        const result = await response.json();
        if (!response.ok || !result.ok) {
            throw new Error(result.error || 'No fue posible actualizar el seguimiento.');
        }
        button.dataset.following = result.following ? 'true' : 'false';
        button.setAttribute('aria-pressed', result.following ? 'true' : 'false');
        button.textContent = result.following ? 'Siguiendo' : 'Seguir artista';
    } catch (error) {
        window.alert(error.message || 'No fue posible actualizar el seguimiento.');
    } finally {
        button.disabled = false;
    }
}

document.querySelectorAll('.artwork-bento-actions .cart').forEach(button => {
    button.addEventListener('click', async () => {
        const originalText = button.textContent;
        const status = button.closest('.artwork-bento-actions').querySelector('.artwork-action-status');
        button.disabled = true;
        button.textContent = '⏳ Agregando...';
        try {
            const response = await fetch('../includes/actions/carrito.php?accion=agregar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ id_obra: button.dataset.obra })
            });
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.error || 'No fue posible agregar la obra al carrito.');
            button.textContent = '✅ En el carrito';
            status.textContent = result.msg || 'Obra agregada al carrito.';
        } catch (error) {
            button.textContent = originalText;
            status.textContent = error.message || 'No fue posible agregar la obra al carrito.';
        } finally {
            button.disabled = false;
        }
    });
});

const reportOverlay = document.getElementById('modal-reporte-perfil-artista');
const reportForm = document.getElementById('form-reporte-perfil-artista');
if (reportOverlay && reportForm) {
    const reportStatus = document.getElementById('estado-reporte-perfil-artista');
    const reportMotive = document.getElementById('reporte-perfil-artista-motivo');
    const reportDetail = document.getElementById('reporte-perfil-artista-detalle');
    const reportDetailLabel = document.getElementById('etiqueta-detalle-perfil');
    const visualMotives = Array.from(document.querySelectorAll('input[name="motivo-perfil-visual"]'));
    const syncReportMotive = () => {
        const selected = visualMotives.find(input => input.checked);
        const motive = selected ? selected.value : 'plagio';
        reportMotive.value = motive;
        reportDetail.style.display = motive === 'otro' ? 'block' : 'none';
        reportDetailLabel.style.display = motive === 'otro' ? 'block' : 'none';
        reportDetail.required = motive === 'otro';
    };
    const closeReport = () => {
        reportOverlay.classList.remove('active');
        reportForm.reset();
        visualMotives[0].checked = true;
        syncReportMotive();
        reportStatus.textContent = '';
    };

    document.querySelectorAll('[data-report-obra]').forEach(button => {
        button.addEventListener('click', () => {
            document.getElementById('reporte-perfil-artista-obra').value = button.dataset.reportObra;
            reportOverlay.classList.add('active');
            syncReportMotive();
            visualMotives[0].focus();
        });
    });
    visualMotives.forEach(input => input.addEventListener('change', syncReportMotive));
    document.getElementById('cancelar-reporte-perfil-artista').addEventListener('click', closeReport);
    document.getElementById('cerrar-reporte-perfil-artista').addEventListener('click', closeReport);
    reportOverlay.addEventListener('click', event => {
        if (event.target === reportOverlay) closeReport();
    });
    reportForm.addEventListener('submit', async event => {
        event.preventDefault();
        const formData = new FormData(reportForm);
        if (formData.get('motivo') === 'otro' && !String(formData.get('detalle') || '').trim()) {
            reportStatus.textContent = 'Describe el motivo del reporte.';
            return;
        }

        const submitButton = reportForm.querySelector('[type="submit"]');
        submitButton.disabled = true;
        reportStatus.textContent = 'Enviando reporte...';
        try {
            const response = await fetch('../includes/actions/reportar.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.error || 'No fue posible enviar el reporte.');
            reportStatus.textContent = result.msg || 'Reporte enviado. Gracias por ayudar a moderar la comunidad.';
            window.setTimeout(closeReport, 1200);
        } catch (error) {
            reportStatus.textContent = error.message || 'No fue posible enviar el reporte.';
        } finally {
            submitButton.disabled = false;
        }
    });
}

const artistReportOverlay = document.getElementById('modal-reporte-artista');
const artistReportForm = document.getElementById('form-reporte-artista');
const openArtistReport = document.getElementById('btn-reportar-artista');
if (artistReportOverlay && artistReportForm && openArtistReport) {
    const artistReportStatus = document.getElementById('estado-reporte-artista');
    const closeArtistReport = () => {
        artistReportOverlay.classList.remove('active');
        artistReportForm.reset();
        artistReportStatus.textContent = '';
    };
    openArtistReport.addEventListener('click', () => artistReportOverlay.classList.add('active'));
    document.getElementById('cancelar-reporte-artista').addEventListener('click', closeArtistReport);
    document.getElementById('cerrar-reporte-artista').addEventListener('click', closeArtistReport);
    artistReportOverlay.addEventListener('click', event => {
        if (event.target === artistReportOverlay) closeArtistReport();
    });
    artistReportForm.addEventListener('submit', async event => {
        event.preventDefault();
        const submitButton = artistReportForm.querySelector('[type="submit"]');
        const formData = new FormData(artistReportForm);
        formData.append('id_artista', '<?= (int)$artista_id ?>');
        submitButton.disabled = true;
        artistReportStatus.textContent = 'Enviando reporte...';
        try {
            const response = await fetch('../includes/actions/reportar_artista.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.error || 'No fue posible enviar el reporte.');
            artistReportStatus.textContent = result.msg || 'Reporte enviado correctamente.';
            window.setTimeout(closeArtistReport, 1200);
        } catch (error) {
            artistReportStatus.textContent = error.message || 'No fue posible enviar el reporte.';
        } finally {
            submitButton.disabled = false;
        }
    });
}
</script>
