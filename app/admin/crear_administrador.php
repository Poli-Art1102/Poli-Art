<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['administrador']);

$project_base = '/poliart/';
$pdo = getDB();

$mensaje_exito = '';
$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre         = trim($_POST['nombre'] ?? '');
    $apellido       = trim($_POST['apellido'] ?? '');
    $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
    $correo         = trim($_POST['correo'] ?? '');
    $password       = $_POST['password'] ?? '';
    $confirm_pwd    = $_POST['confirm_password'] ?? '';

    if ($nombre === '' || $apellido === '' || $nombre_usuario === '' || $correo === '' || $password === '') {
        $mensaje_error = 'Todos los campos son obligatorios.';
    } elseif (strlen($nombre) < 2 || strlen($apellido) < 2) {
        $mensaje_error = 'El nombre y el apellido deben tener al menos 2 caracteres.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $nombre_usuario)) {
        $mensaje_error = 'El nombre de usuario debe tener entre 3 y 50 caracteres y solo puede incluir letras, números y guiones bajos.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = 'El correo electrónico no tiene un formato válido.';
    } elseif ($password !== $confirm_pwd) {
        $mensaje_error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 8) {
        $mensaje_error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT id_usuario FROM usuario
                 WHERE correo = :correo OR nombre_usuario = :nombre_usuario
                 LIMIT 1'
            );
            $stmt->execute([
                'correo' => $correo,
                'nombre_usuario' => $nombre_usuario,
            ]);

            if ($stmt->fetch()) {
                $mensaje_error = 'El correo electrónico o el nombre de usuario ya están registrados.';
            } else {
                $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare(
                    'INSERT INTO usuario
                        (nombre, apellido, nombre_usuario, correo, contrasena_hash, rol, estado)
                     VALUES
                        (:nombre, :apellido, :nombre_usuario, :correo, :contrasena_hash, "administrador", "activo")'
                );
                $stmt->execute([
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'nombre_usuario' => $nombre_usuario,
                    'correo' => $correo,
                    'contrasena_hash' => $password_hash,
                ]);

                $mensaje_exito = "¡Administrador @{$nombre_usuario} registrado correctamente!";
            }
        } catch (PDOException $e) {
            error_log('Error al crear administrador: ' . $e->getMessage());
            $mensaje_error = 'No fue posible registrar el administrador. Verifica que el correo y el nombre de usuario no estén en uso.';
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<style>
.form-container {
    background-color: var(--bg-surface) !important;
    border: 1px solid var(--border-subtle) !important;
    border-radius: 1.25rem;
    padding: 2.5rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    backdrop-filter: blur(12px);
    width: 100%;
    max-width: 580px;
    margin: 0 auto;
    box-sizing: border-box;
}

.form-label {
    display: block;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--text-muted);
    margin-bottom: 0.4rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.form-input {
    width: 100%;
    background-color: var(--hover-item) !important;
    border: 1.5px solid var(--border-subtle) !important;
    color: var(--text-main) !important;
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.2s;
    margin-bottom: 1.25rem;
}

.form-input:focus {
    border-color: var(--brand-orange) !important;
}

.btn-submit {
    width: 100%;
    background: linear-gradient(135deg, var(--brand-orange) 0%, #ea580c 100%) !important;
    color: #ffffff !important;
    font-weight: 800;
    font-size: 0.95rem;
    padding: 0.85rem;
    border: none;
    border-radius: 0.85rem;
    cursor: pointer;
    transition: transform 0.15s ease, opacity 0.2s ease;
    box-shadow: 0 4px 15px rgba(249,115,22,0.35);
}

.btn-submit:hover {
    opacity: 0.95;
    transform: translateY(-1px);
}

.alert-box {
    padding: 0.85rem 1rem;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    margin-bottom: 1.25rem;
    font-weight: 700;
}

.alert-error { background: rgba(239,68,68,0.15); border: 1px solid #ef4444; color: #ef4444; }
.alert-success { background: rgba(34,197,94,0.15); border: 1px solid #22c55e; color: #22c55e; }
</style>

<div style="width: 100%; padding: 0 2rem; box-sizing: border-box;">
    <div class="form-container">

        <!-- HEADER FORMULARIO -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
            <div>
                <h1 style="font-size: 1.4rem; font-weight: 800; color: var(--text-main); margin: 0;">
                    ➕ Crear Nuevo Administrador
                </h1>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.25rem 0 0 0;">
                    Registra una nueva cuenta con permisos de gestión global
                </p>
            </div>
            <a href="/poliart/app/admin/index.php" style="color: var(--brand-orange); font-size: 0.85rem; font-weight: 700; text-decoration: none;">
                ← Volver al Panel
            </a>
        </div>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alert-box alert-error">
                ⚠️ <?= htmlspecialchars($mensaje_error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensaje_exito)): ?>
            <div class="alert-box alert-success">
                ✅ <?= htmlspecialchars($mensaje_exito) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <label class="form-label" for="nombre">Nombre</label>
                    <input class="form-input" type="text" id="nombre" name="nombre" placeholder="Ej. Carlos" required value="<?= htmlspecialchars($_POST['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div>
                    <label class="form-label" for="apellido">Apellido</label>
                    <input class="form-input" type="text" id="apellido" name="apellido" placeholder="Ej. Mendoza" required value="<?= htmlspecialchars($_POST['apellido'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div>
                <label class="form-label" for="nombre_usuario">Nombre de Usuario (@)</label>
                <input class="form-input" type="text" id="nombre_usuario" name="nombre_usuario" placeholder="carlos_admin" required value="<?= htmlspecialchars($_POST['nombre_usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label class="form-label" for="correo">Correo Electrónico</label>
                <input class="form-input" type="email" id="correo" name="correo" placeholder="admin@poliart.co" required value="<?= htmlspecialchars($_POST['correo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <label class="form-label" for="password">Contraseña</label>
                    <input class="form-input" type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <div>
                    <label class="form-label" for="confirm_password">Confirmar Contraseña</label>
                    <input class="form-input" type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                Registrar Administrador
            </button>
        </form>

    </div>
</div>
