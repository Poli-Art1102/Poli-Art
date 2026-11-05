<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['cliente', 'artista']);
ob_start();
require __DIR__ . '/../includes/mensajes.php';
$vistaMensajes = ob_get_clean();
require_once 'header.php';
include_once 'sidebar.php';
echo $vistaMensajes;

