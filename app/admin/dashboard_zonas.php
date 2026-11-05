<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['administrador']);
require_once __DIR__ . '/../admin/header.php';

$project_base = '/poliart/';

// Las 5 Especialidades Oficiales
$especialidades_oficiales = [
    'dibujo_pintura' => 'Dibujo y Pintura',
    'musica'         => 'Música',
    'escenicas'      => 'Artes Escénicas',
    'escritura'      => 'Escritura',
    'escultura'      => 'Escultura'
];

// Cobertura completa por las 16 Comunas y 5 Corregimientos de Medellín
$zonas_medellin = [];
/* $zonas_medellin = [
    ['key' => 'c1',   'nombre' => 'Comuna 1 - Popular', 'artistas' => 12, 'clientes' => 15, 'obras' => 20, 'fecha_actividad' => '2026-10-10'],
    ['key' => 'c2',   'nombre' => 'Comuna 2 - Santa Cruz', 'artistas' => 8, 'clientes' => 10, 'obras' => 14, 'fecha_actividad' => '2026-10-11'],
    ['key' => 'c3',   'nombre' => 'Comuna 3 - Manrique', 'artistas' => 15, 'clientes' => 18, 'obras' => 22, 'fecha_actividad' => '2026-10-12'],
    ['key' => 'c4',   'nombre' => 'Comuna 4 - Aranjuez', 'artistas' => 14, 'clientes' => 16, 'obras' => 19, 'fecha_actividad' => '2026-10-13'],
    ['key' => 'c5',   'nombre' => 'Comuna 5 - Castilla', 'artistas' => 18, 'clientes' => 20, 'obras' => 25, 'fecha_actividad' => '2026-10-14'],
    ['key' => 'c6',   'nombre' => 'Comuna 6 - Doce de Octubre', 'artistas' => 9, 'clientes' => 11, 'obras' => 13, 'fecha_actividad' => '2026-10-09'],
    ['key' => 'c7',   'nombre' => 'Comuna 7 - Robledo', 'artistas' => 22, 'clientes' => 25, 'obras' => 30, 'fecha_actividad' => '2026-10-15'],
    ['key' => 'c8',   'nombre' => 'Comuna 8 - Villa Hermosa', 'artistas' => 16, 'clientes' => 19, 'obras' => 21, 'fecha_actividad' => '2026-10-08'],
    ['key' => 'c9',   'nombre' => 'Comuna 9 - Buenos Aires', 'artistas' => 20, 'clientes' => 24, 'obras' => 28, 'fecha_actividad' => '2026-10-16'],
    ['key' => 'c10',  'nombre' => 'Comuna 10 - La Candelaria', 'artistas' => 35, 'clientes' => 40, 'obras' => 50, 'fecha_actividad' => '2026-09-28'],
    ['key' => 'c11',  'nombre' => 'Comuna 11 - Laureles - Estadio', 'artistas' => 28, 'clientes' => 32, 'obras' => 42, 'fecha_actividad' => '2026-10-12'],
    ['key' => 'c12',  'nombre' => 'Comuna 12 - La América', 'artistas' => 19, 'clientes' => 21, 'obras' => 27, 'fecha_actividad' => '2026-10-07'],
    ['key' => 'c13',  'nombre' => 'Comuna 13 - San Javier', 'artistas' => 42, 'clientes' => 48, 'obras' => 60, 'fecha_actividad' => '2026-10-15'],
    ['key' => 'c14',  'nombre' => 'Comuna 14 - El Poblado', 'artistas' => 45, 'clientes' => 55, 'obras' => 68, 'fecha_actividad' => '2026-10-17'],
    ['key' => 'c15',  'nombre' => 'Comuna 15 - Guayabal', 'artistas' => 24, 'clientes' => 26, 'obras' => 31, 'fecha_actividad' => '2026-10-06'],
    ['key' => 'c16',  'nombre' => 'Comuna 16 - Belén', 'artistas' => 30, 'clientes' => 35, 'obras' => 40, 'fecha_actividad' => '2026-10-05'],
    ['key' => 'cor1', 'nombre' => 'Corregimiento San Sebastián de Palmitas', 'artistas' => 5, 'clientes' => 6, 'obras' => 8, 'fecha_actividad' => '2026-09-20'],
    ['key' => 'cor2', 'nombre' => 'Corregimiento San Cristóbal', 'artistas' => 12, 'clientes' => 14, 'obras' => 17, 'fecha_actividad' => '2026-10-02'],
    ['key' => 'cor3', 'nombre' => 'Corregimiento Altavista', 'artistas' => 10, 'clientes' => 12, 'obras' => 15, 'fecha_actividad' => '2026-09-25'],
    ['key' => 'cor4', 'nombre' => 'Corregimiento San Antonio de Prado', 'artistas' => 15, 'clientes' => 18, 'obras' => 22, 'fecha_actividad' => '2026-10-03'],
    ['key' => 'cor5', 'nombre' => 'Corregimiento Santa Elena', 'artistas' => 18, 'clientes' => 22, 'obras' => 26, 'fecha_actividad' => '2026-10-01']
]; */

