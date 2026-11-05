<?php
/**
 * includes/db.php
 * Conexión PDO a MariaDB para Poli-Art (PHP)
 *
 * Lee credenciales desde api/.env si existe,
 * con fallback a valores por defecto de XAMPP.
 */

// ── Cargar .env de la API si existe ───────────────────────────
$_env_path = dirname(__DIR__, 2) . '/api/.env';
if (file_exists($_env_path)) {
    foreach (file($_env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        $_line = trim($_line);
        // Ignorar comentarios y líneas sin =
        if ($_line === '' || $_line[0] === '#' || strpos($_line, '=') === false) continue;
        [$_key, $_val] = explode('=', $_line, 2);
        $_key = trim($_key);
        $_val = trim($_val);
        // No sobreescribir variables ya definidas en el entorno real
        if (!isset($_ENV[$_key]) && !getenv($_key)) {
            putenv("$_key=$_val");
            $_ENV[$_key] = $_val;
        }
    }
}
unset($_env_path, $_line, $_key, $_val);

// ── Credenciales con fallback a XAMPP por defecto ─────────────
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: 'poliart');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
// DB_PASSWORD es el nombre común usado por .env.example; DB_PASS se mantiene
// como alias para instalaciones existentes.
define('DB_PASS',    getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''));
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        // No exponer detalles de conexión en producción
        die(json_encode(['error' => 'No se pudo conectar a la base de datos.']));
    }

    return $pdo;
}
