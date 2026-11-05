<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);

$idBorrador = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$idBorrador || $idBorrador < 1) {
    header('Location: borradores.php');
    exit;
}

header('Location: subir_borrador.php?id=' . $idBorrador);
exit;
