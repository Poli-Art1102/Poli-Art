<?php
$tipo = basename($_SERVER['PHP_SELF'], '.php');
$titulo = [
    'dashboard_clientes' => 'Clientes',
    'dashboard_artistas' => 'Artistas',
    'dashboard_reportes' => 'Reportes y moderación',
    'dashboard_zonas' => 'Zonas y comunas',
][$tipo] ?? 'Analítica';
$subtitulo = [
    'dashboard_clientes' => 'Conoce qué exploran, guardan y comentan los clientes.',
    'dashboard_artistas' => 'Mide la producción, alcance y colaboración de los artistas.',
    'dashboard_reportes' => 'Supervisa la convivencia y el estado de las denuncias.',
    'dashboard_zonas' => 'Compara la participación cultural por territorio.',
][$tipo] ?? 'Información actual de Poli-Art.';

$esc = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$labels = [];
$datasets = [];
$rows = [];
$kpis = [];

if ($tipo === 'dashboard_clientes') {
    $kpis = [
        ['label' => 'Clientes registrados', 'value' => count($clientes_lista), 'tone' => 'orange'],
        ['label' => 'Clientes activos', 'value' => count(array_filter($clientes_lista, static fn($row) => $row['estado'] === 'Activo')), 'tone' => 'blue'],
        ['label' => 'Conversaciones', 'value' => $cliente_kpis['conversaciones'], 'tone' => 'purple'],
        ['label' => 'Favoritos', 'value' => $cliente_kpis['favoritos'], 'tone' => 'pink'],
    ];
    $interestGroups = [
        'Ilustración & Pintura' => 0,
        'Escultura & 3D' => 0,
        'Música & Audio' => 0,
        'Artes Escénicas' => 0,
        'Literatura & Escritura' => 0,
    ];
    foreach ($interes_categorias as $category) {
        $slug = strtolower((string)$category['slug']);
        $group = str_contains($slug, 'ilustr') || str_contains($slug, 'acuarela') || str_contains($slug, 'oleo')
            ? 'Ilustración & Pintura'
            : (str_contains($slug, 'escultura') || str_contains($slug, 'ceramica')
                ? 'Escultura & 3D'
                : (str_contains($slug, 'musica') || str_contains($slug, 'rock') || str_contains($slug, 'hip-hop') || str_contains($slug, 'electronica')
                    ? 'Música & Audio'
                    : (str_contains($slug, 'escen') || str_contains($slug, 'teatro') || str_contains($slug, 'danza')
                        ? 'Artes Escénicas'
                        : (str_contains($slug, 'literatura') || str_contains($slug, 'novela') || str_contains($slug, 'poesia')
                            ? 'Literatura & Escritura' : null))));
        if ($group !== null) $interestGroups[$group] += (int)$category['total'];
    }
    $labels = array_keys($interestGroups);
    $datasets = [
        'interestLabels' => $labels,
        'interest' => array_values($interestGroups),
        'trendLabels' => array_map(static fn($row) => $row['periodo'], $clientes_tendencia),
        'trendClients' => array_map(static fn($row) => (int)$row['clientes'], $clientes_tendencia),
        'topLabels' => array_map(static fn($row) => '@' . $row['usuario'], $top_clientes),
        'topValues' => array_map(static fn($row) => (int)$row['interacciones'], $top_clientes),
    ];
    $rows = array_map(static fn($row) => ['principal' => '@' . $row['usuario'], 'secondary' => $row['nombre'], 'value' => $row['favoritos'] + $row['mensajes'], 'valueLabel' => 'interacciones'], $clientes_lista);
} elseif ($tipo === 'dashboard_artistas') {
    $kpis = [
        ['label' => 'Artistas registrados', 'value' => count($artistas_lista), 'tone' => 'orange'],
        ['label' => 'Obras publicadas', 'value' => array_sum(array_column($artistas_lista, 'obras_publicadas')), 'tone' => 'blue'],
        ['label' => 'Proyectos colaborativos', 'value' => $impacto_artistas['proyectos'], 'tone' => 'purple'],
        ['label' => 'Invitaciones', 'value' => $impacto_artistas['invitaciones'], 'tone' => 'pink'],
    ];
    $specialtyCounts = [];
    foreach ($artistas_lista as $artist) $specialtyCounts[$artist['especialidad'] ?: 'Sin especialidad'] = ($specialtyCounts[$artist['especialidad'] ?: 'Sin especialidad'] ?? 0) + 1;
    $datasets = [
        'specialtyLabels' => array_keys($specialtyCounts), 'specialtyValues' => array_values($specialtyCounts),
        'trendLabels' => array_map(static fn($row) => $row['periodo'], $produccion_tendencia),
        'trendPublished' => array_map(static fn($row) => (int)$row['publicadas'], $produccion_tendencia),
        'trendDrafts' => array_map(static fn($row) => (int)$row['borradores'], $produccion_tendencia),
        'topLabels' => array_map(static fn($row) => '@' . $row['usuario'], $top_artistas),
        'topValues' => array_map(static fn($row) => (int)$row['me_gusta'], $top_artistas),
    ];
    $rows = array_map(static fn($row) => ['principal' => '@' . $row['usuario'], 'secondary' => $row['especialidad'], 'value' => $row['me_gusta'], 'valueLabel' => 'me gusta'], $artistas_lista);
} elseif ($tipo === 'dashboard_reportes') {
    $total = count($reportes_lista);
    $kpis = [
        ['label' => 'Reportes recibidos', 'value' => $total, 'tone' => 'orange'],
        ['label' => 'Pendientes', 'value' => count(array_filter($reportes_lista, static fn($row) => strtolower($row['estado']) === 'pendiente')), 'tone' => 'yellow'],
        ['label' => 'Resueltos', 'value' => count(array_filter($reportes_lista, static fn($row) => strtolower($row['estado']) === 'resuelto')), 'tone' => 'green'],
        ['label' => 'Ignorados', 'value' => count(array_filter($reportes_lista, static fn($row) => strtolower($row['estado']) === 'ignorado')), 'tone' => 'purple'],
    ];
    $motives = [];
    foreach ($reportes_lista as $report) $motives[$report['tipo']] = ($motives[$report['tipo']] ?? 0) + 1;
    $states = ['Pendiente' => 0, 'Resuelto' => 0, 'Ignorado' => 0];
    foreach ($reportes_lista as $report) { $key = ucfirst(strtolower($report['estado'])); if (isset($states[$key])) $states[$key]++; }
    $months = [];
    foreach ($reportes_lista as $report) { $month = substr($report['fecha'], 0, 7); $months[$month] = ($months[$month] ?? 0) + 1; }
    ksort($months);
    $datasets = ['motiveLabels' => array_keys($motives), 'motiveValues' => array_values($motives), 'stateLabels' => array_keys($states), 'stateValues' => array_values($states), 'trendLabels' => array_keys($months), 'trendValues' => array_values($months)];
    $rows = array_map(static fn($row) => ['principal' => $row['tipo'], 'secondary' => $row['especialidad'], 'value' => ucfirst($row['estado']), 'valueLabel' => $row['fecha']], $reportes_lista);
} else {
    $kpis = [
        ['label' => 'Zonas registradas', 'value' => count($zonas_medellin), 'tone' => 'orange'],
        ['label' => 'Artistas ubicados', 'value' => array_sum(array_column($zonas_medellin, 'artistas')), 'tone' => 'purple'],
        ['label' => 'Clientes ubicados', 'value' => array_sum(array_column($zonas_medellin, 'clientes')), 'tone' => 'blue'],
        ['label' => 'Obras publicadas', 'value' => array_sum(array_column($zonas_medellin, 'obras')), 'tone' => 'green'],
    ];
    $rural = $urbana = 0; foreach ($zonas_medellin as $zone) { if (stripos($zone['nombre'], 'corregimiento') === 0) $rural += $zone['artistas'] + $zone['clientes']; else $urbana += $zone['artistas'] + $zone['clientes']; }
    usort($zonas_medellin, static fn($a, $b) => ($b['artistas'] + $b['clientes'] + $b['obras']) <=> ($a['artistas'] + $a['clientes'] + $a['obras']));
    $datasets = ['zoneLabels' => array_map(static fn($row) => $row['nombre'], $zonas_medellin), 'zoneArtists' => array_map(static fn($row) => (int)$row['artistas'], $zonas_medellin), 'zoneClients' => array_map(static fn($row) => (int)$row['clientes'], $zonas_medellin), 'territoryValues' => [$urbana, $rural], 'territoryLabels' => ['Zona urbana', 'Zona rural']];
    $rows = array_map(static fn($row) => ['principal' => $row['nombre'], 'secondary' => ucfirst($row['fecha_actividad'] ?: 'Sin actividad'), 'value' => $row['obras'], 'valueLabel' => 'obras'], $zonas_medellin);
}
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.modern-dashboard{width:100%;max-width:none;margin:0;align-self:stretch;padding:1rem 2rem 2.5rem;color:var(--text-main);box-sizing:border-box}
.modern-head{display:flex;justify-content:space-between;align-items:end;gap:1rem;margin-bottom:1.25rem;flex-wrap:wrap}.modern-head h1{margin:.35rem 0 .25rem;font-size:clamp(1.45rem,2.5vw,2.2rem);font-weight:900}.modern-head p{margin:0;color:var(--text-muted)}
.modern-back{display:inline-flex;align-items:center;color:var(--brand-orange);font-weight:800;text-decoration:none;font-size:.85rem;padding:.45rem .7rem;border-radius:.55rem;transition:background-color .2s ease,color .2s ease}.modern-back:hover{background:var(--hover-item);color:var(--text-main)}.modern-search{background:var(--bg-surface);border:1px solid var(--border-subtle);border-radius:.8rem;color:var(--text-main);padding:.75rem 1rem;min-width:250px;outline:none}.modern-actions{display:flex;gap:.6rem;align-items:center}.modern-reset{border:1px solid var(--border-subtle);background:transparent;color:var(--text-muted);border-radius:.7rem;padding:.7rem .9rem;cursor:pointer;font-weight:700}
.metric-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1rem}.metric-card,.chart-card,.table-card{background:var(--bg-surface);border:1px solid var(--border-subtle);border-radius:1rem;padding:1.15rem;box-shadow:0 8px 25px rgba(0,0,0,.12)}.metric-card small{display:block;color:var(--text-muted);font-weight:800;text-transform:uppercase;font-size:.7rem}.metric-card strong{display:block;font-size:2rem;margin-top:.4rem}.tone-orange strong{color:#ff9d73}.tone-blue strong{color:#38bdf8}.tone-purple strong{color:#c084fc}.tone-pink strong{color:#f472b6}.tone-yellow strong{color:#facc15}.tone-green strong{color:#4ade80}
.chart-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.chart-card.full{grid-column:1/-1}.chart-title{display:flex;justify-content:space-between;gap:1rem;align-items:center;margin-bottom:.8rem;font-weight:900}.chart-title span{font-size:.78rem;color:var(--text-muted);font-weight:700}.chart-box{height:315px;position:relative}.table-card{margin-top:1rem}.table-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem}.table-head h2{font-size:1rem;margin:0}.data-row{display:grid;grid-template-columns:1.4fr 1.4fr .8fr;gap:1rem;padding:.75rem 0;border-bottom:1px solid rgba(255,255,255,.08);align-items:center}.data-row:last-child{border-bottom:0}.data-row small{color:var(--text-muted)}.data-row strong{font-size:.9rem}.empty-state{padding:1.5rem 0;color:var(--text-muted)}
@media(max-width:900px){.metric-grid{grid-template-columns:repeat(2,1fr)}.chart-grid{grid-template-columns:1fr}.chart-card.full{grid-column:auto}.modern-dashboard{padding:1rem}.data-row{grid-template-columns:1fr 1fr}.data-row strong:last-child{text-align:right}}
@media(max-width:520px){.metric-grid{grid-template-columns:1fr 1fr;gap:.6rem}.metric-card{padding:.85rem}.metric-card strong{font-size:1.5rem}.modern-search{width:100%;min-width:0}.modern-actions{width:100%}.modern-actions .modern-search{flex:1}}
</style>
<div class="modern-dashboard">
  <header class="modern-head"><div><h1><?= $esc($titulo) ?></h1><p><?= $esc($subtitulo) ?></p></div><div class="modern-actions"><input id="dashboardSearch" class="modern-search" type="search" placeholder="Buscar en este dashboard..."><button class="modern-reset" id="dashboardReset">Limpiar</button></div></header>
  <section class="metric-grid"><?php foreach ($kpis as $kpi): ?><article class="metric-card tone-<?= $esc($kpi['tone']) ?>"><small><?= $esc($kpi['label']) ?></small><strong><?= number_format((int)$kpi['value']) ?></strong></article><?php endforeach; ?></section>
  <section class="chart-grid">
    <?php if ($tipo === 'dashboard_clientes'): ?><article class="chart-card"><div class="chart-title">¿Qué categorías interesan más?<span>Me gusta de clientes</span></div><div class="chart-box"><canvas id="chartOne"></canvas></div></article><article class="chart-card"><div class="chart-title">Registro de clientes<span>Por mes</span></div><div class="chart-box"><canvas id="chartTwo"></canvas></div></article><article class="chart-card full"><div class="chart-title">Clientes más activos<span>Me gusta + mensajes</span></div><div class="chart-box"><canvas id="chartThree"></canvas></div></article>
    <?php elseif ($tipo === 'dashboard_artistas'): ?><article class="chart-card"><div class="chart-title">¿De qué especialidad son?<span>Artistas registrados</span></div><div class="chart-box"><canvas id="chartOne"></canvas></div></article><article class="chart-card"><div class="chart-title">Producción creativa<span>Publicadas vs borradores</span></div><div class="chart-box"><canvas id="chartTwo"></canvas></div></article><article class="chart-card full"><div class="chart-title">Artistas más populares<span>Me gusta recibidos</span></div><div class="chart-box"><canvas id="chartThree"></canvas></div></article>
    <?php elseif ($tipo === 'dashboard_reportes'): ?><article class="chart-card"><div class="chart-title">Motivos de reporte<span>Qué está denunciando la comunidad</span></div><div class="chart-box"><canvas id="chartOne"></canvas></div></article><article class="chart-card"><div class="chart-title">Estado de los reportes<span>Seguimiento de moderación</span></div><div class="chart-box"><canvas id="chartTwo"></canvas></div></article><article class="chart-card full"><div class="chart-title">Reportes recibidos<span>Por mes</span></div><div class="chart-box"><canvas id="chartThree"></canvas></div></article>
    <?php else: ?><article class="chart-card"><div class="chart-title">Participación urbana y rural<span>Usuarios ubicados</span></div><div class="chart-box"><canvas id="chartOne"></canvas></div></article><article class="chart-card"><div class="chart-title">Artistas y clientes por zona<span>Comparación territorial</span></div><div class="chart-box"><canvas id="chartTwo"></canvas></div></article><?php endif; ?>
  </section>
  <section class="table-card"><div class="table-head"><h2><?= $tipo === 'dashboard_zonas' ? 'Ranking de zonas' : 'Detalle de registros' ?></h2><small id="resultCount" style="color:var(--text-muted)"></small></div><div id="dataRows"><?php foreach ($rows as $row): ?><div class="data-row" data-search="<?= $esc(strtolower($row['principal'].' '.$row['secondary'])) ?>"><strong><?= $esc($row['principal']) ?></strong><small><?= $esc($row['secondary']) ?></small><strong><?= $esc($row['value']) ?> <small><?= $esc($row['valueLabel']) ?></small></strong></div><?php endforeach; ?><?php if (!$rows): ?><div class="empty-state">Aún no hay datos suficientes para mostrar.</div><?php endif; ?></div></section>
