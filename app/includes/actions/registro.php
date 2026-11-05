<?php
/**
 * includes/actions/registro.php
 * Endpoint POST para registro de cliente y artista.
 * Responde JSON.
 */

require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$rol = trim($_POST['rol'] ?? '');
if (($_POST['acepta_terminos'] ?? '') !== '1') {
    http_response_code(422);
    echo json_encode(['error' => 'Debes aceptar los Términos y Condiciones para registrarte.']);
    exit;
}

if (!in_array($rol, ['cliente', 'artista'], true)) {
    http_response_code(422);
    echo json_encode(['error' => 'Rol inválido.']);
    exit;
}

// Campos comunes
$nombre         = mb_convert_case(trim($_POST['nombre']         ?? ''), MB_CASE_TITLE, 'UTF-8');
$apellido       = mb_convert_case(trim($_POST['apellido']       ?? ''), MB_CASE_TITLE, 'UTF-8');
$nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
$correo         = trim($_POST['correo']         ?? '');
$password       = trim($_POST['password']       ?? '');
$id_zona        = intval($_POST['id_zona']      ?? 0) ?: null;
$telefono       = trim($_POST['telefono']       ?? '') ?: null;

// El nombre artístico coincide con el usuario público para las cuentas de artista.
$nombre_artistico = $rol === 'artista'
    ? $nombre_usuario
    : (mb_convert_case(trim($_POST['nombre_artistico'] ?? ''), MB_CASE_TITLE, 'UTF-8') ?: null);
$especialidad     = trim($_POST['especialidad']     ?? '') ?: null;
$confirm_password = $_POST['confirm_password'] ?? '';

// Validaciones básicas
$errores = [];
if (strlen($nombre) < 2)          $errores[] = 'Nombre muy corto.';
if (strlen($apellido) < 2)        $errores[] = 'Apellido muy corto.';
if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $nombre_usuario))
    $errores[] = 'Nombre de usuario inválido (3-50 caracteres, solo letras, números y _).';
if (!filter_var($correo, FILTER_VALIDATE_EMAIL))
    $errores[] = 'Correo electrónico inválido.';
if (strlen($password) < 8)        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
if ($rol === 'artista') {
    if ($nombre_artistico === null || strlen($nombre_artistico) < 2)
        $errores[] = 'El nombre artístico debe tener al menos 2 caracteres.';
    if ($password !== $confirm_password)
        $errores[] = 'Las contraseñas no coinciden.';
}

if (!empty($errores)) {
    http_response_code(422);
    echo json_encode(['error' => implode(' ', $errores)]);
    exit;
}

$data = compact(
    'nombre', 'apellido', 'nombre_usuario', 'correo', 'password',
    'id_zona', 'telefono', 'nombre_artistico', 'especialidad'
);

$resultado = registrarUsuario($data, $rol);

if (!$resultado['ok']) {
    http_response_code(409);
    echo json_encode(['error' => $resultado['error']]);
    exit;
}

// Auto-login tras registro
$login = loginUsuario($correo, $password);
$redirect = $rol === 'artista'
    ? '/poliart/app/artista/index.php'
    : '/poliart/app/cliente/index.php';

echo json_encode([
    'ok'       => true,
    'redirect' => $redirect,
    'id'       => $resultado['id'],
]);
