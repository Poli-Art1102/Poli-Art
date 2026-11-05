<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['cliente', 'artista']);
require_once __DIR__ . '/../cliente/header.php';
include_once __DIR__ . '/../cliente/sidebar.php';
require_once __DIR__ . '/../includes/catalogo_obras.php';

$project_base = '/poliart/';

$nombre_usuario   = $_SESSION['nombre'] ?? 'Cliente';
$apellido_usuario = $_SESSION['apellido'] ?? 'Poli-Art';
$seudonimo        = $_SESSION['seudonimo'] ?? 'ClienteApp';
$zona_usuario     = $_SESSION['zona'] ?? 'Medellín, Colombia';

// Obras que el cliente ha guardado como favoritas / coleccionadas
/* Datos de prueba retirados; los favoritos se cargan desde la base de datos.
$obras_favoritas = [
    [
        'id' => 1,
        'titulo' => 'Ilustración Neón sobre la Niebla',
        'artista' => 'Carlos Mendoza',
        'imagen' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=600&q=80',
        'ubicacion' => 'El Poblado',
        'likes' => 42
    ],
    [
        'id' => 2,
        'titulo' => 'Retrato Canino al Óleo',
        'artista' => 'Jeffrey Ramos (@Lulo)',
        'imagen' => 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=600&q=80',
        'ubicacion' => 'Laureles',
        'likes' => 88
    ],
    [
        'id' => 3,
        'titulo' => 'Mural Castillo y Niebla',
        'artista' => 'Sofía Rodríguez',
        'imagen' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=600&q=80',
        'ubicacion' => 'Santa Elena',
        'likes' => 64
    ]
];

*/
try {
    $stmtFavoritos = getDB()->prepare(
        'SELECT o.id_obra AS id, o.titulo, o.ruta_imagen AS imagen,
                z.nombre AS ubicacion,
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
    $stmtFavoritos->execute([':id_usuario' => getUserId()]);
    $obras_favoritas = array_map(static function (array $obra): array {
        $obra['imagen'] = normalizarRutaPublica($obra['imagen'], 'obras') ?? '';
        $obra['likes'] = (int)$obra['likes'];
        $obra['ubicacion'] = $obra['ubicacion'] ?? 'Sin ubicación';
        return $obra;
    }, $stmtFavoritos->fetchAll());
} catch (PDOException $e) {
    error_log('Error al cargar obras favoritas del cliente: ' . $e->getMessage());
    $obras_favoritas = [];
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

.stat-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem 1rem;
    background: var(--hover-item);
    border: 1px solid var(--border-subtle);
    border-radius: 0.5rem;
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

.stat-number {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--brand-orange);
}

.stat-label {
    font-size: 0.7rem;
    color: var(--text-muted);
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.05em;
}

.projects-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    width: 100%;
}

@media (min-width: 1400px) {
    .projects-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.project-item {
    position: relative;
    width: 100%;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    background-color: var(--border-subtle);
    cursor: pointer;
    border-radius: 0.75rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border: 1px solid var(--border-subtle);
    transition: transform 0.2s ease, border-color 0.2s ease;
}

.project-item:hover {
    transform: translateY(-3px);
    border-color: var(--brand-orange);
}

.project-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.35s ease;
}

.project-item:hover img {
    transform: scale(1.05);
}

.project-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.3) 50%, rgba(0,0,0,0.1) 100%);
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: flex-end;
    padding: 1rem;
    box-sizing: border-box;
    color: #ffffff;
    opacity: 0;
    transition: opacity 0.25s ease;
}

.project-item:hover .project-overlay {
    opacity: 1;
}

.nav-tab {
    padding: 0.75rem 1.25rem;
    color: var(--text-muted);
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
}

.nav-tab.active, .nav-tab:hover {
    color: var(--brand-orange);
    border-bottom-color: var(--brand-orange);
}
</style>

