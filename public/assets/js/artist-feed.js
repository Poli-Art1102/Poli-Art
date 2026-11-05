'use strict';

document.addEventListener('DOMContentLoaded', async () => {
    document.querySelectorAll('.feed-post-card').forEach(card => {
        const esObraDeOtroArtista = card.querySelector('.btn-seguir-artista-feed');
        const botonReporte = card.querySelector('[onclick^="abrirModalReporteCliente"]');

        if (!esObraDeOtroArtista && botonReporte) {
            botonReporte.remove();
        }

        const estaDisponible = card.dataset.estadoVenta === 'disponible';
        if (esObraDeOtroArtista && estaDisponible && !card.querySelector('.btn-carrito')) {
            const acciones = card.querySelector('[data-feed-actions] > div');
            const referencia = card.querySelector('[onclick^="abrirModalReporteCliente"]');
            const coincidencia = referencia?.getAttribute('onclick')?.match(/\d+/);
            if (acciones && coincidencia) {
                const botonCarrito = document.createElement('button');
                botonCarrito.type = 'button';
                botonCarrito.className = 'artist-cart-button';
                botonCarrito.dataset.artistCart = 'true';
                botonCarrito.dataset.obra = coincidencia[0];
                botonCarrito.textContent = '🛒 Añadir al carrito';
                acciones.insertBefore(botonCarrito, acciones.firstChild);
            }
        }
    });

    const botones = document.querySelectorAll('.btn-carrito, .artist-cart-button');
    if (!botones.length) return;
    try {
        const respuesta = await fetch('/poliart/app/includes/actions/carrito.php?accion=ver');
        if (!respuesta.ok) return;
        const datos = await respuesta.json();
        const idsEnCarrito = new Set((Array.isArray(datos.items) ? datos.items : [])
            .map(item => String(item.id_obra)));
        botones.forEach(boton => {
            if (idsEnCarrito.has(String(boton.dataset.obra))) {
                boton.dataset.inCart = 'true';
                boton.textContent = '✅ En el carrito';
                boton.classList.add('cart-added');
            }
        });
    } catch (_) {
        // Se conserva el texto inicial si no se puede consultar el carrito.
    }
});

document.addEventListener('click', async event => {
    const boton = event.target.closest('.artist-cart-button[data-artist-cart="true"]');
    if (!boton || boton.dataset.inCart === 'true' || boton.disabled) return;

    const idObra = boton.dataset.obra;
    if (!idObra) return;

    boton.disabled = true;
    const textoOriginal = boton.textContent;
    boton.textContent = '⏳ Agregando...';

    try {
        const datosFormulario = new FormData();
        datosFormulario.append('accion', 'agregar');
        datosFormulario.append('id_obra', idObra);

        const respuesta = await fetch('/poliart/app/includes/actions/carrito.php', {
            method: 'POST',
            body: datosFormulario
        });
        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.ok) {
            throw new Error(resultado.error || 'No se pudo agregar la obra al carrito.');
        }

        boton.dataset.inCart = 'true';
        boton.textContent = '✅ En el carrito';
        boton.classList.add('cart-added');
        if (typeof window.showToast === 'function') {
            window.showToast('Obra agregada al carrito 🛒', true);
        }
    } catch (error) {
        boton.textContent = textoOriginal;
        if (typeof window.showToast === 'function') {
            window.showToast(error.message, false);
        } else {
            window.alert(error.message);
        }
    } finally {
        boton.disabled = false;
    }
});
