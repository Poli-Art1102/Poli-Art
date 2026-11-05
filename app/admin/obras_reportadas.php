<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['administrador']);
require_once __DIR__ . '/../admin/header.php';

$project_base = '/poliart/';

// Obras reportadas simuladas con descripción completa e imagen ampliada
$obras_reportadas = [
    [
        'id' => 101,
        'titulo_obra' => 'Atardecer Urbano C-13',
        'artista' => 'Carlos Mendoza',
        'usuario' => 'carlos_m',
        'motivo' => 'Contenido inapropiado / Sensible',
        'descripcion' => 'Pintura digital que representa paisajes urbanos de la Comuna 13. El reporte indica posible vulneración a derechos de imagen de terceros.',
        'zona' => 'Comuna 13 - San Javier',
        'fecha_reporte' => '2026-10-17 22:30:00',
        'estado' => 'Pendiente',
        'imagen' => 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'id' => 102,
        'titulo_obra' => 'Melodía Rota (Audio Remix)',
        'artista' => 'Sofía Rodríguez',
        'usuario' => 'sofia_r',
        'motivo' => 'Infracción de derechos de autor (Copyright)',
        'descripcion' => 'Pista musical con muestras de audio sin licencia registrada por un sello externo.',
        'zona' => 'Comuna 14 - El Poblado',
        'fecha_reporte' => '2026-10-16 15:40:00',
        'estado' => 'Pendiente',
        'imagen' => 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'id' => 103,
        'titulo_obra' => 'Poesía Comercial Spam',
        'artista' => 'Usuario Anónimo',
        'usuario' => 'spam_bot99',
        'motivo' => 'Spam y publicidad engañosa',
        'descripcion' => 'Publicación automatizada que contiene enlaces publicitarios externos no autorizados.',
        'zona' => 'Comuna 10 - La Candelaria',
        'fecha_reporte' => '2026-10-15 09:10:00',
        'estado' => 'Pendiente',
        'imagen' => 'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=800&q=80'
    ]
];

$errorReportes = false;
try {
    $stmtReportes = getDB()->query(
        "SELECT r.id_reporte AS id, o.titulo AS titulo_obra,
                COALESCE(NULLIF(a.nombre_artistico, ''), CONCAT(u.nombre, ' ', u.apellido)) AS artista,
                u.nombre_usuario AS usuario, r.motivo, r.detalle AS descripcion,
                z.nombre AS zona, r.creado_en AS fecha_reporte,
                r.estado, o.ruta_imagen AS imagen
         FROM reporte_contenido r
         INNER JOIN obra o ON o.id_obra = r.id_obra
         INNER JOIN artista a ON a.id_artista = o.id_artista
         INNER JOIN usuario u ON u.id_usuario = a.id_usuario
         LEFT JOIN zona z ON z.id_zona = COALESCE(o.id_zona, u.id_zona)
         WHERE r.estado = 'pendiente'
         ORDER BY r.creado_en ASC"
    );
    $obras_reportadas = array_map(static function (array $reporte): array {
        $reporte['imagen'] = is_string($reporte['imagen'])
            && strpos($reporte['imagen'], '/poliart/uploads/obras/') === 0
            ? $reporte['imagen']
            : '';
        $reporte['zona'] = $reporte['zona'] ?? 'Sin zona';
        $reporte['descripcion'] = $reporte['descripcion'] ?? 'Sin detalles adicionales.';
        return $reporte;
    }, $stmtReportes->fetchAll());
} catch (PDOException $e) {
    error_log('Error al cargar reportes para moderación: ' . $e->getMessage());
    $obras_reportadas = [];
    $errorReportes = true;
}
?>

<style>
.reports-wrapper {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.5rem;
    box-sizing: border-box;
}

.reports-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.reports-card {
    background-color: var(--bg-surface) !important;
    border: 1px solid var(--border-subtle) !important;
    border-radius: 1.25rem;
    padding: 1.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(12px);
}

.search-input-box {
    display: flex;
    align-items: center;
    background-color: var(--hover-item);
    border: 1px solid var(--border-subtle);
    border-radius: 9999px;
    padding: 0.5rem 1.25rem;
    width: 320px;
}

.search-input-box input {
    background: transparent;
    border: none;
    outline: none;
    color: var(--text-main);
    width: 100%;
    font-size: 0.85rem;
}