<div style="display: flex; width: calc(100vw - 260px); height: calc(100vh - 4.25rem); overflow: hidden; background: transparent;">

    <div style="flex-grow: 1; height: 100%; overflow-y: auto; padding: 2rem 2.5rem; box-sizing: border-box;">
        
        <div style="max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">

            <!-- TARJETA CABECERA PERFIL CLIENTE -->
            <div style="background-color: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: 1rem; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); backdrop-filter: blur(8px); transition: background-color 0.3s ease, border-color 0.3s ease;">
                
                <div style="width: 100%; height: 180px; background: linear-gradient(135deg, var(--brand-orange) 0%, #fb923c 50%, #f43f5e 100%); background-size: cover; background-position: center;"></div>

                <div style="padding: 0 2rem 1.25rem 2rem; position: relative;">
                    <div style="display: flex; align-items: flex-end; justify-content: space-between; margin-top: -3.5rem; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                        
                        <div style="display: flex; align-items: flex-end; gap: 1.25rem;">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($nombre_usuario . ' ' . $apellido_usuario) ?>&background=f97316&color=fff&size=128" alt="Avatar" style="width: 6.5rem; height: 6.5rem; border-radius: 9999px; border: 4px solid var(--bg-surface); object-fit: cover; background-color: var(--brand-orange); box-shadow: 0 8px 20px rgba(249, 115, 22, 0.25);">
                            
                            <div style="margin-bottom: 0.25rem;">
                                <div style="display: flex; align-items: center; gap: 0.6rem;">
                                    <h1 style="color: var(--text-main); font-size: 1.75rem; font-weight: 800; margin: 0;">
                                        <?= htmlspecialchars($nombre_usuario . ' ' . $apellido_usuario) ?>
                                    </h1>
                                    <span style="background-color: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); padding: 0.2rem 0.65rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700;">
                                        🛍️ Cliente Poli-Art
                                    </span>
                                </div>
                                <div style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.2rem;">
                                    @<?= strtolower(htmlspecialchars($seudonimo)) ?> &bull; 📍 <?= htmlspecialchars($zona_usuario) ?>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 1.25rem;">
                            <div style="display: flex; gap: 0.6rem;">
                                <div class="stat-box">
<span class="stat-number"><?php try { $stmtComprasPerfil = getDB()->prepare("SELECT COUNT(*) FROM pedido WHERE id_usuario_cliente = ? AND estado_pedido IN ('confirmado','completado')"); $stmtComprasPerfil->execute([getUserId()]); $compras_reales = (int)$stmtComprasPerfil->fetchColumn(); } catch (Throwable $e) { $compras_reales = 0; } ?><?= $compras_reales ?></span>
                                    <span class="stat-label">Compras</span>
                                </div>
                                <div class="stat-box">
                                    <span class="stat-number"><?= count($obras_favoritas) ?></span>
                                    <span class="stat-label">Favoritos</span>
                                </div>
                                <div class="stat-box">
<span class="stat-number"><?php try { $stmtSeguidosPerfil = getDB()->prepare('SELECT COUNT(*) FROM seguimiento WHERE id_seguidor = ?'); $stmtSeguidosPerfil->execute([getUserId()]); $seguidos_reales = (int)$stmtSeguidosPerfil->fetchColumn(); } catch (Throwable $e) { $seguidos_reales = 0; } ?><?= $seguidos_reales ?></span>
                                    <span class="stat-label">Seguidos</span>
                                </div>
                            </div>

                            <a href="editar_perfil.php" class="btn btn-primary">
                                ✏️ Editar perfil
                            </a>
                        </div>
                    </div>

                    <!-- PESTAÑAS DE NAVEGACIÓN ADAPTADAS A CLIENTE/COLECCIONISTA -->
                    <div style="display: flex; gap: 0.5rem; border-top: 1px solid var(--border-subtle); padding-top: 0.75rem; margin-top: 1rem;">
                        <a href="perfil.php" class="nav-tab active">❤️ Favoritos (<?= count($obras_favoritas) ?>)</a>
                        <a href="../includes/obras_apartadas.php" class="nav-tab">🎨 Obras Compradas / Apartadas</a>
<a href="seguidos.php" class="nav-tab">👥 Artistas Seguidos (<?= $seguidos_reales ?>)</a>
                    </div>
                </div>

            </div>

            <!-- GALERÍA DE OBRAS FAVORITAS DEL CLIENTE -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin: 0;">
                        ❤️ Mis Obras Guardadas
                    </h2>
                    <span style="font-size: 0.825rem; color: var(--text-muted);">
                        Colección personal
                    </span>
                </div>

                <div class="projects-grid" id="contenedor-obras">
                    <?php foreach ($obras_favoritas as $obra): ?>
                        <div class="project-item" onclick="window.location.href='ver_obra.php?id=<?= $obra['id'] ?>'">
                            <?php if ($obra['imagen'] !== ''): ?>
                                <img src="<?= htmlspecialchars($obra['imagen'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($obra['titulo'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php else: ?>
                                <div style="height:100%;display:grid;place-items:center;color:var(--text-muted);">🎨 Imagen no disponible</div>
                            <?php endif; ?>

                            <div class="project-overlay">
                                <strong style="font-size: 0.95rem; color: #ffffff; font-weight: 800; display: block;">
                                    <?= htmlspecialchars($obra['titulo']) ?>
                                </strong>
                                <span style="font-size: 0.8rem; color: var(--brand-orange); font-weight: 700; margin-top: 0.15rem;">
                                    🎨 <?= htmlspecialchars($obra['artista']) ?>
                                </span>
                                <div style="display: flex; justify-content: space-between; width: 100%; margin-top: 0.5rem; font-size: 0.75rem; color: #e2e8f0; font-weight: 600;">
                                    <span>❤️ <?= $obra['likes'] ?> Me gusta</span>
                                    <span>📍 <?= htmlspecialchars($obra['ubicacion']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

    </div>
</div>

<script src="<?= $project_base ?>public/assets/js/cliente.js"></script>


