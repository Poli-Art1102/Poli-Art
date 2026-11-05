<?php
require_once __DIR__ . '/db.php';

function normalizarRutaPublica(?string $ruta, string $subdirectorio): ?string
{
    if (!is_string($ruta) || trim($ruta) === '') {
        return null;
    }

    $ruta = trim($ruta);
    $rutaUrl = parse_url($ruta, PHP_URL_PATH);
    if (is_string($rutaUrl) && $rutaUrl !== '') {
        $ruta = $rutaUrl;
    }
    $ruta = str_replace('\\', '/', $ruta);

    // Soporta rutas relativas, URL completas y rutas físicas antiguas de Windows.
    $marcador = '/uploads/' . $subdirectorio . '/';
    $posicion = stripos($ruta, $marcador);
    if ($posicion !== false) {
        $archivo = ltrim(substr($ruta, $posicion + strlen($marcador)), '/');
        return $archivo !== '' ? '/poliart/uploads/' . $subdirectorio . '/' . $archivo : null;
    }
    $marcadorRelativo = 'uploads/' . $subdirectorio . '/';
    if (stripos($ruta, $marcadorRelativo) === 0) {
        $archivo = ltrim(substr($ruta, strlen($marcadorRelativo)), '/');
        return $archivo !== '' ? '/poliart/uploads/' . $subdirectorio . '/' . $archivo : null;
    }

    return null;
}

