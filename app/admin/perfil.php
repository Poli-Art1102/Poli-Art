<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['administrador']);

$project_base = '/poliart/';
$pdo  = getDB();
$id   = getUserId();

$stmt = $pdo->prepare('SELECT nombre, apellido, nombre_usuario, correo, avatar_path, creado_en, ultimo_acceso FROM usuario WHERE id_usuario = ?');
$stmt->execute([$id]);
$admin = $stmt->fetch();

if (!$admin) {
    header('Location: /poliart/app/includes/actions/logout.php');
    exit;
}

$alerta = ['tipo' => '', 'msg' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'perfil';

    if ($accion === 'perfil') {
        $nombre         = trim($_POST['nombre']         ?? '');
        $apellido       = trim($_POST['apellido']       ?? '');
        $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
        $correo         = trim($_POST['correo']         ?? '');

        if (!$nombre || !$apellido || !$nombre_usuario || !$correo) {
            $alerta = ['tipo' => 'error', 'msg' => 'Todos los campos son obligatorios.'];
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $alerta = ['tipo' => 'error', 'msg' => 'El correo no tiene un formato válido.'];
        } else {
            $dup = $pdo->prepare('SELECT id_usuario FROM usuario WHERE (correo = ? OR nombre_usuario = ?) AND id_usuario != ? LIMIT 1');
            $dup->execute([$correo, $nombre_usuario, $id]);
            if ($dup->fetch()) {
                $alerta = ['tipo' => 'error', 'msg' => 'El correo o nombre de usuario ya está en uso.'];
            } else {
                $avatar_path = $admin['avatar_path'];
                if (!empty($_FILES['avatar']['name'])) {
                    $ext     = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg','jpeg','png','webp','gif'];
                    if (!in_array($ext, $allowed)) {
                        $alerta = ['tipo' => 'error', 'msg' => 'Formato no permitido. Usa JPG, PNG o WEBP.'];
                        goto fin;
                    }
                    // __DIR__ = .../poliart/app/admin/  →  subir UN nivel llega a poliart/
                    $dir = dirname(__DIR__, 2) . '/uploads/avatars/';
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $filename = 'admin_' . $id . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $filename)) {
                        $avatar_path = '/poliart/uploads/avatars/' . $filename;
                    } else {
                        $alerta = ['tipo' => 'error', 'msg' => 'Error al mover el archivo. Verifica permisos en uploads/avatars/.'];
                        goto fin;
                    }
                }
                $pdo->prepare('UPDATE usuario SET nombre=?, apellido=?, nombre_usuario=?, correo=?, avatar_path=? WHERE id_usuario=?')
                    ->execute([$nombre, $apellido, $nombre_usuario, $correo, $avatar_path, $id]);
                $_SESSION['user'] = array_merge($_SESSION['user'] ?? [], [
                    'nombre' => $nombre, 'apellido' => $apellido,
                    'nombre_usuario' => $nombre_usuario, 'correo' => $correo,
                    'avatar_path' => $avatar_path,
                ]);
                $stmt->execute([$id]);
                $admin = $stmt->fetch();
                $alerta = ['tipo' => 'ok', 'msg' => '✅ Perfil actualizado correctamente.'];
            }
        }
    }

    if ($accion === 'password') {
        $actual   = $_POST['password_actual']   ?? '';
        $nueva    = $_POST['password_nueva']    ?? '';
        $confirma = $_POST['password_confirma'] ?? '';
        $hash_row = $pdo->prepare('SELECT contrasena_hash FROM usuario WHERE id_usuario = ?');
        $hash_row->execute([$id]);
        $hash = $hash_row->fetchColumn();
        if (!password_verify($actual, $hash)) {
            $alerta = ['tipo' => 'error', 'msg' => 'La contraseña actual no es correcta.'];
        } elseif (strlen($nueva) < 8) {
            $alerta = ['tipo' => 'error', 'msg' => 'La nueva contraseña debe tener al menos 8 caracteres.'];
        } elseif ($nueva !== $confirma) {
            $alerta = ['tipo' => 'error', 'msg' => 'La nueva contraseña y su confirmación no coinciden.'];
        } else {
            $pdo->prepare('UPDATE usuario SET contrasena_hash=? WHERE id_usuario=?')
                ->execute([password_hash($nueva, PASSWORD_BCRYPT, ['cost' => 12]), $id]);
            $alerta = ['tipo' => 'ok', 'msg' => '✅ Contraseña actualizada correctamente.'];
        }
    }

    fin:
}

