<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);
require_once __DIR__ . '/../artista/header.php';
include_once __DIR__ . '/../artista/sidebar.php';

$project_base = '/poliart/';
$tipo_arte = $_GET['tipo'] ?? 'dibujo';

$config_arte = [
    'dibujo' => [
        'titulo' => 'Ilustración, Pintura & Arte Visual',
        'icono' => '🎨',
        'label_archivo' => 'Subir Imagen de Alta Resolución / Lienzo',
        'accept' => 'image/*',
        'placeholder_desc' => 'Describe las técnicas (óleo, acrílico, digital)...',
        'categoria_select' => 'Ilustración & Pintura'
    ],
    'musica' => [
        'titulo' => 'Música, Pistas & Videoclips',
        'icono' => '🎵',
        'label_archivo' => 'Subir Archivo de Video / Audio (.mp4, .mp3, .wav)',
        'accept' => 'video/*,audio/*',
        'placeholder_desc' => 'Añade la letra, instrumental o créditos de producción...',
        'categoria_select' => 'Música & Audio'
    ],
    'escenicas' => [
        'titulo' => 'Artes Escénicas & Teatro',
        'icono' => '🎭',
        'label_archivo' => 'Subir Registro en Video (.mp4)',
        'accept' => 'video/*',
        'placeholder_desc' => 'Escribe la sinopsis de la obra o guion escénico...',
        'categoria_select' => 'Artes Escénicas'
    ],
    'escritura' => [
        'titulo' => 'Libros & Literatura',
        'icono' => '✍️',
        'label_archivo' => 'Subir Portada (2:3) o Documento PDF del Libro',
        'accept' => 'image/*,application/pdf',
        'placeholder_desc' => 'Fragmento, prólogo o contenido literario...',
        'categoria_select' => 'Literatura & Escritura'
    ],
    'escultura' => [
        'titulo' => 'Escultura & Modelo 3D',
        'icono' => '🗿',
        'label_archivo' => 'Subir Fotografía o Render 3D de la Pieza',
        'accept' => 'image/*',
        'placeholder_desc' => 'Especifica materiales, dimensiones o proceso...',
        'categoria_select' => 'Escultura & 3D'
    ]
];

$arte_actual = $config_arte[$tipo_arte] ?? $config_arte['dibujo'];
$permite_multiples_imagenes = in_array($tipo_arte, ['dibujo', 'escultura'], true);
$es_audio = $tipo_arte === 'musica';

try {
    $categoriasSubida = getDB()->query(
        'SELECT nombre FROM categoria_obra
         WHERE activa = 1 AND id_categoria_padre IS NULL
         ORDER BY nombre'
    )->fetchAll();
    $zonasSubida = getDB()->query(
        'SELECT id_zona, nombre FROM zona ORDER BY tipo, nombre'
    )->fetchAll();
} catch (PDOException $e) {
    error_log('Error al cargar categorías y zonas para subir obra: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo cargar el formulario de publicación. Intenta de nuevo más tarde.');
}
?>

<?php $zonaArtistaId = null; $numerosComuna = ['Popular'=>1,'Santa Cruz'=>2,'Manrique'=>3,'Aranjuez'=>4,'Castilla'=>5,'Doce de Octubre'=>6,'Robledo'=>7,'Villa Hermosa'=>8,'Buenos Aires'=>9,'La Candelaria'=>10,'Laureles'=>11,'La América'=>12,'San Javier'=>13,'El Poblado'=>14,'Guayabal'=>15,'Belén'=>16]; try { $stmtZonaArtista = getDB()->prepare('SELECT id_zona FROM usuario WHERE id_usuario = ? LIMIT 1'); $stmtZonaArtista->execute([getUserId()]); $zonaArtista = $stmtZonaArtista->fetch(); $zonaArtistaId = $zonaArtista && $zonaArtista['id_zona'] !== null ? (int)$zonaArtista['id_zona'] : null; $zonasSubida = getDB()->query('SELECT id_zona, nombre, tipo FROM zona ORDER BY tipo, nombre')->fetchAll(); } catch (PDOException $e) { error_log('Error al cargar la zona del artista: ' . $e->getMessage()); } ?><style>
html, body {
    height: 100% !important;
    max-height: 100vh !important;
    overflow: hidden !important;
    margin: 0;
    padding: 0;
}

body {
    background-image: url('<?= $project_base ?>app/includes/madera.jpg') !important;
    background-size: cover !important;
    background-position: center !important;
    background-attachment: fixed !important;
    background-repeat: no-repeat !important;
}

