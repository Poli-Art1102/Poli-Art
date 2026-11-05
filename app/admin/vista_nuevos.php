<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['administrador']);

$project_base = '/poliart/';
$pdo = getDB();

$rol_f   = $_GET['rol']??'';
$pagina  = max(1,(int)($_GET['pagina']??1));
$por_pag = 15; $offset=($pagina-1)*$por_pag;

$where=["u.rol!='administrador'"]; $params=[];
if($rol_f){$where[]='u.rol=?';$params[]=$rol_f;}
$wsql='WHERE '.implode(' AND ',$where);

try{
    $nuevos_30 =(int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE rol!='administrador' AND creado_en>=DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetchColumn();

    $sc=$pdo->prepare("SELECT COUNT(*) FROM usuario u $wsql"); $sc->execute($params);
    $total_filtrado=(int)$sc->fetchColumn();
    $total_paginas=max(1,ceil($total_filtrado/$por_pag));

    $su=$pdo->prepare(
        "SELECT u.id_usuario,u.nombre,u.apellido,u.nombre_usuario,u.correo,u.rol,u.estado,u.creado_en,u.avatar_path,
                z.nombre AS zona, a.especialidad
         FROM usuario u
         LEFT JOIN zona z ON z.id_zona=u.id_zona
         LEFT JOIN artista a ON a.id_usuario=u.id_usuario
         $wsql ORDER BY u.creado_en DESC LIMIT $por_pag OFFSET $offset"
    );
    $su->execute($params); $usuarios=$su->fetchAll();
}catch(PDOException $e){
    $nuevos_30=$total_filtrado=0;
    $total_paginas=1; $usuarios=[];
}

require_once __DIR__ . '/../admin/header.php';
?>

<style>
.vista-wrap{background-color:var(--bg-surface);border:1px solid var(--border-subtle);border-radius:1.25rem;padding:2rem;box-shadow:0 10px 30px rgba(0,0,0,.12);backdrop-filter:blur(12px);max-width:1200px;margin:0 auto;width:100%;box-sizing:border-box;}
.tbl{width:100%;border-collapse:collapse;text-align:left;}
.tbl th{padding:.75rem;font-size:.72rem;font-weight:800;color:var(--text-muted);text-transform:uppercase;border-bottom:1px solid var(--border-subtle);white-space:nowrap;}
.tbl td{padding:.75rem;font-size:.85rem;color:var(--text-main);border-bottom:1px solid var(--border-subtle);vertical-align:middle;}
.tbl tr:hover td{background-color:var(--hover-item);}
.badge{padding:.2rem .6rem;border-radius:9999px;font-size:.7rem;font-weight:700;display:inline-block;}
.b-artista{background:rgba(237,119,69,.15);color:var(--brand-orange);border:1px solid rgba(237,119,69,.3);}
.b-cliente{background:rgba(59,130,246,.15);color:#3b82f6;border:1px solid rgba(59,130,246,.3);}
.b-activo{background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3);}
.b-suspendido{background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3);}
.fi{background-color:var(--bg-surface);border:1.5px solid var(--border-subtle);border-radius:.6rem;padding:.5rem .85rem;color:var(--text-main);font-size:.85rem;outline:none;font-family:inherit;transition:border-color .2s;}
.fi:focus{border-color:#22c55e;}
.pgb{background:var(--hover-item);border:1px solid var(--border-subtle);color:var(--text-main);padding:.4rem .9rem;border-radius:.5rem;font-size:.82rem;font-weight:700;text-decoration:none;transition:all .15s;}
.pgb:hover,.pgb.on{background:#22c55e;color:#fff;border-color:#22c55e;}
</style>

<div style="width:100%;padding:2.5rem 2rem 1.5rem;box-sizing:border-box;">
<div class="vista-wrap">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <a href="/poliart/app/admin/usuarios.php" style="color:var(--text-muted);text-decoration:none;font-size:.85rem;font-weight:700;">← Volver a Usuarios</a>
            <h1 style="font-size:1.6rem;font-weight:800;color:var(--text-main);margin:.3rem 0 0;">🆕 Registros Recientes</h1>
            <p style="font-size:.875rem;color:var(--text-muted);margin:.25rem 0 0;">Usuarios que se han registrado recientemente en Poli-Art</p>
        </div>
        <span style="font-size:.8rem;color:#22c55e;font-weight:700;background:rgba(34,197,94,.1);padding:.4rem .9rem;border-radius:9999px;border:1px solid rgba(34,197,94,.3);">
            🆕 <?=$nuevos_30?> nuevos en 30 días
        </span>
    </div>

    <!-- TABLA (Sin buscador superior) -->
    <div style="overflow-x:auto;background-color:var(--hover-item);border:1px solid var(--border-subtle);border-radius:1rem;padding:1.5rem;">
        <?php if(empty($usuarios)): ?>
            <p style="text-align:center;color:var(--text-muted);padding:2rem 0;">No hay registros encontrados.</p>
        <?php else: ?>
        <table class="tbl">
            <thead><tr><th>Usuario</th><th>Correo</th><th>Rol</th><th>Especialidad / Tipo</th><th>Zona</th><th>Registrado hace</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach($usuarios as $u):
                $diff = time()-strtotime($u['creado_en']);
                $tiempo = $diff < 3600 ? 'Hace '.round($diff/60).' min'
                        : ($diff < 86400 ? 'Hace '.round($diff/3600).' h'
                        : 'Hace '.round($diff/86400).' día(s)');
                $av=!empty($u['avatar_path'])?htmlspecialchars($u['avatar_path'])
                    :'https://ui-avatars.com/api/?name='.urlencode($u['nombre'].' '.$u['apellido']).'&background=22c55e&color=fff&size=40';
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:.6rem;">
                        <img src="<?=$av?>" alt="" style="width:34px;height:34px;border-radius:9999px;object-fit:cover;border:2px solid var(--border-subtle);"
                             onerror="this.src='https://ui-avatars.com/api/?name=<?=urlencode($u['nombre'])?>&background=22c55e&color=fff&size=40'">
                        <div>
                            <strong><?=htmlspecialchars($u['nombre'].' '.$u['apellido'])?></strong>
                            <span style="display:block;font-size:.75rem;color:var(--text-muted);">@<?=htmlspecialchars($u['nombre_usuario'])?></span>
                        </div>
                    </div>
                </td>
                <td style="font-size:.8rem;color:var(--text-muted);"><?=htmlspecialchars($u['correo'])?></td>
                <td><span class="badge b-<?=$u['rol']==='artista'?'artista':'cliente'?>"><?=ucfirst($u['rol'])?></span></td>
                <td style="font-size:.82rem;"><?=$u['especialidad']?htmlspecialchars($u['especialidad']):'—'?></td>
                <td style="font-size:.82rem;"><?=$u['zona']?htmlspecialchars($u['zona']):'—'?></td>
                <td style="font-size:.78rem;color:#22c55e;font-weight:700;white-space:nowrap;"><?=$tiempo?><br><span style="color:var(--text-muted);font-weight:400;"><?=date('d/m/Y H:i',strtotime($u['creado_en']))?></span></td>
                <td><span class="badge b-<?=$u['estado']==='suspendido'?'suspendido':'activo'?>"><?=ucfirst($u['estado'])?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php if($total_paginas>1): ?>
    <div style="display:flex;justify-content:center;gap:.5rem;margin-top:1.5rem;flex-wrap:wrap;">
        <?php for($p=1;$p<=$total_paginas;$p++): ?>
        <a href="?pagina=<?=$p?>" class="pgb <?=$p===$pagina?'on':''?>"><?=$p?></a>
        <?php endfor; ?>
    </div>
    <p style="text-align:center;font-size:.8rem;color:var(--text-muted);margin-top:.5rem;">Mostrando <?=count($usuarios)?> de <?=$total_filtrado?> usuarios</p>
    <?php endif; ?>

</div>
</div>

<?php ob_start(); if ($_SERVER['REQUEST_METHOD'] !== 'POST') echo '<script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll("form[method=GET]").forEach(function(f){f.querySelectorAll("select").forEach(function(s){s.addEventListener("change",function(){f.submit();});});f.querySelectorAll("button[type=submit]").forEach(function(b){if((b.textContent||"").trim().toLowerCase()==="filtrar")b.remove();});});});</script>'; ?>
