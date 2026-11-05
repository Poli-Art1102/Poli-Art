<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['administrador']);

$project_base = '/poliart/';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax_accion'])) {
    header('Content-Type: application/json; charset=utf-8');
    $accion=$_POST['ajax_accion']??''; $id=(int)($_POST['id_usuario']??0);
    if(!$id){echo json_encode(['ok'=>false,'error'=>'ID inválido.']);exit;}
    $en=$accion==='suspender'?'suspendido':'activo';
    $pdo->prepare("UPDATE usuario SET estado=? WHERE id_usuario=? AND rol='artista'")->execute([$en,$id]);
    echo json_encode(['ok'=>true,'nuevo_estado'=>$en]); exit;
}

$buscar   = trim($_GET['q']??'');
$estado_f = $_GET['estado']??'';
$espec_f  = $_GET['especialidad']??'';
$pagina   = max(1,(int)($_GET['pagina']??1));
$por_pag  = 15; $offset=($pagina-1)*$por_pag;

$where=["u.rol='artista'"]; $params=[];
if($buscar){
    $where[]="(u.nombre LIKE ? OR u.apellido LIKE ? OR u.nombre_usuario LIKE ? OR a.nombre_artistico LIKE ?)";
    $like="%$buscar%"; array_push($params,$like,$like,$like,$like);
}
if($estado_f){$where[]='u.estado=?';$params[]=$estado_f;}
if($espec_f) {$where[]='a.especialidad=?';$params[]=$espec_f;}
$wsql='WHERE '.implode(' AND ',$where);