require_once __DIR__ . '/../admin/header.php';
?>

<style>
/* Todas las reglas usan variables CSS — responden automáticamente al tema */
.perfil-card {
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1.25rem;
    padding: 2rem;
    backdrop-filter: blur(12px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}
.form-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.4rem;
}
.form-input {
    width: 100%;
    background-color: var(--hover-item);
    border: 1.5px solid var(--border-subtle);
    color: var(--text-main);
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.2s, box-shadow 0.2s;
    font-family: inherit;
}
.form-input:focus {
    border-color: var(--brand-orange);
    box-shadow: 0 0 0 3px rgba(237,119,69,0.18);
}
.avatar-ring {
    width: 100px; height: 100px;
    border-radius: 9999px;
    border: 3px solid var(--brand-orange);
    object-fit: cover;
    box-shadow: 0 6px 20px rgba(237,119,69,0.25);
    cursor: pointer;
    transition: transform 0.2s;
}
.avatar-ring:hover { transform: scale(1.05); }
.alert-ok    { background: rgba(34,197,94,0.12);  border: 1px solid rgba(34,197,94,0.4);  color: #22c55e; padding: 0.85rem 1rem; border-radius: 0.75rem; font-size: 0.875rem; margin-bottom: 1.25rem; font-weight: 700; }
.alert-error { background: rgba(239,68,68,0.12);  border: 1px solid rgba(239,68,68,0.4);  color: #ef4444; padding: 0.85rem 1rem; border-radius: 0.75rem; font-size: 0.875rem; margin-bottom: 1.25rem; font-weight: 700; }
.info-badge {
    background: rgba(237,119,69,0.15);
    color: var(--brand-orange);
    border: 1px solid rgba(237,119,69,0.3);
    padding: 0.22rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.78rem;
    font-weight: 800;
    display: inline-block;
}
.meta-text {
    font-size: 0.8rem;
    color: var(--text-muted);
}
.meta-text strong { color: var(--text-main); }
.hint-box {
    background: rgba(237,119,69,0.08);
    border: 1px solid rgba(237,119,69,0.2);
    border-radius: 0.75rem;
    padding: 0.85rem 1rem;
    font-size: 0.82rem;
    color: var(--text-muted);
    margin-bottom: 1.5rem;
}
.danger-zone {
    margin-top: 1.5rem;
    background-color: var(--bg-surface);
    border: 1.5px solid rgba(239,68,68,0.35);
    border-left: 4px solid #ef4444;
    border-radius: 1.25rem;
    padding: 1.5rem;
    backdrop-filter: blur(12px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}
.btn-logout {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(239,68,68,0.12);
    color: #ef4444;
    border: 1px solid rgba(239,68,68,0.32);
    padding: 0.65rem 1.25rem;
    border-radius: 0.65rem;
    font-weight: 700;
    font-size: 0.875rem;
    text-decoration: none;
    transition: background 0.2s;
}
.btn-logout:hover { background: rgba(239,68,68,0.25); }

/* Dentro de la zona de sesión el texto siempre se ve bien */
.danger-zone h3   { font-size: 0.95rem; font-weight: 800; color: #ef4444; margin: 0 0 0.4rem 0; }
.danger-zone p    { font-size: 0.85rem; color: var(--text-muted); margin: 0 0 1rem 0; }
</style>

<div style="width: 100%; max-width: 860px; margin: 0 auto; padding: 2.5rem 2rem 4rem 2rem; box-sizing: border-box;">

    <!-- ENCABEZADO / TARJETA DE PERFIL -->
    <div class="perfil-card" style="margin-bottom: 1.5rem;">
        <div style="display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;">

            <!-- AVATAR -->
            <div style="position: relative; flex-shrink: 0;">
                <?php
                    $avatar_src = !empty($admin['avatar_path'])
                        ? htmlspecialchars($admin['avatar_path'])
                        : 'https://ui-avatars.com/api/?name=' . urlencode($admin['nombre'].' '.$admin['apellido']) . '&background=ED7745&color=fff&size=120';
                ?>
                <!-- Input y form de avatar siempre presente en el DOM -->
                <form id="form-avatar" method="POST" enctype="multipart/form-data" style="display:none;">
                    <input type="hidden" name="accion" value="perfil">
                    <!-- campos ocultos con los valores actuales para no vaciarlos -->
                    <input type="hidden" name="nombre"         value="<?= htmlspecialchars($admin['nombre']) ?>">
                    <input type="hidden" name="apellido"       value="<?= htmlspecialchars($admin['apellido']) ?>">
                    <input type="hidden" name="nombre_usuario" value="<?= htmlspecialchars($admin['nombre_usuario']) ?>">
                    <input type="hidden" name="correo"         value="<?= htmlspecialchars($admin['correo']) ?>">
                    <input type="file" id="avatar-input" name="avatar"
                           accept="image/jpeg,image/png,image/webp"
                           onchange="previewAvatar(this)">
                </form>

                <img id="avatar-preview" src="<?= $avatar_src ?>" alt="Avatar" class="avatar-ring"
                     onclick="document.getElementById('avatar-input').click()"
                     onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($admin['nombre'].' '.$admin['apellido']) ?>&background=ED7745&color=fff&size=120'"
                     title="Haz clic para cambiar foto de perfil">
                <span style="position:absolute; bottom:2px; right:2px; background:var(--brand-orange); color:#fff; border-radius:9999px; width:26px; height:26px; display:flex; align-items:center; justify-content:center; font-size:0.75rem; pointer-events:none; box-shadow:0 2px 6px rgba(0,0,0,0.3); cursor:pointer;">📷</span>
            </div>

            <!-- INFO -->
            <div style="flex: 1; min-width: 0;">
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin: 0;">
                    <?= htmlspecialchars($admin['nombre'] . ' ' . $admin['apellido']) ?>
                </h1>
                <p class="meta-text" style="margin: 0.2rem 0 0.55rem 0;">
                    @<?= htmlspecialchars($admin['nombre_usuario']) ?> · <?= htmlspecialchars($admin['correo']) ?>
                </p>
                <span class="info-badge">🛡️ Administrador</span>
            </div>

            <!-- FECHAS -->
            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.35rem; flex-shrink: 0;">
                <span class="meta-text">🗓️ Miembro desde: <strong><?= date('d/m/Y', strtotime($admin['creado_en'])) ?></strong></span>
                <?php if ($admin['ultimo_acceso']): ?>
                <span class="meta-text">🕐 Último acceso: <strong><?= date('d/m/Y H:i', strtotime($admin['ultimo_acceso'])) ?></strong></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TABS -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.25rem;">
        <button class="btn btn-outline" id="tab-perfil"   onclick="switchTab('perfil')">👤 Datos del Perfil</button>
        <button class="btn btn-neutral"  id="tab-password" onclick="switchTab('password')">🔐 Cambiar Contraseña</button>
    </div>

    <!-- ALERTA -->
    <?php if ($alerta['msg']): ?>
        <div class="<?= $alerta['tipo'] === 'ok' ? 'alert-ok' : 'alert-error' ?>">
            <?= htmlspecialchars($alerta['msg']) ?>
        </div>
    <?php endif; ?>

    <!-- SECCIÓN: DATOS DEL PERFIL -->
    <div id="sec-perfil" class="perfil-card">
        <h2 style="font-size: 1.05rem; font-weight: 800; color: var(--text-main); margin: 0 0 1.5rem 0;">✏️ Editar Datos Personales</h2>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="perfil">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; margin-bottom: 1.1rem;">
                <div>
                    <label class="form-label">Nombre <span style="color:#ef4444">*</span></label>
                    <input class="form-input" type="text" name="nombre" value="<?= htmlspecialchars($admin['nombre']) ?>" required>
                </div>
                <div>
                    <label class="form-label">Apellido <span style="color:#ef4444">*</span></label>
                    <input class="form-input" type="text" name="apellido" value="<?= htmlspecialchars($admin['apellido']) ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; margin-bottom: 1.5rem;">
                <div>
                    <label class="form-label">Nombre de usuario <span style="color:#ef4444">*</span></label>
                    <input class="form-input" type="text" name="nombre_usuario" value="<?= htmlspecialchars($admin['nombre_usuario']) ?>" required>
                </div>
                <div>
                    <label class="form-label">Correo Electrónico <span style="color:#ef4444">*</span></label>
                    <input class="form-input" type="email" name="correo" value="<?= htmlspecialchars($admin['correo']) ?>" required>
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem;">
                <button type="submit" class="btn btn-primary btn-lg">💾 Guardar Cambios</button>
                <button type="button" class="btn btn-neutral btn-lg" onclick="window.history.back()">Cancelar</button>
            </div>
        </form>
    </div>

    <!-- SECCIÓN: CAMBIAR CONTRASEÑA -->
    <div id="sec-password" class="perfil-card" style="display:none;">
        <h2 style="font-size: 1.05rem; font-weight: 800; color: var(--text-main); margin: 0 0 1.5rem 0;">🔐 Cambiar Contraseña</h2>

        <form method="POST">
            <input type="hidden" name="accion" value="password">

            <div style="margin-bottom: 1.1rem;">
                <label class="form-label">Contraseña Actual <span style="color:#ef4444">*</span></label>
                <input class="form-input" type="password" name="password_actual" required placeholder="••••••••">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; margin-bottom: 1.25rem;">
                <div>
                    <label class="form-label">Nueva Contraseña <span style="color:#ef4444">*</span></label>
                    <input class="form-input" type="password" name="password_nueva" required placeholder="Mínimo 8 caracteres">
                </div>
                <div>
                    <label class="form-label">Confirmar Nueva Contraseña <span style="color:#ef4444">*</span></label>
                    <input class="form-input" type="password" name="password_confirma" required placeholder="Repite la contraseña">
                </div>
            </div>

            <div class="hint-box">⚠️ Usa al menos 8 caracteres. Combina letras, números y símbolos para mayor seguridad.</div>

            <div style="display: flex; gap: 0.75rem;">
                <button type="submit" class="btn btn-primary btn-lg">🔐 Actualizar Contraseña</button>
                <button type="button" class="btn btn-neutral btn-lg" onclick="switchTab('perfil')">Cancelar</button>
            </div>
        </form>
    </div>

    <!-- ZONA DE SESIÓN -->
    <div class="danger-zone">
        <h3>⚠️ Zona de Sesión</h3>
        <p>Cierra tu sesión actual de administrador de forma segura.</p>
                        <a href="/poliart/app/includes/actions/logout.php" class="btn btn-danger">🚪 Cerrar Sesión</a>
    </div>

</div>

<script>
function previewAvatar(input) {
    if (!input.files || !input.files[0]) return;

    // Preview inmediato
    const reader = new FileReader();
    reader.onload = e => { document.getElementById('avatar-preview').src = e.target.result; };
    reader.readAsDataURL(input.files[0]);

    // Enviar el form-avatar automáticamente para guardar en servidor
    document.getElementById('form-avatar').submit();
}

function switchTab(tab) {
    document.getElementById('sec-perfil').style.display   = tab === 'perfil'   ? 'block' : 'none';
    document.getElementById('sec-password').style.display = tab === 'password' ? 'block' : 'none';
    document.getElementById('tab-perfil').className   = 'btn ' + (tab === 'perfil'   ? 'btn-outline' : 'btn-neutral');
    document.getElementById('tab-password').className = 'btn ' + (tab === 'password' ? 'btn-outline' : 'btn-neutral');
}

<?php if ($alerta['msg'] && ($_POST['accion'] ?? '') === 'password'): ?>
switchTab('password');
<?php endif; ?>
</script>


