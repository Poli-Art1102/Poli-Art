'use strict';
/**
 * includes/js/cliente.js
 * Interacciones del feed del cliente: me gusta, carrito, seguir artistas, reportes.
 */

// ── Toast global (también usado por carrito.php) ──────────────
function showToast(msg, ok = true) {
    const toast = document.createElement('div');
    toast.textContent = msg;
    toast.style.cssText = `
        position:fixed; bottom:1.5rem; right:1.5rem; z-index:9999;
        background:${ok ? 'var(--bg-surface,#1e293b)' : '#991b1b'};
        color:${ok ? 'var(--text-main,#fff)' : '#fff'};
        border:1px solid ${ok ? 'var(--brand-orange,#f97316)' : '#ef4444'};
        padding:.75rem 1.25rem; border-radius:.75rem;
        font-size:.875rem; font-weight:700;
        box-shadow:0 4px 16px rgba(0,0,0,.35);
        transition:opacity .3s ease; pointer-events:none;
    `;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3000);
}

// ── Sincronizar badge del carrito con el servidor ─────────────
async function sincronizarBadgeCarrito() {
    try {
        const res  = await fetch('/poliart/app/includes/actions/carrito.php?accion=ver');
        if (!res.ok) return;
        const data = await res.json();
        const count = Array.isArray(data.items) ? data.items.length : 0;
        const badge = document.getElementById('carrito-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-flex' : 'none';
        }
    } catch (_) { /* silencioso */ }
}

document.addEventListener('DOMContentLoaded', () => {

    // Sincronizar badge al cargar cualquier página del cliente
    sincronizarBadgeCarrito();

    // ── Búsqueda en tiempo real ───────────────────────────────
    const searchBar = document.getElementById('search-bar');
    const artCards  = document.querySelectorAll('#art-container > div');
    if (searchBar) {
        searchBar.addEventListener('input', (e) => {
            const q = e.target.value.toLowerCase();
            artCards.forEach(card => {
                const title = card.querySelector('h3')?.textContent.toLowerCase() ?? '';
                card.style.display = title.includes(q) ? '' : 'none';
            });
        });
    }

    // ── Me gusta ──────────────────────────────────────────────
    document.querySelectorAll('.btn-like').forEach(btn => {
        btn.addEventListener('click', async () => {
            const idObra = btn.dataset.obra;
            if (!idObra) return;
            try {
                const fd = new FormData();
                fd.append('id_obra', idObra);
                const res  = await fetch('/poliart/app/includes/actions/megusta.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (res.status === 401) { window.location.href = '/poliart/app/auth/login_cliente.php'; return; }
                if (!res.ok || !data.ok) { showToast(data.error || 'No se pudo actualizar el me gusta.', false); return; }
                const heart   = btn.querySelector('.heart-icon');
                const counter = btn.querySelector('.like-text');
                if (heart)   heart.textContent   = data.liked ? '❤️' : '🤍';
                if (counter) counter.textContent = data.total + ' Me gusta';
                btn.setAttribute('aria-pressed', data.liked ? 'true' : 'false');
            } catch (err) {
                showToast(err.message || 'Error de red al actualizar el me gusta.', false);
            }
        });
    });

    // ── Añadir al carrito ─────────────────────────────────────
    document.querySelectorAll('.btn-carrito').forEach(btn => {
        btn.addEventListener('click', async () => {
            const idObra = btn.dataset.obra;
            if (!idObra) return;
            const original = btn.innerHTML;
            btn.disabled    = true;
            btn.textContent = '⏳ Agregando...';
            try {
                const fd = new FormData();
                fd.append('accion',  'agregar');
                fd.append('id_obra', idObra);
                const res  = await fetch('/poliart/app/includes/actions/carrito.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (res.status === 401) { window.location.href = '/poliart/app/auth/login_cliente.php'; return; }
                if (data.ok) {
                    btn.textContent       = '✅ En el carrito';
                    btn.style.borderColor = '#22c55e';
                    btn.style.color       = '#22c55e';
                    // Actualizar badge del header con conteo real desde servidor
                    sincronizarBadgeCarrito();
                    showToast('Obra agregada al carrito 🛒', true);
                } else {
                    btn.innerHTML = original;
                    showToast(data.error || 'No se pudo agregar al carrito.', false);
                }
            } catch (_) {
                btn.innerHTML = original;
                showToast('Error de red al agregar la obra al carrito.', false);
            } finally {
                btn.disabled = false;
            }
        });
    });

    // ── Seguir artistas ───────────────────────────────────────
    document.querySelectorAll('.btn-seguir-artista-feed').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!btn.dataset.artistaId || btn.disabled) return;
            btn.disabled = true;
            try {
                const res = await fetch('/poliart/app/includes/actions/seguir.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ id_artista: btn.dataset.artistaId })
                });
                const data = await res.json();
                if (res.status === 401) { window.location.href = '/poliart/app/auth/login_cliente.php'; return; }
                if (!res.ok || !data.ok) { showToast(data.error || 'No se pudo actualizar el seguimiento.', false); return; }
 document.querySelectorAll('.btn-seguir-artista-feed[data-artista-id="' + btn.dataset.artistaId + '"]').forEach((artistButton) => { artistButton.dataset.following = data.following ? 'true' : 'false'; artistButton.setAttribute('aria-pressed', data.following ? 'true' : 'false'); artistButton.textContent = data.following ? '✓ Siguiendo' : '➕ Seguir artista'; });
                btn.setAttribute('aria-pressed', data.following ? 'true' : 'false');
                btn.textContent = data.following ? '✓ Siguiendo' : '➕ Seguir artista';
            } catch (err) {
                showToast(err.message || 'Error de red al seguir al artista.', false);
            } finally {
                btn.disabled = false;
            }
        });
    });

    // ── Reportar obra (funciones globales para el HTML inline) ─
    window.abrirModalReporte = (obraId) => {
        const input = document.getElementById('reporteObraId');
        const modal = document.getElementById('modalReporte');
        if (input) input.value = obraId;
        if (modal)  modal.style.display = 'flex';
    };

    window.cerrarModalReporte = () => {
        const modal = document.getElementById('modalReporte');
        if (modal) { modal.style.display = 'none'; document.getElementById('formReporte')?.reset(); }
    };

    window.enviarReporte = async (e) => {
        e.preventDefault();
        const obraId  = document.getElementById('reporteObraId')?.value;
        const motivo  = document.querySelector('#formReporte input[name="motivo"]:checked')?.value;
        const detalle = document.getElementById('detallesReporte')?.value ?? '';
        if (!motivo) { alert('Selecciona un motivo.'); return; }
        try {
            const fd = new FormData();
            fd.append('id_obra', obraId);
            fd.append('motivo',  motivo);
            fd.append('detalle', detalle);
            const res  = await fetch('/poliart/app/includes/actions/reportar.php', { method: 'POST', body: fd });
            const data = await res.json();
            showToast(data.ok ? '✅ Reporte enviado. Gracias.' : '❌ ' + (data.error || 'Error.'), data.ok);
        } catch (_) {
            showToast('❌ Error de red al enviar el reporte.', false);
        }
        cerrarModalReporte();
    };
});
