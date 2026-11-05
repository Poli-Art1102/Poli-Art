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

// Reportes reales de contenido y de artistas.
$reportes_lista = [];
/*
    [
        'id' => 101,
        'tipo' => 'Contenido Inapropiado',
        'especialidad' => 'Dibujo y Pintura',
        'esp_id' => 'dibujo_pintura',
        'estado' => 'Pendiente',
        'fecha' => '2026-10-17',
        'prioridad' => 'Alta'
    ],
    [
        'id' => 102,
        'tipo' => 'Derechos de Autor',
        'especialidad' => 'Música',
        'esp_id' => 'musica',
        'estado' => 'Resuelto',
        'fecha' => '2026-10-15',
        'prioridad' => 'Media'
    ],
    [
        'id' => 103,
        'tipo' => 'Spam / Publicidad',
        'especialidad' => 'Escritura',
        'esp_id' => 'escritura',
        'estado' => 'Resuelto',
        'fecha' => '2026-10-12',
        'prioridad' => 'Baja'
    ],
    [
        'id' => 104,
        'tipo' => 'Conducta Inapropiada',
        'especialidad' => 'Artes Escénicas',
        'esp_id' => 'escenicas',
        'estado' => 'Pendiente',
        'fecha' => '2026-09-28',
        'prioridad' => 'Alta'
    ],
    [
        'id' => 105,
        'tipo' => 'Plagio de Modelo 3D',
        'especialidad' => 'Escultura',
        'esp_id' => 'escultura',
        'estado' => 'Resuelto',
        'fecha' => '2026-08-14',
        'prioridad' => 'Media'
    ]
];*/

