<?php
require_once __DIR__ . '/../includes/auth.php';
$project_base = '/poliart/';

$zonas = [];
$error_zonas = '';
try {
    $zonas = getDB()->query('SELECT id_zona, nombre FROM zona ORDER BY nombre')->fetchAll();
} catch (PDOException $e) {
    error_log('Error al cargar zonas para registro de artista: ' . $e->getMessage());
    $error_zonas = 'No fue posible cargar las zonas. Puedes continuar sin seleccionar una.';
}
?>

<!DOCTYPE html>
<html lang="es" id="html-root" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poli-Art | Registro de Artista</title>
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
        .card { background-color: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: 1.5rem; padding: 2.5rem; width: 100%; max-width: 500px; margin: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.15); backdrop-filter: blur(14px); }
        .form-label { display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .form-input { width: 100%; background-color: var(--input-bg); border: 1.5px solid var(--border-subtle); color: var(--text-main); border-radius: 0.75rem; padding: 0.75rem 1rem; font-size: 0.95rem; outline: none; box-sizing: border-box; transition: border-color 0.2s, box-shadow 0.2s; }
        .form-input:focus { border-color: var(--brand-orange); box-shadow: 0 0 0 3px rgba(237,119,69,0.18); }
        .form-hint { font-size: 0.78rem; color: var(--text-muted); margin-top: 0.3rem; }
        .required { color: #ef4444; }
        .btn-primary { width: 100%; background: linear-gradient(135deg, var(--brand-orange), #d45f2e); color: #fff; font-weight: 800; font-size: 1rem; padding: 0.85rem; border: none; border-radius: 0.85rem; cursor: pointer; transition: opacity 0.2s, transform 0.15s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; box-shadow: 0 4px 16px rgba(237,119,69,0.35); }
        .btn-primary:hover { opacity: 0.92; transform: translateY(-1px); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .alert-box { display: none; padding: 0.85rem 1rem; border-radius: 0.75rem; font-size: 0.875rem; margin-bottom: 1.25rem; }
        .alert-error  { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.4); color: #ef4444; }
        .alert-success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.4); color: #22c55e; }
        .divider { border: none; border-top: 1px solid var(--border-subtle); margin: 1.5rem 0; }
        .link-muted { font-size: 0.82rem; color: var(--text-muted); text-align: center; }
        .link-accent { color: var(--brand-orange); font-weight: 700; text-decoration: none; }
        .link-accent:hover { text-decoration: underline; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .field { margin-bottom: 1rem; }
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
                <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin: 0;">Únete como Artista</h1>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.4rem;">Publica y vende tus obras a la comunidad</p>
            </div>

            <div id="alert-box" class="alert-box" role="alert" aria-live="polite"></div>
            <?php if ($error_zonas !== ''): ?>
                <div class="alert-box alert-error" style="display: block;" role="alert">
                    <?= htmlspecialchars($error_zonas, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form id="register-form">
                <input type="hidden" name="rol" value="artista">

                <div class="grid-2">
                    <div class="field">
                        <label class="form-label" for="nombre">Nombre <span class="required">*</span></label>
                        <input class="form-input" type="text" id="nombre" name="nombre" required minlength="2" maxlength="100" autocomplete="given-name" placeholder="Luis" style="text-transform: capitalize;">
                    </div>
                    <div class="field">
                        <label class="form-label" for="apellido">Apellido <span class="required">*</span></label>
                        <input class="form-input" type="text" id="apellido" name="apellido" required minlength="2" maxlength="100" autocomplete="family-name" placeholder="Martínez" style="text-transform: capitalize;">
                    </div>
                </div>

                <div class="field">
                    <label class="form-label" for="nombre_usuario">Nombre de usuario <span class="required">*</span></label>
                    <input class="form-input" type="text" id="nombre_usuario" name="nombre_usuario" required minlength="3" maxlength="50" pattern="[A-Za-z0-9_]+" autocomplete="username" placeholder="lulo_art">
                    <p class="form-hint">Este será tu nombre público. Usa entre 3 y 50 letras, números o guiones bajos; sin espacios.</p>
                </div>

                <div class="field">
                    <label class="form-label" for="correo">Correo Electrónico <span class="required">*</span></label>
                    <input class="form-input" type="email" id="correo" name="correo" required maxlength="254" autocomplete="email" placeholder="correo@ejemplo.com">
                </div>

                <div class="field">
                    <label class="form-label" for="password">Contraseña <span class="required">*</span></label>
                    <input class="form-input" type="password" id="password" name="password" required minlength="8" autocomplete="off" placeholder="Mínimo 8 caracteres">
                </div>

                <div class="field">
                    <label class="form-label" for="confirm_password">Confirmar contraseña <span class="required">*</span></label>
                    <input class="form-input" type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="off" placeholder="Repite la contraseña">
                </div>

                <div class="field">
                    <label class="form-label" for="especialidad">Especialidad</label>
                    <select class="form-input" id="especialidad" name="especialidad">
                        <option value="">Seleccionar...</option>
                        <option value="Ilustración & Pintura">🎨 Ilustración &amp; Pintura</option>
                        <option value="Música & Audio">🎵 Música &amp; Audio</option>
                        <option value="Artes Escénicas">🎭 Artes Escénicas</option>
                        <option value="Literatura & Escritura">✍️ Literatura &amp; Escritura</option>
                        <option value="Escultura & 3D">🗿 Escultura &amp; 3D</option>
                    </select>
                </div>

                <div class="field">
                    <label class="form-label" for="telefono">Teléfono</label>
                    <input class="form-input" type="tel" id="telefono" name="telefono" maxlength="30" autocomplete="tel" placeholder="300 000 0000">
                </div>

                <div class="field">
                    <label class="form-label" for="id_zona">Zona / Comuna</label>
                    <select class="form-input" id="id_zona" name="id_zona">
                        <option value="">Seleccionar...</option>
                        <?php foreach ($zonas as $z): ?>
                            <option value="<?= (int)$z['id_zona'] ?>"><?= htmlspecialchars($z['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <label class="terms-check" style="display:flex;align-items:flex-start;gap:.55rem;margin:.35rem 0 1rem;color:var(--text-muted);font-size:.82rem;line-height:1.4;">
                    <input type="checkbox" name="acepta_terminos" value="1" required style="margin-top:.2rem;accent-color:var(--brand-orange);">
                    <span>Acepto los <a class="link-accent" href="<?= $project_base ?>app/inicio/terminos.php" target="_blank" rel="noopener">Términos y Condiciones</a>.</span>
                </label>
                <button type="submit" class="btn-primary" id="btn-submit" style="margin-top: 0.5rem;">
                    <span id="btn-text">Crear Cuenta Artista</span>
                    <svg id="btn-spinner" style="display:none; animation: spin 1s linear infinite;" width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.3"/>
                        <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                </button>
            </form>

            <hr class="divider">
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <p class="link-muted">¿Ya tienes cuenta? <a class="link-accent" href="<?= $project_base ?>app/auth/login_artista.php">Inicia sesión aquí</a></p>
                <p class="link-muted">¿Eres cliente? <a class="link-accent" href="<?= $project_base ?>app/registro/registro_cliente.php">Registro de cliente</a></p>
            </div>
        </div>
    </div>

    <script>
    (function(){
        const form    = document.getElementById('register-form');
        const alertBox = document.getElementById('alert-box');
        const spinner = document.getElementById('btn-spinner');
        const btnText = document.getElementById('btn-text');
        const btn     = document.getElementById('btn-submit');

        const savedTheme = localStorage.getItem('poliart_theme');
        const theme = savedTheme === 'dark' || savedTheme === 'light' ? savedTheme : 'light';
        document.documentElement.classList.remove('light', 'dark');
        document.documentElement.classList.add(theme);

        function showAlert(msg, ok) {
            alertBox.className = 'alert-box ' + (ok ? 'alert-success' : 'alert-error');
            alertBox.textContent = msg;
            alertBox.style.display = 'block';
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            spinner.style.display = 'inline-block';
            btnText.textContent = 'Creando cuenta...';
            btn.disabled = true;
            try {
                const res  = await fetch('/poliart/app/includes/actions/registro.php', { method: 'POST', body: new FormData(form) });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.ok) { showAlert(data.error || 'Error al registrar.', false); }
                else { showAlert('¡Bienvenido a Poli-Art! Accediendo...', true); setTimeout(() => { window.location.href = data.redirect; }, 800); }
            } catch (error) {
                console.error('Error al enviar el registro de artista:', error);
                showAlert('No se pudo conectar con el servidor. Inténtalo de nuevo.', false);
            }
            finally { spinner.style.display = 'none'; btnText.textContent = 'Crear Cuenta Artista'; btn.disabled = false; }
        });
    })();
    </script>
    <script src="<?= $project_base ?>public/assets/js/password-visibility.js"></script>
    <script src="<?= $project_base ?>public/assets/js/auth-theme.js?v=1"></script>
</body>
</html>
