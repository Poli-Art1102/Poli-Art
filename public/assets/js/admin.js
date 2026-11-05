document.addEventListener('DOMContentLoaded', () => {
    // 1. Acciones de Moderación de Obras (Aprobar / Rechazar)
    const actionButtons = document.querySelectorAll('[data-action="approve"], [data-action="reject"]');

    actionButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const card = e.target.closest('.moderation-card') || e.target.closest('tr');
            const action = button.getAttribute('data-action');
            const workTitle = card ? card.querySelector('.title')?.textContent || 'la obra' : 'la obra';

            if (action === 'approve') {
                button.textContent = 'Aprobada ✓';
                button.className = 'px-3 py-1 bg-green-600 text-white rounded-md text-xs font-bold';
                if (window.showNotification) showNotification(`"${workTitle}" ha sido aprobada.`, 'success');
            } else {
                button.textContent = 'Rechazada ✕';
                button.className = 'px-3 py-1 bg-red-600 text-white rounded-md text-xs font-bold';
                if (window.showNotification) showNotification(`"${workTitle}" ha sido rechazada.`, 'error');
            }

            // Ocultar suavemente el elemento tras la acción
            setTimeout(() => {
                if (card) {
                    card.style.transition = 'all 0.4s opacity';
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 400);
                }
            }, 1000);
        });
    });

    // 2. Buscador en tiempo real de la tabla de usuarios
    const userSearchInput = document.getElementById('search-users');
    if (userSearchInput) {
        userSearchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#users-table tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
});