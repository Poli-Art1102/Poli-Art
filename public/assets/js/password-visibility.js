'use strict';

(() => {
    function enhancePasswordInput(input) {
        if (input.dataset.passwordVisibilityReady === 'true') return;
        input.dataset.passwordVisibilityReady = 'true';

        const oldToggle = input.parentElement.querySelector(
            '.toggle-pwd, .password-toggle'
        );
        if (oldToggle) oldToggle.remove();

        const wrapper = document.createElement('span');
        wrapper.className = 'password-visibility-field';
        input.parentElement.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        input.style.paddingRight = '3.35rem';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'password-visibility-toggle';
        button.setAttribute('aria-label', 'Mostrar contraseña');
        button.setAttribute('aria-pressed', 'false');
        button.textContent = '🙈';
        button.addEventListener('click', () => {
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.classList.toggle('is-visible', visible);
            button.textContent = visible ? '🙉' : '🙈';
            button.setAttribute('aria-pressed', visible ? 'true' : 'false');
            button.setAttribute(
                'aria-label',
                (visible ? 'Ocultar' : 'Mostrar') + ' ' + (input.labels?.[0]?.textContent.trim() || 'contraseña')
            );
        });
        wrapper.appendChild(button);
    }

    function enhancePasswordInputs(root = document) {
        root.querySelectorAll('input[type="password"]').forEach(enhancePasswordInput);
    }

    enhancePasswordInputs();

    new MutationObserver(records => {
        for (const record of records) {
            for (const node of record.addedNodes) {
                if (node.nodeType !== Node.ELEMENT_NODE) continue;
                if (node.matches('input[type="password"]')) enhancePasswordInput(node);
                enhancePasswordInputs(node);
            }
        }
    }).observe(document.documentElement, { childList: true, subtree: true });
})();