.report-item-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 0.75rem;
    border-bottom: 1px solid var(--border-subtle);
    gap: 1rem;
    flex-wrap: wrap;
    border-radius: 0.75rem;
    transition: background-color 0.2s ease;
}

.report-item-row:hover {
    background-color: var(--hover-item);
}

.report-item-row:last-child {
    border-bottom: none;
}

.badge-motivo {
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 700;
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-action {
    padding: 0.45rem 0.85rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
    border: 1px solid var(--border-subtle);
    transition: all 0.2s ease;
}

.btn-resolver {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
    border-color: rgba(34, 197, 94, 0.3);
}
.btn-resolver:hover {
    background: #22c55e;
    color: #ffffff;
}

.btn-descartar {
    background: var(--hover-item);
    color: var(--text-muted);
}
.btn-descartar:hover {
    border-color: var(--text-main);
    color: var(--text-main);
}

/* MODAL DE PREVISUALIZACIÓN DE OBRA */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(8px);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 99999;
    padding: 1.5rem;
    box-sizing: border-box;
}

.modal-content-box {
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1.25rem;
    max-width: 650px;
    width: 100%;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0,0,0,0.6);
    position: relative;
    animation: modalFadeIn 0.25s ease-out;
}

@keyframes modalFadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.modal-close-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(0,0,0,0.7);
    border: 1px solid var(--border-subtle);
    color: #ffffff;
    width: 2.2rem;
    height: 2.2rem;
    border-radius: 9999px;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 10;
}

.modal-close-btn:hover {
    background: var(--brand-orange);
}
</style>

<div class="reports-wrapper">
    
    <!-- ENCABEZADO Y BUSCADOR -->
    <div class="reports-header">
        <div>
            <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; font-weight: 700;">&larr; Volver al Panel General</a>
            <h1 style="font-size: 1.6rem; font-weight: 900; color: var(--text-main); margin: 0.2rem 0 0 0;">
                ⚠️ Revisión de Obras Reportadas
            </h1>
        </div>

        <div class="search-input-box">
            <span style="margin-right: 0.5rem; color: var(--text-muted);">🔍</span>
            <input type="text" id="reportSearch" placeholder="Buscar por obra, artista o motivo..." onkeyup="filterReports()">
        </div>
    </div>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="reports-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1rem; font-weight: 800; color: var(--text-main); margin: 0;">
                Incidencias Pendientes de Moderación (<?= count($obras_reportadas) ?>)
            </h3>
            <span style="font-size: 0.75rem; color: var(--text-muted);">Haz clic en cualquier obra para verla en tamaño completo</span>
        </div>

        <div id="reportsList">
            <?php if ($errorReportes): ?>
                <p role="alert">No fue posible cargar los reportes.</p>
            <?php elseif (!$obras_reportadas): ?>
                <p>No hay reportes pendientes de moderación.</p>
            <?php endif; ?>
            <?php foreach ($obras_reportadas as$rep): ?>
                <div class="report-item-row" id="report-row-<?= $rep['id'] ?>" data-text="<?= strtolower(htmlspecialchars($rep['titulo_obra'] . ' ' .$rep['artista'] . ' ' . $rep['motivo'] . ' ' .$rep['zona'])) ?>">
                    
                    <!-- ÁREA CLICABLE -->
                    <div style="display: flex; align-items: center; gap: 1rem; max-width: 600px; cursor: pointer;" 
                         class="obra-preview-click"
                         data-titulo="<?= htmlspecialchars($rep['titulo_obra']) ?>"
                         data-artista="<?= htmlspecialchars($rep['artista']) ?>"
                         data-usuario="<?= htmlspecialchars($rep['usuario']) ?>"
                         data-motivo="<?= htmlspecialchars($rep['motivo']) ?>"
                         data-descripcion="<?= htmlspecialchars($rep['descripcion']) ?>"
                         data-zona="<?= htmlspecialchars($rep['zona']) ?>"
                         data-fecha="<?= htmlspecialchars($rep['fecha_reporte']) ?>"
                         data-imagen="<?= htmlspecialchars($rep['imagen']) ?>"
                         onclick="abrirModalObra(this)">
                        
                        <img src="<?= $rep['imagen'] ?>" style="width: 3.8rem; height: 3.8rem; border-radius: 0.75rem; object-fit: cover; border: 1px solid var(--border-subtle);">
                        
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <span class="badge-motivo"><?= $rep['motivo'] ?></span>
                                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;"><?= $rep['zona'] ?></span>
                            </div>
                            <strong style="display: block; font-size: 0.95rem; color: var(--text-main);"><?= htmlspecialchars($rep['titulo_obra']) ?> 👁️</strong>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">Autor: <strong style="color: var(--brand-orange);"><?= htmlspecialchars($rep['artista']) ?></strong> (@<?= htmlspecialchars($rep['usuario']) ?>) &bull; <?=$rep['fecha_reporte'] ?></span>
                        </div>
                    </div>

                    <!-- BOTONES DE ACCIÓN -->
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <button class="btn-action btn-resolver" onclick="resolverReporte(<?= $rep['id'] ?>)">
                            Resolver / Retirar Obra
                        </button>
                        <button class="btn-action btn-descartar" onclick="descartarReporte(<?= $rep['id'] ?>)">
                            Descartar Reporte
                        </button>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    </div>

