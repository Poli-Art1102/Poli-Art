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

// Datos cargados desde la base de datos; no se muestran artistas ficticios.
$artistas_lista = [];
$produccion_tendencia = [];
$top_artistas = [];
$impacto_artistas = ['proyectos' => 0, 'invitaciones' => 0];
/*
    [
        'id' => 1,
        'nombre' => 'Jeffrey Ramos',
        'usuario' => 'lulo',
        'email' => 'jeffrey@poliart.com',
        'especialidad' => 'Dibujo y Pintura',
        'esp_id' => 'dibujo_pintura',
        'zona' => 'Comuna 14 - El Poblado',
        'estado' => 'Verificado',
        'obras_publicadas' => 14,
        'fecha_registro' => '2026-10-17',
        'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80'
    ],
    [
        'id' => 2,
        'nombre' => 'Sofía Rodríguez',
        'usuario' => 'sofia_r',
        'email' => 'sofia.art@gmail.com',
        'especialidad' => 'Escultura',
        'esp_id' => 'escultura',
        'zona' => 'Comuna 13 - San Javier',
        'estado' => 'Verificado',
        'obras_publicadas' => 8,
        'fecha_registro' => '2026-10-15',
        'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=150&q=80'
    ],
    [
        'id' => 3,
        'nombre' => 'Carlos Mendoza',
        'usuario' => 'carlos_m',
        'email' => 'carlos.m@gmail.com',
        'especialidad' => 'Música',
        'esp_id' => 'musica',
        'zona' => 'Comuna 10 - La Candelaria',
        'estado' => 'Pendiente Verificación',
        'obras_publicadas' => 3,
        'fecha_registro' => '2026-10-12',
        'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=150&q=80'
    ],
    [
        'id' => 4,
        'nombre' => 'Mateo Maloka',
        'usuario' => 'malokasunset',
        'email' => 'maloka@poliart.com',
        'especialidad' => 'Artes Escénicas',
        'esp_id' => 'escenicas',
        'zona' => 'Comuna 11 - Laureles',
        'estado' => 'Verificado',
        'obras_publicadas' => 11,
        'fecha_registro' => '2026-09-20',
        'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=150&q=80'
    ],
    [
        'id' => 5,
        'nombre' => 'Valeria Gómez',
        'usuario' => 'valeria_words',
        'email' => 'valeria@poliart.com',
        'especialidad' => 'Escritura',
        'esp_id' => 'escritura',
        'zona' => 'Comuna 14 - El Poblado',
        'estado' => 'Verificado',
        'obras_publicadas' => 6,
        'fecha_registro' => '2026-08-14',
        'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'
    ]
];*/

