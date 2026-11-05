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

// Datos cargados desde la base de datos; nunca usamos registros de ejemplo.
$clientes_lista = [];
$clientes_tendencia = [];
$interes_categorias = [];
$top_clientes = [];
$cliente_kpis = ['conversaciones' => 0, 'favoritos' => 0];
/*
    [
        'id' => 201,
        'nombre' => 'Camila Pérez',
        'usuario' => 'cami_perez',
        'email' => 'camila@gmail.com',
        'interes_principal' => 'Dibujo y Pintura',
        'esp_id' => 'dibujo_pintura',
        'estado' => 'Activo',
        'fecha_registro' => '2026-10-16'
    ],
    [
        'id' => 202,
        'nombre' => 'Santiago Restrepo',
        'usuario' => 'santi_r',
        'email' => 'santiago@gmail.com',
        'interes_principal' => 'Música',
        'esp_id' => 'musica',
        'estado' => 'Activo',
        'fecha_registro' => '2026-10-14'
    ],
    [
        'id' => 203,
        'nombre' => 'Mariana Torres',
        'usuario' => 'mari_torres',
        'email' => 'mariana@hotmail.com',
        'interes_principal' => 'Escritura',
        'esp_id' => 'escritura',
        'estado' => 'Inactivo',
        'fecha_registro' => '2026-10-10'
    ],
    [
        'id' => 204,
        'nombre' => 'Daniel Ospina',
        'usuario' => 'dani_ospina',
        'email' => 'daniel@gmail.com',
        'interes_principal' => 'Artes Escénicas',
        'esp_id' => 'escenicas',
        'estado' => 'Activo',
        'fecha_registro' => '2026-09-25'
    ],
    [
        'id' => 205,
        'nombre' => 'Elena Montoya',
        'usuario' => 'elena_m',
        'email' => 'elena@gmail.com',
        'interes_principal' => 'Escultura',
        'esp_id' => 'escultura',
        'estado' => 'Activo',
        'fecha_registro' => '2026-08-18'
    ]
];*/

