<?php
$perfil_rol = 'artista';
require_once __DIR__ . '/../includes/auth.php';
if (!isLoggedIn() || getUserRole() !== 'artista') {
    header('Location: /poliart/app/auth/views/login_artista.php');
    exit;
}
$pdoPerfil = getDB(); 
$idPerfil = getUserId();
$stmtPerfil = $pdoPerfil->prepare('SELECT u.*, z.nombre AS zona FROM usuario u LEFT JOIN zona z ON z.id_zona=u.id_zona WHERE u.id_usuario=? LIMIT 1');
$stmtPerfil->execute([$idPerfil]); 
$perfil = $stmtPerfil->fetch();
if (!$perfil) { header('Location: /poliart/core/logout.php'); exit; }

$zonasPerfil = $pdoPerfil->query('SELECT id_zona,nombre,tipo FROM zona ORDER BY tipo,nombre')->fetchAll();
$csrfPerfil = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32)); 
$_SESSION['csrf_token'] = $csrfPerfil;
$alertaPerfil = ['tipo' => '', 'msg' => '']; 
$accion = $_POST['accion'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrfPerfil, (string)($_POST['csrf_token'] ?? ''))) {
        $alertaPerfil = ['tipo' => 'error', 'msg' => 'La sesión del formulario venció. Recarga la página.'];
    } elseif ($accion === 'password') {
        $actual = (string)($_POST['password_actual'] ?? ''); 
        $nueva = (string)($_POST['password_nueva'] ?? ''); 
        $confirm = (string)($_POST['password_confirmacion'] ?? '');
        if (!password_verify($actual, (string)$perfil['contrasena_hash'])) {
            $alertaPerfil = ['tipo' => 'error', 'msg' => 'La contraseña actual no es correcta.'];
        } elseif (strlen($nueva) < 8 || $nueva !== $confirm) {
            $alertaPerfil = ['tipo' => 'error', 'msg' => 'La nueva contraseña debe tener al menos 8 caracteres y coincidir con su confirmación.'];
        } else {
            $pdoPerfil->prepare('UPDATE usuario SET contrasena_hash=? WHERE id_usuario=?')->execute([password_hash($nueva, PASSWORD_BCRYPT, ['cost' => 12]), $idPerfil]);
            $alertaPerfil = ['tipo' => 'ok', 'msg' => 'Contraseña actualizada correctamente.'];
        }
    } else {
        $nombre = trim((string)($_POST['nombre'] ?? '')); 
        $apellido = trim((string)($_POST['apellido'] ?? '')); 
        $usuario = trim((string)($_POST['nombre_usuario'] ?? '')); 
        $correo = trim((string)($_POST['correo'] ?? '')); 
        $telefono = trim((string)($_POST['telefono'] ?? '')); 
        $idZona = trim((string)($_POST['id_zona'] ?? ''));
        
        if ($nombre === '' || $apellido === '' || $usuario === '' || $correo === '') {
            $alertaPerfil = ['tipo' => 'error', 'msg' => 'Completa todos los campos obligatorios.'];
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $alertaPerfil = ['tipo' => 'error', 'msg' => 'El correo no tiene un formato válido.'];
        } else {
            $dup = $pdoPerfil->prepare('SELECT id_usuario FROM usuario WHERE (correo=? OR nombre_usuario=?) AND id_usuario<>? LIMIT 1');
            $dup->execute([$correo, $usuario, $idPerfil]);
            if ($dup->fetch()) {
                $alertaPerfil = ['tipo' => 'error', 'msg' => 'El correo o nombre de usuario ya está en uso.'];
            } else {
                $avatar = $perfil['avatar_path'];
                if (!empty($_FILES['avatar']['name']) && ($_FILES['avatar']['error'] ?? 4) === UPLOAD_ERR_OK) {
                    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['avatar']['tmp_name']);
                    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$mime] ?? null;
                    if ($ext && $_FILES['avatar']['size'] <= 5 * 1024 * 1024) {
                        $dir = dirname(__DIR__, 2) . '/public/uploads/avatars/';
                        if (!is_dir($dir)) mkdir($dir, 0755, true);
                        $file = 'perfil_' . $idPerfil . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $file)) $avatar = '/poliart/public/uploads/avatars/' . $file;
                    }
                }
                $portada = $perfil['portada_path'] ?? null;
                if (!empty($_FILES['portada']['name']) && ($_FILES['portada']['error'] ?? 4) === UPLOAD_ERR_OK) {
                    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['portada']['tmp_name']);
                    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$mime] ?? null;
                    if ($ext && $_FILES['portada']['size'] <= 5 * 1024 * 1024) {
                        $dir = dirname(__DIR__, 2) . '/public/uploads/portadas/';
                        if (!is_dir($dir)) mkdir($dir, 0755, true);
                        $file = 'perfil_' . $idPerfil . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                        if (move_uploaded_file($_FILES['portada']['tmp_name'], $dir . $file)) {
                            $portada = '/poliart/public/uploads/portadas/' . $file;
                        }
                    }
                }
                $pdoPerfil->prepare('UPDATE usuario SET nombre=?,apellido=?,nombre_usuario=?,correo=?,telefono=?,id_zona=?,avatar_path=?,portada_path=? WHERE id_usuario=?')->execute([$nombre, $apellido, $usuario, $correo, $telefono !== '' ? $telefono : null, $idZona !== '' ? (int)$idZona : null, $avatar, $portada, $idPerfil]);
                $stmtPerfil->execute([$idPerfil]); 
                $perfil = $stmtPerfil->fetch();
                $alertaPerfil = ['tipo' => 'ok', 'msg' => 'Perfil actualizado correctamente.'];
            }
        }
    }
}

