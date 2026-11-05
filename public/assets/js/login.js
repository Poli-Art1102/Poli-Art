document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('login-client-form');
    const togglePasswordBtn = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password');
    const alertContainer = document.getElementById('alert-container');

    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            togglePasswordBtn.textContent = isPassword ? 'Ocultar' : 'Ver';
        });
    }

    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const errorEmail = document.getElementById('error-email');
            const errorPassword = document.getElementById('error-password');

            let isValid = true;

            // Validación de Email
            if (!email.value.includes('@') || email.value.trim() === '') {
                errorEmail.classList.remove('hidden');
                email.classList.add('border-red-500');
                isValid = false;
            } else {
                errorEmail.classList.add('hidden');
                email.classList.remove('border-red-500');
            }

            // Validación de Password
            if (password.value.trim() === '') {
                errorPassword.classList.remove('hidden');
                password.classList.add('border-red-500');
                isValid = false;
            } else {
                errorPassword.classList.add('hidden');
                password.classList.remove('border-red-500');
            }

            if (isValid) {
                alertContainer.className = 'mb-4 p-3 rounded-lg text-sm bg-green-900/50 border border-green-500 text-green-200';
                alertContainer.textContent = '¡Inicio de sesión exitoso! Redirigiendo...';
                alertContainer.classList.remove('hidden');

                setTimeout(() => {
                    window.location.href = '/poliart/app/cliente/index.php';
                }, 1200);
            }
        });
    }
});
