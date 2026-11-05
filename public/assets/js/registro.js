document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('register-client-form');
    const alertContainer = document.getElementById('alert-container');

    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const nombre = document.getElementById('nombre');
            const email = document.getElementById('email');
            const password = document.getElementById('password');

            let isValid = true;

            if (nombre.value.trim() === '') {
                document.getElementById('error-nombre').classList.remove('hidden');
                nombre.classList.add('border-red-500');
                isValid = false;
            } else {
                document.getElementById('error-nombre').classList.add('hidden');
                nombre.classList.remove('border-red-500');
            }

            if (!email.value.includes('@')) {
                document.getElementById('error-email').classList.remove('hidden');
                email.classList.add('border-red-500');
                isValid = false;
            } else {
                document.getElementById('error-email').classList.add('hidden');
                email.classList.remove('border-red-500');
            }

            if (password.value.length < 6) {
                document.getElementById('error-password').classList.remove('hidden');
                password.classList.add('border-red-500');
                isValid = false;
            } else {
                document.getElementById('error-password').classList.add('hidden');
                password.classList.remove('border-red-500');
            }

            if (isValid) {
                alertContainer.className = 'mb-4 p-3 rounded-lg text-sm bg-green-900/50 border border-green-500 text-green-200';
                alertContainer.textContent = '¡Registro completado! Puedes iniciar sesión ahora.';
                alertContainer.classList.remove('hidden');

                setTimeout(() => {
                    window.location.href = '/poliart/app/auth/login_cliente.php';
                }, 1500);
            }
        });
    }
});
