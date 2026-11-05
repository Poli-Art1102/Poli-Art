<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['administrador']);

$project_base = '/poliart/';
$pdo = getDB();

// ── Acción AJAX: suspender / reactivar ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_accion'])) {
    header('Content-Type: application/json; charset=utf-8');
    $accion     = $_POST['ajax_accion'] ?? '';
    $id_usuario = (int)($_POST['id_usuario'] ?? 0);

    if (!$id_usuario) {
        echo json_encode(['ok' => false, 'error' => 'ID inválido.']); exit;
    }
    $check = $pdo->prepare('SELECT rol FROM usuario WHERE id_usuario = ?');
    $check->execute([$id_usuario]);
    $target = $check->fetch();
    if (!$target) {
        echo json_encode(['ok' => false, 'error' => 'Usuario no encontrado.']); exit;
    }
    if ($target['rol'] === 'administrador') {
        echo json_encode(['ok' => false, 'error' => 'No se puede suspender a un administrador.']); exit;
    }
    if ($accion === 'suspender') {
        $pdo->prepare("UPDATE usuario SET estado='suspendido' WHERE id_usuario=?")->execute([$id_usuario]);
        echo json_encode(['ok' => true, 'nuevo_estado' => 'suspendido']);
    } elseif ($accion === 'reactivar') {
        $pdo->prepare("UPDATE usuario SET estado='activo' WHERE id_usuario=?")->execute([$id_usuario]);
        echo json_encode(['ok' => true, 'nuevo_estado' => 'activo']);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Acción desconocida.']);
    }
    exit;
}