try {
    $total_todos   =(int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE rol='artista'")->fetchColumn();
    $total_activos =(int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE rol='artista' AND estado='activo'")->fetchColumn();
    $total_susp    =(int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE rol='artista' AND estado='suspendido'")->fetchColumn();
    $nuevos_mes    =(int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE rol='artista' AND creado_en>=DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetchColumn();

    // Especialidades disponibles para filtro
    $specs=$pdo->query("SELECT DISTINCT especialidad FROM artista WHERE especialidad IS NOT NULL ORDER BY especialidad")->fetchAll(PDO::FETCH_COLUMN);

    $sc=$pdo->prepare("SELECT COUNT(*) FROM usuario u LEFT JOIN artista a ON a.id_usuario=u.id_usuario $wsql");
    $sc->execute($params); $total_filtrado=(int)$sc->fetchColumn();
    $total_paginas=max(1,ceil($total_filtrado/$por_pag));

    $su=$pdo->prepare(
        "SELECT u.id_usuario,u.nombre,u.apellido,u.nombre_usuario,u.correo,u.estado,u.creado_en,u.avatar_path,
                z.nombre AS zona, a.nombre_artistico, a.especialidad,
                (SELECT COUNT(*) FROM obra o WHERE o.id_artista=a.id_artista AND o.estado_publicacion='publicada') AS total_obras
         FROM usuario u
         LEFT JOIN zona z ON z.id_zona=u.id_zona
         LEFT JOIN artista a ON a.id_usuario=u.id_usuario
         $wsql ORDER BY u.creado_en DESC LIMIT $por_pag OFFSET $offset"
    );
    $su->execute($params); $usuarios=$su->fetchAll();
} catch(PDOException $e){
    $total_todos=$total_activos=$total_susp=$nuevos_mes=$total_filtrado=0;
    $total_paginas=1; $usuarios=[]; $specs=[];
}

require_once __DIR__ . '/../admin/header.php';
?>

<style>
.vista-wrap{background-color:var(--bg-surface);border:1px solid var(--border-subtle);border-radius:1.25rem;padding:2rem;box-shadow:0 10px 30px rgba(0,0,0,.12);backdrop-filter:blur(12px);max-width:1200px;margin:0 auto;width:100%;box-sizing:border-box;}
.stat{background-color:var(--hover-item);border:1px solid var(--border-subtle);border-radius:1rem;padding:1.1rem 1.25rem;}
.tbl{width:100%;border-collapse:collapse;text-align:left;}
.tbl th{padding:.75rem;font-size:.72rem;font-weight:800;color:var(--text-muted);text-transform:uppercase;border-bottom:1px solid var(--border-subtle);white-space:nowrap;}
.tbl td{padding:.75rem;font-size:.85rem;color:var(--text-main);border-bottom:1px solid var(--border-subtle);vertical-align:middle;}
.tbl tr:hover td{background-color:var(--hover-item);}
.badge{padding:.2rem .6rem;border-radius:9999px;font-size:.7rem;font-weight:700;display:inline-block;}
.b-activo{background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3);}
.b-suspendido{background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3);}
.b-inactivo{background:rgba(107,114,128,.15);color:#6b7280;border:1px solid rgba(107,114,128,.3);}
.fi{background-color:var(--bg-surface);border:1.5px solid var(--border-subtle);border-radius:.6rem;padding:.5rem .85rem;color:var(--text-main);font-size:.85rem;outline:none;font-family:inherit;transition:border-color .2s;}
.fi:focus{border-color:var(--brand-orange);}
.bsm{background:var(--hover-item);border:1px solid var(--border-subtle);color:var(--text-main);padding:.32rem .7rem;border-radius:.45rem;font-size:.75rem;font-weight:700;cursor:pointer;transition:all .15s;white-space:nowrap;font-family:inherit;}
.bsm:hover{border-color:var(--brand-orange);color:var(--brand-orange);}
.bsm.danger:hover{border-color:#ef4444;color:#ef4444;}
.pgb{background:var(--hover-item);border:1px solid var(--border-subtle);color:var(--text-main);padding:.4rem .9rem;border-radius:.5rem;font-size:.82rem;font-weight:700;text-decoration:none;transition:all .15s;}
.pgb:hover,.pgb.on{background:var(--brand-orange);color:#fff;border-color:var(--brand-orange);}
</style>

<div style="width:100%;padding:0 2rem 4rem;box-sizing:border-box;">
<div class="vista-wrap">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <a href="/poliart/app/admin/usuarios.php" style="color:var(--text-muted);text-decoration:none;font-size:.85rem;font-weight:700;">← Volver a Usuarios</a>
            <h1 style="font-size:1.6rem;font-weight:800;color:var(--text-main);margin:.3rem 0 0;">🎨 Artistas</h1>
            <p style="font-size:.875rem;color:var(--text-muted);margin:.25rem 0 0;">Creadores registrados en Poli-Art</p>
        </div>
        <span style="font-size:.8rem;color:var(--brand-orange);font-weight:700;background:rgba(237,119,69,.1);padding:.4rem .9rem;border-radius:9999px;border:1px solid rgba(237,119,69,.3);">
            🎨 Total: <?=$total_todos?> artistas
        </span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:2rem;">
        <div class="stat"><div style="font-size:.7rem;font-weight:800;color:var(--text-muted);text-transform:uppercase;">Total</div><div style="font-size:1.8rem;font-weight:900;color:var(--brand-orange);"><?=$total_todos?></div></div>
        <div class="stat"><div style="font-size:.7rem;font-weight:800;color:var(--text-muted);text-transform:uppercase;">Activos</div><div style="font-size:1.8rem;font-weight:900;color:#22c55e;"><?=$total_activos?></div></div>
        <div class="stat"><div style="font-size:.7rem;font-weight:800;color:var(--text-muted);text-transform:uppercase;">Suspendidos</div><div style="font-size:1.8rem;font-weight:900;color:#ef4444;"><?=$total_susp?></div></div>
        <div class="stat"><div style="font-size:.7rem;font-weight:800;color:var(--text-muted);text-transform:uppercase;">Nuevos (30d)</div><div style="font-size:1.8rem;font-weight:900;color:#22c55e;"><?=$nuevos_mes?></div></div>
    </div>

    <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;margin-bottom:1.25rem;">
        <input class="fi" type="text" name="q" placeholder="🔍 Nombre, @usuario, nombre artístico..." value="<?=htmlspecialchars($buscar)?>" style="flex:1;min-width:200px;">
        <select class="fi" name="estado">
            <option value="">Todos los estados</option>
            <option value="activo"     <?=$estado_f==='activo'    ?'selected':''?>>Activo</option>
            <option value="suspendido" <?=$estado_f==='suspendido'?'selected':''?>>Suspendido</option>
        </select>
        <?php if(!empty($specs)): ?>
        <select class="fi" name="especialidad">
            <option value="">Todas las especialidades</option>
            <?php foreach($specs as $s): ?>
            <option value="<?=htmlspecialchars($s)?>" <?=$espec_f===$s?'selected':''?>><?=htmlspecialchars($s)?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <button type="submit" class="btn btn-outline btn-sm">Filtrar</button>
        <?php if($buscar||$estado_f||$espec_f): ?><a href="?" class="btn btn-neutral btn-sm">✕ Limpiar</a><?php endif; ?>
    </form>

    <div style="overflow-x:auto;background-color:var(--hover-item);border:1px solid var(--border-subtle);border-radius:1rem;padding:1.5rem;">
        <?php if(empty($usuarios)): ?>
            <p style="text-align:center;color:var(--text-muted);padding:2rem 0;">No se encontraron artistas.</p>
        <?php else: ?>
        <table class="tbl">
            <thead><tr><th>Artista</th><th>Nombre artístico</th><th>Especialidad</th><th>Obras</th><th>Zona</th><th>Registro</th><th>Estado</th><th style="text-align:right;">Acción</th></tr></thead>
            <tbody>
            <?php foreach($usuarios as $u):
                $es_susp=$u['estado']==='suspendido';
                $av=!empty($u['avatar_path'])?htmlspecialchars($u['avatar_path'])
                   :'https://ui-avatars.com/api/?name='.urlencode($u['nombre'].' '.$u['apellido']).'&background=ED7745&color=fff&size=40';
            ?>
            <tr id="row-<?=$u['id_usuario']?>">
                <td>
                    <div style="display:flex;align-items:center;gap:.6rem;">
                        <img src="<?=$av?>" alt="" style="width:34px;height:34px;border-radius:9999px;object-fit:cover;border:2px solid var(--border-subtle);"
                             onerror="this.src='https://ui-avatars.com/api/?name=<?=urlencode($u['nombre'])?>&background=ED7745&color=fff&size=40'">
                        <div>
                            <strong><?=htmlspecialchars($u['nombre'].' '.$u['apellido'])?></strong>
                            <span style="display:block;font-size:.75rem;color:var(--text-muted);">@<?=htmlspecialchars($u['nombre_usuario'])?></span>
                        </div>
                    </div>
                </td>
                <td style="font-size:.82rem;"><?=$u['nombre_artistico']?htmlspecialchars($u['nombre_artistico']):'—'?></td>
                <td style="font-size:.82rem;"><?=$u['especialidad']?htmlspecialchars($u['especialidad']):'—'?></td>
                <td style="font-size:.85rem;font-weight:700;color:var(--brand-orange);"><?=$u['total_obras']?></td>
                <td style="font-size:.82rem;"><?=$u['zona']?htmlspecialchars($u['zona']):'—'?></td>
                <td style="font-size:.78rem;color:var(--text-muted);white-space:nowrap;"><?=date('d/m/Y',strtotime($u['creado_en']))?></td>
                <td><span class="badge b-<?=$es_susp?'suspendido':($u['estado']==='inactivo'?'inactivo':'activo')?>" id="badge-<?=$u['id_usuario']?>"><?=ucfirst($u['estado'])?></span></td>
                <td style="text-align:right;">
                    <button class="btn btn-<?=$es_susp?'green':'danger'?> btn-sm" id="btn-<?=$u['id_usuario']?>" onclick="toggleEstado(<?=$u['id_usuario']?>,'<?=$u['estado']?>')">
                        <?=$es_susp?'✅ Reactivar':'🚫 Suspender'?>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php if($total_paginas>1): ?>
    <div style="display:flex;justify-content:center;gap:.5rem;margin-top:1.5rem;flex-wrap:wrap;">
        <?php $qs=http_build_query(array_filter(['q'=>$buscar,'estado'=>$estado_f,'especialidad'=>$espec_f]));
        for($p=1;$p<=$total_paginas;$p++): ?>
        <a href="?<?=$qs.($qs?'&':'')?>pagina=<?=$p?>" class="pgb <?=$p===$pagina?'on':''?>"><?=$p?></a>
        <?php endfor; ?>
    </div>
    <p style="text-align:center;font-size:.8rem;color:var(--text-muted);margin-top:.5rem;">Mostrando <?=count($usuarios)?> de <?=$total_filtrado?> artistas</p>
    <?php endif; ?>

</div>
</div>

<script>
async function toggleEstado(id, estadoActual) {
    const accion=estadoActual==='suspendido'?'reactivar':'suspender';
    if(!confirm(`¿Deseas ${accion} esta cuenta?`))return;
    const btn=document.getElementById('btn-'+id),badge=document.getElementById('badge-'+id);
    btn.disabled=true;btn.textContent='...';
    try{
        const fd=new FormData();fd.append('ajax_accion',accion);fd.append('id_usuario',id);
        const res=await fetch('/poliart/app/admin/vista_artistas.php',{method:'POST',body:fd});
        const data=await res.json();
        if(data.ok){
            const n=data.nuevo_estado;
            badge.className='badge b-'+(n==='suspendido'?'suspendido':'activo');
            badge.textContent=n.charAt(0).toUpperCase()+n.slice(1);
            btn.className='btn btn-'+(n==='suspendido'?'green':'danger')+' btn-sm';
            btn.textContent=n==='suspendido'?'✅ Reactivar':'🚫 Suspender';
            btn.onclick=()=>toggleEstado(id,n);
        }else{alert(data.error||'Error.');}
    }catch(_){alert('Error de red.');}
    finally{btn.disabled=false;}
}
</script>

<?php ob_start(); if ($_SERVER['REQUEST_METHOD'] !== 'POST') echo '<script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll("form[method=GET]").forEach(function(f){f.querySelectorAll("select").forEach(function(s){s.addEventListener("change",function(){f.submit();});});f.querySelectorAll("button[type=submit]").forEach(function(b){if((b.textContent||"").trim().toLowerCase()==="filtrar")b.remove();});});});</script>'; ?>