function obtenerObrasPorCategoria(?string $slug, ?int $idUsuario = null, ?int $limite = null): array {
    $limiteSql = $limite === null ? '' : ' LIMIT ' . max(1, min($limite, 100));
    $stmt = getDB()->prepare(
        'SELECT o.id_obra AS id, o.titulo, o.descripcion, o.ruta_imagen,
                o.precio, o.moneda, o.estado_venta, o.publicada_en, o.creado_en,
                c.nombre AS categoria,
                z.nombre AS zona,
                o.id_artista AS artista_id,
                COALESCE(NULLIF(a.nombre_artistico, \'\'), CONCAT(u.nombre, \' \', u.apellido)) AS artista,
                u.nombre_usuario, u.id_usuario,
                u.avatar_path,
                (SELECT COUNT(*) FROM me_gusta mg WHERE mg.id_obra = o.id_obra) AS total_likes,
                (SELECT COUNT(*) FROM obra_visita ov WHERE ov.id_obra = o.id_obra) AS total_vistas,
                EXISTS(
                    SELECT 1 FROM me_gusta mg
                    WHERE mg.id_obra = o.id_obra AND mg.id_usuario = :id_usuario
                ) AS liked,
                EXISTS(
                    SELECT 1 FROM seguimiento s
                    WHERE s.id_seguidor = :id_usuario_seguidor
                      AND s.id_seguido = u.id_usuario
                ) AS following
         FROM obra o
         INNER JOIN categoria_obra c ON c.id_categoria = o.id_categoria
         LEFT JOIN categoria_obra padre ON padre.id_categoria = c.id_categoria_padre
         INNER JOIN artista a ON a.id_artista = o.id_artista
         INNER JOIN usuario u ON u.id_usuario = a.id_usuario
         LEFT JOIN zona z ON z.id_zona = COALESCE(o.id_zona, u.id_zona)
         WHERE o.estado_publicacion = \'publicada\'
           AND (
                :todas_categorias = 1
                OR c.slug = :slug_categoria OR padre.slug = :slug_padre
           )
         ORDER BY COALESCE(o.publicada_en, o.creado_en) DESC, o.id_obra DESC' . $limiteSql
    );
    $stmt->execute([
        ':id_usuario' => $idUsuario,
        ':id_usuario_seguidor' => $idUsuario,
        ':todas_categorias' => $slug === null ? 1 : 0,
        ':slug_categoria' => $slug,
        ':slug_padre' => $slug,
    ]);

    $obras = $stmt->fetchAll();
    foreach ($obras as &$obra) {
        $obra['ruta_imagen'] = normalizarRutaPublica($obra['ruta_imagen'], 'obras');
        $obra['avatar_path'] = normalizarRutaPublica($obra['avatar_path'], 'avatars');
        $obra['total_likes'] = (int)$obra['total_likes'];
        $obra['total_vistas'] = (int)$obra['total_vistas'];
        $obra['liked'] = (bool)$obra['liked'];
        $obra['following'] = (bool)$obra['following'];
    }
    unset($obra);

    return $obras;
}

function obtenerObrasDelArtista(int $idUsuario, bool $soloPublicadas = false): array {
    $sql =
        'SELECT o.id_obra AS id, o.titulo, o.descripcion, o.ruta_imagen, o.precio,
                o.moneda, o.estado_publicacion, o.estado_venta, o.creado_en,
                c.nombre AS categoria,
                (SELECT COUNT(*) FROM me_gusta mg WHERE mg.id_obra = o.id_obra) AS likes
         FROM obra o
         INNER JOIN artista a ON a.id_artista = o.id_artista
         INNER JOIN categoria_obra c ON c.id_categoria = o.id_categoria
         WHERE a.id_usuario = :id_usuario'
        . ($soloPublicadas ? ' AND o.estado_publicacion = \'publicada\'' : '')
        . ' ORDER BY o.actualizado_en DESC, o.id_obra DESC';
    $stmt = getDB()->prepare($sql);
    $stmt->execute([':id_usuario' => $idUsuario]);

    return array_map(static function (array $obra): array {
        $imagen = normalizarRutaPublica($obra['ruta_imagen'], 'obras') ?? '';
        return [
            'id' => (int)$obra['id'],
            'titulo' => $obra['titulo'],
            'precio' => '$ ' . number_format((float)$obra['precio'], 2, ',', '.') . ' ' . $obra['moneda'],
            'imagen' => $imagen,
            'tipo' => 'foto',
            'categoria' => $obra['categoria'],
            'tiempo' => date('d/m/Y', strtotime($obra['creado_en'])),
            'descripcion' => $obra['descripcion'] ?? '',
            'likes' => (int)$obra['likes'],
            'estado_publicacion' => $obra['estado_publicacion'],
            'estado_venta' => $obra['estado_venta'],
            'estado' => $obra['estado_publicacion'] === 'publicada'
                ? $obra['estado_venta']
                : $obra['estado_publicacion'],
        ];
    }, $stmt->fetchAll());
}

function presentarEstadoObraArtista(array $obra): array {
    if ($obra['estado_publicacion'] === 'pendiente') {
        return ['etiqueta' => 'Pendiente de revisión', 'color' => '#b45309', 'fondo' => 'rgba(245, 158, 11, 0.15)', 'visible' => false];
    }
    if ($obra['estado_publicacion'] === 'rechazada') {
        return ['etiqueta' => 'Rechazada', 'color' => '#b91c1c', 'fondo' => 'rgba(239, 68, 68, 0.15)', 'visible' => false];
    }
    if ($obra['estado_publicacion'] === 'oculta') {
        return ['etiqueta' => 'Oculta', 'color' => '#6b7280', 'fondo' => 'rgba(156, 163, 175, 0.15)', 'visible' => false];
    }

    $estadosVenta = [
        'disponible' => ['Disponible para venta', '#15803d', 'rgba(34, 197, 94, 0.15)'],
        'reservada' => ['Reservada', '#a16207', 'rgba(234, 179, 8, 0.15)'],
        'vendida' => ['Vendida', '#b91c1c', 'rgba(239, 68, 68, 0.15)'],
        'no_disponible' => ['No disponible', '#6b7280', 'rgba(156, 163, 175, 0.15)'],
    ];
    $estadoVenta = $estadosVenta[$obra['estado_venta']] ?? $estadosVenta['no_disponible'];

    return [
        'etiqueta' => $estadoVenta[0],
        'color' => $estadoVenta[1],
        'fondo' => $estadoVenta[2],
        'visible' => true,
    ];
}

function obtenerObrasFavoritas(int $idUsuario): array {
    $stmt = getDB()->prepare(
        'SELECT o.id_obra AS id, o.titulo, o.descripcion, o.ruta_imagen AS imagen,
                o.precio, o.moneda, z.nombre AS zona,
                o.id_artista AS artista_id,
                COALESCE(NULLIF(a.nombre_artistico, \'\'), CONCAT(u.nombre, \' \', u.apellido)) AS artista,
                (SELECT COUNT(*) FROM me_gusta total WHERE total.id_obra = o.id_obra) AS likes
         FROM me_gusta mg
         INNER JOIN obra o ON o.id_obra = mg.id_obra AND o.estado_publicacion = \'publicada\'
         INNER JOIN artista a ON a.id_artista = o.id_artista
         INNER JOIN usuario u ON u.id_usuario = a.id_usuario
         LEFT JOIN zona z ON z.id_zona = COALESCE(o.id_zona, u.id_zona)
         WHERE mg.id_usuario = :id_usuario
         ORDER BY mg.creado_en DESC'
    );
    $stmt->execute([':id_usuario' => $idUsuario]);

    return array_map(static function (array $obra): array {
        $obra['imagen'] = normalizarRutaPublica($obra['imagen'], 'obras') ?? '';
        $obra['ubicacion'] = $obra['zona'] ?? 'Sin zona';
        $obra['likes'] = (int)$obra['likes'];
        $obra['vertical'] = false;
        return $obra;
    }, $stmt->fetchAll());
}

function escaparCatalogo(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function renderizarVistaPreviaObra(
    ?string $ruta,
    string $titulo,
    string $alto = '240px',
    string $mediaId = '',
    string $ajusteImagen = 'cover'
): void {
    $rutaValida = normalizarRutaPublica($ruta, 'obras') ?? '';
    if ($rutaValida === '') {
        ?>
        <div style="height:<?= escaparCatalogo($alto) ?>;display:grid;place-items:center;color:var(--text-muted, #9ca3af);background:var(--hover-item, #374151);">
            Archivo no disponible
        </div>
        <?php
        return;
    }

    $extension = strtolower(pathinfo((string)parse_url($rutaValida, PHP_URL_PATH), PATHINFO_EXTENSION));
    $ajusteImagen = in_array($ajusteImagen, ['cover', 'contain'], true) ? $ajusteImagen : 'cover';
    $estiloContenedor = 'height:' . escaparCatalogo($alto) . ';display:grid;place-items:center;overflow:hidden;background:var(--hover-item, #374151);';
    $atributoMediaId = $mediaId === '' ? '' : ' id="' . escaparCatalogo($mediaId) . '"';
    ?>
    <div style="<?= $estiloContenedor ?>">
        <?php if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)): ?>
<img src="<?= escaparCatalogo($rutaValida) ?>" alt="<?= escaparCatalogo($titulo) ?>" loading="lazy" width="640" height="480" style="width:100%;height:100%;object-fit:<?= escaparCatalogo($ajusteImagen) ?>;">
        <?php elseif ($extension === 'mp4'): ?>
            <video<?= $atributoMediaId ?> controls preload="metadata" aria-label="<?= escaparCatalogo($titulo) ?>" style="width:100%;height:100%;object-fit:contain;">
                <source src="<?= escaparCatalogo($rutaValida) ?>" type="video/mp4">
                Tu navegador no puede reproducir este video.
            </video>
        <?php elseif (in_array($extension, ['mp3', 'wav'], true)): ?>
            <div style="width:100%;padding:1rem;box-sizing:border-box;text-align:center;">
                <p style="margin:0 0 .75rem;color:var(--text-main, #fff);"><?= escaparCatalogo($titulo) ?></p>
                <audio<?= $atributoMediaId ?> controls preload="metadata" style="width:100%;">
                    <source src="<?= escaparCatalogo($rutaValida) ?>">
                    Tu navegador no puede reproducir este audio.
                </audio>
            </div>
        <?php elseif ($extension === 'pdf'): ?>
            <a href="<?= escaparCatalogo($rutaValida) ?>" target="_blank" rel="noopener noreferrer" style="color:var(--brand-orange, #f97316);font-weight:700;">
                Abrir documento PDF
            </a>
        <?php else: ?>
            <a href="<?= escaparCatalogo($rutaValida) ?>" target="_blank" rel="noopener noreferrer" style="color:var(--brand-orange, #f97316);font-weight:700;">
                Abrir archivo de la obra
            </a>
        <?php endif; ?>
    </div>
    <?php
}

function renderizarCatalogoObras(
    array $obras,
    string $titulo,
    string $descripcion,
    string $rutaInicio
): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $esVisitante = empty($_SESSION['user_id']);
    $registroOnclick = "window.location.href='/poliart/app/registro/registro_cliente.php?redirect=' + encodeURIComponent(window.location.href)";
    ?>
<main class="catalogo-page" style="max-width: 1200px; margin: 0 auto; padding: 2rem; width: 100%; box-sizing: border-box;">
<header class="catalogo-heading" style="background: var(--bg-surface, #1f2937); color: var(--text-main, #fff); border: 1px solid var(--border-subtle, #374151); padding: 1.5rem; border-radius: 1rem; margin-bottom: 1.5rem;">
            <h1 style="margin: 0 0 .5rem;"><?= escaparCatalogo($titulo) ?></h1>
            <p style="color: var(--text-muted, #9ca3af); margin: 0;"><?= escaparCatalogo($descripcion) ?></p>
        </header>
        <?php if (!$obras): ?>
            <p style="padding: 2rem; text-align: center; color: var(--text-muted, #9ca3af); background: var(--bg-surface, #1f2937); border-radius: 1rem;">
                Aún no hay obras publicadas en esta categoría.
            </p>
        <?php else: ?>
<div class="catalogo-grid" id="grid-obras-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.25rem;">
                <?php foreach ($obras as $obra): ?>
<article class="catalogo-card" onclick="<?= $esVisitante ? "window.location.href='/poliart/app/registro/registro_cliente.php?redirect=' + encodeURIComponent(window.location.href)" : 'mostrarFeedCliente(' . (int)$obra['id'] . ')' ?>" style="overflow: hidden; border: 1px solid var(--border-subtle, #374151); border-radius: 1rem; background: var(--bg-surface, #1f2937); color: var(--text-main, #fff); cursor:pointer;">
                        <a href="#feed-obras-container" onclick="event.preventDefault();event.stopPropagation();<?= $esVisitante ? "window.location.href='/poliart/app/registro/registro_cliente.php?redirect=' + encodeURIComponent(window.location.href)" : 'mostrarFeedCliente(' . (int)$obra['id'] . ')' ?>" aria-label="Ver obra <?= escaparCatalogo($obra['titulo']) ?>" style="display:block;text-decoration:none;color:inherit;">
                            <?php renderizarVistaPreviaObra($obra['ruta_imagen'], $obra['titulo']); ?>
                        </a>
                        <div style="padding: 1rem;">
                            <?php if (!empty($obra['avatar_path'])): ?>
                                <a href="/poliart/app/cliente/perfil_artista.php?id=<?= (int)$obra['artista_id'] ?>" aria-label="Ver perfil de <?= escaparCatalogo($obra['artista']) ?>" style="display:inline-block;line-height:0;margin-bottom:.65rem;" onclick="event.stopPropagation()">
                                    <img src="<?= escaparCatalogo($obra['avatar_path']) ?>" alt="" loading="lazy" width="40" height="40" style="width:2.5rem;height:2.5rem;border-radius:9999px;object-fit:cover;">
                                </a>
                            <?php endif; ?>
                            <h2 style="font-size: 1rem; margin: 0 0 .5rem;"><?= escaparCatalogo($obra['titulo']) ?></h2>
                            <p style="font-size: .85rem; color: var(--text-muted, #9ca3af); margin: 0 0 .75rem;">
                                <?= escaparCatalogo($obra['artista']) ?> · <?= escaparCatalogo($obra['categoria']) ?>
                            </p>
                            <p style="font-size: .85rem; line-height: 1.5; margin: 0 0 .75rem;">
                                <?= escaparCatalogo(mb_strimwidth((string)($obra['descripcion'] ?? ''), 0, 150, '…', 'UTF-8')) ?>
                            </p>
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: .5rem;">
                                <strong><?= escaparCatalogo(number_format((float)$obra['precio'], 0, ',', '.')) ?> <?= escaparCatalogo($obra['moneda']) ?></strong>
                                <span aria-label="Me gusta">❤️ <?= (int)$obra['total_likes'] ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <button type="button" id="btn-volver-grid" onclick="mostrarCuadriculaCliente()" style="display:none;margin:1rem 0;padding:.65rem 1.25rem;border:1px solid var(--border-subtle);border-radius:.6rem;background:var(--hover-item);color:var(--text-main);font-weight:700;cursor:pointer;">← Volver a la galería</button>
            <style>
#feed-obras-container{display:none;flex-direction:column;gap:2rem;width:100%;max-width:680px;margin:0 auto}
#feed-obras-container .client-work-status{display:inline-flex;align-items:center;margin-left:.55rem;padding:.2rem .55rem;border:1px solid rgba(148,163,184,.35);border-radius:999px;font-size:.72rem;font-weight:700;vertical-align:middle}
#feed-obras-container .client-work-status[data-status="disponible"]{color:#22c55e;border-color:rgba(34,197,94,.5);background:rgba(34,197,94,.12)}
#feed-obras-container .client-work-status[data-status="reservada"]{color:#f59e0b;border-color:rgba(245,158,11,.5);background:rgba(245,158,11,.12)}
#feed-obras-container .client-work-status[data-status="vendida"]{color:#f87171;border-color:rgba(248,113,113,.5);background:rgba(248,113,113,.12)}
#feed-obras-container .client-work-status[data-status="no_disponible"]{color:#cbd5e1;border-color:rgba(148,163,184,.45);background:rgba(148,163,184,.12)}
#feed-obras-container .client-feed-action{display:inline-flex;align-items:center;justify-content:center;gap:.45rem;padding:.5rem .9rem;border:1px solid rgba(148,163,184,.3);border-radius:.6rem;color:var(--text-main);background:rgba(148,163,184,.1);font-size:.825rem;font-weight:700;text-decoration:none;cursor:pointer;transition:all .2s ease}
#feed-obras-container .client-details-action{border-color:rgba(56,189,248,.38);background:rgba(56,189,248,.12);color:#38bdf8}
#feed-obras-container .client-details-action:hover{border-color:rgba(56,189,248,.65);background:rgba(56,189,248,.2)}
                #feed-obras-container>article{width:100%;max-width:680px}
                #feed-obras-container .reporte-option-global{box-sizing:border-box}
            </style>
            <div id="feed-obras-container" style="display:none;">
                <?php renderizarFeedObras($obras, '/poliart/app/cliente/ver_obra.php'); ?>
            </div>
            <script>
            function mostrarFeedCliente(obraId) {
                var grid = document.getElementById('grid-obras-container');
                var feed = document.getElementById('feed-obras-container');
                var volver = document.getElementById('btn-volver-grid');
                if (!grid || !feed) return;
                grid.style.display = 'none';
                feed.style.display = 'flex';
                if (volver) volver.style.display = 'inline-block';
                var post = feed.querySelector('[data-obra-id="' + obraId + '"]');
                if (post) post.scrollIntoView({behavior:'smooth', block:'start'});
            }
            function mostrarCuadriculaCliente() {
                var grid = document.getElementById('grid-obras-container');
                var feed = document.getElementById('feed-obras-container');
                var volver = document.getElementById('btn-volver-grid');
                if (grid) grid.style.display = 'grid';
                if (feed) feed.style.display = 'none';
                if (volver) volver.style.display = 'none';
            }
            </script>
        <?php endif; ?>
    </main>
    <?php
}

function renderizarFeedObras(array $obras, string $rutaObra, string $ajusteImagen = 'cover'): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $esVisitante = empty($_SESSION['user_id']);
    $registroOnclick = "window.location.href='/poliart/app/registro/registro_cliente.php?redirect=' + encodeURIComponent(window.location.href)";
    ?>
    <?php if (!$obras): ?>
        <p style="padding:2rem;text-align:center;color:var(--text-muted);background:var(--bg-surface);border:1px solid var(--border-subtle);border-radius:1rem;">
            Aún no hay obras publicadas. Cuando los artistas publiquen, aparecerán aquí.
        </p>
    <?php else: ?>
        <?php foreach ($obras as $obra): ?>
<article class="feed-post-card client-feed-post-card" data-obra-id="<?= (int)$obra['id'] ?>" data-estado-venta="<?= escaparCatalogo((string)$obra['estado_venta']) ?>" style="background:var(--bg-surface);border:1px solid var(--border-subtle);border-radius:1rem;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,.08);">
                <div style="padding:1rem 1.25rem;display:flex;align-items:center;gap:.75rem;">
                    <a href="/poliart/app/cliente/perfil_artista.php?id=<?= (int)$obra['artista_id'] ?>" onclick="event.stopPropagation()" aria-label="Ver perfil de <?= escaparCatalogo($obra['artista']) ?>" style="display:block;text-decoration:none;color:inherit;line-height:0;">
                    <?php if ($obra['avatar_path']): ?>
<img src="<?= escaparCatalogo($obra['avatar_path']) ?>" alt="" loading="lazy" width="44" height="44" style="width:2.75rem;height:2.75rem;border-radius:9999px;object-fit:cover;">
                    <?php else: ?>
                        <span aria-hidden="true" style="width:2.75rem;height:2.75rem;border-radius:9999px;background:var(--hover-item);display:grid;place-items:center;">🎨</span>
                    <?php endif; ?>
                    </a>
                    <div style="min-width:0;">
                        <strong style="display:block;color:var(--text-main);"><?= escaparCatalogo($obra['artista']) ?></strong>
                        <span style="font-size:.8rem;color:var(--text-muted);"><?= escaparCatalogo($obra['zona'] ?? 'Sin zona') ?> · <?= escaparCatalogo($obra['categoria']) ?></span>
                    </div>
                </div>
                <div style="padding:1rem 1.25rem;">
<h2 style="margin:0 0 .5rem;font-size:1.1rem;color:var(--text-main);"><?= escaparCatalogo($obra['titulo']) ?> <span class="client-work-status" data-status="<?= escaparCatalogo((string)($obra['estado_venta'] ?? 'no_disponible')) ?>"><?= escaparCatalogo(['disponible' => 'Disponible para venta', 'reservada' => 'Reservada', 'vendida' => 'Vendida', 'no_disponible' => 'No disponible'][$obra['estado_venta'] ?? 'no_disponible'] ?? 'No disponible') ?></span></h2>
                    <?php if (!empty($obra['descripcion'])): ?>
                        <p style="margin:.25rem 0 .75rem;color:var(--text-muted);line-height:1.5;"><?= escaparCatalogo(mb_strimwidth((string)$obra['descripcion'], 0, 220, '…', 'UTF-8')) ?></p>
                    <?php endif; ?>
                </div>
                <div class="feed-image-box">
                    <?php renderizarVistaPreviaObra($obra['ruta_imagen'], $obra['titulo'], 'min(520px, 70vh)', '', 'contain'); ?>
                </div>
                <div style="padding:1rem 1.25rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;">
                        <strong style="color:var(--text-main);"><?= escaparCatalogo(number_format((float)$obra['precio'], 0, ',', '.') . ' ' . $obra['moneda']) ?></strong>
                        <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                            <button type="button" class="btn-like" data-obra="<?= (int)$obra['id'] ?>" <?= $esVisitante ? 'onclick="' . $registroOnclick . '"' : '' ?> aria-pressed="<?= $obra['liked'] ? 'true' : 'false' ?>" style="padding:.45rem .7rem;border:1px solid var(--border-subtle);border-radius:.5rem;background:var(--hover-item);color:var(--text-main);cursor:pointer;">
                                <span class="heart-icon"><?= $obra['liked'] ? '❤️' : '🤍' ?></span>
                                <span class="like-text"><?= (int)$obra['total_likes'] ?> Me gusta</span>
                            </button>
                            <?php if ($obra['estado_venta'] === 'disponible'): ?>
                                <button type="button" class="btn-carrito" data-obra="<?= (int)$obra['id'] ?>" <?= $esVisitante ? 'onclick="' . $registroOnclick . '"' : '' ?> style="padding:.45rem .7rem;border:1px solid var(--brand-orange);border-radius:.5rem;background:var(--brand-orange);color:#fff;cursor:pointer;">Añadir al carrito</button>
                            <?php endif; ?>
                            <button type="button" onclick="<?= $esVisitante ? $registroOnclick : 'abrirReporteFeed(' . (int)$obra['id'] . ')' ?>" style="padding:.45rem .7rem;border:1px solid var(--border-subtle);border-radius:.5rem;background:var(--bg-surface);color:var(--text-main);cursor:pointer;">Reportar</button>
<a class="client-feed-action client-details-action" href="<?= $esVisitante ? '/poliart/app/registro/registro_cliente.php?redirect=' . rawurlencode($rutaObra . '?id=' . (int)$obra['id']) : escaparCatalogo($rutaObra) . '?id=' . (int)$obra['id'] ?>">Ver detalles</a>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
    <style>
        .reporte-option-global { display:block; margin:.75rem 0; padding:.9rem 1rem; border:1px solid var(--brand-orange); border-radius:.85rem; cursor:pointer; }
        .reporte-option-global input { accent-color:var(--brand-orange); margin-right:.75rem; }
        .reporte-option-global strong, .reporte-option-global span { display:block; margin-left:1.55rem; }
        .reporte-option-global strong { margin-top:-1.35rem; color:var(--text-main); }
        .reporte-option-global span { margin-top:.3rem; color:var(--text-muted); font-size:.9rem; }
    </style>
    <div id="modalReporteFeed" role="dialog" aria-modal="true" aria-labelledby="tituloReporteFeed" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,.65);align-items:center;justify-content:center;padding:1rem;">
        <form id="formReporteFeed" style="width:100%;max-width:600px;padding:1rem;border-radius:1rem;background:var(--bg-surface);color:var(--text-main);">
            <button type="button" onclick="cerrarReporteFeed()" aria-label="Cerrar" style="float:right;width:2.75rem;height:2.75rem;border:1px solid var(--border-subtle);border-radius:50%;background:transparent;color:var(--text-main);font-size:1.5rem;font-weight:800;cursor:pointer;">×</button>
            <h2 id="tituloReporteFeed" style="margin-top:0;">⚠️ Reportar esta obra</h2>
            <input type="hidden" name="id_obra" id="idObraReporteFeed">
            <label style="display:block;margin-bottom:.75rem;">Motivo
                <div class="reporte-options-global" role="radiogroup" aria-label="Motivo del reporte">
                    <label class="reporte-option-global"><input type="radio" name="motivo-feed-visual" value="plagio" checked><strong>Plagio / Copia no autorizada</strong><span>Derechos de autor pertenecientes a otro creador.</span></label>
                    <label class="reporte-option-global"><input type="radio" name="motivo-feed-visual" value="inapropiado"><strong>Contenido Inapropiado u Ofensivo</strong><span>Imágenes explícitas, violencia o incitación al odio.</span></label>
                    <label class="reporte-option-global"><input type="radio" name="motivo-feed-visual" value="spam"><strong>Spam / Información Engañosa</strong><span>Precio engañoso, fraude o publicación sospechosa.</span></label>
                    <label class="reporte-option-global"><input type="radio" name="motivo-feed-visual" value="otro"><strong>Otro motivo</strong><span>Otra infracción a las políticas de Poli-Art.</span></label>
                </div>
                <select name="motivo" required style="display:none;">
                    <option value="plagio">Plagio o copia no autorizada</option>
                    <option value="inapropiado">Contenido inapropiado</option>
                    <option value="spam">Spam o información engañosa</option>
                    <option value="otro">Otro motivo</option>
                </select>
            </label>
            <label id="etiquetaDetalleReporteFeed" style="display:none;margin-bottom:.75rem;">Detalles del reporte
                <textarea id="detalleReporteFeed" name="detalle" maxlength="2000" rows="3" style="display:block;width:100%;padding:.65rem;margin-top:.35rem;"></textarea>
            </label>
            <p id="estadoReporteFeed" role="status" style="min-height:1.2rem;"></p>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                <button type="button" onclick="cerrarReporteFeed()" style="padding:.5rem .75rem;">Cancelar</button>
                <button type="submit" style="padding:.5rem .75rem;">Enviar reporte</button>
            </div>
        </form>
    </div>
    <script>
    function abrirReporteFeed(idObra) {
        document.getElementById('idObraReporteFeed').value = idObra;
        document.getElementById('estadoReporteFeed').textContent = '';
        document.querySelector('input[name="motivo-feed-visual"]').checked = true;
        document.querySelector('#formReporteFeed select[name="motivo"]').value = 'plagio';
        document.getElementById('etiquetaDetalleReporteFeed').style.display = 'none';
        document.getElementById('detalleReporteFeed').required = false;
        document.getElementById('modalReporteFeed').style.display = 'flex';
    }

    function cerrarReporteFeed() {
        document.getElementById('modalReporteFeed').style.display = 'none';
        document.getElementById('formReporteFeed').reset();
        document.querySelector('input[name="motivo-feed-visual"]').checked = true;
        document.querySelector('#formReporteFeed select[name="motivo"]').value = 'plagio';
        document.getElementById('etiquetaDetalleReporteFeed').style.display = 'none';
        document.getElementById('detalleReporteFeed').required = false;
    }

    document.querySelectorAll('input[name="motivo-feed-visual"]').forEach(function (input) {
        input.addEventListener('change', function () {
            document.querySelector('#formReporteFeed select[name="motivo"]').value = input.value;
            const mostrarDetalle = input.value === 'otro';
            document.getElementById('etiquetaDetalleReporteFeed').style.display = mostrarDetalle ? 'block' : 'none';
            document.getElementById('detalleReporteFeed').required = mostrarDetalle;
        });
    });

    document.getElementById('formReporteFeed').addEventListener('submit', async function (event) {
        event.preventDefault();
        const status = document.getElementById('estadoReporteFeed');
        try {
            const response = await fetch('/poliart/app/includes/actions/reportar.php', {
                method: 'POST',
                body: new FormData(event.currentTarget)
            });
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.error || 'No se pudo enviar el reporte.');
            status.textContent = result.msg || 'Reporte enviado. Gracias por avisarnos.';
            event.currentTarget.reset();
        } catch (error) {
            status.textContent = error.message || 'No se pudo enviar el reporte.';
        }
    });
    </script>
    <?php
}
