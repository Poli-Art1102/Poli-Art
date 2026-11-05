<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$project_base = '/poliart/';
$stmtIdentidad = getDB()->prepare(
    'SELECT u.nombre, u.apellido, u.avatar_path
     FROM usuario u
     WHERE u.id_usuario = :id_usuario AND u.rol = \'artista\'
     LIMIT 1'
);
$stmtIdentidad->execute([':id_usuario' => getUserId()]);
$identidad = $stmtIdentidad->fetch();

if (!$identidad) {
    header('Location: /poliart/app/includes/actions/logout.php');
    exit;
}

$nombre_user = (string)$identidad['nombre'];
$foto_perfil = $identidad['avatar_path'] ?? '';
$foto_perfil   = is_string($foto_perfil) && strpos($foto_perfil, '/poliart/uploads/avatars/') === 0
    ? $foto_perfil
    : '';
$_SESSION['user'] = array_merge($_SESSION['user'] ?? [], [
    'nombre' => $identidad['nombre'],
    'apellido' => $identidad['apellido'],
    'avatar_path' => $foto_perfil !== '' ? $foto_perfil : null,
]);
?>
<!DOCTYPE html>
<html lang="es" id="html-root" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poli-Art | Panel de Artista</title>
    <link rel="stylesheet" href="<?= $project_base ?>public/assets/css/tailwind.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?= $project_base ?>public/assets/css/password-visibility.css">
    <link rel="stylesheet" href="<?= $project_base ?>public/assets/css/botones.css?v=1">
<link rel="stylesheet" href="<?= $project_base ?>public/assets/css/botones-global.css?v=5">
<link rel="stylesheet" href="<?= $project_base ?>public/assets/css/mensajes.css?v=1">
<script src="<?= $project_base ?>public/assets/js/cart-state.js?v=1" defer></script>
<script src="<?= $project_base ?>public/assets/js/artist-feed.js?v=1" defer></script>
<script src="<?= $project_base ?>public/assets/js/availability-state.js?v=1" defer></script>
<script src="<?= $project_base ?>public/assets/js/new-work-flow.js?v=1" defer></script>
<link rel="stylesheet" href="<?= $project_base ?>public/assets/css/new-work.css?v=1">
    <style>
    /* PALETA MODO CLARO ☀️ (FONDO #FCE3DC) */
    :root {
        --brand-orange: #ED7745;
        --brand-yellow: #EDCE45;
        --brand-mint: #45EDCE;
        --brand-cyan: #45D4ED;
        --brand-blue: #4591ED;
        --brand-purple: #C573FF;
        --brand-pink: #FF94D4;
        
        --bg-body: #FFF5F2;
        --bg-surface: rgba(253, 235, 230, 0.96);
        --bg-nav: #FCE3DC; /* TU COLOR EXACTO MODO CLARO */
        --border-subtle: #F5C7B8;
        --text-main: #2C140B;
        --text-muted: #7A4B3A;
        --hover-item: #F7D4C8;
    }

    /* PALETA MODO OSCURO 🌙 (FONDO #632912 TIERRA/CAFÉ OSCURO) */
    html.dark {
        --brand-orange: #FF9D73;
        --brand-yellow: #EDCE45;
        --brand-mint: #45EDCE;
        --brand-cyan: #45D4ED;
        --brand-blue: #6BA8FF;
        --brand-purple: #D799FF;
        --brand-pink: #FF94D4;
        
        --bg-body: #321408;
        --bg-surface: rgba(77, 34, 15, 0.95);
        --bg-nav: #632912; /* TU NUEVO COLOR EXACTO MODO OSCURO */
        --border-subtle: #853F22;
        --text-main: #FFF1EB;
        --text-muted: #E0C0B3;
        --hover-item: #4D220F;
    }

    html {
        scrollbar-width: thin;
        scrollbar-color: color-mix(in srgb, var(--brand-orange) 38%, var(--bg-nav)) transparent;
    }

    html::-webkit-scrollbar {
        width: 8px;
    }

    html::-webkit-scrollbar-track {
        background: transparent;
    }

    html::-webkit-scrollbar-thumb {
        border: 2px solid transparent;
        border-radius: 999px;
        background: color-mix(in srgb, var(--brand-orange) 38%, var(--bg-nav));
        background-clip: padding-box;
    }

    html::-webkit-scrollbar-thumb:hover {
        background: color-mix(in srgb, var(--brand-orange) 60%, var(--bg-nav));
        background-clip: padding-box;
    }

    body {
        background-image: url('<?= $project_base ?>app/includes/madera.jpg') !important;
        background-size: cover !important;
        background-position: center !important;
        background-attachment: fixed !important;
        background-repeat: no-repeat !important;
        color: var(--text-main) !important;
        font-family: system-ui, -apple-system, sans-serif;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        margin: 0;
        transition: background-color 0.3s ease, color 0.3s ease;
        overflow: hidden;
    }

    html {
        height: 100%;
        overflow: hidden;
    }

    .custom-header {
        background-color: var(--bg-nav) !important;
        border-bottom: 1px solid var(--border-subtle) !important;
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }
</style>
<style id="artist-back-layout">
    .custom-header { position: fixed !important; top: 0; left: 0; right: 0; z-index: 100; flex: 0 0 4.25rem; min-height: 4.25rem; }
    body > main { margin-top: 4.25rem; }
    .global-back-row { position: absolute !important; top: 4.25rem !important; left: 273px !important; z-index: 60 !important; margin-top: 0 !important; padding: .55rem 2rem 0 !important; }
    .global-back-row + main { height: calc(100vh - 4.25rem) !important; min-height: 0 !important; margin-top: 4.25rem !important; }
    @media (max-width: 700px) { .global-back-row { left: 0 !important; padding-left: 1rem !important; } }
