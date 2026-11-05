<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['cliente', 'artista']);
$esArtista = getUserRole() === 'artista';
if ($esArtista) {
    require_once __DIR__ . '/../artista/header.php';
    include_once __DIR__ . '/../artista/sidebar.php';
} else {
    require_once __DIR__ . '/../cliente/header.php';
    include_once __DIR__ . '/../cliente/sidebar.php';
}
$project_base = '/poliart/';
?>

<style>
/* ── Contenedor principal ─────────────────────────────────── */
.carrito-wrap {
    flex: 1;
    min-width: 0;
    padding: 1.5rem;
    box-sizing: border-box;
}

.carrito-inner {
    max-width: 960px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* ── Card única ───────────────────────────────────────────── */
.carrito-card {
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1.25rem;
    padding: 1.5rem;
    backdrop-filter: blur(12px);
    box-shadow: 0 8px 24px rgba(0,0,0,.1);
}

/* ── Encabezado ───────────────────────────────────────────── */
.carrito-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.carrito-header h1 {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--text-main);
    margin: 0;
    display: flex;
    align-items: center;
    gap: .5rem;
}

.carrito-badge {
    font-size: .78rem;
    font-weight: 800;
    padding: .3rem .75rem;
    border-radius: 9999px;
    background: rgba(237,119,69,.15);
    border: 1px solid rgba(237,119,69,.3);
    color: var(--brand-orange);
}

/* ── Obras ────────────────────────────────────────────────── */
#carrito-items {
    display: flex;
    flex-direction: column;
    gap: .75rem;
}

.cart-item {
    display: grid;
    grid-template-columns: 96px 1fr auto;
    align-items: center;
    gap: .85rem;
    padding: .85rem;
    border: 1px solid var(--border-subtle);
    border-radius: .85rem;
    background: var(--hover-item);
    transition: border-color .2s;
}
.cart-item:hover { border-color: var(--brand-orange); }

.cart-item-image {
    width: 96px;
    height: 84px;
    border-radius: .55rem;
    object-fit: cover;
}
.cart-item-placeholder {
    width: 96px;
    height: 84px;
    border-radius: .55rem;
    background: var(--border-subtle);
    display: grid;
    place-items: center;
    font-size: 1.5rem;
}
.cart-item-title {
    margin: 0 0 .2rem;
    font-size: .95rem;
    font-weight: 800;
    color: var(--text-main);
}
.cart-item-artist {
    margin: 0 0 .2rem;
    font-size: .8rem;
    color: var(--text-muted);
}
.cart-item-price {
    font-size: .9rem;
    font-weight: 800;
    color: var(--brand-orange);
}
.cart-item-unavailable {
    display: inline-flex;
    margin-top: .3rem;
    padding: .15rem .5rem;
    border: 1px solid rgba(239,68,68,.35);
    border-radius: 9999px;
    background: rgba(239,68,68,.1);
    color: #ef4444;
    font-size: .7rem;
    font-weight: 700;
}
.cart-remove {
    padding: .38rem .65rem;
    border: 1px solid rgba(239,68,68,.3);
    border-radius: .5rem;
    background: rgba(239,68,68,.08);
    color: #ef4444;
    font-size: .75rem;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    font-family: inherit;
    transition: background .15s, border-color .15s;
}
.cart-remove:hover {
    background: rgba(239,68,68,.18);
    border-color: rgba(239,68,68,.6);
}

/* ── Empty state ──────────────────────────────────────────── */
#cart-empty {
    text-align: center;
    padding: 2.5rem 1rem;
    color: var(--text-muted);
}
#cart-empty .empty-icon {
    font-size: 2.5rem;
    margin-bottom: .75rem;
}
#cart-empty h3 {
    margin: 0 0 .4rem;
    color: var(--text-main);
    font-size: 1rem;
}
#cart-empty p { margin: 0; font-size: .85rem; }

/* ── Separador ────────────────────────────────────────────── */
.carrito-divider {
    border: none;
    border-top: 1px solid var(--border-subtle);
    margin: 1.25rem 0;
}

/* ── Total ────────────────────────────────────────────────── */
#carrito-totales .total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: .95rem;
    font-weight: 800;
    color: var(--text-main);
    padding: .5rem 0;
}