try {
    $stmtArtistas = getDB()->query(
        "SELECT a.id_artista, u.nombre, u.apellido, u.nombre_usuario, u.correo,
                u.estado, u.creado_en, u.avatar_path, z.nombre AS zona, z.tipo AS tipo_zona,
                a.especialidad,
                (SELECT COUNT(*) FROM me_gusta mg INNER JOIN obra og ON og.id_obra = mg.id_obra WHERE og.id_artista = a.id_artista) AS me_gusta,
                (SELECT COUNT(*) FROM borrador_obra bo WHERE bo.id_artista = a.id_artista) AS borradores,
                (SELECT COUNT(*) FROM obra o WHERE o.id_artista = a.id_artista AND o.estado_publicacion = 'publicada') AS obras_publicadas
         FROM artista a
         INNER JOIN usuario u ON u.id_usuario = a.id_usuario
         LEFT JOIN zona z ON z.id_zona = u.id_zona
         ORDER BY u.creado_en DESC"
    );
    $artistas_lista = array_map(static function (array $artista): array {
        $especialidad = mb_strtolower((string)$artista['especialidad'], 'UTF-8');
        $espId = strpos($especialidad, 'música') !== false || strpos($especialidad, 'musica') !== false
            ? 'musica'
            : (strpos($especialidad, 'escen') !== false
                ? 'escenicas'
                : (strpos($especialidad, 'escrit') !== false
                    ? 'escritura'
                    : (strpos($especialidad, 'escult') !== false ? 'escultura' : 'dibujo_pintura')));
        $zona = $artista['zona']
            ? ($artista['tipo_zona'] === 'comuna' ? 'Comuna ' : 'Corregimiento ') . $artista['zona']
            : 'Sin zona';
        return [
            'id' => (int)$artista['id_artista'],
            'nombre' => trim($artista['nombre'] . ' ' . $artista['apellido']),
            'usuario' => $artista['nombre_usuario'],
            'email' => $artista['correo'],
            'especialidad' => $artista['especialidad'] ?? 'Sin especialidad',
            'esp_id' => $espId,
            'zona' => $zona,
            'estado' => $artista['estado'] === 'activo' ? 'Activo' : 'Suspendido',
            'obras_publicadas' => (int)$artista['obras_publicadas'],
            'fecha_registro' => date('Y-m-d', strtotime($artista['creado_en'])),
            'avatar' => is_string($artista['avatar_path'])
                && strpos($artista['avatar_path'], '/poliart/uploads/avatars/') === 0
                ? $artista['avatar_path']
                : '',
            'me_gusta' => (int)$artista['me_gusta'],
            'borradores' => (int)$artista['borradores'],
        ];
    }, $stmtArtistas->fetchAll());
    $db = getDB();
    $stmtProduccion = $db->query("SELECT DATE_FORMAT(creado_en, '%Y-%m') AS periodo, COUNT(*) AS publicadas FROM obra WHERE estado_publicacion = 'publicada' GROUP BY periodo ORDER BY periodo");
    $produccion_tendencia = array_map(static fn(array $row): array => ['periodo' => $row['periodo'], 'publicadas' => (int)$row['publicadas']], $stmtProduccion->fetchAll());
    $stmtBorradores = $db->query("SELECT DATE_FORMAT(creado_en, '%Y-%m') AS periodo, COUNT(*) AS borradores FROM borrador_obra GROUP BY periodo ORDER BY periodo");
    foreach ($stmtBorradores->fetchAll() as $row) {
        $found = false;
        foreach ($produccion_tendencia as &$period) {
            if ($period['periodo'] === $row['periodo']) { $period['borradores'] = (int)$row['borradores']; $found = true; break; }
        }
        unset($period);
        if (!$found) $produccion_tendencia[] = ['periodo' => $row['periodo'], 'publicadas' => 0, 'borradores' => (int)$row['borradores']];
    }
    usort($produccion_tendencia, static fn(array $a, array $b): int => strcmp($a['periodo'], $b['periodo']));
    foreach ($produccion_tendencia as &$period) $period['borradores'] = (int)($period['borradores'] ?? 0);
    unset($period);
    $top_artistas = array_map(static fn(array $artist): array => ['usuario' => $artist['usuario'], 'me_gusta' => $artist['me_gusta']], $artistas_lista);
    usort($top_artistas, static fn(array $a, array $b): int => $b['me_gusta'] <=> $a['me_gusta']);
    $top_artistas = array_slice($top_artistas, 0, 10);
    $impacto_artistas['proyectos'] = (int)$db->query("SELECT COUNT(*) FROM proyecto_colaborativo")->fetchColumn();
    $impacto_artistas['invitaciones'] = (int)$db->query("SELECT COUNT(*) FROM participante_colaboracion")->fetchColumn();
} catch (PDOException $e) {
    error_log('Error al cargar el panel de artistas: ' . $e->getMessage());
    $artistas_lista = [];
    $produccion_tendencia = [];
    $top_artistas = [];
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
                🎨 Dashboard Interactivo de Artistas
            </h1>
        </div>

        <div class="search-box-bento">
            <span style="margin-right: 0.5rem; color: var(--text-muted);">🔍</span>
            <input type="text" id="bentoSearch" placeholder="Buscar por nombre, artista o técnica..." onkeyup="updateDashboardView()">
        </div>
    </div>

    <!-- BENTO GRID SYSTEM -->
    <div class="bento-grid">
        
        <!-- CARD 1: SELECCIÓN DE ARTES (GRÁFICO INTERACTIVO PARA FILTRAR TODO) -->
        <div class="bento-card col-span-5">
            <div class="bento-card-title">
                <span>Disciplinas de Arte</span>
                <span id="selectedArtBadge" style="font-size: 0.72rem; color: var(--brand-orange); font-weight: 700;">Todas las Artes</span>
            </div>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin: -0.5rem 0 0.5rem 0;">Haz clic en una sección para filtrar los demás gráficos</p>
            <div style="height: 190px;">
                <canvas id="artSelectionChart"></canvas>
            </div>
        </div>

        <!-- CARD 2: TARJETA DE REGISTROS Y MÉTRICAS CLAVE -->
        <div class="bento-card col-span-3">
            <div class="bento-card-title">
                <span>Registros y KPIs</span>
                <button onclick="resetFilters()" style="background: transparent; border: 1px solid var(--border-subtle); color: var(--text-muted); font-size: 0.7rem; border-radius: 0.4rem; padding: 0.2rem 0.4rem; cursor: pointer;">Reset</button>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-top: 0.2rem;">
                <div>
                    <div class="kpi-number" id="kpiTotalNum" style="color: var(--brand-orange);">0</div>
                    <div class="kpi-label">Total Artistas</div>
                </div>
                
                <div style="display: flex; justify-content: space-between;">
                    <div>
                        <div class="kpi-number" id="kpiVerificados" style="color: #22c55e; font-size: 1.5rem;">0</div>
                        <div class="kpi-label">Activos</div>
                    </div>
                    <div>
                        <div class="kpi-number" id="kpiPendientes" style="color: #f59e0b; font-size: 1.5rem;">0</div>
                        <div class="kpi-label">Pendientes</div>
                    </div>
                </div>

                <div>
                    <div class="kpi-number" id="kpiObras" style="color: #3b82f6; font-size: 1.4rem;">0</div>
                    <div class="kpi-label">Obras Publicadas</div>
                </div>
                <div style="display:flex;justify-content:space-between;gap:.75rem;border-top:1px solid var(--border-subtle);padding-top:.65rem;">
                    <div><div class="kpi-number" style="font-size:1.25rem;color:#a855f7;"><?= (int)$impacto_artistas['proyectos'] ?></div><div class="kpi-label">Proyectos</div></div>
                    <div><div class="kpi-number" style="font-size:1.25rem;color:#ec4899;"><?= (int)$impacto_artistas['invitaciones'] ?></div><div class="kpi-label">Invitaciones</div></div>
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

        <!-- CARD 4: GRÁFICO TENDENCIA Y ACTIVIDAD HISTÓRICA -->
        <div class="bento-card col-span-12">
            <div class="bento-card-title">
                <span>Flujo de Registros & Obras Publicadas</span>
                <span id="activeFilterLabel" style="font-size: 0.75rem; color: var(--text-muted);">Filtrado dinámico</span>
            </div>
            <div style="height: 220px;">
                <canvas id="mainSmoothChart"></canvas>
            </div>
        </div>

        <div class="bento-card col-span-12">
            <div class="bento-card-title"><span>Artistas más populares</span><span style="font-size:.75rem;color:var(--text-muted);">Me gusta recibidos</span></div>
            <div style="height:220px;"><canvas id="topArtistsChart"></canvas></div>
        </div>

    </div>

</div>

<script>
// DATOS BASE DE ARTISTAS
const rawArtistas = <?= json_encode($artistas_lista) ?>;
const especialidadesOficiales = <?= json_encode($especialidades_oficiales) ?>;
const productionData = <?= json_encode($produccion_tendencia) ?>;
const topArtistsData = <?= json_encode($top_artistas) ?>;

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

let artSelectionChart, mainSmoothChart, topArtistsChart;

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    renderCalendar();
    updateDashboardView();
});