try {
    $stmtReportes = getDB()->query(
        "SELECT r.id_reporte, r.motivo, r.estado, r.creado_en,
                c.slug AS slug_categoria, c.nombre AS especialidad
         FROM reporte_contenido r
         INNER JOIN obra o ON o.id_obra = r.id_obra
         INNER JOIN categoria_obra c ON c.id_categoria = o.id_categoria
         ORDER BY r.creado_en DESC"
    );
    $reportes_lista = array_map(static function (array $reporte): array {
        $slug = $reporte['slug_categoria'];
        $espId = strpos($slug, 'ilustracion') !== false ? 'dibujo_pintura'
            : (strpos($slug, 'musica') !== false ? 'musica'
                : (strpos($slug, 'escenicas') !== false ? 'escenicas'
                    : (strpos($slug, 'literatura') !== false || strpos($slug, 'poesia') !== false ? 'escritura'
                        : (strpos($slug, 'escultura') !== false || strpos($slug, 'ceramica') !== false ? 'escultura' : ''))));
        return [
            'id' => (int)$reporte['id_reporte'],
            'tipo' => $reporte['motivo'],
            'especialidad' => $reporte['especialidad'],
            'esp_id' => $espId,
            'estado' => ucfirst($reporte['estado']),
            'fecha' => date('Y-m-d', strtotime($reporte['creado_en'])),
            'prioridad' => 'No definida',
        ];
    }, $stmtReportes->fetchAll());
    $stmtArtista = getDB()->query("SELECT id_reporte_artista AS id_reporte, motivo, estado, creado_en FROM reporte_artista ORDER BY creado_en DESC");
    foreach ($stmtArtista->fetchAll() as $reporte) {
        $reportes_lista[] = [
            'id' => (int)$reporte['id_reporte'], 'tipo' => $reporte['motivo'], 'especialidad' => 'Artista',
            'esp_id' => '', 'estado' => ucfirst($reporte['estado']), 'fecha' => date('Y-m-d', strtotime($reporte['creado_en'])), 'prioridad' => 'No definida'
        ];
    }
} catch (PDOException $e) {
    error_log('Error al cargar panel de reportes: ' . $e->getMessage());
    $reportes_lista = [];
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
    width: 300px;
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
</style>

<div class="dashboard-bento-wrapper">
    
    <!-- ENCABEZADO Y BUSCADOR GENERAL -->
    <div class="bento-header">
        <div>
            <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; font-weight: 700;">&larr; Volver al Panel General</a>
            <h1 style="font-size: 1.6rem; font-weight: 900; color: var(--text-main); margin: 0.2rem 0 0 0;">
                🚩 Dashboard de Analítica de Reportes
            </h1>
        </div>

        <div class="search-box-bento">
            <span style="margin-right: 0.5rem; color: var(--text-muted);">🔍</span>
            <input type="text" id="bentoSearch" placeholder="Buscar por tipo de reporte o técnica..." onkeyup="updateDashboardView()">
        </div>
    </div>

    <!-- BENTO GRID SYSTEM -->
    <div class="bento-grid">
        
        <!-- CARD 1: DONA INTERACTIVA DE REPORTES POR ARTES -->
        <div class="bento-card col-span-5">
            <div class="bento-card-title">
                <span>Reportes por Disciplina</span>
                <span id="selectedArtBadge" style="font-size: 0.72rem; color: var(--brand-orange); font-weight: 700;">Todas las Artes</span>
            </div>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin: -0.5rem 0 0.5rem 0;">Haz clic en un arte para filtrar las métricas</p>
            <div style="height: 190px;">
                <canvas id="reportsByArtChart"></canvas>
            </div>
        </div>

        <!-- CARD 2: KPIS Y MÉTRICAS DE REPORTES -->
        <div class="bento-card col-span-3">
            <div class="bento-card-title">
                <span>Estado de Incidencias</span>
                <button onclick="resetFilters()" style="background: transparent; border: 1px solid var(--border-subtle); color: var(--text-muted); font-size: 0.7rem; border-radius: 0.4rem; padding: 0.2rem 0.4rem; cursor: pointer;">Reset</button>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-top: 0.2rem;">
                <div>
                    <div class="kpi-number" id="kpiTotalNum" style="color: var(--brand-orange);">0</div>
                    <div class="kpi-label">Total Reportes</div>
                </div>
                
                <div style="display: flex; justify-content: space-between;">
                    <div>
                        <div class="kpi-number" id="kpiResueltos" style="color: #22c55e; font-size: 1.5rem;">0</div>
                        <div class="kpi-label">Resueltos</div>
                    </div>
                    <div>
                        <div class="kpi-number" id="kpiPendientes" style="color: #f59e0b; font-size: 1.5rem;">0</div>
                        <div class="kpi-label">Pendientes</div>
                    </div>
                </div>

                <div>
                    <div class="kpi-number" id="kpiAltaPrioridad" style="color: #ef4444; font-size: 1.4rem;">0</div>
                    <div class="kpi-label">Prioridad Alta</div>
                </div>
            </div>
        </div>

        <!-- CARD 3: CALENDARIO INTERACTIVO (RESTRINGIDO AL PRESENTE) -->
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

        <!-- CARD 4: GRÁFICO HISTÓRICO DE INCIDENCIAS -->
        <div class="bento-card col-span-12">
            <div class="bento-card-title">
                <span>Evolución de Reportes Recibidos vs Resueltos</span>
                <span id="activeFilterLabel" style="font-size: 0.75rem; color: var(--text-muted);">Filtrado en tiempo real</span>
            </div>
            <div style="height: 220px;">
                <canvas id="reportsSmoothChart"></canvas>
            </div>
        </div>

    </div>

</div>

<script>
// DATOS BASE DE REPORTES
const rawReportes = <?= json_encode($reportes_lista) ?>;
const especialidadesOficiales = <?= json_encode($especialidades_oficiales) ?>;
const reportMotives = <?= json_encode(array_values(array_reduce($reportes_lista, static function (array $carry, array $row): array { $carry[$row['tipo']] = ($carry[$row['tipo']] ?? 0) + 1; return $carry; }, []))) ?>;
const reportMotiveLabels = <?= json_encode(array_keys(array_reduce($reportes_lista, static function (array $carry, array $row): array { $carry[$row['tipo']] = true; return $carry; }, []))) ?>;

// LÍMITE DE CALENDARIO (OCTUBRE 2026)
const MAX_YEAR = 2026;
const MAX_MONTH = 9;

let currentYear = 2026;
let currentMonth = 9;
let selectedFullDate = null;
let selectedEspId = null;

const monthNames = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
];

const orangeBrand = '#f97316';
const purpleAccent = '#a855f7';
const blueCyan = '#06b6d4';
const yellowWarn = '#f59e0b';
const greenSuccess = '#22c55e';
const redDanger = '#ef4444';

let reportsByArtChart, reportsSmoothChart;

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    renderCalendar();
    updateDashboardView();
});

