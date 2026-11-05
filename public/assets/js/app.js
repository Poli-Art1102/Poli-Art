document.addEventListener('DOMContentLoaded', () => {
    const themeToggleBtn = document.getElementById('theme-toggle');
    const sunIcon = document.getElementById('theme-toggle-sun');
    const moonIcon = document.getElementById('theme-toggle-moon');

    const currentTheme = localStorage.getItem('color-theme') || 'dark';

    function applyTheme(theme) {
        const header = document.querySelector('header');
        const sidebar = document.querySelector('aside');
        const cards = document.querySelectorAll('div[style*="background-color: #1f2937"], div[style*="background-color: rgb(31, 41, 55)"]');
        const footer = document.querySelector('footer');
        const sidebarLinks = document.querySelectorAll('aside a');

        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
            document.body.style.backgroundColor = '#111827';
            document.body.style.color = '#f3f4f6';

            if (header) {
                header.style.backgroundColor = '#1f2937';
                header.style.borderColor = '#374151';
            }
            if (sidebar) {
                sidebar.style.backgroundColor = '#1f2937';
                sidebar.style.borderColor = '#374151';
                sidebar.style.color = '#f3f4f6';
            }
            if (footer) {
                footer.style.backgroundColor = '#1f2937';
                footer.style.borderColor = '#374151';
                footer.style.color = '#9ca3af';
            }
            cards.forEach(card => {
                card.style.backgroundColor = '#1f2937';
                card.style.borderColor = '#374151';
                card.style.color = '#ffffff';
            });
            sidebarLinks.forEach(link => {
                if (!link.style.color.includes('ef4444')) { // No cambiar el botón de salir que es rojo
                    link.style.color = '#f3f4f6';
                }
            });

            if (sunIcon) sunIcon.style.display = 'inline';
            if (moonIcon) moonIcon.style.display = 'none';

        } else { // MODO CLARO
            document.documentElement.classList.remove('dark');
            document.body.style.backgroundColor = '#f3f4f6';
            document.body.style.color = '#111827';

            if (header) {
                header.style.backgroundColor = '#ffffff';
                header.style.borderColor = '#e5e7eb';
            }
            if (sidebar) {
                sidebar.style.backgroundColor = '#ffffff';
                sidebar.style.borderColor = '#e5e7eb';
                sidebar.style.color = '#111827';
            }
            if (footer) {
                footer.style.backgroundColor = '#ffffff';
                footer.style.borderColor = '#e5e7eb';
                footer.style.color = '#4b5563';
            }
            cards.forEach(card => {
                card.style.backgroundColor = '#ffffff';
                card.style.borderColor = '#e5e7eb';
                card.style.color = '#111827';
            });
            sidebarLinks.forEach(link => {
                if (!link.style.color.includes('ef4444')) {
                    link.style.color = '#111827';
                }
            });

            if (sunIcon) sunIcon.style.display = 'none';
            if (moonIcon) moonIcon.style.display = 'inline';
        }
    }

    applyTheme(currentTheme);

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const activeTheme = localStorage.getItem('color-theme') || 'dark';
            const newTheme = activeTheme === 'dark' ? 'light' : 'dark';

            localStorage.setItem('color-theme', newTheme);
            applyTheme(newTheme);
        });
    }
});