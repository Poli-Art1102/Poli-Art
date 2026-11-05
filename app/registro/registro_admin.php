<?php
/**
 * Registro de administrador — solo accesible si ya hay un admin logueado.
 * En producción, este formulario debería estar protegido o desactivado.
 */
require_once __DIR__ . '/../includes/auth.php';
if (isLoggedIn() && getUserRole() !== 'administrador') {
    redirectToHome();
}
require_once __DIR__ . '/../inicio/header.php';
?>

<section class="max-w-md mx-auto my-12 bg-gray-800 p-8 rounded-2xl border border-gray-700 shadow-2xl">
    <div class="mb-6 text-center">
        <div class="text-4xl mb-2">🛡️</div>
        <h2 class="text-2xl font-bold text-white">Crear Administrador</h2>
        <p class="text-sm text-red-400 mt-1">⚠️ Acceso restringido</p>
    </div>

    <div id="alert-container" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

    <form id="register-form" class="space-y-4" novalidate>
        <input type="hidden" name="rol" value="administrador">

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Nombre</label>
            <input type="text" name="nombre" required placeholder="Admin" style="text-transform: capitalize;"
                   class="w-full bg-gray-900 border border-gray-700 text-white rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-red-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Apellido</label>
            <input type="text" name="apellido" required placeholder="Sistema" style="text-transform: capitalize;"
                   class="w-full bg-gray-900 border border-gray-700 text-white rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-red-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Nombre de usuario</label>
            <input type="text" name="nombre_usuario" required placeholder="admin2"
                   class="w-full bg-gray-900 border border-gray-700 text-white rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-red-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Correo</label>
            <input type="email" name="correo" required placeholder="admin2@poliart.co"
                   class="w-full bg-gray-900 border border-gray-700 text-white rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-red-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Contraseña</label>
            <input type="password" name="password" required autocomplete="off" placeholder="Mínimo 8 caracteres"
                   class="w-full bg-gray-900 border border-gray-700 text-white rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-red-500">
        </div>

        <label class="flex items-start gap-2 text-sm text-gray-300 leading-5">
            <input type="checkbox" name="acepta_terminos" value="1" required class="mt-1 accent-red-500">
            <span>Acepto los <a href="<?= $project_base ?>app/inicio/terminos.php" target="_blank" rel="noopener" class="text-red-300 underline">Términos y Condiciones</a>.</span>
        </label>
        <button type="submit" id="btn-submit"
                class="w-full bg-red-700 hover:bg-red-600 text-white font-semibold py-2.5 rounded-lg transition flex justify-center items-center gap-2">
            <span id="btn-text">Crear Admin</span>
            <svg id="btn-spinner" class="hidden animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
        </button>
    </form>
</section>

<script>
(function () {
    // El endpoint de registro normal no acepta rol 'administrador'.
    // Este form usa un endpoint dedicado que requiere sesión admin activa.
    const form    = document.getElementById('register-form');
    const alert   = document.getElementById('alert-container');
    const spinner = document.getElementById('btn-spinner');
    const btnText = document.getElementById('btn-text');

    function showAlert(msg, ok = false) {
        alert.className = `mb-4 p-3 rounded-lg text-sm ${
            ok  ? 'bg-green-900/50 border border-green-500 text-green-200'
                : 'bg-red-900/50 border border-red-500 text-red-300'
        }`;
        alert.textContent = msg;
        alert.classList.remove('hidden');
    }

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        spinner.classList.remove('hidden');
        btnText.textContent = 'Creando...';
        document.getElementById('btn-submit').disabled = true;

        try {
            const fd  = new FormData(form);
            const res = await fetch('/poliart/app/includes/actions/registro_admin.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (!res.ok || !data.ok) {
                showAlert(data.error || 'Error al crear admin.');
            } else {
                showAlert('Administrador creado con éxito.', true);
                form.reset();
            }
        } catch (_) {
            showAlert('Error de red.');
        } finally {
            spinner.classList.add('hidden');
            btnText.textContent = 'Crear Admin';
            document.getElementById('btn-submit').disabled = false;
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