try {
    $stmtZonas = getDB()->query(
        "SELECT z.id_zona, z.nombre, z.tipo,
                (SELECT COUNT(*) FROM usuario u
                 WHERE u.id_zona = z.id_zona AND u.rol = 'artista') AS artistas,
                (SELECT COUNT(*) FROM usuario u
                 WHERE u.id_zona = z.id_zona AND u.rol = 'cliente') AS clientes,
                (SELECT COUNT(*) FROM obra o WHERE o.id_zona = z.id_zona AND o.estado_publicacion = 'publicada') AS obras,
                GREATEST(
                    COALESCE((SELECT MAX(u.actualizado_en) FROM usuario u WHERE u.id_zona = z.id_zona), '1000-01-01'),
                    COALESCE((SELECT MAX(o.actualizado_en) FROM obra o WHERE o.id_zona = z.id_zona), '1000-01-01')
                ) AS fecha_actividad
         FROM zona z
         ORDER BY z.tipo, z.nombre"
    );
    $zonas_medellin = array_map(static function (array $zona): array {
        return [
            'key' => 'z' . (int)$zona['id_zona'],
            'nombre' => ucfirst($zona['tipo']) . ' ' . $zona['nombre'],
            'artistas' => (int)$zona['artistas'],
            'clientes' => (int)$zona['clientes'],
            'obras' => (int)$zona['obras'],
            'fecha_actividad' => $zona['fecha_actividad'] === '1000-01-01'
                ? ''
                : date('Y-m-d', strtotime($zona['fecha_actividad'])),
        ];
    }, $stmtZonas->fetchAll());
} catch (PDOException $e) {
    error_log('Error al cargar zonas del panel: ' . $e->getMessage());
    $zonas_medellin = [];
}
include __DIR__ . '/dashboard_moderno.php';
exit;
?>

<!-- Carga de Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.dashboard-bento-wrapper {
    width: 100%;
    max-width: 1300px;
    margin: 0 auto;
    padding: 1.5rem;
    box-sizing: border-box;
}

.bento-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.search-box-bento {
    display: flex;
    align-items: center;
    background-color: var(--hover-item);
    border: 1px solid var(--border-subtle);
    border-radius: 9999px;
    padding: 0.5rem 1.25rem;
    width: 320px;
}

.search-box-bento input {
    background: transparent;
    border: none;
    outline: none;
    color: var(--text-main);
    width: 100%;
    font-size: 0.85rem;
}

.bento-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 1.25rem;
}

.bento-card {
    background-color: var(--bg-surface) !important;
    border: 1px solid var(--border-subtle) !important;
    border-radius: 1.25rem;
    padding: 1.25rem;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    backdrop-filter: blur(10px);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-sizing: border-box;
    position: relative;
}

.bento-card-title {
    font-size: 0.9rem;
    font-weight: 800;
    color: var(--text-main);
    margin: 0 0 0.85rem 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.col-span-3 { grid-column: span 3; }
.col-span-4 { grid-column: span 4; }
.col-span-5 { grid-column: span 5; }
.col-span-12 { grid-column: span 12; }

@media (max-width: 1024px) {
    .col-span-3, .col-span-4, .col-span-5 {
        grid-column: span 12;
    }
}

.kpi-number {
    font-size: 2rem;
    font-weight: 900;
    color: var(--text-main);
    line-height: 1;
}

.kpi-label {
    font-size: 0.72rem;
    color: var(--text-muted);
    font-weight: 700;
    text-transform: uppercase;
}

/* CALENDARIO BENTO */
.bento-calendar {
    width: 100%;
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
    font-weight: 800;
    color: var(--brand-orange);
    margin-bottom: 0.85rem;
}

.cal-btn-nav {
    background: var(--hover-item);
    border: 1px solid var(--border-subtle);
    color: var(--text-main);
    width: 2rem;
    height: 2rem;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-weight: 800;
    transition: all 0.2s ease;
}

.cal-btn-nav:hover:not(:disabled) {
    border-color: var(--brand-orange);
    color: var(--brand-orange);
}

.cal-btn-nav:disabled {
    opacity: 0.2;
    cursor: not-allowed;
    border-color: transparent;
}

.calendar-days-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.3rem;
    text-align: center;
    font-size: 0.75rem;
}

