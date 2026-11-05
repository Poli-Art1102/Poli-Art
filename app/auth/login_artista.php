<?php
require_once __DIR__ . '/../includes/auth.php';
$project_base = '/poliart/';
?>
<!DOCTYPE html>
<html lang="es" id="html-root" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poli-Art | Iniciar Sesión Artista</title>
    <link rel="stylesheet" href="<?= $project_base ?>public/assets/css/tailwind.css?v=50">
    <link rel="stylesheet" href="<?= $project_base ?>public/assets/css/botones.css?v=1">
    <link rel="stylesheet" href="<?= $project_base ?>public/assets/css/botones-global.css?v=3">
    <link rel="stylesheet" href="<?= $project_base ?>public/assets/css/password-visibility.css">
    <style>
        :root {
            --brand-orange: #ED7745; --brand-yellow: #EDCE45;
            --bg-nav: #FCE3DC; --border-subtle: #F5C7B8;
            --text-main: #2C140B; --text-muted: #7A4B3A;
            --hover-item: #F7D4C8; --input-bg: rgba(255,255,255,0.7);
            --bg-surface: rgba(253,235,230,0.97);
        }
        html.dark {
            --brand-orange: #FF9D73; --brand-yellow: #EDCE45;
            --bg-nav: #632912; --border-subtle: #853F22;
            --text-main: #FFF1EB; --text-muted: #E0C0B3;
            --hover-item: #4D220F; --input-bg: rgba(50,20,8,0.7);
            --bg-surface: rgba(77,34,15,0.97);
        }
        body {
            background-image: url('<?= $project_base ?>app/includes/madera.jpg');
            background-size: cover; background-position: center;
            background-attachment: fixed; background-repeat: no-repeat;
            color: var(--text-main); font-family: system-ui, -apple-system, sans-serif;
            min-height: 100vh; display: flex; flex-direction: column; margin: 0;
        }
        .page-nav { background-color: var(--bg-nav); border-bottom: 1px solid var(--border-subtle); padding: 0 2rem; height: 4rem; display: flex; align-items: center; justify-content: space-between; }
        .logo { font-size: 1.4rem; font-weight: 800; color: var(--brand-orange); text-decoration: none; }
        .back-link { font-size: 0.85rem; color: var(--text-muted); text-decoration: none; font-weight: 600; }
        .back-link:hover { color: var(--brand-orange); }
        .card { background-color: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: 1.5rem; padding: 2.5rem; width: 100%; max-width: 420px; margin: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.15); backdrop-filter: blur(14px); }
        .form-label { display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .form-input { width: 100%; background-color: var(--input-bg); border: 1.5px solid var(--border-subtle); color: var(--text-main); border-radius: 0.75rem; padding: 0.75rem 1rem; font-size: 0.95rem; outline: none; box-sizing: border-box; transition: border-color 0.2s, box-shadow 0.2s; }
        .form-input:focus { border-color: var(--brand-orange); box-shadow: 0 0 0 3px rgba(237,119,69,0.18); }
        .btn-primary { width: 100%; background: linear-gradient(135deg, var(--brand-orange), #d45f2e); color: #fff; font-weight: 800; font-size: 1rem; padding: 0.85rem; border: none; border-radius: 0.85rem; cursor: pointer; transition: opacity 0.2s, transform 0.15s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; box-shadow: 0 4px 16px rgba(237,119,69,0.35); }
        .btn-primary:hover { opacity: 0.92; transform: translateY(-1px); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .alert-box { display: none; padding: 0.85rem 1rem; border-radius: 0.75rem; font-size: 0.875rem; margin-bottom: 1.25rem; }
        .alert-error  { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.4); color: #ef4444; }
        .alert-success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.4); color: #22c55e; }
        .divider { border: none; border-top: 1px solid var(--border-subtle); margin: 1.5rem 0; }
        .link-muted { font-size: 0.82rem; color: var(--text-muted); }
        .link-accent { color: var(--brand-orange); font-weight: 700; text-decoration: none; }
        .link-accent:hover { text-decoration: underline; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <nav class="page-nav">
        <a href="<?= $project_base ?>app/inicio/index.php" class="logo">🎨 Poli-Art</a>
        <a href="<?= $project_base ?>app/inicio/index.php" class="back-link global-back-button">← <span class="back-label">Volver al inicio</span></a>
    </nav>

    <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem;">
        <div class="card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">🎨</div>
                <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin: 0;">Espacio del Artista</h1>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.4rem;">Accede a tu espacio creativo</p>
            </div>

            <div id="alert-box" class="alert-box"></div>

            <form id="login-form" novalidate>
                <input type="hidden" name="rol" value="artista">

                <div style="margin-bottom: 1.1rem;">
                    <label class="form-label" for="correo">Correo Electrónico</label>
                    <input class="form-input" type="email" id="correo" name="correo" required placeholder="correo@ejemplo.com">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label" for="password">Contraseña</label>
                    <div style="position: relative;">
                        <input class="form-input" type="password" id="password" name="password" required placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="btn-primary" id="btn-submit">
                    <span id="btn-text">Ingresar</span>
                    <svg id="btn-spinner" style="display:none; animation: spin 1s linear infinite;" width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.3"/>
                        <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                </button>
            </form>

            <hr class="divider">
            <div style="text-align: center; display: flex; flex-direction: column; gap: 0.5rem;">
                <p class="link-muted">¿No tienes cuenta? <a class="link-accent" href="<?= $project_base ?>app/registro/registro_artista.php">Regístrate como artista</a></p>
                <p class="link-muted">¿Eres cliente? <a class="link-accent" href="<?= $project_base ?>app/auth/login_cliente.php">Login cliente</a></p>
            </div>
        </div>
    </div>

    <script>
    (function(){
        const form    = document.getElementById('login-form');
        const alertBox = document.getElementById('alert-box');
        const spinner = document.getElementById('btn-spinner');
        const btnText = document.getElementById('btn-text');
        const btn     = document.getElementById('btn-submit');

        document.getElementById('html-root').className = localStorage.getItem('poliart_theme') || 'light';

        function showAlert(msg, ok) {
            alertBox.className = 'alert-box ' + (ok ? 'alert-success' : 'alert-error');
            alertBox.textContent = msg;
            alertBox.style.display = 'block';
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            spinner.style.display = 'inline-block';
            btnText.textContent = 'Verificando...';
            btn.disabled = true;
            try {
                const res  = await fetch('/poliart/app/includes/actions/login.php', { method: 'POST', body: new FormData(form) });
                const data = await res.json();
                if (!res.ok || !data.ok) { showAlert(data.error || 'Error al iniciar sesión.', false); }
                else { showAlert('¡Bienvenido de vuelta! Redirigiendo...', true); setTimeout(() => { window.location.href = data.redirect; }, 800); }
            } catch(_) { showAlert('Error de red. Verifica tu conexión.', false); }
            finally { spinner.style.display = 'none'; btnText.textContent = 'Ingresar'; btn.disabled = false; }
        });
    })();
    </script>
    <script src="<?= $project_base ?>public/assets/js/password-visibility.js"></script>
    <script src="<?= $project_base ?>public/assets/js/auth-theme.js?v=1"></script>
</body>
</html>