</style>
 </head>
<body>
    
    <header class="custom-header" style="position: sticky; top: 0; z-index: 50; width: 100%; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);">
        <div style="width: 100%; padding: 0 2rem; height: 4.25rem; display: flex; align-items: center; justify-content: space-between; box-sizing: border-box;">
            
            <a href="<?= $project_base ?>app/artista/index.php" style="font-size: 1.5rem; font-weight: 800; color: var(--brand-orange); text-decoration: none; display: flex; align-items: center; gap: 0.5rem;">
                🎨 <span style="background: linear-gradient(135deg, var(--brand-orange), var(--brand-yellow)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Poli-Art</span>
            </a>
            
            <div style="display: flex; align-items: center; gap: 1.25rem;">
                <button id="theme-toggle" type="button" onclick="toggleTheme()" style="background-color: var(--hover-item); border: 1.5px solid var(--brand-orange); color: var(--text-main); padding: 0.45rem 0.85rem; border-radius: 0.6rem; cursor: pointer; display: flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; transition: all 0.25s ease;" title="Cambiar tema">
                    <span id="theme-toggle-icon">☀️</span>
                </button>

                <a href="<?= $project_base ?>app/artista/perfil.php" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; border-left: 1px solid var(--border-subtle); padding-left: 1.25rem;">
                    <?php if ($foto_perfil): ?>
                        <img src="<?= htmlspecialchars($foto_perfil, ENT_QUOTES, 'UTF-8') ?>" alt="Foto de perfil" style="width: 2.4rem; height: 2.4rem; border-radius: 9999px; object-fit: cover; border: 2px solid var(--brand-orange);">
                    <?php else: ?>
                        <div style="width: 2.4rem; height: 2.4rem; border-radius: 9999px; background: linear-gradient(135deg, var(--brand-orange), var(--brand-yellow)); color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem; box-shadow: 0 3px 10px rgba(237, 119, 69, 0.35);">
                            <?= htmlspecialchars(strtoupper(substr($nombre_user, 0, 2)), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>
                    <span style="font-size: 0.9rem; font-weight: 800; color: var(--text-main);">
                        <?= htmlspecialchars($nombre_user) ?>
                    </span>
                </a>
            </div>
        </div>
    </header>

    <?php if (basename($_SERVER['PHP_SELF']) !== 'index.php'): ?>
    <div class="global-back-row">
        <button type="button" class="global-back-button" onclick="if (document.referrer) { history.back(); } else { window.location.href='<?= $project_base ?>app/inicio/index.php'; }" title="Volver a la página anterior">← <span class="back-label">Volver</span></button>
    </div>
    <?php endif; ?>
    
    <main style="flex: 1 1 auto; width: 100%; height: calc(100vh - 4.25rem); min-height: 0; display: flex; overflow-y: hidden; box-sizing: border-box;">

    <script>
    function applyTheme(theme) {
        const html = document.getElementById('html-root');
        const icon = document.getElementById('theme-toggle-icon');
        if (theme === 'dark') {
            html.classList.add('dark');
            html.classList.remove('light');
            icon.textContent = '🌙';
        } else {
            html.classList.remove('dark');
            html.classList.add('light');
            icon.textContent = '☀️';
        }
    }

    function toggleTheme() {
        const currentTheme = localStorage.getItem('poliart_theme') === 'dark' ? 'light' : 'dark';
        localStorage.setItem('poliart_theme', currentTheme);
        applyTheme(currentTheme);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = localStorage.getItem('poliart_theme') || 'light';
        applyTheme(savedTheme);
    });
    </script>
<link rel="stylesheet" href="/poliart/public/assets/css/scrollbar-global.css?v=4">