/* ── Nota sin pasarela ────────────────────────────────────── */
.carrito-nota {
    display: flex;
    align-items: flex-start;
    gap: .6rem;
    padding: .85rem 1rem;
    border: 1px solid var(--border-subtle);
    border-radius: .85rem;
    background: var(--hover-item);
    font-size: .82rem;
    color: var(--text-muted);
    line-height: 1.5;
    margin-top: 1rem;
}

/* ── Botones de contacto ──────────────────────────────────── */
#carrito-contactos {
    display: flex;
    flex-direction: column;
    gap: .6rem;
    margin-top: 1rem;
}

.btn-contactar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    width: 100%;
    padding: .85rem 1rem;
    border: none;
    border-radius: .85rem;
    background: linear-gradient(135deg, var(--brand-orange), #d45f2e);
    color: #fff;
    font-size: .95rem;
    font-weight: 800;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(237,119,69,.3);
    transition: opacity .2s, transform .15s;
}
.btn-contactar:hover { opacity: .9; transform: translateY(-1px); }

/* ── Mensaje de estado ────────────────────────────────────── */
#carrito-mensaje {
    font-size: .85rem;
    color: var(--text-muted);
    min-height: 1.2rem;
    margin: .5rem 0 0;
}
</style>

<div class="carrito-wrap">
    <div class="carrito-inner">

        <!-- ENCABEZADO — card separada -->
        <div class="carrito-card carrito-header">
            <h1>🛒 Mi carrito</h1>
            <span id="cart-count" class="carrito-badge">0 obras</span>
        </div>

        <!-- CONTENIDO — obras, total y contacto -->
        <div class="carrito-card">

            <!-- LISTA DE OBRAS -->
            <div id="carrito-items"></div>

            <!-- ESTADO VACÍO -->
            <div id="cart-empty" hidden>
                <div class="empty-icon">🖼️</div>
                <h3>Tu carrito está vacío</h3>
                <p>Explora el feed y agrega las obras que quieras comprar o apartar.</p>
            </div>

            <!-- TOTALES + CONTACTO -->
            <div id="carrito-footer" hidden>
                <hr class="carrito-divider">
                <div id="carrito-totales"></div>
                <div class="carrito-nota">
                    <span style="font-size:1rem; flex-shrink:0;">💬</span>
                    <span>Este sitio no procesa pagos. Para comprar o apartar una obra, contacta directamente al artista por el chat.</span>
                </div>
                <div id="carrito-contactos"></div>
            </div>

            <p id="carrito-mensaje" role="status" aria-live="polite"></p>

        </div>

    </div>
</div>