.cal-day-name {
    color: var(--brand-orange);
    font-weight: 800;
    padding-bottom: 0.35rem;
}

.cal-day-num {
    padding: 0.45rem 0;
    border-radius: 0.5rem;
    color: var(--text-main);
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
}

.cal-day-num:hover {
    background-color: var(--hover-item);
}

.cal-day-num.active {
    background: linear-gradient(135deg, var(--brand-orange) 0%, #ea580c 100%);
    color: #ffffff;
    font-weight: 800;
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
}

.cal-day-num.empty {
    cursor: default;
    opacity: 0;
}

/* ESTILOS PARA LA LEYENDA TIPO LISTA CON SCROLL */
.custom-legend-container {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    max-height: 190px;
    overflow-y: auto;
    padding-right: 0.4rem;
    width: 55%;
}

.custom-legend-container::-webkit-scrollbar {
    width: 4px;
}

.custom-legend-container::-webkit-scrollbar-thumb {
    background: var(--border-subtle);
    border-radius: 4px;
}

.legend-item-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.4rem;
    border-radius: 0.4rem;
    cursor: pointer;
    transition: background-color 0.2s ease;
    font-size: 0.75rem;
    color: var(--text-main);
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.legend-item-row:hover {
    background-color: var(--hover-item);
}

.legend-item-row.active-zone {
    background-color: rgba(249, 115, 22, 0.2);
    color: var(--brand-orange);
    font-weight: 800;
    border: 1px solid var(--brand-orange);
}

.color-bullet {
    width: 10px;
    height: 10px;
    border-radius: 3px;
    flex-shrink: 0;
}
</style>

<div class="dashboard-bento-wrapper">
    
    <!-- ENCABEZADO Y BUSCADOR GENERAL -->
    <div class="bento-header">
        <div>
            <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; font-weight: 700;">&larr; Volver al Panel General</a>
            <h1 style="font-size: 1.6rem; font-weight: 900; color: var(--text-main); margin: 0.2rem 0 0 0;">
                📍 Dashboard de Zonas: Comunas y Corregimientos
            </h1>
        </div>

        <div class="search-box-bento">
            <span style="margin-right: 0.5rem; color: var(--text-muted);">🔍</span>
            <input type="text" id="bentoSearch" placeholder="Buscar comuna o corregimiento..." onkeyup="updateDashboardView()">
        </div>
    </div>

    <!-- BENTO GRID SYSTEM -->
    <div class="bento-grid">
        
        <!-- CARD 1: DONA INTERACTIVA + LEYENDA CON SCROLL PARA LOS NOMBRES COMPLETOS -->
        <div class="bento-card col-span-5">
            <div class="bento-card-title">
                <span>Concentración en Medellín</span>
                <span id="selectedComunaBadge" style="font-size: 0.72rem; color: var(--brand-orange); font-weight: 700;">Todas las Zonas</span>
            </div>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin: -0.5rem 0 0.5rem 0;">Haz clic en una zona de la lista o de la dona para filtrar</p>
            
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; height: 190px;">
                <!-- Canvas de la Dona -->
                <div style="width: 45%; height: 100%; position: relative;">
                    <canvas id="comunasMedellinChart"></canvas>
                </div>
                
                <!-- Lista de Leyenda con Scroll -->
                <div class="custom-legend-container" id="customZonesLegend">
                    <!-- Filas dinámicas en JS -->
                </div>
            </div>
        </div>

        <!-- CARD 2: KPIS Y MÉTRICAS DUALES (ARTISTAS Y CLIENTES) -->
        <div class="bento-card col-span-3">
            <div class="bento-card-title">
                <span>Cobertura Territorial</span>
                <button onclick="resetFilters()" style="background: transparent; border: 1px solid var(--border-subtle); color: var(--text-muted); font-size: 0.7rem; border-radius: 0.4rem; padding: 0.2rem 0.4rem; cursor: pointer;">Reset</button>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-top: 0.2rem;">
                <div>
                    <div class="kpi-number" id="kpiComunas" style="color: #06b6d4;">21</div>
                    <div class="kpi-label">Zonas Activas</div>
                </div>
                
                <div style="display: flex; justify-content: space-between;">
                    <div>
                        <div class="kpi-number" id="kpiArtistas" style="color: var(--brand-orange); font-size: 1.4rem;">0</div>
                        <div class="kpi-label">Artistas</div>
                    </div>
                    <div>
                        <div class="kpi-number" id="kpiClientes" style="color: #3b82f6; font-size: 1.4rem;">0</div>
                        <div class="kpi-label">Clientes</div>
                    </div>
                </div>

                <div>
                    <div class="kpi-number" id="kpiObras" style="color: #22c55e; font-size: 1.3rem;">0</div>
                    <div class="kpi-label">Obras Circulando</div>
                </div>
            </div>
        </div>

        <!-- CARD 3: CALENDARIO INTERACTIVO (LIMITADO A OCTUBRE 2026) -->
        <div class="bento-card col-span-4">
            <div class="bento-calendar">
                <div class="calendar-header">
                    <button class="cal-btn-nav" onclick="changeMonth(-1)">&lt;</button>
                    <span id="calMonthTitle" style="font-weight: 800; font-size: 0.95rem;"></span>
                    <button class="cal-btn-nav" id="nextMonthBtn" onclick="changeMonth(1)">&gt;</button>
                </div>
                <div class="calendar-days-grid" id="calendarDaysGrid">
                    <!-- Días renderizados dinámicamente -->
                </div>
            </div>
        </div>

        <!-- CARD 4: GRÁFICO COMPARATIVO DUAL -->
        <div class="bento-card col-span-12">
            <div class="bento-card-title">
                <span>Distribución de Artistas y Clientes por Comuna o Corregimiento</span>
                <span id="activeFilterLabel" style="font-size: 0.75rem; color: var(--text-muted);">Filtrado en tiempo real</span>
            </div>
            <div style="height: 220px;">
                <canvas id="zonesBarChart"></canvas>
            </div>
        </div>

    </div>

</div>

<script>
const rawZonas = <?= json_encode($zonas_medellin) ?>;

const coloresTerritoriales = [
    '#f97316', '#ea580c', '#c2410c', '#a855f7', '#9333ea', 
    '#3b82f6', '#2563eb', '#06b6d4', '#0891b2', '#10b981', 
    '#059669', '#84cc16', '#65a30d', '#eab308', '#ca8a04', 
    '#ec4899', '#db2777', '#f43f5e', '#e11d48', '#6366f1', '#4f46e5'
];

// LÍMITE DE CALENDARIO (OCTUBRE 2026)
const MAX_YEAR = 2026;
const MAX_MONTH = 9;

let currentYear = 2026;
let currentMonth = 9;
let selectedFullDate = null;
let selectedZoneKey = null;

const monthNames = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
];

