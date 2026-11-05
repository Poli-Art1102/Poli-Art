<?php
/**
 * includes/auth.php
 * Funciones de autenticación, sesión y protección de rutas para Poli-Art
 */

if (session_status() === PHP_SESSION_NONE) {
    session_name('POLIARTSESSID');
session_cache_limiter('nocache');
session_set_cookie_params(['lifetime' => 0, 'path' => '/poliart/', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'httponly' => true, 'samesite' => 'Lax']);
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
}

require_once __DIR__ . '/db.php';

// ─────────────────────────────────────────────
// Helpers de sesión
// ─────────────────────────────────────────────

function cargarUsuarioSesion(): ?array {
    static $cargado = false;
    static $usuario = null;
    if ($cargado) return $usuario;
    $cargado = true;

    $id = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) return null;

    $stmt = getDB()->prepare(
        'SELECT u.*, a.id_artista, a.nombre_artistico, a.especialidad
         FROM usuario u
         LEFT JOIN artista a ON a.id_usuario = u.id_usuario
         WHERE u.id_usuario = :id AND u.estado = \'activo\'
         LIMIT 1'
    );
    $stmt->execute([':id' => $id]);
    $usuario = $stmt->fetch() ?: null;
    if ($usuario === null) {
        $_SESSION = [];
        return null;
    }

    unset($usuario['contrasena_hash']);
    $_SESSION['user_id'] = (int)$usuario['id_usuario'];
    $_SESSION['user'] = $usuario;
    return $usuario;
}

function isLoggedIn(): bool {
    return cargarUsuarioSesion() !== null;
}

function getCurrentUser(): ?array {
    return cargarUsuarioSesion();
}

function getUserRole(): ?string {
    return getCurrentUser()['rol'] ?? null;
}

function getUserId(): ?int {
    $usuario = getCurrentUser();
    return $usuario !== null ? (int)$usuario['id_usuario'] : null;
}

// ─────────────────────────────────────────────
// Protección de rutas
// ─────────────────────────────────────────────

/**
 * Redirige al login si el usuario no está autenticado o no tiene el rol esperado.
 * @param string|string[] $roles  Rol o lista de roles permitidos.
 */
function requireLogin($roles = []): void {
    if (!isLoggedIn()) {
        $redirect = urlencode($_SERVER['REQUEST_URI']);
        header('Location: /poliart/app/auth/login_cliente.php?redirect=' . $redirect);
        exit;
    }

    if (!empty($roles)) {
        $roles = (array)$roles;
        $userRole = getUserRole();
        if (!in_array($userRole, $roles, true)) {
            // Redirigir al inicio del rol correspondiente
            redirectToHome();
        }
    }
}

function redirectToHome(): void {
    $role = getUserRole();
    $map = [
        'administrador' => '/poliart/app/admin/index.php',
        'artista'       => '/poliart/app/artista/index.php',
        'cliente'       => '/poliart/app/cliente/index.php',
    ];
    header('Location: ' . ($map[$role] ?? '/poliart/app/inicio/index.php'));
    exit;
}

// ─────────────────────────────────────────────
// Login
// ─────────────────────────────────────────────

/**
 * Valida credenciales y crea la sesión.
 * @return array ['ok' => bool, 'error' => string, 'user' => array]
 */
function loginUsuario(string $correo, string $password): array {
    $pdo = getDB();

    $stmt = $pdo->prepare(
        'SELECT u.*, a.id_artista, a.nombre_artistico, a.especialidad
         FROM usuario u
         LEFT JOIN artista a ON a.id_usuario = u.id_usuario
         WHERE u.correo = :correo
         LIMIT 1'
    );
    $stmt->execute([':correo' => trim($correo)]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['ok' => false, 'error' => 'Credenciales incorrectas.'];
    }

    if (!password_verify($password, $user['contrasena_hash'])) {
        return ['ok' => false, 'error' => 'Credenciales incorrectas.'];
    }

    if ($user['estado'] !== 'activo') {
        return ['ok' => false, 'error' => 'Tu cuenta está suspendida o inactiva. Contacta con soporte.'];
    }

    // Actualizar último acceso
    $pdo->prepare('UPDATE usuario SET ultimo_acceso = NOW() WHERE id_usuario = :id')
        ->execute([':id' => $user['id_usuario']]);

    // Guardar en sesión (sin exponer el hash)
    unset($user['contrasena_hash']);
    session_regenerate_id(true);
    $_SESSION = [];
    $_SESSION['user_id'] = (int)$user['id_usuario'];
    $_SESSION['user']    = $user;

    return ['ok' => true, 'user' => $user];
}

// ─────────────────────────────────────────────
// Registro
// ─────────────────────────────────────────────

/**
 * Registra un nuevo cliente o artista.
 * @param array $data  Campos del formulario ya validados.
 * @param string $rol  'cliente' | 'artista'
 * @return array ['ok' => bool, 'error' => string, 'id' => int]
 */
function registrarUsuario(array $data, string $rol): array {
    $pdo = getDB();

    // Verificar unicidad de correo y nombre de usuario
    $stmt = $pdo->prepare(
        'SELECT id_usuario FROM usuario WHERE correo = :correo OR nombre_usuario = :nu LIMIT 1'
    );
    $stmt->execute([':correo' => $data['correo'], ':nu' => $data['nombre_usuario']]);
    if ($stmt->fetch()) {
        return ['ok' => false, 'error' => 'El correo o nombre de usuario ya está registrado.'];
    }

    $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

    $pdo->beginTransaction();
    try {
        $ins = $pdo->prepare(
            'INSERT INTO usuario
                (nombre, apellido, nombre_usuario, correo, contrasena_hash, rol, id_zona, telefono)
             VALUES
                (:nombre, :apellido, :nu, :correo, :hash, :rol, :zona, :tel)'
        );
        $ins->execute([
            ':nombre'   => trim($data['nombre']),
            ':apellido' => trim($data['apellido']),
            ':nu'       => trim($data['nombre_usuario']),
            ':correo'   => trim($data['correo']),
            ':hash'     => $hash,
            ':rol'      => $rol,
            ':zona'     => $data['id_zona'] ?? null,
            ':tel'      => $data['telefono'] ?? null,
        ]);
        $newId = (int)$pdo->lastInsertId();

        if ($rol === 'artista') {
            $pdo->prepare(
                'INSERT INTO artista (id_usuario, nombre_artistico, especialidad)
                 VALUES (:idu, :na, :esp)'
            )->execute([
                ':idu' => $newId,
                ':na'  => trim($data['nombre_artistico'] ?? $data['nombre']),
                ':esp' => $data['especialidad'] ?? null,
            ]);

            // Crear carrito inicial para el artista (también puede comprar)
            $pdo->prepare(
                'INSERT INTO carrito (id_usuario, estado) VALUES (:id, "activo")'
            )->execute([':id' => $newId]);
        }

        if ($rol === 'cliente') {
            $pdo->prepare(
                'INSERT INTO carrito (id_usuario, estado) VALUES (:id, "activo")'
            )->execute([':id' => $newId]);
        }

        $pdo->commit();
        return ['ok' => true, 'id' => $newId];

    } catch (PDOException $e) {
        $pdo->rollBack();
        return ['ok' => false, 'error' => 'Error al crear la cuenta. Intenta de nuevo.'];
    }
}

// ─────────────────────────────────────────────
// Respuesta JSON para peticiones AJAX
// ─────────────────────────────────────────────

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function isAjax(): bool {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