require_once __DIR__ . '/header.php';
include_once __DIR__ . '/sidebar.php';

$avatarPerfil = $perfil['avatar_path'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($perfil['nombre'] . ' ' . $perfil['apellido']) . '&background=ED7745&color=fff&size=120';
$portadaPerfil = !empty($perfil['portada_path']) ? $perfil['portada_path'] : '';
?>
<style>
/* Forzar scroll global de la ventana pegado al borde derecho */
html, body {
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
    overflow-y: auto !important;
}

.edit-profile-page {
    width: 100%;
    max-width: 1000px;
    margin: 0 auto;
    padding: 2.5rem 2rem 4rem;
    box-sizing: border-box;
}

.admin-profile-card { background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: 1.25rem; padding: 2rem; backdrop-filter: blur(12px); box-shadow: 0 8px 24px rgba(0,0,0,.1); margin-bottom: 1.5rem; }
.admin-cover-card { height: 150px; padding: 0 !important; overflow: hidden; position: relative; margin-bottom: 1.25rem; border-radius: 1.25rem; }
.admin-cover-card img { width: 100%; height: 100%; object-fit: cover; }
.admin-cover-empty { height: 100%; display: grid; place-items: center; background: linear-gradient(135deg,#7c321d,#f97316); color: #fff; font-weight: 800; font-size: 1.1rem; }
.admin-cover-card label { position: absolute; right: 1rem; bottom: 1rem; background: var(--brand-orange); color: #fff; border: 2px solid var(--bg-surface); border-radius: 9999px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.admin-profile-head { display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
.admin-avatar { position: relative; flex-shrink: 0; }
.admin-avatar img { width: 100px; height: 100px; border-radius: 9999px; border: 3px solid var(--brand-orange); object-fit: cover; }
.admin-avatar label { position: absolute; bottom: 2px; right: 2px; background: var(--brand-orange); color: #fff; border-radius: 9999px; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.file-hidden { display: none; }
.admin-profile-info { flex: 1; min-width: 0; }
.admin-profile-info h1 { font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin: 0; }
.admin-profile-info p { margin: .2rem 0 .55rem; color: var(--text-muted); font-size: .8rem; }
.admin-role { background: rgba(237,119,69,.15); color: var(--brand-orange); border: 1px solid rgba(237,119,69,.3); padding: .22rem .75rem; border-radius: 9999px; font-size: .78rem; font-weight: 800; display: inline-block; }
.admin-profile-meta { display: flex; flex-direction: column; align-items: flex-end; gap: .35rem; color: var(--text-muted); font-size: .8rem; }
.admin-profile-meta strong { color: var(--text-main); }
.admin-tabs { display: flex; gap: .5rem; margin: 1.5rem 0 1.25rem; }
.admin-section-title { font-size: 1.05rem; font-weight: 800; color: var(--text-main); margin: 0 0 1.5rem; }
.admin-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; margin-bottom: 1.1rem; }
.admin-label { display: block; font-size: .8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: .4rem; }
.admin-input { width: 100%; background: var(--hover-item); border: 1.5px solid var(--border-subtle); color: var(--text-main); border-radius: .75rem; padding: .75rem 1rem; font: inherit; box-sizing: border-box; outline: none; }
.admin-input:focus { border-color: var(--brand-orange); box-shadow: 0 0 0 3px rgba(237,119,69,.18); }
.admin-buttons { display: flex; gap: .75rem; }
.admin-alert { padding: .85rem 1rem; border-radius: .75rem; margin-bottom: 1.25rem; font-weight: 700; }
.admin-alert.ok { color: #22c55e; background: rgba(34,197,94,.12); }
.admin-alert.error { color: #ef4444; background: rgba(239,68,68,.12); }
.password-section { display: none; }
@media(max-width:700px){.admin-profile-meta{width:100%;align-items:flex-start}.admin-grid{grid-template-columns:1fr}}
</style>

<style>
html,body{overflow:hidden!important}
body>main{width:100vw!important;max-width:none!important;height:calc(100vh - 4.25rem)!important;min-height:0!important;margin-top:4.25rem!important;overflow-y:auto!important;overflow-x:hidden!important}
</style>
<style>
html,body{height:100%!important;max-height:100vh!important;overflow:hidden!important}
body>main{height:calc(100vh - 4.25rem)!important;min-height:0!important;overflow:hidden!important;margin-top:4.25rem!important}
#scroll-main-wrapper{flex-grow:1;height:100%;overflow-y:auto;overflow-x:hidden;padding:2rem 2.5rem;box-sizing:border-box}
</style>
<div id="scroll-main-wrapper">
<div class="edit-profile-page">
    <section class="admin-profile-card admin-cover-card">
        <?php if($portadaPerfil!==''): ?>
            <img src="<?= htmlspecialchars($portadaPerfil,ENT_QUOTES,'UTF-8') ?>" alt="Portada del perfil">
        <?php else: ?>
            <div class="admin-cover-empty">Portada del perfil</div>
        <?php endif; ?>
        <label for="portada-input" title="Cambiar foto de portada" aria-label="Cambiar foto de portada">📷</label>
        <input class="file-hidden" type="file" id="portada-input" name="portada" form="perfil-form" accept="image/jpeg,image/png,image/webp,image/gif">
    </section>

    <section class="admin-profile-card">
        <div class="admin-profile-head">
            <div class="admin-avatar">
                <img src="<?= htmlspecialchars($avatarPerfil,ENT_QUOTES,'UTF-8') ?>" alt="Avatar">
                <label for="avatar-input">📷</label>
            </div>
            <div class="admin-profile-info">
                <h1><?= htmlspecialchars($perfil['nombre'].' '.$perfil['apellido']) ?></h1>
                <p>@<?= htmlspecialchars($perfil['nombre_usuario']) ?> · <?= htmlspecialchars($perfil['correo']) ?></p>
                <span class="admin-role">🎨 Artista</span>
            </div>
            <div class="admin-profile-meta">
                <span>🗓️ Miembro desde: <strong><?= !empty($perfil['creado_en'])?date('d/m/Y',strtotime($perfil['creado_en'])):'—' ?></strong></span>
                <span>🕐 Último acceso: <strong><?= !empty($perfil['ultimo_acceso'])?date('d/m/Y H:i',strtotime($perfil['ultimo_acceso'])):'—' ?></strong></span>
            </div>
        </div>
    </section>

    <div class="admin-tabs">
        <button type="button" class="btn btn-outline" id="tab-perfil">👤 Datos del Perfil</button>
        <button type="button" class="btn btn-neutral" id="tab-password">🔐 Cambiar Contraseña</button>
    </div>

    <?php if($alertaPerfil['msg']!==''): ?>
        <div class="admin-alert <?= $alertaPerfil['tipo']==='ok'?'ok':'error' ?>"><?= htmlspecialchars($alertaPerfil['msg']) ?></div>
    <?php endif; ?>

    <section class="admin-profile-card" id="sec-perfil">
        <h2 class="admin-section-title">✏️ Editar Datos Personales</h2>
        <form id="perfil-form" method="POST" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="accion" value="perfil">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfPerfil) ?>">
            <input class="file-hidden" type="file" id="avatar-input" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif">
            
            <div class="admin-grid">
                <div>
                    <label class="admin-label">Nombre <span style="color:#ef4444">*</span></label>
                    <input class="admin-input" name="nombre" value="<?= htmlspecialchars($perfil['nombre']) ?>" required>
                </div>
                <div>
                    <label class="admin-label">Apellido <span style="color:#ef4444">*</span></label>
                    <input class="admin-input" name="apellido" value="<?= htmlspecialchars($perfil['apellido']) ?>" required>
                </div>
            </div>

            <div class="admin-grid">
                <div>
                    <label class="admin-label">Nombre de usuario <span style="color:#ef4444">*</span></label>
                    <input class="admin-input" name="nombre_usuario" value="<?= htmlspecialchars($perfil['nombre_usuario']) ?>" required>
                </div>
                <div>
                    <label class="admin-label">Correo electrónico <span style="color:#ef4444">*</span></label>
                    <input class="admin-input" type="email" name="correo" value="<?= htmlspecialchars($perfil['correo']) ?>" required>
                </div>
            </div>

            <div class="admin-grid">
                <div>
                    <label class="admin-label">Teléfono</label>
                    <input class="admin-input" name="telefono" value="<?= htmlspecialchars($perfil['telefono']??'') ?>">
                </div>
                <div>
                    <label class="admin-label">Zona / Comuna</label>
                    <select class="admin-input" name="id_zona">
                        <option value="">Sin zona</option>
                        <?php foreach($zonasPerfil as $zona): ?>
                            <option value="<?= (int)$zona['id_zona'] ?>" <?= (int)$zona['id_zona']===(int)($perfil['id_zona']??0)?'selected':'' ?>><?= htmlspecialchars($zona['nombre']) ?> (<?= htmlspecialchars($zona['tipo']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="admin-buttons">
                <button type="submit" class="btn btn-primary btn-lg">💾 Guardar Cambios</button>
                <a href="perfil.php" class="btn btn-neutral btn-lg" style="text-decoration:none">Cancelar</a>
            </div>
        </form>
    </section>

    <section class="admin-profile-card password-section" id="sec-password">
        <h2 class="admin-section-title">🔐 Cambiar Contraseña</h2>
        <form method="POST" autocomplete="off">
            <input type="hidden" name="accion" value="password">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfPerfil) ?>">
            <div style="margin-bottom:1.1rem">
                <label class="admin-label">Contraseña actual *</label>
                <input class="admin-input" type="password" name="password_actual" required>
            </div>
            <div class="admin-grid">
                <div>
                    <label class="admin-label">Nueva contraseña *</label>
                    <input class="admin-input" type="password" name="password_nueva" minlength="8" required>
                </div>
                <div>
                    <label class="admin-label">Confirmar nueva contraseña *</label>
                    <input class="admin-input" type="password" name="password_confirmacion" minlength="8" required>
                </div>
            </div>
            <div class="admin-buttons">
                <button type="submit" class="btn btn-primary btn-lg">🔐 Actualizar Contraseña</button>
                <button type="button" class="btn btn-neutral btn-lg" id="cancel-password">Cancelar</button>
            </div>
        </form>
    </section>
</div></div>
<script>
(function(){
    const p=document.getElementById('sec-perfil'),s=document.getElementById('sec-password'),a=document.getElementById('tab-perfil'),b=document.getElementById('tab-password');
    function tab(x){const q=x==='password';p.style.display=q?'none':'block';s.style.display=q?'block':'none';a.className='btn '+(q?'btn-neutral':'btn-outline');b.className='btn '+(q?'btn-outline':'btn-neutral')}
    a.onclick=()=>tab('perfil');b.onclick=()=>tab('password');document.getElementById('cancel-password').onclick=()=>tab('perfil');
    <?php if($accion==='password'&&$alertaPerfil['msg']!==''): ?>tab('password');<?php endif; ?>
})();
</script>