const orangeBrand = '#f97316';
const blueClient = '#3b82f6';

let comunasMedellinChart, zonesBarChart;

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    renderCustomLegend();
    renderCalendar();
    updateDashboardView();
});

function initCharts() {
    const artCtx = document.getElementById('comunasMedellinChart').getContext('2d');
    
    // Gráfico de Dona
    comunasMedellinChart = new Chart(artCtx, {
        type: 'doughnut',
        data: {
            labels: rawZonas.map(z => z.nombre),
            datasets: [{
                data: rawZonas.map(z => z.artistas + z.clientes),
                backgroundColor: coloresTerritoriales,
                borderWidth: 2,
                borderColor: 'rgba(20, 20, 20, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }, // Desactivamos la leyenda nativa problemática
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const index = context.dataIndex;
                            const z = rawZonas[index];
                            return ` ${z.nombre}: ${z.artistas} Artistas / ${z.clientes} Clientes`;
                        }
                    }
                }
            },
            onClick: (evt, activeElements) => {
                if (activeElements.length > 0) {
                    const index = activeElements[0].index;
                    const clickedZone = rawZonas[index];
                    toggleZoneSelection(clickedZone.key, clickedZone.nombre);
                }
            }
        }
    });

    // Barras comparativas
    const barCtx = document.getElementById('zonesBarChart').getContext('2d');
    zonesBarChart = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: rawZonas.map(z => z.nombre),
            datasets: [
                {
                    label: 'Artistas Registrados',
                    data: rawZonas.map(z => z.artistas),
                    backgroundColor: orangeBrand,
                    borderRadius: 5
                },
                {
                    label: 'Clientes registrados',
                    data: rawZonas.map(z => z.clientes),
                    backgroundColor: blueClient,
                    borderRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: '#9ca3af', font: { size: 11 } } } },
            scales: {
                x: { ticks: { color: '#9ca3af' }, grid: { display: false } },
                y: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        }
    });
}

