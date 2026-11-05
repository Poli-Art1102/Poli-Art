<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['administrador']);

$project_base = '/poliart/';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax_accion'])) {
    header('Content-Type: application/json; charset=utf-8');
    $id=(int)($_POST['id_usuario']??0);
    if(!$id){http_response_code(422);echo json_encode(['ok'=>false,'error'=>'ID inválido.']);exit;}
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE usuario SET estado='activo' WHERE id_usuario=? AND estado='suspendido' AND rol!='administrador'");
        $stmt->execute([$id]);
        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['ok'=>false,'error'=>'La cuenta suspendida no existe o ya fue reactivada.']);
            exit;
        }
        $pdo->prepare(
            "INSERT INTO administrador_log (id_admin, accion, entidad, id_entidad, descripcion)
             VALUES (?, 'reactivar_usuario', 'usuario', ?, 'Cuenta reactivada desde el listado de suspendidos')"
        )->execute([getUserId(), $id]);
        $pdo->commit();
        echo json_encode(['ok'=>true,'nuevo_estado'=>'activo']); exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Error al reactivar usuario: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['ok'=>false,'error'=>'No fue posible reactivar la cuenta.']);
        exit;
    }
}

$buscar = trim($_GET['q']??'');
$rol_f  = $_GET['rol']??'';
$pagina = max(1,(int)($_GET['pagina']??1));
$por_pag=15; $offset=($pagina-1)*$por_pag;

$where=["u.estado='suspendido'","u.rol!='administrador'"]; $params=[];
if($buscar){
    $where[]="(u.nombre LIKE ? OR u.apellido LIKE ? OR u.nombre_usuario LIKE ? OR u.correo LIKE ?)";
    $like="%$buscar%"; array_push($params,$like,$like,$like,$like);
}
if($rol_f){$where[]='u.rol=?';$params[]=$rol_f;}
$wsql='WHERE '.implode(' AND ',$where);

