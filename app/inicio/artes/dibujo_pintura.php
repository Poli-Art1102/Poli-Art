<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/catalogo_obras.php';

$tituloCatalogo = '🎨 Dibujo y Pintura';
$descripcionCatalogo = 'Cuadros, ilustraciones digitales y pinturas en lienzo.';
$errorCatalogo = false;

try {
    $obrasCatalogo = obtenerObrasPorCategoria('ilustracion-pintura', isLoggedIn() ? getUserId() : null);
} catch (PDOException $e) {
    error_log('Error al cargar el catálogo de dibujo y pintura: ' . $e->getMessage());
    $obrasCatalogo = [];
    $errorCatalogo = true;
}

require_once __DIR__ . '/../header.php';
?>
<?php if ($errorCatalogo): ?>
    <p style="margin: 2rem; color: var(--text-main);">No fue posible cargar las obras. Intenta de nuevo más tarde.</p>
<?php else: ?>
    <?php renderizarCatalogoObras($obrasCatalogo, $tituloCatalogo, $descripcionCatalogo, '/poliart/'); ?>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
