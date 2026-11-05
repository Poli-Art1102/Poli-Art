<?php
/**
 * includes/actions/login.php
 * Endpoint POST que procesa el login para los tres roles.
 * Responde JSON para llamadas fetch() desde el frontend.
 */

require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$correo   = trim($_POST['correo']   ?? '');
$password = trim($_POST['password'] ?? '');
$rol      = trim($_POST['rol']      ?? '');  // 'cliente' | 'artista' | 'administrador'

if (!$correo || !$password) {
    http_response_code(422);
    echo json_encode(['error' => 'Correo y contraseña son obligatorios.']);
    exit;
}

$resultado = loginUsuario($correo, $password);

if (!$resultado['ok']) {
    http_response_code(401);
    echo json_encode(['error' => $resultado['error']]);
    exit;
}

$user = $resultado['user'];

// Verificar que el rol solicitado coincide con el rol real
if ($rol && $user['rol'] !== $rol) {
    // Destruir sesión si la hubiera y responder
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
    http_response_code(403);
    echo json_encode(['error' => 'No tienes acceso con este tipo de cuenta.']);
    exit;
}

// Determinar URL de redirección según rol
$destinos = [
    'administrador' => '/poliart/app/admin/index.php',
    'artista'       => '/poliart/app/artista/index.php',
    'cliente'       => '/poliart/app/cliente/index.php',
];
$redirect = $destinos[$user['rol']] ?? '/poliart/app/inicio/index.php';

echo json_encode([
    'ok'       => true,
    'redirect' => $redirect,
    'user'     => [
        'nombre'   => $user['nombre'],
        'rol'      => $user['rol'],
        'avatar'   => $user['avatar_path'] ?? null,
    ]
]);
