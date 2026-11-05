<?php
/**
 * includes/logout.php
 * Cierra la sesión del usuario y redirige al inicio.
 */

if (session_status() === PHP_SESSION_NONE) {
session_name('POLIARTSESSID');
session_set_cookie_params(['lifetime' => 0, 'path' => '/poliart/', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'httponly' => true, 'samesite' => 'Lax']);
session_start();
}

// Limpiar todas las variables de sesión
$_SESSION = [];

// Destruir la cookie de sesión si existe
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: /poliart/app/inicio/index.php');
exit;
