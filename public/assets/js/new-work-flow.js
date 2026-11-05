'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const opciones = [
        ['dibujo', '🎨', 'Ilustración, Pintura & Arte Visual', 'Lienzos, arte digital, acuarelas y bocetos.'],
        ['musica', '🎵', 'Música, Pistas & Videoclips', 'Composiciones, canciones, maquetas y videos.'],
        ['escenicas', '🎭', 'Artes Escénicas & Teatro', 'Obras de teatro, performances y danza en vivo.'],
        ['escritura', '✍️', 'Libros & Literatura', 'Novelas, cuentos, poemarios y ensayos.'],
        ['escultura', '🗿', 'Escultura & Modelo 3D', 'Modelado, piezas 3D y piezas artesanales.']
    ];

    const abrirSelector = event => {
        event.preventDefault();
        if (document.querySelector('.new-work-modal-backdrop')) return;

        const backdrop = document.createElement('div');
        backdrop.className = 'new-work-modal-backdrop';
        backdrop.innerHTML = `<section class="new-work-modal" role="dialog" aria-modal="true" aria-labelledby="new-work-title">
            <header class="new-work-modal-header"><h2 id="new-work-title">🚀 Publicar Nueva Obra</h2><button type="button" class="new-work-modal-close" aria-label="Cerrar">×</button></header>
            <div class="new-work-options"></div>
        </section>`;

        const lista = backdrop.querySelector('.new-work-options');
        opciones.forEach(([tipo, icono, titulo, descripcion]) => {
            const opcion = document.createElement('a');
            opcion.className = 'new-work-option';
            opcion.href = `subir_obra.php?tipo=${tipo}`;
            opcion.innerHTML = `<span class="new-work-option-icon">${icono}</span><span><strong>${titulo}</strong><small>${descripcion}</small></span>`;
            lista.appendChild(opcion);
        });

        const cerrar = () => backdrop.remove();
        backdrop.querySelector('.new-work-modal-close').addEventListener('click', cerrar);
        backdrop.addEventListener('click', event => { if (event.target === backdrop) cerrar(); });
        document.body.appendChild(backdrop);
        backdrop.querySelector('.new-work-modal-close').focus();
    };

    document.querySelectorAll('a[href$="subir_obra.php"], a[href$="seleccionar_obra.php"]').forEach(link => {
        link.addEventListener('click', abrirSelector);
    });
});