<script>
async function solicitarCarrito(accion, datos = {}) {
    const url = '/poliart/app/includes/actions/carrito.php?accion=' + encodeURIComponent(accion);
    const res = await fetch(url, {
        method: accion === 'ver' ? 'GET' : 'POST',
        headers: accion === 'ver' ? {} : { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:   accion === 'ver' ? undefined : new URLSearchParams({ accion, ...datos })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Error al comunicarse con el carrito.');
    return data;
}

function renderizarCarrito(carrito) {
 const estructura = document.getElementById('carrito-items');
 if (!estructura) { throw new Error('No se encontró la lista del carrito.'); }
 if (!document.getElementById('carrito-totales')) { const nodo = document.createElement('div'); nodo.id = 'carrito-totales'; estructura.parentElement.insertBefore(nodo, estructura); }
 if (!document.getElementById('carrito-contactos')) { const nodo = document.createElement('div'); nodo.id = 'carrito-contactos'; estructura.parentElement.appendChild(nodo); }
    const itemsEl    = document.getElementById('carrito-items');
    const totalesEl  = document.getElementById('carrito-totales');
    const contactosEl= document.getElementById('carrito-contactos');
    const emptyEl    = document.getElementById('cart-empty');
    const footerEl   = document.getElementById('carrito-footer');
    const countEl    = document.getElementById('cart-count');
    const mensajeEl  = document.getElementById('carrito-mensaje');

    itemsEl.replaceChildren();
    totalesEl.replaceChildren();
    contactosEl.replaceChildren();
    mensajeEl.textContent = '';
    if (Array.isArray(carrito.reservas_removidas) && carrito.reservas_removidas.length) {
        mensajeEl.textContent = 'Otro cliente ya ha reservado esta obra: ' + carrito.reservas_removidas.join(', ') + '.';
        mensajeEl.className = 'carrito-reserva-aviso';
    }

    const n = carrito.items.length;
    countEl.textContent = n + (n === 1 ? ' obra' : ' obras');
    emptyEl.hidden  = n > 0;
    footerEl.hidden = n === 0;

    const artistas = {};

    carrito.items.forEach(item => {
        const fila = document.createElement('article');
        fila.className = 'cart-item';

        // Imagen
        const rutaImg = typeof item.ruta_imagen === 'string'
            && item.ruta_imagen.startsWith('/poliart/uploads/obras/')
            ? item.ruta_imagen : '';
        if (rutaImg) {
            const img = document.createElement('img');
            img.className = 'cart-item-image';
            img.src = rutaImg; img.alt = item.titulo; img.loading = 'lazy';
            fila.appendChild(img);
        } else {
            const ph = document.createElement('span');
            ph.className = 'cart-item-placeholder';
            ph.setAttribute('aria-hidden','true');
            ph.textContent = '🎨';
            fila.appendChild(ph);
        }

        // Info
        const info = document.createElement('div');
        const autor = item.nombre_artistico?.trim() || item.nombre_usuario || 'Artista';

        const h3 = document.createElement('h3');
        h3.className = 'cart-item-title'; h3.textContent = item.titulo;

        const p = document.createElement('p');
        p.className = 'cart-item-artist'; p.textContent = 'Por ' + autor;

        const precio = document.createElement('span');
        precio.className = 'cart-item-price';
        precio.textContent = Number(item.precio_unitario).toLocaleString('es-CO',{
            minimumFractionDigits:2, maximumFractionDigits:2
        }) + ' ' + item.moneda;

        info.append(h3, p, precio);

        if (item.estado_venta === 'reservada' && item.reserva_propia) {
            const badge = document.createElement('span');
            badge.className = 'cart-item-unavailable cart-item-reserved-own';
            badge.textContent = 'Reservada para ti';
            info.appendChild(badge);
        } else if (item.estado_publicacion !== 'publicada' || item.estado_venta !== 'disponible') {
            const badge = document.createElement('span');
            badge.className = 'cart-item-unavailable';
            badge.textContent = 'Ya no disponible';
            info.appendChild(badge);
        }

        // Botón quitar
        const btn = document.createElement('button');
        btn.className = 'cart-remove btn btn-danger btn-sm'; btn.type = 'button'; btn.textContent = 'Quitar';
        btn.setAttribute('aria-label', 'Quitar ' + item.titulo);
        btn.onclick = () => quitarDelCarrito(item.id_carrito_detalle);

        fila.append(info, btn);
        itemsEl.appendChild(fila);

        // Agrupar por artista
        const key = item.id_usuario_artista || item.nombre_usuario;
        if (!artistas[key]) {
            artistas[key] = { nombre: autor, obras: [], id_artista: item.id_artista ?? null };
        }
        artistas[key].obras.push(item.titulo);
    });

    // Totales
    Object.entries(carrito.totales).forEach(([moneda, val]) => {
        const row = document.createElement('div');
        row.className = 'total-row';
        const lbl = document.createElement('span'); lbl.textContent = 'Total ' + moneda;
        const num = document.createElement('strong');
        num.textContent = Number(val).toLocaleString('es-CO',{
            minimumFractionDigits:2, maximumFractionDigits:2
        }) + ' ' + moneda;
        row.append(lbl, num);
        totalesEl.appendChild(row);
    });

    // Botones contactar por artista
    Object.values(artistas).forEach(art => {
        const lista = art.obras.length === 1
            ? `"${art.obras[0]}"`
            : art.obras.map(t=>`"${t}"`).join(', ');
        const msg = encodeURIComponent(
            `Hola ${art.nombre}! Me interesan tus obras ${lista}. ¿Están disponibles? Me gustaría saber cómo proceder para comprar o apartar.`
        );
        const url = art.id_artista
            ? `/poliart/app/cliente/mensajes.php?artista_id=${art.id_artista}&msg=${msg}`
            : `/poliart/app/cliente/mensajes.php?msg=${msg}`;

        const a = document.createElement('a');
        a.href = url;
        a.className = 'btn btn-primary btn-lg btn-full';
        a.innerHTML = `💬 Contactar a ${art.nombre}`;
        contactosEl.appendChild(a);
    });
}

async function cargarCarrito() {
    document.getElementById('carrito-mensaje').textContent = 'Cargando…';
    try {
        renderizarCarrito(await solicitarCarrito('ver'));
    } catch (e) {
        document.getElementById('carrito-mensaje').textContent = e.message || 'No se pudo cargar el carrito.';
    }
}

async function quitarDelCarrito(id) {
    try {
        await solicitarCarrito('quitar', { id_carrito_detalle: id });
        await cargarCarrito();
    } catch (e) {
        document.getElementById('carrito-mensaje').textContent = e.message || 'No se pudo quitar la obra.';
    }
}

document.addEventListener('DOMContentLoaded', cargarCarrito);
</script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    setTimeout(async function () {
      const contactos = document.getElementById('carrito-contactos');
      const items = document.getElementById('carrito-items');
      if (!contactos || !items || !items.children.length) return;
      try {
        const data = await solicitarCarrito('ver');
        contactos.replaceChildren();
        contactos.hidden = true;
        data.items.forEach(function (item) {
          const autor = (item.nombre_artistico || item.nombre_usuario || 'Artista').trim();
          const msg = encodeURIComponent(`Hola ${autor}! Me interesa tu obra "${item.titulo}". ¿Está disponible? Me gustaría saber cómo proceder para comprarla o apartarla.`);
          const enlace = document.createElement('a');
          enlace.href = item.id_artista ? `/poliart/app/cliente/mensajes.php?artista_id=${item.id_artista}&msg=${msg}` : `/poliart/app/cliente/mensajes.php?msg=${msg}`;
          enlace.className = 'btn-contactar cart-item-contact';
          enlace.textContent = `💬 Contactar artista por: ${item.titulo}`;
          const fila = items.children[data.items.indexOf(item)];
          if (fila && fila.children[1]) {
            const categoria = document.createElement('span');
            categoria.className = 'cart-item-category';
            categoria.textContent = `🎨 Arte: ${item.categoria || 'Sin categoría'}`;
            fila.children[1].appendChild(categoria);
            fila.children[1].appendChild(enlace);
          }
        });
      } catch (_) {}
    }, 250);
  });
</script>
<style>
  .cart-item-contact {
    display: inline-flex !important;
    width: auto !important;
    max-width: 100%;
    margin-top: .55rem;
    padding: .45rem .75rem !important;
    border-radius: .55rem !important;
    font-size: .76rem !important;
    line-height: 1.2;
    box-shadow: none !important;
  }
  .cart-item-contact:hover { transform: none !important; }
  .carrito-reserva-aviso { margin: .75rem 0; color: #facc15; font-weight: 700; }
  .cart-item-reserved-own { color: #facc15; border-color: rgba(234,179,8,.55); background: rgba(234,179,8,.14); }
  #carrito-totales, .carrito-divider { display: none !important; }
  .cart-item-category { display: block; margin-top: .25rem; color: var(--text-muted); font-size: .78rem; font-weight: 700; }
  .cart-item > div { min-width: 0; }
  @media (max-width: 620px) {
    .cart-item { grid-template-columns: 72px 1fr auto; gap: .65rem; }
    .cart-item-image, .cart-item-placeholder { width: 72px; height: 68px; }
    .cart-item-contact { font-size: .7rem !important; }
  }
</style>
<script>
  document.addEventListener('error', function (event) {
    const imagen = event.target;
    if (!imagen.matches || !imagen.matches('.cart-item-image')) return;
    const reemplazo = document.createElement('span');
    reemplazo.className = 'cart-item-placeholder';
    reemplazo.setAttribute('aria-hidden', 'true');
    reemplazo.textContent = '🎨';
    imagen.replaceWith(reemplazo);
  }, true);
</script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const items = document.getElementById('carrito-items');
    const nota = document.querySelector('.carrito-nota');
    const totales = document.getElementById('carrito-totales');
    const divisor = document.querySelector('.carrito-divider');
    if (!items || !nota) return;
    items.parentElement.insertBefore(nota, items);
    if (totales) totales.remove();
    if (divisor) divisor.remove();
    nota.style.marginTop = '0';
    nota.style.marginBottom = '1rem';
  });
</script>

