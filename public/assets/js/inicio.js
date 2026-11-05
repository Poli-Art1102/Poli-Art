document.addEventListener('DOMContentLoaded', () => {
    // Animación interactiva para las tarjetas destacadas al hacer scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('opacity-100', 'translate-y-0');
                entry.target.classList.remove('opacity-0', 'translate-y-6');
            }
        });
    }, { threshold: 0.1 });

    const animatedElements = document.querySelectorAll('.art-card, section');
    animatedElements.forEach(el => {
        el.classList.add('transition-all', 'duration-500', 'opacity-0', 'translate-y-6');
        observer.observe(el);
    });

    // Contador interactivo de métricas si existen en inicio/index.php
    const counters = document.querySelectorAll('.counter-value');
    counters.forEach(counter => {
        const target = +counter.getAttribute('data-target');
        let count = 0;
        const speed = target / 50;

        const updateCount = () => {
            count += speed;
            if (count < target) {
                counter.innerText = Math.ceil(count);
                setTimeout(updateCount, 30);
            } else {
                counter.innerText = target;
            }
        };
        updateCount();
    });
});