function initCharts() {
    // 1. GRÁFICO DE DONA INTERACTIVO (REPORTES POR ARTE)
    const artCtx = document.getElementById('reportsByArtChart').getContext('2d');
    reportsByArtChart = new Chart(artCtx, {
        type: 'doughnut',
        data: {
            labels: Object.values(especialidadesOficiales),
            datasets: [{
                data: reportMotiveLabels.map(label => reportMotives[reportMotiveLabels.indexOf(label)]),
                backgroundColor: [orangeBrand, purpleAccent, blueCyan, yellowWarn, greenSuccess],
                borderWidth: 2,
                borderColor: 'rgba(20, 20, 20, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { color: '#d1d5db', font: { size: 10, weight: 'bold' } }
                }
            },
            onClick: (evt, activeElements) => {
                if (activeElements.length > 0) {
                    const index = activeElements[0].index;
                    const keys = Object.keys(especialidadesOficiales);
                    const clickedEspId = keys[index];

                    if (selectedEspId === clickedEspId) {
                        selectedEspId = null;
                    } else {
                        selectedEspId = clickedEspId;
                    }
                    updateDashboardView();
                }
            }
        }
    });

    // 2. GRÁFICO HISTÓRICO DE FLUJO DE REPORTES
    const smoothCtx = document.getElementById('reportsSmoothChart').getContext('2d');
    reportsSmoothChart = new Chart(smoothCtx, {
        type: 'line',
        data: {
            labels: [...new Set(rawReportes.map(row => row.fecha.slice(0, 7)))],
            datasets: [
                {
                    label: 'Reportes Recibidos',
                    data: [...new Set(rawReportes.map(row => row.fecha.slice(0, 7)))].map(period => rawReportes.filter(row => row.fecha.startsWith(period)).length),
                    borderColor: redDanger,
                    backgroundColor: 'rgba(239, 68, 68, 0.12)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4
                },
                {
                    label: 'Reportes Resueltos',
                    data: [...new Set(rawReportes.map(row => row.fecha.slice(0, 7)))].map(period => rawReportes.filter(row => row.fecha.startsWith(period) && row.estado === 'Resuelto').length),
                    borderColor: greenSuccess,
                    borderDash: [5, 5],
                    tension: 0.4,
                    pointRadius: 3
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

// RENDERIZADO DEL CALENDARIO CON BLOQUEO A FUTURO
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
    selectedEspId = null;
    currentYear = MAX_YEAR;
    currentMonth = MAX_MONTH;
    document.getElementById('bentoSearch').value = '';
    renderCalendar();
    updateDashboardView();
}

// ACTUALIZACIÓN DE DATOS SEGÚN FILTROS
function updateDashboardView() {
    const searchVal = document.getElementById('bentoSearch').value.toLowerCase();
    const currentMonthPrefix = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}`;

    const filtered = rawReportes.filter(rep => {
        const matchesSearch = rep.tipo.toLowerCase().includes(searchVal) ||
                              rep.especialidad.toLowerCase().includes(searchVal);
                              
        let matchesDate = true;
        if (selectedFullDate) {
            matchesDate = (rep.fecha === selectedFullDate);
        } else {
            matchesDate = rep.fecha.startsWith(currentMonthPrefix);
        }

        const matchesEsp = !selectedEspId || rep.esp_id === selectedEspId;

        return matchesSearch && matchesDate && matchesEsp;
    });

    // Actualizar KPIs de Reportes
    document.getElementById('kpiTotalNum').textContent = filtered.length;
    const resueltosCount = filtered.filter(r => r.estado === 'Resuelto').length;
    document.getElementById('kpiResueltos').textContent = resueltosCount;
    document.getElementById('kpiPendientes').textContent = filtered.length - resueltosCount;
    
    const altaCount = filtered.filter(r => r.prioridad === 'Alta').length;
    document.getElementById('kpiAltaPrioridad').textContent = altaCount;

    // Badges descriptivos
    if (selectedEspId) {
        document.getElementById('selectedArtBadge').textContent = especialidadesOficiales[selectedEspId];
    } else {
        document.getElementById('selectedArtBadge').textContent = 'Todas las Artes';
    }

    // Animar gráfico de tendencia
    if (reportsSmoothChart) {
        reportsSmoothChart.data.datasets[0].data = [
            0, 0, 0, 0, 0, 0, 0, 
            filtered.length > 2 ? 1 : 0, 
            filtered.length > 4 ? 2 : 1, 
            filtered.length
        ];
        reportsSmoothChart.update();
    }
}
</script>

