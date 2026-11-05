(function () {
    'use strict';

    function applyTheme(theme) {
        var root = document.getElementById('html-root') || document.documentElement;
        var selected = theme === 'dark' ? 'dark' : 'light';
        root.classList.remove('light', 'dark');
        root.classList.add(selected);

        var button = document.getElementById('auth-theme-toggle');
        if (button) {
            button.textContent = selected === 'dark' ? '☀️' : '🌙';
            button.setAttribute('aria-label', selected === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
            button.title = button.getAttribute('aria-label');
        }
    }

    function init() {
        var saved = localStorage.getItem('poliart_theme');
        applyTheme(saved === 'dark' ? 'dark' : 'light');

        var nav = document.querySelector('.page-nav');
        if (!nav || document.getElementById('auth-theme-toggle')) return;

        var button = document.createElement('button');
        button.type = 'button';
        button.id = 'auth-theme-toggle';
        button.style.cssText = 'margin-left:auto;margin-right:1rem;background:var(--hover-item);border:1.5px solid var(--brand-orange);color:var(--text-main);padding:.45rem .7rem;border-radius:.6rem;cursor:pointer;font-size:1rem;line-height:1;';
        button.addEventListener('click', function () {
            var next = (document.documentElement.classList.contains('dark')) ? 'light' : 'dark';
            localStorage.setItem('poliart_theme', next);
            applyTheme(next);
        });

        nav.insertBefore(button, nav.querySelector('.back-link') || null);
        applyTheme(localStorage.getItem('poliart_theme'));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