</div>
<script>
const data=<?= json_encode($datasets, JSON_UNESCAPED_UNICODE) ?>;const colors=['#ff9d73','#a855f7','#38bdf8','#facc15','#4ade80','#f472b6','#fb7185','#818cf8'];
const common={responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'#e5e7eb',font:{weight:'700'}}}},scales:{x:{ticks:{color:'#cbd5e1'},grid:{display:false}},y:{beginAtZero:true,ticks:{color:'#cbd5e1',precision:0},grid:{color:'rgba(255,255,255,.08)'}}}};
function chart(id,type,labels,datasets,options={}){const el=document.getElementById(id);if(!el)return;new Chart(el,{type,data:{labels,datasets},options:{...common,...options}})}
<?php if ($tipo === 'dashboard_clientes'): ?>chart('chartOne','bar',data.interestLabels,[{label:'Me gusta',data:data.interest,backgroundColor:'#ff9d73',borderRadius:6}],{indexAxis:'y'});chart('chartTwo','line',data.trendLabels,[{label:'Clientes nuevos',data:data.trendClients,borderColor:'#38bdf8',backgroundColor:'rgba(56,189,248,.15)',fill:true,tension:.35}]);chart('chartThree','bar',data.topLabels,[{label:'Interacciones',data:data.topValues,backgroundColor:'#a855f7',borderRadius:6}],{indexAxis:'y'});
<?php elseif ($tipo === 'dashboard_artistas'): ?>chart('chartOne','bar',data.specialtyLabels,[{label:'Artistas',data:data.specialtyValues,backgroundColor:'#ff9d73',borderRadius:6}],{indexAxis:'y'});chart('chartTwo','line',data.trendLabels,[{label:'Obras publicadas',data:data.trendPublished,borderColor:'#ff9d73',backgroundColor:'rgba(255,157,115,.15)',fill:true,tension:.35},{label:'Borradores',data:data.trendDrafts,borderColor:'#a855f7',tension:.35}]);chart('chartThree','bar',data.topLabels,[{label:'Me gusta',data:data.topValues,backgroundColor:'#a855f7',borderRadius:6}],{indexAxis:'y'});
<?php elseif ($tipo === 'dashboard_reportes'): ?>chart('chartOne','bar',data.motiveLabels,[{label:'Reportes',data:data.motiveValues,backgroundColor:'#f87171',borderRadius:6}],{indexAxis:'y'});chart('chartTwo','bar',data.stateLabels,[{label:'Cantidad',data:data.stateValues,backgroundColor:['#facc15','#4ade80','#a855f7'],borderRadius:6}],{});chart('chartThree','line',data.trendLabels,[{label:'Reportes',data:data.trendValues,borderColor:'#f87171',backgroundColor:'rgba(248,113,113,.15)',fill:true,tension:.35}]);
<?php else: ?>chart('chartOne','doughnut',data.territoryLabels,[{data:data.territoryValues,backgroundColor:['#38bdf8','#a855f7'],borderColor:'#4d220f',borderWidth:2}],{scales:{}});chart('chartTwo','bar',data.zoneLabels,[{label:'Artistas',data:data.zoneArtists,backgroundColor:'#ff9d73',borderRadius:5},{label:'Clientes',data:data.zoneClients,backgroundColor:'#38bdf8',borderRadius:5}],{scales:{x:{ticks:{color:'#cbd5e1',maxRotation:60,minRotation:30},grid:{display:false}},y:common.scales.y}});
<?php endif; ?>
const search=document.getElementById('dashboardSearch'),rows=[...document.querySelectorAll('.data-row')],count=document.getElementById('resultCount');function filterRows(){const q=search.value.toLowerCase().trim();let visible=0;rows.forEach(row=>{const show=!q||row.dataset.search.includes(q);row.style.display=show?'grid':'none';if(show)visible++});count.textContent=visible+' resultado'+(visible===1?'':'s')}search.addEventListener('input',filterRows);document.getElementById('dashboardReset').addEventListener('click',()=>{search.value='';filterRows()});filterRows();
</script>