// RENDERIZADO DE LA LEYENDA HTML CON SCROLL Y SELECCIÓN COMPLETA
function renderCustomLegend() {
    const container = document.getElementById('customZonesLegend');
    container.innerHTML = '';

    rawZonas.forEach((z, idx) => {
        const item = document.createElement('div');
        item.className = 'legend-item-row';
        if (selectedZoneKey === z.key) {
            item.classList.add('active-zone');
        }

        item.innerHTML = `
            <span class="color-bullet" style="background-color: ${coloresTerritoriales[idx % coloresTerritoriales.length]};"></span>
            <span title="${z.nombre}">${z.nombre}</span>
        `;

        item.onclick = () => toggleZoneSelection(z.key, z.nombre);
        container.appendChild(item);
    });
}

function toggleZoneSelection(key, nombre) {
    if (selectedZoneKey === key) {
        selectedZoneKey = null;
        document.getElementById('selectedComunaBadge').textContent = 'Todas las Zonas';
    } else {
        selectedZoneKey = key;
        document.getElementById('selectedComunaBadge').textContent = nombre;
    }
    renderCustomLegend();
    updateDashboardView();
}

function renderCalendar() {
    document.getElementById('calMonthTitle').textContent = `${monthNames[currentMonth]} ${currentYear}`;
    
    const nextBtn = document.getElementById('nextMonthBtn');
    if (currentYear > MAX_YEAR || (currentYear === MAX_YEAR && currentMonth >= MAX_MONTH)) {
        nextBtn.disabled = true;
    } else {
        nextBtn.disabled = false;
    }

    const grid = document.getElementById('calendarDaysGrid');
    grid.innerHTML = '';

    const dayHeaders = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];
    dayHeaders.forEach(dh => {
        const span = document.createElement('span');
        span.className = 'cal-day-name';
        span.textContent = dh;
        grid.appendChild(span);
    });

    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const totalDays = new Date(currentYear, currentMonth + 1, 0).getDate();

    for (let i = 0; i < firstDay; i++) {
        const emptySpan = document.createElement('span');
        emptySpan.className = 'cal-day-num empty';
        grid.appendChild(emptySpan);
    }

    for (let day = 1; day <= totalDays; day++) {
        const daySpan = document.createElement('span');
        daySpan.className = 'cal-day-num';
        daySpan.textContent = day;

        const formattedMonth = String(currentMonth + 1).padStart(2, '0');
        const formattedDay = String(day).padStart(2, '0');
        const fullDateStr = `${currentYear}-${formattedMonth}-${formattedDay}`;

        if (selectedFullDate === fullDateStr) {
            daySpan.classList.add('active');
        }

        daySpan.onclick = () => selectCalendarDate(fullDateStr);
        grid.appendChild(daySpan);
    }
}

function changeMonth(delta) {
    if (delta > 0 && (currentYear > MAX_YEAR || (currentYear === MAX_YEAR && currentMonth >= MAX_MONTH))) {
        return;
    }

    currentMonth += delta;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    } else if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    
    selectedFullDate = null;
    renderCalendar();
    updateDashboardView();
}

function selectCalendarDate(dateStr) {
    if (selectedFullDate === dateStr) {
        selectedFullDate = null;
    } else {
        selectedFullDate = dateStr;
    }
    renderCalendar();
    updateDashboardView();
}

function resetFilters() {
    selectedFullDate = null;
    selectedZoneKey = null;
    currentYear = MAX_YEAR;
    currentMonth = MAX_MONTH;
    document.getElementById('bentoSearch').value = '';
    document.getElementById('selectedComunaBadge').textContent = 'Todas las Zonas';
    renderCustomLegend();
    renderCalendar();
    updateDashboardView();
}

function updateDashboardView() {
    const searchVal = document.getElementById('bentoSearch').value.toLowerCase();

    const filtered = rawZonas.filter(z => {
        const matchesSearch = z.nombre.toLowerCase().includes(searchVal);
        const matchesZone = !selectedZoneKey || z.key === selectedZoneKey;
        return matchesSearch && matchesZone;
    });

    document.getElementById('kpiComunas').textContent = filtered.length;
    const totalArtistas = filtered.reduce((acc, z) => acc + z.artistas, 0);
    const totalClientes = filtered.reduce((acc, z) => acc + z.clientes, 0);
    const totalObras = filtered.reduce((acc, z) => acc + z.obras, 0);

    document.getElementById('kpiArtistas').textContent = totalArtistas;
    document.getElementById('kpiClientes').textContent = totalClientes;
    document.getElementById('kpiObras').textContent = totalObras;

    if (zonesBarChart) {
        zonesBarChart.data.labels = filtered.map(z => z.nombre);
        zonesBarChart.data.datasets[0].data = filtered.map(z => z.artistas);
        zonesBarChart.data.datasets[1].data = filtered.map(z => z.clientes);
        zonesBarChart.update();
    }
}
</script>

