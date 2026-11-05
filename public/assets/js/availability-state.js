'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const estados = {
        disponible: ['✅ Disponible', 'availability-available'],
        reservada: ['⏳ Reservada', 'availability-reserved'],
        vendida: ['🏷️ Vendida', 'availability-sold'],
        no_disponible: ['🚫 No disponible', 'availability-unavailable']
    };

    document.querySelectorAll('[data-estado-venta]').forEach(card => {
        const estado = estados[card.dataset.estadoVenta];
        if (!estado || card.querySelector('.availability-badge')) return;

        const badge = document.createElement('span');
        badge.className = `availability-badge ${estado[1]}`;
        badge.textContent = estado[0];

        const tituloObra = card.querySelector('.feed-post-description h2, .feed-post-description h3');
        const encabezado = card.querySelector('.feed-post-header, .feed-card-header, .feed-post-description');
        if (tituloObra) {
            tituloObra.appendChild(badge);
        } else if (encabezado) {
            encabezado.appendChild(badge);
        }

        if (card.dataset.estadoVenta !== 'disponible') {
            card.querySelectorAll('.btn-carrito, .artist-cart-button, .artist-work-action.cart').forEach(button => button.remove());
        }
    });
});