try{
    $total       =(int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE estado='suspendido' AND rol!='administrador'")->fetchColumn();
    $total_cli   =(int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE estado='suspendido' AND rol='cliente'")->fetchColumn();
    $total_art   =(int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE estado='suspendido' AND rol='artista'")->fetchColumn();

    $sc=$pdo->prepare("SELECT COUNT(*) FROM usuario u $wsql"); $sc->execute($params);
    $total_filtrado=(int)$sc->fetchColumn();
    $total_paginas=max(1,ceil($total_filtrado/$por_pag));

    $su=$pdo->prepare(
        "SELECT u.id_usuario,u.nombre,u.apellido,u.nombre_usuario,u.correo,u.rol,u.estado,u.creado_en,u.actualizado_en,u.avatar_path,
                z.nombre AS zona
         FROM usuario u LEFT JOIN zona z ON z.id_zona=u.id_zona
         $wsql ORDER BY u.actualizado_en DESC LIMIT $por_pag OFFSET $offset"
    );
    $su->execute($params); $usuarios=$su->fetchAll();
}catch(PDOException $e){
    $total=$total_cli=$total_art=$total_filtrado=0; $total_paginas=1; $usuarios=[];
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
.b-artista{background:rgba(237,119,69,.15);color:var(--brand-orange);border:1px solid rgba(237,119,69,.3);}
.b-cliente{background:rgba(59,130,246,.15);color:#3b82f6;border:1px solid rgba(59,130,246,.3);}
.fi{background-color:var(--bg-surface);border:1.5px solid var(--border-subtle);border-radius:.6rem;padding:.5rem .85rem;color:var(--text-main);font-size:.85rem;outline:none;font-family:inherit;transition:border-color .2s;}
.fi:focus{border-color:#ef4444;}
.bsm{background:var(--hover-item);border:1px solid var(--border-subtle);color:var(--text-main);padding:.32rem .7rem;border-radius:.45rem;font-size:.75rem;font-weight:700;cursor:pointer;transition:all .15s;white-space:nowrap;font-family:inherit;}
.bsm:hover{border-color:var(--brand-orange);color:var(--brand-orange);}
.bsm.reactivar{background:rgba(34,197,94,.1);color:#22c55e;border-color:rgba(34,197,94,.3);}
.bsm.reactivar:hover{background:#22c55e;color:#fff;border-color:#22c55e;}
.pgb{background:var(--hover-item);border:1px solid var(--border-subtle);color:var(--text-main);padding:.4rem .9rem;border-radius:.5rem;font-size:.82rem;font-weight:700;text-decoration:none;transition:all .15s;}
.pgb:hover,.pgb.on{background:#ef4444;color:#fff;border-color:#ef4444;}
</style>

<div style="width:100%;padding:0 2rem 4rem;box-sizing:border-box;">
<div class="vista-wrap">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <a href="/poliart/app/admin/usuarios.php" style="color:var(--text-muted);text-decoration:none;font-size:.85rem;font-weight:700;">← Volver a Usuarios</a>
            <h1 style="font-size:1.6rem;font-weight:800;color:var(--text-main);margin:.3rem 0 0;">🚫 Usuarios Suspendidos</h1>
            <p style="font-size:.875rem;color:var(--text-muted);margin:.25rem 0 0;">Cuentas con acceso restringido. Puedes reactivarlas aquí.</p>
        </div>
        <span style="font-size:.8rem;color:#ef4444;font-weight:700;background:rgba(239,68,68,.1);padding:.4rem .9rem;border-radius:9999px;border:1px solid rgba(239,68,68,.3);">
            🚫 <?=$total?> cuenta<?=$total!==1?'s':''?> suspendida<?=$total!==1?'s':''?>
        </span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:2rem;">
        <div class="stat"><div style="font-size:.7rem;font-weight:800;color:var(--text-muted);text-transform:uppercase;">Total suspendidos</div><div style="font-size:1.8rem;font-weight:900;color:#ef4444;"><?=$total?></div></div>
        <div class="stat"><div style="font-size:.7rem;font-weight:800;color:var(--text-muted);text-transform:uppercase;">Clientes</div><div style="font-size:1.8rem;font-weight:900;color:#3b82f6;"><?=$total_cli?></div></div>
        <div class="stat"><div style="font-size:.7rem;font-weight:800;color:var(--text-muted);text-transform:uppercase;">Artistas</div><div style="font-size:1.8rem;font-weight:900;color:var(--brand-orange);"><?=$total_art?></div></div>
    </div>

    <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;margin-bottom:1.25rem;">
        <input class="fi" type="text" name="q" placeholder="🔍 Buscar nombre, @usuario, correo..." value="<?=htmlspecialchars($buscar)?>" style="flex:1;min-width:220px;">
        <select class="fi" name="rol">
            <option value="">Todos los roles</option>
            <option value="cliente" <?=$rol_f==='cliente'?'selected':''?>>Cliente</option>
            <option value="artista" <?=$rol_f==='artista'?'selected':''?>>Artista</option>
        </select>
        <button type="submit" class="bsm" style="padding:.5rem 1rem;">Filtrar</button>
        <?php if($buscar||$rol_f): ?><a href="?" class="bsm">✕ Limpiar</a><?php endif; ?>
    </form>

    <div style="overflow-x:auto;background-color:var(--hover-item);border:1px solid rgba(239,68,68,.2);border-radius:1rem;padding:1.5rem;">
        <?php if(empty($usuarios)): ?>
            <div style="text-align:center;padding:3rem 0;color:var(--text-muted);">
                <div style="font-size:3rem;margin-bottom:.75rem;">🎉</div>
                <h3 style="color:var(--text-main);margin:0 0 .5rem;">¡Sin cuentas suspendidas!</h3>
                <p style="margin:0;">Todos los usuarios se encuentran activos.</p>
            </div>
        <?php else: ?>
        <table class="tbl">
            <thead><tr><th>Usuario</th><th>Correo</th><th>Rol</th><th>Zona</th><th>Suspendido desde</th><th style="text-align:right;">Acción</th></tr></thead>
            <tbody>
            <?php foreach($usuarios as $u):
                $av=!empty($u['avatar_path'])?htmlspecialchars($u['avatar_path'])
                   :'https://ui-avatars.com/api/?name='.urlencode($u['nombre'].' '.$u['apellido']).'&background=ef4444&color=fff&size=40';
            ?>
            <tr id="row-<?=$u['id_usuario']?>">
                <td>
                    <div style="display:flex;align-items:center;gap:.6rem;">
                        <img src="<?=$av?>" alt="" style="width:34px;height:34px;border-radius:9999px;object-fit:cover;border:2px solid rgba(239,68,68,.4);"
                             onerror="this.src='https://ui-avatars.com/api/?name=<?=urlencode($u['nombre'])?>&background=ef4444&color=fff&size=40'">
                        <div>
                            <strong><?=htmlspecialchars($u['nombre'].' '.$u['apellido'])?></strong>
                            <span style="display:block;font-size:.75rem;color:var(--text-muted);">@<?=htmlspecialchars($u['nombre_usuario'])?></span>
                        </div>
                    </div>
                </td>
                <td style="font-size:.8rem;color:var(--text-muted);"><?=htmlspecialchars($u['correo'])?></td>
                <td><span class="badge b-<?=$u['rol']==='artista'?'artista':'cliente'?>"><?=ucfirst($u['rol'])?></span></td>
                <td style="font-size:.82rem;"><?=$u['zona']?htmlspecialchars($u['zona']):'—'?></td>
                <td style="font-size:.78rem;color:#ef4444;white-space:nowrap;font-weight:700;"><?=date('d/m/Y H:i',strtotime($u['actualizado_en']))?></td>
                <td style="text-align:right;">
                    <button class="btn btn-green btn-sm" id="btn-<?=$u['id_usuario']?>" onclick="reactivar(<?=$u['id_usuario']?>)">
                        ✅ Reactivar
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
        <?php $qs=http_build_query(array_filter(['q'=>$buscar,'rol'=>$rol_f]));
        for($p=1;$p<=$total_paginas;$p++): ?>
        <a href="?<?=$qs.($qs?'&':'')?>pagina=<?=$p?>" class="pgb <?=$p===$pagina?'on':''?>"><?=$p?></a>
        <?php endfor; ?>
    </div>
    <p style="text-align:center;font-size:.8rem;color:var(--text-muted);margin-top:.5rem;">Mostrando <?=count($usuarios)?> de <?=$total_filtrado?> suspendidos</p>
    <?php endif; ?>

</div>
</div>

<script>
async function reactivar(id) {
    if(!confirm('¿Reactivar esta cuenta?'))return;
    const btn=document.getElementById('btn-'+id);
    btn.disabled=true; btn.textContent='...';
    try{
        const fd=new FormData(); fd.append('ajax_accion','reactivar'); fd.append('id_usuario',id);
        const res=await fetch('/poliart/app/admin/vista_suspendidos.php',{method:'POST',body:fd});
        const data=await res.json();
        if(data.ok){
            const fila=document.getElementById('row-'+id);
            fila.style.opacity='.4';
            fila.style.transition='opacity .4s';
            setTimeout(()=>fila.remove(),400);
        }else{alert(data.error||'Error.');}
    }catch(_){alert('Error de red.');}
    finally{btn.disabled=false;}
}
</script>

<?php ob_start(); if ($_SERVER['REQUEST_METHOD'] !== 'POST') echo '<script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll("form[method=GET]").forEach(function(f){f.querySelectorAll("select").forEach(function(s){s.addEventListener("change",function(){f.submit();});});f.querySelectorAll("button[type=submit]").forEach(function(b){if((b.textContent||"").trim().toLowerCase()==="filtrar")b.remove();});});});</script>'; ?>
