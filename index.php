<?php
declare(strict_types=1);

require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/ModuleLoader.php';

$moduleTargets = [
    'admin' => __DIR__ . '/app/admin/index.php',
    'artista' => __DIR__ . '/app/artista/index.php',
    'cliente' => __DIR__ . '/app/cliente/index.php',
    'auth' => __DIR__ . '/app/auth/index.php',
    'inicio' => __DIR__ . '/app/inicio/index.php',
];

$moduleLoader = new ModuleLoader(__DIR__ . '/app');
$router = new Router();
$registerModuleRoute = static function (string $module) use ($router, $moduleLoader, $moduleTargets): void {
    $router->get('/' . $module, static function () use ($module, $moduleLoader, $moduleTargets): void {
        $moduleLoader->load($moduleTargets[$module]);
    });
};

foreach (array_keys($moduleTargets) as $module) {
    $registerModuleRoute($module);
}

$router->get('/', static function () use ($moduleLoader, $moduleTargets): void {
    $moduleLoader->load($moduleTargets['inicio']);
});

// Compatibilidad con la regla de reescritura existente y enlaces antiguos.
$requestedModule = strtolower(trim((string) ($_GET['module'] ?? '')));
if ($requestedModule !== '') {
    if (!isset($moduleTargets[$requestedModule])) {
        http_response_code(404);
        echo 'Módulo no encontrado';
        exit;
    }

    $moduleLoader->load($moduleTargets[$requestedModule]);
    exit;
}

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
if ($basePath !== '' && $basePath !== '/' && str_starts_with($requestPath, $basePath)) {
    $requestPath = substr($requestPath, strlen($basePath));
}
if ($requestPath === '/index.php' || $requestPath === '') {
    $requestPath = '/';
}
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $requestPath);