</div>

<!-- MODAL EMERGENTE PARA INSPECCIONAR OBRA -->
<div class="modal-overlay" id="obraModal" onclick="cerrarModal(event)">
    <div class="modal-content-box">
        <button class="modal-close-btn" onclick="cerrarModalDirecto()">&times;</button>
        <img id="modalImagen" src="" style="width: 100%; max-height: 360px; object-fit: cover; border-bottom: 1px solid var(--border-subtle); display: block;">
        <div style="padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                <span class="badge-motivo" id="modalMotivo"></span>
                <span style="font-size: 0.8rem; color: var(--text-muted);" id="modalZona"></span>
            </div>
            <h2 style="font-size: 1.3rem; font-weight: 900; color: var(--text-main); margin: 0 0 0.5rem 0;" id="modalTitulo"></h2>
            <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1.25rem;" id="modalDescripcion"></p>
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-subtle); padding-top: 1rem;">
                <span style="font-size: 0.8rem; color: var(--text-muted);" id="modalAutor"></span>
                <span style="font-size: 0.75rem; color: var(--brand-orange); font-weight: 700;" id="modalFecha"></span>
            </div>
        </div>
    </div>
</div>

<script>
function filterReports() {
    const query = document.getElementById('reportSearch').value.toLowerCase();
    const rows = document.querySelectorAll('.report-item-row');

    rows.forEach(row => {
        const text = row.getAttribute('data-text');
        if (text.includes(query)) {
            row.style.display = 'flex';
        } else {
            row.style.display = 'none';
        }
    });
}

function abrirModalObra(element) {
    const titulo = element.getAttribute('data-titulo');
    const artista = element.getAttribute('data-artista');
    const usuario = element.getAttribute('data-usuario');
    const motivo = element.getAttribute('data-motivo');
    const descripcion = element.getAttribute('data-descripcion');
    const zona = element.getAttribute('data-zona');
    const fecha = element.getAttribute('data-fecha');
    const imagen = element.getAttribute('data-imagen');

    document.getElementById('modalImagen').src = imagen;
    document.getElementById('modalTitulo').textContent = titulo;
    document.getElementById('modalMotivo').textContent = motivo;
    document.getElementById('modalZona').textContent = zona;
    document.getElementById('modalDescripcion').textContent = descripcion;
    document.getElementById('modalAutor').textContent = `Autor: ${artista} (@${usuario})`;
    document.getElementById('modalFecha').textContent = `Reportado el ${fecha}`;

    document.getElementById('obraModal').style.display = 'flex';
}

function cerrarModal(e) {
    if (e.target.id === 'obraModal') {
        document.getElementById('obraModal').style.display = 'none';
    }
}

function cerrarModalDirecto() {
    document.getElementById('obraModal').style.display = 'none';
}

async function actualizarReporte(id, accion) {
    const mensaje = accion === 'retirar'
        ? '¿Deseas retirar la obra y resolver el reporte?'
        : '¿Deseas descartar el reporte y conservar la obra?';
    if (!confirm(mensaje)) return;

    try {
        const response = await fetch('../includes/actions/moderar_reporte.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id_reporte: id, accion })
        });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.error || 'No fue posible guardar la revisión.');
        document.getElementById(`report-row-${id}`)?.remove();
    } catch (error) {
        alert(error.message || 'Error al actualizar el reporte.');
    }
}

function resolverReporte(id) {
    actualizarReporte(id, 'retirar');
}

function descartarReporte(id) {
    actualizarReporte(id, 'ignorar');
}
</script>

