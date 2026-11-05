<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['administrador']);
$project_base = '/poliart/';
$errorUsuarios = '';
try {
    $usuarios_bloqueados = getDB()->query(
        "SELECT u.id_usuario AS id, u.nombre, u.apellido, u.nombre_usuario AS username,
                u.correo AS email, u.rol, u.actualizado_en AS fecha_bloqueo, z.nombre AS comuna
         FROM usuario u LEFT JOIN zona z ON z.id_zona = u.id_zona
         WHERE u.estado = 'suspendido' AND u.rol <> 'administrador'
         ORDER BY u.actualizado_en DESC"
    )->fetchAll();
} catch (PDOException $e) {
    error_log('Error al cargar cuentas suspendidas: ' . $e->getMessage());
    $usuarios_bloqueados = [];
    $errorUsuarios = 'No fue posible cargar las cuentas suspendidas.';
}
require_once __DIR__ . '/../admin/header.php';
?>

<style>
.container-blocked {
    background-color: var(--bg-surface) !important;
    border: 1px solid var(--border-subtle) !important;
    border-radius: 1.25rem;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(12px);
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    box-sizing: border-box;
}

.blocked-card {
    background-color: var(--hover-item) !important;
    border: 1px solid var(--border-subtle) !important;
    border-radius: 1rem;
    padding: 1.25rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    flex-wrap: wrap;
    transition: border-color 0.2s ease;
}

.blocked-card:hover {
    border-color: #ef4444 !important;
}

.search-input {
    background-color: var(--hover-item) !important;
    border: 1px solid var(--border-subtle) !important;
    color: var(--text-main) !important;
    padding: 0.75rem 1rem;
    border-radius: 0.75rem;
    width: 100%;
    max-width: 350px;
    outline: none;
    font-size: 0.9rem;
}

.search-input:focus {
    border-color: var(--brand-orange) !important;
}

.btn-reactivate {
    background-color: rgba(34, 197, 94, 0.15);
    color: #22c55e;
    border: 1px solid rgba(34, 197, 94, 0.3);
    padding: 0.55rem 1rem;
    border-radius: 0.65rem;
    font-weight: 700;
    font-size: 0.82rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-reactivate:hover {
    background-color: #22c55e;
    color: #ffffff;
}

.badge-role {
    background: var(--hover-item);
    border: 1px solid var(--border-subtle);
    color: var(--text-muted);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.25rem 0.6rem;
    border-radius: 9999px;
}
</style>

<div style="width: 100%; padding: 0 2rem; box-sizing: border-box;">
    
    <div class="container-blocked">
        
        <!-- HEADER -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <a href="<?= $project_base ?>app/admin/index.php" style="text-decoration: none; color: var(--brand-orange); font-weight: 700; font-size: 0.85rem;">
                    &larr; Volver al Panel Principal
                </a>
                <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin: 0.5rem 0 0 0;">
                    🚫 Usuarios Bloqueados / Sancionados
                </h1>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0.25rem 0 0 0;">
                    Gestión de cuentas suspendidas por infracciones de términos de servicio
                </p>
            </div>

            <input type="text" id="searchInput" class="search-input" placeholder="🔍 Buscar por nombre, usuario o correo..." onkeyup="filtrarUsuarios()">
        </div>

        <!-- LISTADO DE USUARIOS BLOQUEADOS -->
        <div id="listaUsuarios">
            <?php if ($errorUsuarios !== ''): ?>
                <p role="alert" style="text-align:center;padding:3rem;color:#dc2626;"><?= htmlspecialchars($errorUsuarios, ENT_QUOTES, 'UTF-8') ?></p>
            <?php elseif (empty($usuarios_bloqueados)): ?>
                <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                    <h3>🎉 No hay usuarios sancionados actualmente</h3>
                    <p>Todos los usuarios de Poli-Art se encuentran en estado activo.</p>
                </div>
            <?php else: ?>
                <?php foreach ($usuarios_bloqueados as $user): ?>
                    <div class="blocked-card user-item" data-search="<?= htmlspecialchars(mb_strtolower($user['nombre'] . ' ' . $user['apellido'] . ' ' . $user['username'] . ' ' . $user['email'], 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>">
                        
                        <!-- INFO USUARIO -->
                        <div style="display: flex; align-items: center; gap: 1rem; min-width: 250px;">
                            <div style="font-size: 2rem; background: rgba(239, 68, 68, 0.1); padding: 0.75rem; border-radius: 50%;">
                                🚫
                            </div>
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <strong style="color: var(--text-main); font-size: 1rem;">
                                        <?= htmlspecialchars($user['nombre'] . ' ' . $user['apellido'], ENT_QUOTES, 'UTF-8') ?>
                                    </strong>
                                    <span class="badge-role"><?= htmlspecialchars($user['rol']) ?></span>
                                </div>
                                <span style="font-size: 0.82rem; color: var(--brand-orange); font-weight: 600; display: block; margin-top: 0.1rem;">
                                    @<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?> &bull; <span style="color: var(--text-muted);"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                                <span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 0.2rem;">
                                    📍 <?= htmlspecialchars($user['comuna'] ?? 'Sin zona', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        </div>

                        <!-- ESTADO Y FECHA -->
                        <div style="flex: 1; min-width: 280px; max-width: 450px;">
                            <strong style="font-size: 0.78rem; color: #ef4444; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 0.25rem;">
                                Estado de la cuenta
                            </strong>
                            <p style="font-size: 0.85rem; color: var(--text-main); margin: 0; line-height: 1.4;">
                                Suspendida
                            </p>
                            <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; display: block; margin-top: 0.4rem;">
                                🕒 Actualizada el <?= htmlspecialchars($user['fecha_bloqueo'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                        <!-- ACCIONES -->
                        <div style="display: flex; gap: 0.75rem; align-items: center;">
                            <button id="reactivar-<?= (int)$user['id'] ?>" class="btn btn-green" onclick="reactivarUsuario(<?= (int)$user['id'] ?>, <?= htmlspecialchars(json_encode('@' . $user['username']), ENT_QUOTES, 'UTF-8') ?>)">
                                ✅ Reactivar Cuenta
                            </button>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

</div>

<script>
function filtrarUsuarios() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    const items = document.querySelectorAll('.user-item');

    items.forEach(item => {
        const text = item.getAttribute('data-search');
        if (text.includes(query)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

async function reactivarUsuario(id, username) {
    if (confirm(`¿Estás seguro de que deseas desbloquear y reactivar la cuenta de ${username}?`)) {
        const button = document.getElementById(`reactivar-${id}`);
        if (button) button.disabled = true;
        try {
            const data = new FormData();
            data.append('ajax_accion', 'reactivar');
            data.append('id_usuario', id);
            const response = await fetch('vista_suspendidos.php', { method: 'POST', body: data });
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.error || 'No fue posible reactivar la cuenta.');
            window.location.reload();
        } catch (error) {
            window.alert(error.message || 'No fue posible reactivar la cuenta.');
            if (button) button.disabled = false;
        }
    }
}
</script>

