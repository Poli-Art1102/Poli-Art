<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['administrador']);

header('Location: /poliart/app/admin/obras_reportadas.php');
exit;
