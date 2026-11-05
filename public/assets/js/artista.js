document.addEventListener('DOMContentLoaded', () => {
    // 1. Previsualización dinámica de la imagen antes de subir la obra
    const fileInput = document.querySelector('input[type="file"]');
    const previewContainer = document.getElementById('image-preview-container');

    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    let previewImg = document.getElementById('image-preview');
                    
                    if (!previewImg) {
                        previewImg = document.createElement('img');
                        previewImg.id = 'image-preview';
                        previewImg.className = 'mt-4 rounded-xl border border-gray-700 max-h-64 object-cover w-full shadow-md';
                        
                        // Insertar después del campo file
                        fileInput.parentNode.appendChild(previewImg);
                    }
                    
                    previewImg.src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 2. Control del Formulario de Subir Obra
    const uploadForm = document.querySelector('form[action*="subir_obra"], form');
    if (uploadForm && window.location.pathname.includes('subir_obra')) {
        uploadForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const submitBtn = uploadForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="animate-pulse">Subiendo obra...</span>';

            setTimeout(() => {
                if (window.showNotification) {
                    showNotification('¡Tu obra ha sido publicada con éxito!', 'success');
                } else {
                    alert('¡Tu obra ha sido publicada con éxito!');
                }
                
                setTimeout(() => {
                    window.location.href = 'proyectos.php';
                }, 1200);
            }, 1500);
        });
    }

    // 3. Confirmación de eliminación de proyectos
    const deleteButtons = document.querySelectorAll('[data-action="delete-project"]');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm('¿Estás seguro de que deseas eliminar este proyecto? Esta acción no se puede deshacer.')) {
                const projectCard = btn.closest('.project-card') || btn.closest('div');
                if (projectCard) projectCard.remove();
                if (window.showNotification) showNotification('Proyecto eliminado.', 'info');
            }
        });
    });

    // 4. Filtrado interactivo de obras por categoría en el perfil del artista
    const filterChips = document.querySelectorAll('.filter-chip');
    if (filterChips.length > 0) {
        filterChips.forEach(chip => {
            chip.addEventListener('click', () => {
                // Cambiar estado activo visual de los botones
                filterChips.forEach(c => c.classList.remove('active'));
                chip.classList.add('active');

                const categoriaSeleccionada = chip.getAttribute('data-categoria');
                const obras = document.querySelectorAll('.project-item');

                obras.forEach(obra => {
                    const catObra = obra.getAttribute('data-categoria');
                    if (categoriaSeleccionada === 'todas' || catObra === categoriaSeleccionada) {
                        obra.style.display = 'block';
                    } else {
                        obra.style.display = 'none';
                    }
                });
            });
        });
    }
});