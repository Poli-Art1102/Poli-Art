<?php
/**
 * includes/actions/registro_admin.php
 * Crear un nuevo administrador — solo ejecutable por un admin logueado.
 */

require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json; charset=utf-8');
if (($_POST['acepta_terminos'] ?? '') !== '1') { http_response_code(422); echo json_encode(['error' => 'Debes aceptar los Términos y Condiciones para registrar al administrador.']); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

// Protección: solo admin puede crear otro admin
if (!isLoggedIn() || getUserRole() !== 'administrador') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado.']);
    exit;
}

$nombre         = mb_convert_case(trim($_POST['nombre']         ?? ''), MB_CASE_TITLE, 'UTF-8');
$apellido       = mb_convert_case(trim($_POST['apellido']       ?? ''), MB_CASE_TITLE, 'UTF-8');
$nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
$correo         = trim($_POST['correo']         ?? '');
$password       = trim($_POST['password']       ?? '');

$errores = [];
if (strlen($nombre) < 2)   $errores[] = 'Nombre muy corto.';
if (strlen($apellido) < 2) $errores[] = 'Apellido muy corto.';
if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $nombre_usuario))
    $errores[] = 'Nombre de usuario inválido.';
if (!filter_var($correo, FILTER_VALIDATE_EMAIL))
    $errores[] = 'Correo inválido.';
if (strlen($password) < 8)
    $errores[] = 'Contraseña mínimo 8 caracteres.';

if (!empty($errores)) {
    http_response_code(422);
    echo json_encode(['error' => implode(' ', $errores)]);
    exit;
}

$pdo = getDB();

// Unicidad
$stmt = $pdo->prepare('SELECT id_usuario FROM usuario WHERE correo = ? OR nombre_usuario = ? LIMIT 1');
$stmt->execute([$correo, $nombre_usuario]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'Correo o nombre de usuario ya registrado.']);
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$pdo->prepare(
    'INSERT INTO usuario (nombre, apellido, nombre_usuario, correo, contrasena_hash, rol)
     VALUES (?, ?, ?, ?, ?, "administrador")'
)->execute([$nombre, $apellido, $nombre_usuario, $correo, $hash]);

echo json_encode(['ok' => true]);