try {
    $stmtClientes = getDB()->query(
        "SELECT u.id_usuario, u.nombre, u.apellido, u.nombre_usuario, u.correo,
                u.estado, u.creado_en,
                (SELECT COUNT(*) FROM me_gusta mg WHERE mg.id_usuario = u.id_usuario) AS favoritos,
                (SELECT COUNT(*) FROM mensaje m WHERE m.id_emisor = u.id_usuario) AS mensajes
         FROM usuario u
         WHERE u.rol = 'cliente'
         ORDER BY u.creado_en DESC"
    );
    $clientes_lista = array_map(static function (array $cliente): array {
        return [
            'id' => (int)$cliente['id_usuario'],
            'nombre' => trim($cliente['nombre'] . ' ' . $cliente['apellido']),
            'usuario' => $cliente['nombre_usuario'],
            'email' => $cliente['correo'],
            'interes_principal' => 'Sin preferencia registrada',
            'esp_id' => '',
            'estado' => $cliente['estado'] === 'activo' ? 'Activo' : 'Inactivo',
            'fecha_registro' => date('Y-m-d', strtotime($cliente['creado_en'])),
            'favoritos' => (int)$cliente['favoritos'],
            'mensajes' => (int)$cliente['mensajes'],
        ];
    }, $stmtClientes->fetchAll());

    $db = getDB();
    $stmtIntereses = $db->query(
        "SELECT c.slug, c.nombre, COUNT(CASE WHEN lu.id_usuario IS NOT NULL THEN mg.id_obra END) AS total
         FROM categoria_obra c
         LEFT JOIN obra o ON o.id_categoria = c.id_categoria
         LEFT JOIN me_gusta mg ON mg.id_obra = o.id_obra
         LEFT JOIN usuario lu ON lu.id_usuario = mg.id_usuario AND lu.rol = 'cliente'
         WHERE c.activa = 1
         GROUP BY c.id_categoria, c.slug, c.nombre ORDER BY total DESC"
    );
    $interes_categorias = array_map(static fn(array $row): array => [
        'slug' => $row['slug'], 'nombre' => $row['nombre'], 'total' => (int)$row['total']
    ], $stmtIntereses->fetchAll());

    $stmtTendencia = $db->query(
        "SELECT DATE_FORMAT(u.creado_en, '%Y-%m') AS periodo, COUNT(*) AS clientes
         FROM usuario u WHERE u.rol = 'cliente' GROUP BY periodo ORDER BY periodo"
    );
    $clientes_tendencia = array_map(static fn(array $row): array => [
        'periodo' => $row['periodo'], 'clientes' => (int)$row['clientes']
    ], $stmtTendencia->fetchAll());
    $top_clientes = array_map(static fn(array $cliente): array => [
        'usuario' => $cliente['usuario'], 'interacciones' => $cliente['favoritos'] + $cliente['mensajes']
    ], $clientes_lista);
    usort($top_clientes, static fn(array $a, array $b): int => $b['interacciones'] <=> $a['interacciones']);
    $top_clientes = array_slice($top_clientes, 0, 10);
    $cliente_kpis['conversaciones'] = (int)$db->query("SELECT COUNT(DISTINCT CONCAT(LEAST(m.id_emisor,m.id_receptor), '-', GREATEST(m.id_emisor,m.id_receptor))) FROM mensaje m INNER JOIN usuario u ON u.id_usuario = m.id_emisor WHERE u.rol = 'cliente'")->fetchColumn();
    $cliente_kpis['favoritos'] = (int)$db->query("SELECT COUNT(*) FROM me_gusta mg INNER JOIN usuario u ON u.id_usuario = mg.id_usuario WHERE u.rol = 'cliente'")->fetchColumn();
} catch (PDOException $e) {
    error_log('Error al cargar el panel de clientes: ' . $e->getMessage());
    $clientes_lista = [];
    $clientes_tendencia = [];
    $interes_categorias = [];
    $top_clientes = [];
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
                👥 Dashboard de Analítica de Clientes
            </h1>
        </div>

        <div class="search-box-bento">
            <span style="margin-right: 0.5rem; color: var(--text-muted);">🔍</span>
            <input type="text" id="bentoSearch" placeholder="Buscar por cliente o arte de interés..." onkeyup="updateDashboardView()">
        </div>
    </div>

    <!-- BENTO GRID SYSTEM -->
    <div class="bento-grid">
        
        <!-- CARD 1: DONA INTERACTIVA DE PREFERENCIA DE CLIENTES POR ARTES -->
        <div class="bento-card col-span-5">
            <div class="bento-card-title">
                <span>Preferencias por Disciplina</span>
                <span id="selectedArtBadge" style="font-size: 0.72rem; color: var(--brand-orange); font-weight: 700;">Todas las Artes</span>
            </div>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin: -0.5rem 0 0.5rem 0;">Haz clic en un arte para filtrar las métricas de clientes</p>
            <div style="height: 190px;">
                <canvas id="clientsByArtChart"></canvas>
            </div>
        </div>

        <!-- CARD 2: KPIS Y MÉTRICAS DE CLIENTES -->
        <div class="bento-card col-span-3">
            <div class="bento-card-title">
                <span>Métricas de Clientes</span>
                <button onclick="resetFilters()" style="background: transparent; border: 1px solid var(--border-subtle); color: var(--text-muted); font-size: 0.7rem; border-radius: 0.4rem; padding: 0.2rem 0.4rem; cursor: pointer;">Reset</button>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-top: 0.2rem;">
                <div>
                    <div class="kpi-number" id="kpiTotalNum" style="color: var(--brand-orange);">0</div>
                    <div class="kpi-label">Total Clientes</div>
                </div>
                
                <div style="display: flex; justify-content: space-between;">
                    <div>
                        <div class="kpi-number" id="kpiActivos" style="color: #22c55e; font-size: 1.5rem;">0</div>
                        <div class="kpi-label">Activos</div>
                    </div>
                    <div>
                        <div class="kpi-number" id="kpiInactivos" style="color: #f59e0b; font-size: 1.5rem;">0</div>
                        <div class="kpi-label">Inactivos</div>
                    </div>
                </div>

                <div>
                    <div class="kpi-number" id="kpiClientesActivos" style="color: #3b82f6; font-size: 1.4rem;">0</div>
                    <div class="kpi-label">Clientes activos</div>
                </div>
                <div style="display:flex;justify-content:space-between;gap:.75rem;border-top:1px solid var(--border-subtle);padding-top:.65rem;">
                    <div><div class="kpi-number" style="font-size:1.25rem;color:#a855f7;"><?= (int)$cliente_kpis['conversaciones'] ?></div><div class="kpi-label">Conversaciones</div></div>
                    <div><div class="kpi-number" style="font-size:1.25rem;color:#ec4899;"><?= (int)$cliente_kpis['favoritos'] ?></div><div class="kpi-label">Favoritos</div></div>
                </div>
            </div>
        </div>

        <!-- CARD 3: CALENDARIO INTERACTIVO (LIMITADO AL PRESENTE) -->
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

        <!-- CARD 4: GRÁFICO HISTÓRICO DE REGISTRO DE CLIENTES -->
        <div class="bento-card col-span-12">
            <div class="bento-card-title">
                <span>Crecimiento de clientes registrados</span>
                <span id="activeFilterLabel" style="font-size: 0.75rem; color: var(--text-muted);">Filtrado en tiempo real</span>
            </div>
            <div style="height: 220px;">
                <canvas id="clientsSmoothChart"></canvas>
            </div>
        </div>

        <div class="bento-card col-span-12">
            <div class="bento-card-title"><span>Clientes más activos</span><span style="font-size:.75rem;color:var(--text-muted);">Me gusta + mensajes</span></div>
            <div style="height:220px;"><canvas id="topClientsChart"></canvas></div>
        </div>

    </div>

</div>

<script>
// DATOS BASE DE CLIENTES
const rawClientes = <?= json_encode($clientes_lista) ?>;
const especialidadesOficiales = <?= json_encode($especialidades_oficiales) ?>;
const interestData = <?= json_encode($interes_categorias) ?>;
const trendData = <?= json_encode($clientes_tendencia) ?>;
const topClientsData = <?= json_encode($top_clientes) ?>;

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

let clientsByArtChart, clientsSmoothChart, topClientsChart;

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    renderCalendar();
    updateDashboardView();
});

