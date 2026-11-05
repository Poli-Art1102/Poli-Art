<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$project_base = $project_base ?? '/poliart/';
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poli-Art</title>
    
    <link rel="stylesheet" href="<?= $project_base ?>public/assets/css/tailwind.css?v=20">
    <link rel="stylesheet" href="<?= $project_base ?>public/assets/css/botones.css?v=1">
    <link rel="stylesheet" href="<?= $project_base ?>public/assets/css/password-visibility.css">
    <link rel="stylesheet" href="<?= $project_base ?>public/assets/css/scrollbar-global.css?v=3">
    
    <style>
        html,
        body {
            height: 100%;
            min-height: 0;
        }

        body.inicio-page {
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .inicio-page .main-navbar,
        .inicio-page .poliart-footer {
            flex: 0 0 auto;
        }

        .inicio-scroll-area {
            flex: 1 1 auto;
            min-height: 0;
            width: 100%;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            overflow-y: auto;
            scrollbar-gutter: stable;
        }

        .inicio-scroll-area > :not(.poliart-footer) {
            flex: 1 0 auto;
        }

        .inicio-scroll-area .catalogo-page {
            max-width: 1600px !important;
        }

        .inicio-scroll-area .catalogo-grid {
            grid-template-columns: repeat(auto-fill, minmax(260px, 320px)) !important;
            justify-content: start !important;
        }

        :root, html.light {
            --brand-orange:       #ED7745;
            --brand-yellow:       #EDCE45;
            --bg-surface:        rgba(253, 235, 230, 0.96);
            --bg-body:           #FFF5F2;
            --bg-nav:            #FCE3DC;
            --border-subtle:      #F5C7B8;
            --hover-item:        #F7D4C8;
            --text-main:         #2C140B;
            --text-muted:        #7A4B3A;
            --header-bg:          var(--bg-nav);
            --header-border:      var(--border-subtle);
            --header-control-bg:  var(--hover-item);
            --header-text:        var(--text-main);
            --header-menu-hover:  #F2C4B3;
        }

        html.dark {
            --brand-orange:       #FF9D73;
            --brand-yellow:       #EDCE45;
            --bg-surface:        rgba(77, 34, 15, 0.95);
            --bg-body:           #321408;
            --bg-nav:            #4D220F; /* Marrón chocolate exacto de las tarjetas Poli-Art */
            --border-subtle:      #853F22;
            --hover-item:        #632912;
            --text-main:         #FFF1EB;
            --text-muted:        #E0C0B3;
            --header-bg:          var(--bg-nav);
            --header-border:      var(--border-subtle);
            --header-control-bg:  var(--hover-item);
            --header-text:        var(--text-main);
            --header-menu-hover:  #3D1B0C;
        }

        html, body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
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
        }

        /* FORZADO DE PALETA DE COLOR SEGÚN EL TEMA ACTIVO */
        .main-navbar {
            background-color: var(--header-bg) !important;
            border-bottom: 1px solid var(--header-border) !important;
            position: sticky;
            top: 0;
            z-index: 50;
            width: 100%;
            backdrop-filter: blur(12px);
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .navbar-container {
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

        .nav-btn-action {
            background: var(--header-control-bg) !important;
            border: 1.5px solid var(--border-subtle) !important;
            color: var(--header-text) !important;
            padding: 0.5rem 1rem;
            border-radius: 0.6rem;
            font-weight: 700;
            font-size: 0.875rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .nav-btn-action:hover {
            border-color: var(--brand-orange) !important;
            background: var(--header-menu-hover) !important;
        }

        .nav-btn-primary {
            background: linear-gradient(135deg, var(--brand-orange) 0%, #ea580c 100%) !important;
            color: #ffffff !important;
            border: none !important;
            padding: 0.5rem 1.25rem;
            border-radius: 0.6rem;
            font-weight: 800;
            font-size: 0.875rem;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .nav-group {
            position: relative;
        }

        .nav-submenu {
            position: absolute;
            top: calc(100% + 0.5rem);
            right: 0;
            display: none;
            flex-direction: column;
            min-width: 170px;
            background: var(--header-bg) !important;
            border: 1px solid var(--header-border) !important;
            border-radius: 0.7rem;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            z-index: 60;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .nav-submenu.is-open {
            display: flex;
        }

        .nav-submenu a {
            color: var(--header-text) !important;
            text-decoration: none;
            padding: 0.75rem 1rem;
            font-size: 0.8rem;
            font-weight: 700;
            border-bottom: 1px solid var(--header-border);
            transition: background 0.2s ease;
        }

        .nav-submenu a:last-child {
            border-bottom: none;
        }

        .nav-submenu a:hover {
            background: var(--header-menu-hover) !important;
        }

        .theme-toggle-btn {
            background: var(--header-control-bg) !important;
            border: 1.5px solid var(--border-subtle) !important;
            padding: 0.5rem;
            border-radius: 0.6rem;
            cursor: pointer;
            color: var(--header-text) !important;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .theme-toggle-btn:hover {
            border-color: var(--brand-orange) !important;
            transform: scale(1.05);
        }
    </style>

    <script>
        (function () {
            const storedTheme = localStorage.getItem('poliart_theme');
            const theme = storedTheme === 'light' || storedTheme === 'dark' ? storedTheme : 'dark';

            document.documentElement.classList.remove('light', 'dark');
            document.documentElement.classList.add(theme);
        })();
    </script>
</head>
<body class="inicio-page">

    <header class="main-navbar">
        <div class="navbar-container">
            
            <!-- LOGO DE POLI-ART -->
            <a href="<?= $project_base ?>app/inicio/index.php" class="brand-logo">
                🎨 <span>Poli-Art</span>
            </a>

            <!-- ZONA DERECHA (INICIAR SESIÓN / CREAR CUENTA Y TEMA) -->
            <div style="display: flex; align-items: center; gap: 0.85rem;">
                
                <div class="nav-group">
                    <button type="button" class="nav-btn-action nav-toggle" data-menu="register-menu">
                        Crear Cuenta ▾
                    </button>
                    <div class="nav-submenu" id="register-menu" aria-label="Opciones de registro">
                        <a href="<?= $project_base ?>app/registro/registro_cliente.php">Cliente</a>
                        <a href="<?= $project_base ?>app/registro/registro_artista.php">Artista</a>
                    </div>
                </div>

                <div class="nav-group">
                    <button type="button" class="nav-btn-primary nav-toggle" data-menu="login-menu">
                        Iniciar Sesión ▾
                    </button>
                    <div class="nav-submenu" id="login-menu" aria-label="Opciones de inicio de sesión">
                        <a href="<?= $project_base ?>app/auth/login_cliente.php">Cliente</a>
                        <a href="<?= $project_base ?>app/auth/login_artista.php">Artista</a>
                        <a href="<?= $project_base ?>app/auth/login_admin.php">Administrador</a>
                    </div>
                </div>

                <button id="theme-toggle" type="button" class="theme-toggle-btn" title="Cambiar tema">
                    <span id="theme-toggle-sun" style="display: none; font-size: 1.1rem;">🌙</span>
                    <span id="theme-toggle-moon" style="display: none; font-size: 1.1rem;">☀️</span>
                </button>

            </div>
        </div>
    </header>

    <div class="inicio-scroll-area">

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btn      = document.getElementById('theme-toggle');
            const sunIcon  = document.getElementById('theme-toggle-sun');
            const moonIcon = document.getElementById('theme-toggle-moon');
            const toggles  = document.querySelectorAll('.nav-toggle');

            if (btn && sunIcon && moonIcon) {
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
            }

            toggles.forEach((toggle) => {
                toggle.addEventListener('click', (event) => {
                    event.stopPropagation();

                    const menuId = toggle.dataset.menu;
                    const menu = document.getElementById(menuId);

                    if (!menu) {
                        return;
                    }

                    const isOpen = menu.classList.contains('is-open');

                    document.querySelectorAll('.nav-submenu').forEach((submenu) => {
                        submenu.classList.remove('is-open');
                    });

                    if (!isOpen) {
                        menu.classList.add('is-open');
                    }
                });
            });

            document.addEventListener('click', (event) => {
                if (!event.target.closest('.nav-toggle') && !event.target.closest('.nav-submenu')) {
                    document.querySelectorAll('.nav-submenu').forEach((submenu) => {
                        submenu.classList.remove('is-open');
                    });
                }
            });
        });
    </script>