.form-card {
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
    backdrop-filter: blur(8px);
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

.form-input-art {
    width: 100%;
    background-color: var(--hover-item);
    border: 1px solid var(--border-subtle);
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    color: var(--text-main);
    font-size: 0.9rem;
    outline: none;
    box-sizing: border-box;
    transition: all 0.2s ease;
}

.form-input-art:focus {
    border-color: var(--brand-orange);
    box-shadow: 0 0 0 2px rgba(237, 119, 69, 0.2);
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 0.4rem;
}

.vista-previa-obra {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: .75rem;
    margin-top: .75rem;
    padding: .75rem;
    border: 1px solid var(--border-subtle);
    border-radius: .75rem;
    background: rgba(44, 20, 11, .5);
}

.vista-previa-obra-item {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: .4rem;
    padding: .5rem;
    border: 1px solid rgba(237, 119, 69, .35);
    border-radius: .6rem;
    background: var(--hover-item);
    color: var(--text-muted);
    font-size: .75rem;
}

.vista-previa-obra-item img,
.vista-previa-obra-item video {
    display: block;
    width: 100%;
    height: 220px;
    object-fit: contain;
    border-radius: .45rem;
    background: #000;
}

.vista-previa-obra-item audio {
    width: 100%;
    margin: 1rem 0;
}
</style>

<div style="display: flex; width: 100vw; height: calc(100vh - 4.25rem); overflow: hidden; background: transparent;">
    
    <div style="flex-grow: 1; height: 100%; overflow-y: auto; padding: 2.5rem; box-sizing: border-box;">
        
        <div style="max-width: 720px; margin: 0 auto;">

            <div class="form-card">
                
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1rem;">
                    <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <span>✨</span> Nueva Publicación: <?= $arte_actual['icono'] ?> <?= $arte_actual['titulo'] ?>
                    </h1>
                </div>

<form action="procesar_subida_multimedia.php" method="POST" enctype="multipart/form-data" autocomplete="off" style="display: flex; flex-direction: column; gap: 1.25rem;">

                    <input type="hidden" name="tipo_arte" value="<?= $tipo_arte ?>">

                    <div>
                        <label for="archivo_obra" class="form-label">
                            <?= $arte_actual['label_archivo'] ?>
                        </label>
<input id="archivo_obra" type="file" name="<?= $permite_multiples_imagenes ? 'archivo_obra[]' : 'archivo_obra' ?>" accept="<?= $permite_multiples_imagenes ? 'image/*' : $arte_actual['accept'] ?>" class="form-input-art" <?= $permite_multiples_imagenes ? 'multiple' : '' ?> required>
<div id="vista-previa-obra" class="vista-previa-obra" aria-live="polite" hidden></div>
<?php if ($es_audio): ?>
    <label for="portada_obra" class="form-label-art">Imagen de portada (obligatoria)</label>
    <input id="portada_obra" type="file" name="portada_obra" accept="image/*" class="form-input-art" required>
<?php endif; ?>
                    </div>

                    <div>
                        <label for="descripcion_obra" class="form-label">📝 Descripción de la Obra</label>
                        <textarea id="descripcion_obra" name="descripcion" rows="3" class="form-input-art" placeholder="<?= $arte_actual['placeholder_desc'] ?>" style="resize: none;"></textarea>
                    </div>

                    <div>
                        <label for="titulo_obra" class="form-label">🏷️ Título de la Obra</label>
                        <input id="titulo_obra" type="text" name="titulo" class="form-input-art" autocomplete="off" placeholder="Ej: Neón sobre la niebla" required>
                    </div>

                    <div>
                        <label for="colaboradores_obra" class="form-label">👥 Etiquetar colaboradores <span style="font-weight: 500; color: var(--text-muted);">(opcional)</span></label>
                        <input id="colaboradores_obra" type="text" name="colaboradores" class="form-input-art" placeholder="@usuario">
                    </div>

                    <div>
                        <label for="categoria_obra" class="form-label">🎨 Categoría</label>
                        <select id="categoria_obra" name="categoria" class="form-input-art">
                            <?php foreach ($categoriasSubida as $categoria): ?>
                                <option value="<?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>" <?= $categoria['nombre'] === $arte_actual['categoria_select'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="ubicacion_obra" class="form-label">📍 Ubicación / Comuna</label>
<select id="ubicacion_obra" name="ubicacion" class="form-input-art" data-comuna-artista="1">
<option value="">Seleccionar comuna...</option>
                            <?php foreach ($zonasSubida as $zona): ?>
<?php $numeroComuna = $numerosComuna[$zona['nombre']] ?? null; $etiquetaZona = $numeroComuna ? 'Comuna ' . $numeroComuna . ' — ' . $zona['nombre'] : ucfirst((string)($zona['tipo'] ?? 'Zona')) . ' — ' . $zona['nombre']; ?><option value="<?= (int)$zona['id_zona'] ?>" <?= $zonaArtistaId === (int)$zona['id_zona'] ? 'selected' : '' ?>><?= htmlspecialchars($etiquetaZona, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary btn-lg btn-full">
                            🚀 Publicar Obra
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('archivo_obra');
    const preview = document.getElementById('vista-previa-obra');
    if (!input || !preview) return;

    let urlsTemporales = [];

    function limpiarVistaPrevia() {
        urlsTemporales.forEach(url => URL.revokeObjectURL(url));
        urlsTemporales = [];
        preview.replaceChildren();
        preview.hidden = true;
    }

    function actualizarVistaPrevia() {
        limpiarVistaPrevia();
        const archivos = Array.from(input.files || []);
        if (!archivos.length) return;

        preview.hidden = false;
        archivos.forEach(function (archivo) {
            const url = URL.createObjectURL(archivo);
            urlsTemporales.push(url);

            const tarjeta = document.createElement('div');
            tarjeta.className = 'vista-previa-obra-item';

            if (archivo.type.startsWith('image/')) {
                const imagen = document.createElement('img');
                imagen.src = url;
                imagen.alt = 'Vista previa de ' + archivo.name;
                tarjeta.appendChild(imagen);
            } else if (archivo.type.startsWith('video/')) {
                const video = document.createElement('video');
                video.src = url;
                video.controls = true;
                video.preload = 'metadata';
                tarjeta.appendChild(video);
            } else if (archivo.type.startsWith('audio/')) {
                const audio = document.createElement('audio');
                audio.src = url;
                audio.controls = true;
                tarjeta.appendChild(audio);
            } else {
                const archivoTexto = document.createElement('span');
                archivoTexto.textContent = 'Archivo seleccionado: ' + archivo.name;
                tarjeta.appendChild(archivoTexto);
            }

            const nombre = document.createElement('small');
            nombre.textContent = archivo.name;
            tarjeta.appendChild(nombre);
            preview.appendChild(tarjeta);
        });
    }

    input.addEventListener('change', actualizarVistaPrevia);
});
</script>

