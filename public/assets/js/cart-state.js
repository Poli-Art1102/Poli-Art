'use strict';

/* Mantiene visibles los botones de compra y refleja el estado real del carrito. */
document.addEventListener('DOMContentLoaded', async () => {
    const botones = document.querySelectorAll('.btn-carrito, .artist-work-action.cart');
    if (!botones.length) return;

    try {
        const respuesta = await fetch('/poliart/app/includes/actions/carrito.php?accion=ver');
        if (!respuesta.ok) return;
        const datos = await respuesta.json();
        const idsEnCarrito = new Set((Array.isArray(datos.items) ? datos.items : [])
            .map(item => String(item.id_obra)));

        botones.forEach(boton => {
            const estaEnCarrito = idsEnCarrito.has(String(boton.dataset.obra));
            boton.dataset.inCart = estaEnCarrito ? 'true' : 'false';
            if (estaEnCarrito) {
                boton.textContent = '✅ En el carrito';
                boton.classList.add('cart-added');
            }
        });
    } catch (_) {
        // El botón conserva su texto original si el carrito no responde.
    }
});
