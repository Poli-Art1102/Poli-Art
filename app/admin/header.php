<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/auth.php';

$project_base = '/poliart/';
$current_user = getCurrentUser();
$nombre_user  = is_array($current_user) && !empty($current_user['nombre']) ? $current_user['nombre'] : 'Administrador';
$foto_perfil  = is_array($current_user) && !empty($current_user['avatar_path']) ? $current_user['avatar_path'] : '';
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poli-Art | Administración</title>
    
    <link rel="stylesheet" href="<?= $project_base ?>public/assets/css/tailwind.css?v=20">
    <link rel="stylesheet" href="<?= $project_base ?>public/assets/css/botones.css?v=1">
<link rel="stylesheet" href="<?= $project_base ?>public/assets/css/botones-global.css?v=5">
<link rel="stylesheet" href="<?= $project_base ?>public/assets/css/mensajes.css?v=1">
    <link rel="stylesheet" href="<?= $project_base ?>public/assets/css/password-visibility.css">
<link rel="stylesheet" href="<?= $project_base ?>public/assets/css/scrollbar-global.css?v=3">
    
    <style>
        :root, html.light {
            --brand-orange:  #ED7745;
            --brand-yellow:  #EDCE45;
            --bg-surface:    rgba(253, 235, 230, 0.95);
            --bg-body:       #FFF5F2;
            --bg-nav:        #FCE3DC;
            --border-subtle: #F5C7B8;
            --hover-item:    #F7D4C8;
            --text-main:     #2C140B;
            --text-muted:    #7A4B3A;
        }

        html.dark {
            --brand-orange:  #FF9D73;
            --brand-yellow:  #EDCE45;
            --bg-surface:    rgba(77, 34, 15, 0.95);
            --bg-body:       #321408;
            --bg-nav:        #632912;
            --border-subtle: #853F22;
            --hover-item:    #4D220F;
            --text-main:     #FFF1EB;
            --text-muted:    #E0C0B3;
        }

        html, body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: system-ui, -apple-system, sans-serif;
            color: var(--text-main);
            transition: color 0.3s ease;
        }

        body {
            background-image: url('<?= $project_base ?>app/includes/madera.jpg') !important;
            background-size: cover !important;
            background-position: center !important;
            background-attachment: fixed !important;
            background-repeat: no-repeat !important;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* FORZAR COLOR DE FONDO DEL NAVBAR EN MODO CLARO Y OSCURO */
        .admin-header {
            background-color: var(--bg-nav) !important;
            border-bottom: 1px solid var(--border-subtle) !important;
            position: sticky;
            top: 0;
            z-index: 50;
            width: 100%;
            backdrop-filter: blur(12px);
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .header-content {
            width: 100%;
            padding: 0 1.5rem;
            height: 4.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-sizing: border-box;
        }

        .brand-logo {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--brand-orange);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge-admin {
            font-size: 0.75rem;
            background: var(--hover-item);
            color: var(--brand-orange);
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            border: 1px solid var(--border-subtle);
            font-weight: 700;
        }

        .user-profile-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            border-left: 1px solid var(--border-subtle);
            padding-left: 1rem;
        }

        .user-avatar {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 9999px;
            object-fit: cover;
            border: 2px solid var(--brand-orange);
        }

        .user-name {
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .theme-button {
            background: var(--hover-item);
            border: 1px solid var(--border-subtle);
            padding: 0.45rem;
            border-radius: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
            transition: all 0.2s ease;
        }

        .theme-button:hover {
            border-color: var(--brand-orange);
            transform: scale(1.05);
        }
    </style>

    <script>
        // Aplicar tema ANTES de renderizar para evitar flash de color.
        (function () {
            const storedTheme = localStorage.getItem('poliart_theme');
            const theme = storedTheme === 'light' || storedTheme === 'dark' ? storedTheme : 'dark';

            document.documentElement.classList.remove('light', 'dark');
            document.documentElement.classList.add(theme);
        })();
    </script>
<style id="admin-back-layout">
    .admin-header { position: fixed !important; top: 0; left: 0; right: 0; z-index: 100; flex: 0 0 4.25rem; min-height: 4.25rem; }
    body > main { margin-top: 4.25rem; }
    .global-back-row { position: absolute !important; top: 4.25rem !important; left: 0 !important; z-index: 60 !important; margin-top: 0 !important; padding: .55rem 2rem 0 !important; }
    .global-back-row + main { height: calc(100vh - 4.25rem) !important; min-height: 0 !important; margin-top: 4.25rem !important; }
    @media (max-width: 700px) { .global-back-row { padding-left: 1rem !important; } }
</style>
 </head>
<body>
    
    <header class="admin-header">
        <div class="header-content">
            
            <a href="<?= $project_base ?>app/admin/index.php" class="brand-logo">
                🎨 <span>Poli-Art</span> <span class="badge-admin">Admin</span>
            </a>

            <div style="display: flex; align-items: center; gap: 1.25rem;">
                
                <a href="<?= $project_base ?>app/admin/perfil.php" class="user-profile-link">
                    <?php if (!empty($foto_perfil)): ?>
                        <img src="<?= htmlspecialchars($foto_perfil) ?>" alt="Perfil" class="user-avatar">
                    <?php else: ?>
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($nombre_user) ?>&background=f97316&color=fff" alt="Perfil" class="user-avatar">
                    <?php endif; ?>

                    <span class="user-name">
                        <?= htmlspecialchars($nombre_user) ?>
                    </span>
                </a>

                <button id="theme-toggle" type="button" class="theme-button" title="Cambiar tema">
                    <span id="theme-toggle-sun" style="display: none; font-size: 1.1rem;">🌙</span>
                    <span id="theme-toggle-moon" style="display: none; font-size: 1.1rem;">☀️</span>
                </button>

            </div>
        </div>
    </header>

    <?php if (basename($_SERVER['PHP_SELF']) !== 'index.php'): ?>
    <div class="global-back-row">
        <button type="button" class="global-back-button" onclick="if (document.referrer) { history.back(); } else { window.location.href='<?= $project_base ?>app/inicio/index.php'; }" title="Volver a la página anterior">← <span class="back-label">Volver</span></button>
    </div>
    <?php endif; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btn      = document.getElementById('theme-toggle');
            const sunIcon  = document.getElementById('theme-toggle-sun');
            const moonIcon = document.getElementById('theme-toggle-moon');

            if (!btn || !sunIcon || !moonIcon) {
                return;
            }

            function updateIcons() {
                const dark = document.documentElement.classList.contains('dark');
                sunIcon.style.display  = dark ? 'inline' : 'none';
                moonIcon.style.display = dark ? 'none' : 'inline';
            }

            updateIcons();

            btn.addEventListener('click', () => {
                const dark = document.documentElement.classList.contains('dark');
                const nextTheme = dark ? 'light' : 'dark';

                document.documentElement.classList.remove('light', 'dark');
                document.documentElement.classList.add(nextTheme);
                localStorage.setItem('poliart_theme', nextTheme);
                updateIcons();
            });
        });
    </script>

    <!-- Contenedor principal -->
    <main style="flex: 1 1 auto; width: 100%; height: calc(100vh - 4.25rem); min-height: 0; display: flex; flex-direction: column; overflow-y: auto; box-sizing: border-box; padding-top: 1.25rem;">