// ── Métricas ──────────────────────────────────────────────────
try {
    $total_clientes    = (int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE rol='cliente'")->fetchColumn();
    $total_artistas    = (int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE rol='artista'")->fetchColumn();
    $total_suspendidos = (int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE estado='suspendido'")->fetchColumn();
    $total_nuevos      = (int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND rol != 'administrador'")->fetchColumn();
    $total_usuarios    = $total_clientes + $total_artistas;
    $pct_clientes      = $total_usuarios > 0 ? round(($total_clientes / $total_usuarios) * 100) : 0;
    $pct_artistas      = $total_usuarios > 0 ? round(($total_artistas / $total_usuarios) * 100) : 0;
} catch (PDOException $e) {
    $total_clientes = $total_artistas = $total_suspendidos = $total_nuevos = 0;
    $pct_clientes = $pct_artistas = 0;
    $total_usuarios = 0;
}

// ── Distribución por todas las zonas, comunas y corregimientos ──
$zonas = [];
try {
    $totalUsuariosReales = (int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE rol IN ('cliente', 'artista')")->fetchColumn();
    $stmtZonasReales = $pdo->query(
        "SELECT z.nombre, COUNT(u.id_usuario) AS cantidad 
         FROM zona z 
         LEFT JOIN usuario u ON u.id_zona = z.id_zona AND u.rol IN ('cliente', 'artista') 
         GROUP BY z.id_zona, z.nombre 
         ORDER BY cantidad DESC, z.nombre ASC"
    );
    foreach ($stmtZonasReales->fetchAll() as $zonaReal) {
        $cantidadReal = (int)$zonaReal['cantidad'];
        $zonas[] = [
            'nombre'     => $zonaReal['nombre'],
            'cantidad'   => $cantidadReal,
            'porcentaje' => $totalUsuariosReales > 0 ? round(($cantidadReal / $totalUsuariosReales) * 100, 1) : 0,
        ];
    }
} catch (PDOException $e) {
    $zonas = [];
}

// ── Lista de usuarios con paginación ──────────────────────────
$pagina   = max(1, (int)($_GET['pagina'] ?? 1));
$por_pag  = 15;
$offset   = ($pagina - 1) * $por_pag;

$where  = ["u.rol != 'administrador'"];
$params = [];
$wsql = 'WHERE ' . implode(' AND ', $where);

try {
    $sc = $pdo->prepare("SELECT COUNT(*) FROM usuario u $wsql");
    $sc->execute($params);
    $total_filtrado = (int)$sc->fetchColumn();
    $total_paginas  = max(1, ceil($total_filtrado / $por_pag));

    $su = $pdo->prepare(
        "SELECT u.id_usuario, u.nombre, u.apellido, u.nombre_usuario, u.correo,
                u.rol, u.estado, u.creado_en, u.avatar_path,
                z.nombre AS zona, a.especialidad
         FROM usuario u
         LEFT JOIN zona    z ON z.id_zona    = u.id_zona
         LEFT JOIN artista a ON a.id_usuario = u.id_usuario
         $wsql
         ORDER BY u.creado_en DESC
         LIMIT $por_pag OFFSET $offset"
    );
    $su->execute($params);
    $usuarios = $su->fetchAll();
} catch (PDOException $e) {
    $usuarios = [];
    $total_filtrado = 0;
    $total_paginas  = 1;
}

require_once __DIR__ . '/../admin/header.php';
?>

<style>
.dash-wrap {
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1.25rem;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
    backdrop-filter: blur(12px);
    max-width: 1200px;
    margin: 0 auto;
    box-sizing: border-box;
    width: 100%;
}
.ic {
    background-color: var(--hover-item);
    border: 1px solid var(--border-subtle);
    border-radius: 1rem;
    padding: 1.5rem;
}
.pb { background-color: var(--border-subtle); border-radius: 9999px; height: .6rem; overflow: hidden; }
.pf { background: linear-gradient(90deg, var(--brand-orange), #d45f2e); height: 100%; border-radius: 9999px; }
.tbl { width: 100%; border-collapse: collapse; text-align: left; }
.tbl th { padding: .75rem; font-size: .72rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border-subtle); white-space: nowrap; }
.tbl td { padding: .75rem; font-size: .85rem; color: var(--text-main); border-bottom: 1px solid var(--border-subtle); vertical-align: middle; }
.tbl tr:hover td { background-color: var(--hover-item); }
.badge { padding: .2rem .6rem; border-radius: 9999px; font-size: .7rem; font-weight: 700; white-space: nowrap; display: inline-block; }
.b-artista   { background: rgba(237,119,69,.15); color: var(--brand-orange); border: 1px solid rgba(237,119,69,.3); }
.b-cliente   { background: rgba(59,130,246,.15);  color: #3b82f6;            border: 1px solid rgba(59,130,246,.3); }
.b-activo    { background: rgba(34,197,94,.15);   color: #22c55e;            border: 1px solid rgba(34,197,94,.3); }
.b-suspendido{ background: rgba(239,68,68,.15);   color: #ef4444;            border: 1px solid rgba(239,68,68,.3); }
.b-inactivo  { background: rgba(107,114,128,.15); color: #6b7280;            border: 1px solid rgba(107,114,128,.3); }
.bsm {
    background: var(--hover-item);
    border: 1px solid var(--border-subtle);
    color: var(--text-main);
    padding: .35rem .75rem; border-radius: .45rem;
    font-size: .75rem; font-weight: 700; cursor: pointer;
    transition: all .15s; white-space: nowrap; font-family: inherit;
}
.bsm:hover        { border-color: var(--brand-orange); color: var(--brand-orange); }
.pgb {
    background: var(--hover-item); border: 1px solid var(--border-subtle);
    color: var(--text-main); padding: .4rem .9rem;
    border-radius: .5rem; font-size: .82rem; font-weight: 700;
    cursor: pointer; text-decoration: none; transition: all .15s;
}
.pgb:hover, .pgb.on { background: var(--brand-orange); color: #fff; border-color: var(--brand-orange); }
</style>

<div style="width:100%; padding:0 2rem 4rem; box-sizing:border-box;">
<div class="dash-wrap">

    <!-- ENCABEZADO -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:2rem; flex-wrap:wrap; gap:1rem;">
        <div>
            <a href="/poliart/app/admin/index.php" style="color:var(--text-muted); text-decoration:none; font-size:.85rem; font-weight:700;">← Volver al Panel</a>
            <h1 style="font-size:1.6rem; font-weight:800; color:var(--text-main); margin:.3rem 0 0;">👥 Gestionar Usuarios</h1>
            <p style="font-size:.875rem; color:var(--text-muted); margin:.25rem 0 0;">Usuarios registrados, roles, estados y distribución por zonas</p>
        </div>
        <span style="font-size:.8rem; color:var(--brand-orange); font-weight:700; background:var(--hover-item); padding:.4rem .9rem; border-radius:9999px; border:1px solid var(--border-subtle);">
            📊 Total: <?= $total_usuarios ?> usuarios
        </span>
    </div>

    <!-- MÉTRICAS -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1.25rem; margin-bottom:2rem;">

        <a href="/poliart/app/admin/vista_clientes.php"
           style="text-decoration:none; display:flex; flex-direction:column; justify-content:space-between; min-height:120px;
                  padding:1.5rem; background-color:var(--bg-surface); border:1.5px solid var(--border-subtle);
                  border-top:4px solid #3b82f6; border-radius:1.25rem; box-shadow:0 8px 24px rgba(0,0,0,.1);
                  backdrop-filter:blur(12px); transition:transform .2s, box-shadow .2s;"
           onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 32px rgba(59,130,246,.25)';"
           onmouseout="this.style.transform='';this.style.boxShadow='0 8px 24px rgba(0,0,0,.1)';">
            <span style="font-size:.72rem; font-weight:800; color:var(--text-muted); text-transform:uppercase; letter-spacing:.06em;">👤 Clientes</span>
            <span style="font-size:2.5rem; font-weight:900; line-height:1; color:#3b82f6; display:block; margin:.5rem 0;"><?= $total_clientes ?></span>
            <span style="font-size:.8rem; font-weight:700; color:var(--text-muted);"><?= $pct_clientes ?>% del total <span style="color:#3b82f6; float:right;">Ver →</span></span>
        </a>

        <a href="/poliart/app/admin/vista_artistas.php"
           style="text-decoration:none; display:flex; flex-direction:column; justify-content:space-between; min-height:120px;
                  padding:1.5rem; background-color:var(--bg-surface); border:1.5px solid var(--border-subtle);
                  border-top:4px solid var(--brand-orange); border-radius:1.25rem; box-shadow:0 8px 24px rgba(0,0,0,.1);
                  backdrop-filter:blur(12px); transition:transform .2s, box-shadow .2s;"
           onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 32px rgba(237,119,69,.25)';"
           onmouseout="this.style.transform='';this.style.boxShadow='0 8px 24px rgba(0,0,0,.1)';">
            <span style="font-size:.72rem; font-weight:800; color:var(--text-muted); text-transform:uppercase; letter-spacing:.06em;">🎨 Artistas</span>
            <span style="font-size:2.5rem; font-weight:900; line-height:1; color:var(--brand-orange); display:block; margin:.5rem 0;"><?= $total_artistas ?></span>
            <span style="font-size:.8rem; font-weight:700; color:var(--text-muted);"><?= $pct_artistas ?>% del total <span style="color:var(--brand-orange); float:right;">Ver →</span></span>
        </a>

        <a href="/poliart/app/admin/vista_suspendidos.php"
           style="text-decoration:none; display:flex; flex-direction:column; justify-content:space-between; min-height:120px;
                  padding:1.5rem; background-color:var(--bg-surface); border:1.5px solid var(--border-subtle);
                  border-top:4px solid #ef4444; border-radius:1.25rem; box-shadow:0 8px 24px rgba(0,0,0,.1);
                  backdrop-filter:blur(12px); transition:transform .2s, box-shadow .2s;"
           onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 32px rgba(239,68,68,.25)';"
           onmouseout="this.style.transform='';this.style.boxShadow='0 8px 24px rgba(0,0,0,.1)';">
            <span style="font-size:.72rem; font-weight:800; color:var(--text-muted); text-transform:uppercase; letter-spacing:.06em;">🚫 Suspendidos</span>
            <span style="font-size:2.5rem; font-weight:900; line-height:1; color:#ef4444; display:block; margin:.5rem 0;"><?= $total_suspendidos ?></span>
            <span style="font-size:.8rem; font-weight:700; color:#ef4444;">Cuentas restringidas <span style="float:right;">Ver →</span></span>
        </a>

        <a href="/poliart/app/admin/vista_nuevos.php"
           style="text-decoration:none; display:flex; flex-direction:column; justify-content:space-between; min-height:120px;
                  padding:1.5rem; background-color:var(--bg-surface); border:1.5px solid var(--border-subtle);
                  border-top:4px solid #22c55e; border-radius:1.25rem; box-shadow:0 8px 24px rgba(0,0,0,.1);
                  backdrop-filter:blur(12px); transition:transform .2s, box-shadow .2s;"
           onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 32px rgba(34,197,94,.25)';"
           onmouseout="this.style.transform='';this.style.boxShadow='0 8px 24px rgba(0,0,0,.1)';">
            <span style="font-size:.72rem; font-weight:800; color:var(--text-muted); text-transform:uppercase; letter-spacing:.06em;">🆕 Nuevos (30 días)</span>
            <span style="font-size:2.5rem; font-weight:900; line-height:1; color:#22c55e; display:block; margin:.5rem 0;"><?= $total_nuevos ?></span>
            <span style="font-size:.8rem; font-weight:700; color:#22c55e;">Registros recientes <span style="float:right;">Ver →</span></span>
        </a>

    </div>

    <!-- ZONAS (Todas las comunas y corregimientos) -->
    <?php if (!empty($zonas)): ?>
    <div class="ic" style="margin-bottom:2rem;">
        <h3 style="font-size:1.05rem; font-weight:800; color:var(--text-main); margin:0 0 1.25rem;">📍 Distribución por Comunas y Corregimientos</h3>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:1rem;">
            <?php foreach ($zonas as $z): ?>
            <div style="background:var(--bg-surface); padding:.85rem 1rem; border:1px solid var(--border-subtle); border-radius:.75rem;">
                <div style="display:flex; justify-content:space-between; font-size:.82rem; font-weight:700; color:var(--text-main); margin-bottom:.3rem;">
                    <span><?= htmlspecialchars($z['nombre']) ?></span>
                    <span style="color:var(--brand-orange);"><?= $z['cantidad'] ?> usuarios</span>
                </div>
                <div class="pb"><div class="pf" style="width:<?= $z['porcentaje'] ?>%;"></div></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- TABLA (Sin contenedor IC superior vacío) -->
    <div style="overflow-x:auto;">
        <?php if (empty($usuarios)): ?>
            <p style="text-align:center; color:var(--text-muted); padding:2rem 0;">No se encontraron usuarios.</p>
        <?php else: ?>
        <table class="tbl">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Zona</th>
                    <th>Especialidad</th>
                    <th>Registro</th>
                    <th>Estado</th>
                    <th style="text-align:right;">Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u):
                $es_susp = $u['estado'] === 'suspendido';
                $badge_estado = match($u['estado']) {
                    'activo'     => 'b-activo',
                    'suspendido' => 'b-suspendido',
                    default      => 'b-inactivo',
                };
                $label_estado = ucfirst($u['estado']);
                $av = !empty($u['avatar_path'])
                    ? htmlspecialchars($u['avatar_path'])
                    : 'https://ui-avatars.com/api/?name=' . urlencode($u['nombre'].' '.$u['apellido']) . '&background=ED7745&color=fff&size=40';
            ?>
            <tr id="row-<?= $u['id_usuario'] ?>">
                <td>
                    <div style="display:flex; align-items:center; gap:.6rem;">
                        <img src="<?= $av ?>" alt="" style="width:34px; height:34px; border-radius:9999px; object-fit:cover; border:2px solid var(--border-subtle);"
                             onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($u['nombre']) ?>&background=ED7745&color=fff&size=40'">
                        <div>
                            <strong><?= htmlspecialchars($u['nombre'].' '.$u['apellido']) ?></strong>
                            <span style="display:block; font-size:.75rem; color:var(--text-muted);">@<?= htmlspecialchars($u['nombre_usuario']) ?></span>
                        </div>
                    </div>
                </td>
                <td style="font-size:.8rem; color:var(--text-muted);"><?= htmlspecialchars($u['correo']) ?></td>
                <td><span class="badge <?= $u['rol']==='artista'?'b-artista':'b-cliente' ?>"><?= ucfirst($u['rol']) ?></span></td>
                <td style="font-size:.82rem;"><?= $u['zona'] ? htmlspecialchars($u['zona']) : '—' ?></td>
                <td style="font-size:.82rem;"><?= $u['especialidad'] ? htmlspecialchars($u['especialidad']) : '—' ?></td>
                <td style="font-size:.78rem; color:var(--text-muted); white-space:nowrap;"><?= date('d/m/Y', strtotime($u['creado_en'])) ?></td>
                <td>
                    <span class="badge <?= $badge_estado ?>" id="badge-<?= $u['id_usuario'] ?>"><?= $label_estado ?></span>
                </td>
                <td style="text-align:right;">
                    <?php if ($u['estado'] !== 'inactivo'): ?>
                    <button class="bsm <?= $es_susp ? '' : 'danger' ?>"
                            id="btn-<?= $u['id_usuario'] ?>"
                            onclick="toggleEstado(<?= $u['id_usuario'] ?>, '<?= $u['estado'] ?>')">
                        <?= $es_susp ? '✅ Reactivar' : '🚫 Suspender' ?>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- PAGINACIÓN -->
    <?php if ($total_paginas > 1): ?>
    <div style="display:flex; justify-content:center; align-items:center; gap:.5rem; margin-top:1.5rem; flex-wrap:wrap;">
        <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
        <a href="/poliart/app/admin/usuarios.php?pagina=<?= $p ?>"
           class="pgb <?= $p===$pagina?'on':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <p style="text-align:center; font-size:.8rem; color:var(--text-muted); margin-top:.5rem;">
        Mostrando <?= count($usuarios) ?> de <?= $total_filtrado ?> usuarios
    </p>
    <?php endif; ?>

</div>
</div>

<script>
async function toggleEstado(id, estadoActual) {
    const accion = estadoActual === 'suspendido' ? 'reactivar' : 'suspender';
    if (!confirm(`¿Estás seguro de que deseas ${accion} a este usuario?`)) return;

    const btn   = document.getElementById('btn-'   + id);
    const badge = document.getElementById('badge-' + id);
    btn.disabled = true;
    btn.textContent = '...';

    try {
        const fd = new FormData();
        fd.append('ajax_accion', accion);
        fd.append('id_usuario',  id);
        const res  = await fetch('/poliart/app/admin/usuarios.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.ok) {
            const nuevo = data.nuevo_estado;
            const claseMap = { activo: 'b-activo', suspendido: 'b-suspendido', inactivo: 'b-inactivo' };
            badge.className   = 'badge ' + (claseMap[nuevo] ?? 'b-inactivo');
            badge.textContent = nuevo.charAt(0).toUpperCase() + nuevo.slice(1);
            btn.className     = 'bsm ' + (nuevo === 'suspendido' ? '' : 'danger');
            btn.textContent   = nuevo === 'suspendido' ? '✅ Reactivar' : '🚫 Suspender';
            btn.onclick       = () => toggleEstado(id, nuevo);
        } else {
            alert('Error: ' + (data.error || 'No se pudo completar la acción.'));
        }
    } catch (_) {
        alert('Error de red. Intenta de nuevo.');
    } finally {
        btn.disabled = false;
    }
}
</script>

<?php ob_start(); if ($_SERVER['REQUEST_METHOD'] !== 'POST') echo '<script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll("form[method=GET]").forEach(function(f){f.querySelectorAll("select").forEach(function(s){s.addEventListener("change",function(){f.submit();});});f.querySelectorAll("button[type=submit]").forEach(function(b){if((b.textContent||"").trim().toLowerCase()==="filtrar")b.remove();});});});</script>'; ?>