function initCharts() {
    // 1. GRÁFICO DE DONA INTERACTIVO PARA FILTRAR POR ARTES
    const artCtx = document.getElementById('artSelectionChart').getContext('2d');
    artSelectionChart = new Chart(artCtx, {
        type: 'doughnut',
        data: {
            labels: Object.values(especialidadesOficiales),
            datasets: [{
                data: Object.keys(especialidadesOficiales).map(key => rawArtistas.filter(artist => artist.esp_id === key).length),
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

    // 2. GRÁFICO DE TENDENCIA Y REGISTROS
    const smoothCtx = document.getElementById('mainSmoothChart').getContext('2d');
    mainSmoothChart = new Chart(smoothCtx, {
        type: 'line',
        data: {
            labels: productionData.map(row => row.periodo),
            datasets: [
                {
                    label: 'Obras Publicadas',
                    data: productionData.map(row => row.publicadas),
                    borderColor: orangeBrand,
                    backgroundColor: 'rgba(249, 115, 22, 0.12)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4
                },
                {
                    label: 'Registros de Artistas',
                    data: productionData.map(row => row.borradores),
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

    const topCtx = document.getElementById('topArtistsChart').getContext('2d');
    topArtistsChart = new Chart(topCtx, {
        type: 'bar',
        data: { labels: topArtistsData.map(row => '@' + row.usuario), datasets: [{ label: 'Me gusta', data: topArtistsData.map(row => row.me_gusta), backgroundColor: orangeBrand, borderRadius: 6 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: '#d1d5db' }, grid: { display: false } }, y: { beginAtZero: true, ticks: { color: '#9ca3af', precision: 0 }, grid: { color: 'rgba(255,255,255,.05)' } } } }
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

// ACTUALIZACIÓN DE DATOS
function updateDashboardView() {
    const searchVal = document.getElementById('bentoSearch').value.toLowerCase();
    const currentMonthPrefix = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}`;

    const filtered = rawArtistas.filter(art => {
        const matchesSearch = art.nombre.toLowerCase().includes(searchVal) ||
                              art.usuario.toLowerCase().includes(searchVal) ||
                              art.zona.toLowerCase().includes(searchVal) ||
                              art.especialidad.toLowerCase().includes(searchVal);
                              
        let matchesDate = true;
        if (selectedFullDate) {
            matchesDate = (art.fecha_registro === selectedFullDate);
        } else {
            matchesDate = art.fecha_registro.startsWith(currentMonthPrefix);
        }

        const matchesEsp = !selectedEspId || art.esp_id === selectedEspId;

        return matchesSearch && matchesDate && matchesEsp;
    });

    // Actualizar Tarjetas de Registros y KPIs
    document.getElementById('kpiTotalNum').textContent = filtered.length;
    const verifCount = filtered.filter(a => a.estado === 'Activo').length;
    document.getElementById('kpiVerificados').textContent = verifCount;
    document.getElementById('kpiPendientes').textContent = filtered.length - verifCount;
    
    const totalObras = filtered.reduce((acc, a) => acc + a.obras_publicadas, 0);
    document.getElementById('kpiObras').textContent = totalObras;

    // Badges descriptivos
    if (selectedEspId) {
        document.getElementById('selectedArtBadge').textContent = especialidadesOficiales[selectedEspId];
    } else {
        document.getElementById('selectedArtBadge').textContent = 'Todas las Artes';
    }

    // Animar gráfico de tendencia con los datos filtrados
    if (mainSmoothChart) {
        mainSmoothChart.data.datasets[1].data = [
            0, 0, 0, 0, 0, 0, 0, 
            filtered.length > 2 ? 1 : 0, 
            filtered.length > 4 ? 2 : 1, 
            filtered.length
        ];
        mainSmoothChart.update();
    }
}
</script>

