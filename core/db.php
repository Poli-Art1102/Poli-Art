<?php
declare(strict_types=1);

// Mantener un único origen de configuración y una única conexión PDO para
// todo el frontend PHP. El conector compartido también carga api/.env.
require_once dirname(__DIR__) . '/app/includes/db.php';

function db(): PDO
{
    return getDB();
}