function initCharts() {
    // 1. GRÁFICO DE DONA INTERACTIVO (PREFERENCIAS DE CLIENTES POR ARTE)
    const artCtx = document.getElementById('clientsByArtChart').getContext('2d');
    clientsByArtChart = new Chart(artCtx, {
        type: 'doughnut',
        data: {
            labels: Object.values(especialidadesOficiales),
            datasets: [{
                data: Object.keys(especialidadesOficiales).map(key => {
                    const row = interestData.find(item => item.slug.includes(key) || (key === 'dibujo_pintura' && (item.slug.includes('ilustr') || item.slug.includes('pint'))));
                    return row ? row.total : 0;
                }),
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

    // 2. GRÁFICO HISTÓRICO DE CRECIMIENTO Y ACTIVIDAD DE CLIENTES
    const smoothCtx = document.getElementById('clientsSmoothChart').getContext('2d');
    clientsSmoothChart = new Chart(smoothCtx, {
        type: 'line',
        data: {
            labels: trendData.map(row => row.periodo),
            datasets: [
                {
                    label: 'Clientes nuevos',
                    data: trendData.map(row => row.clientes),
                    borderColor: orangeBrand,
                    backgroundColor: 'rgba(249, 115, 22, 0.12)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4
                },
                {
                    label: 'Nuevos Clientes Registrados',
                    data: trendData.map(row => row.clientes),
                    borderColor: blueCyan,
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

    const topCtx = document.getElementById('topClientsChart').getContext('2d');
    topClientsChart = new Chart(topCtx, {
        type: 'bar',
        data: { labels: topClientsData.map(row => '@' + row.usuario), datasets: [{ label: 'Interacciones', data: topClientsData.map(row => row.interacciones), backgroundColor: '#a855f7', borderRadius: 6 }] },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { color: '#9ca3af', precision: 0 }, grid: { color: 'rgba(255,255,255,.05)' } }, y: { ticks: { color: '#d1d5db' }, grid: { display: false } } } }
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

    const filtered = rawClientes.filter(cli => {
        const matchesSearch = cli.nombre.toLowerCase().includes(searchVal) ||
                              cli.usuario.toLowerCase().includes(searchVal) ||
                              cli.interes_principal.toLowerCase().includes(searchVal);
                              
        let matchesDate = true;
        if (selectedFullDate) {
            matchesDate = (cli.fecha_registro === selectedFullDate);
        } else {
            matchesDate = cli.fecha_registro.startsWith(currentMonthPrefix);
        }

        const matchesEsp = !selectedEspId || cli.esp_id === selectedEspId;

        return matchesSearch && matchesDate && matchesEsp;
    });

    // Actualizar KPIs de Clientes
    document.getElementById('kpiTotalNum').textContent = filtered.length;
    const activosCount = filtered.filter(c => c.estado === 'Activo').length;
    document.getElementById('kpiActivos').textContent = activosCount;
    document.getElementById('kpiInactivos').textContent = filtered.length - activosCount;
    
    document.getElementById('kpiClientesActivos').textContent = filtered.filter(c => c.estado === 'Activo').length;

    // Badges descriptivos
    if (selectedEspId) {
        document.getElementById('selectedArtBadge').textContent = especialidadesOficiales[selectedEspId];
    } else {
        document.getElementById('selectedArtBadge').textContent = 'Todas las Artes';
    }

    // Animar gráfico de tendencia
    if (clientsSmoothChart) {
        clientsSmoothChart.data.datasets[1].data = trendData.map(row => row.clientes);
        clientsSmoothChart.update();
    }
}
</script>

