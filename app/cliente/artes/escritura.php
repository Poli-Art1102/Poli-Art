<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/catalogo_obras.php';

$tituloCatalogo = '✍️ Escritura';
$descripcionCatalogo = 'Obras literarias, poemarios y textos creativos.';
$errorCatalogo = false;

try {
    $obrasCatalogo = obtenerObrasPorCategoria('literatura-escritura', isLoggedIn() ? getUserId() : null);
} catch (PDOException $e) {
    error_log('Error al cargar el catálogo de escritura: ' . $e->getMessage());
    $obrasCatalogo = [];
    $errorCatalogo = true;
}

require_once __DIR__ . '/../../cliente/header.php';
include_once __DIR__ . '/../../cliente/sidebar.php';
?>
<div style="display: flex; width: 100%; min-height: calc(100vh - 4rem);">
    <?php if ($errorCatalogo): ?>
        <p style="margin: 2rem; color: var(--text-main);">No fue posible cargar las obras. Intenta de nuevo más tarde.</p>
    <?php else: ?>
        <?php renderizarCatalogoObras($obrasCatalogo, $tituloCatalogo, $descripcionCatalogo, '/poliart/'); ?>
    <?php endif; ?>
</div>

