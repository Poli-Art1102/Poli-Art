<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);

$project_base = '/poliart/';
$pdo = getDB();
$stmtArtista = $pdo->prepare('SELECT id_artista FROM artista WHERE id_usuario = :id_usuario');
$stmtArtista->execute([':id_usuario' => getUserId()]);
$idArtista = (int)$stmtArtista->fetchColumn();
if (!$idArtista) {
    http_response_code(403);
    exit('La cuenta no tiene un perfil de artista asociado.');
}

$idBorrador = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$tipoArte = trim($_GET['tipo'] ?? 'dibujo');
$categoriasTipo = [
    'dibujo' => 'ilustracion-pintura',
    'musica' => 'musica-audio',
    'escenicas' => 'artes-escenicas',
    'escritura' => 'literatura-escritura',
    'escultura' => 'escultura-3d',
];
$slugCategoria = $categoriasTipo[$tipoArte] ?? $categoriasTipo['dibujo'];
$stmtCategoria = $pdo->prepare('SELECT id_categoria FROM categoria_obra WHERE slug = :slug LIMIT 1');
$stmtCategoria->execute([':slug' => $slugCategoria]);
$idCategoria = (int)$stmtCategoria->fetchColumn() ?: null;

$borrador = ['titulo' => '', 'descripcion' => '', 'progreso' => 0, 'ruta_archivo' => ''];
$errorBorrador = '';

if ($idBorrador) {
    $stmtBorrador = $pdo->prepare(
        'SELECT titulo, descripcion, progreso, ruta_archivo FROM borrador_obra
         WHERE id_borrador = :id AND id_artista = :artista LIMIT 1'
    );
    $stmtBorrador->execute([':id' => $idBorrador, ':artista' => $idArtista]);
    $borrador = $stmtBorrador->fetch() ?: $borrador;
    if (!$borrador['titulo'] && $idBorrador) {
        http_response_code(404);
        exit('El borrador no existe.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $progreso = filter_input(INPUT_POST, 'progreso', FILTER_VALIDATE_INT);
    $rutasImagenes = [];

    // Conservar imagen previa si ya existía
    if (!empty($borrador['ruta_archivo'])) {
        $rutasImagenes = explode(',', $borrador['ruta_archivo']);
    }

    // Procesar múltiples imágenes subidas
    if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
        $dirDestino = '../uploads/obras/';
        if (!is_dir($dirDestino)) {
            mkdir($dirDestino, 0755, true);
        }

        $totalArchivos = count($_FILES['imagenes']['name']);
        for ($i = 0; $i < $totalArchivos; $i++) {
            if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['imagenes']['name'][$i], PATHINFO_EXTENSION));
                $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (in_array($ext, $permitidas, true)) {
                    $nombreArchivo = 'borrador_' . time() . '_' . $i . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                    $destinoCompleto = $dirDestino . $nombreArchivo;
                    if (move_uploaded_file($_FILES['imagenes']['tmp_name'][$i], $destinoCompleto)) {
                        $rutasImagenes[] = '/poliart/uploads/obras/' . $nombreArchivo;
                    }
                }
            }
        }
    }

    $stringRutas = implode(',', array_unique($rutasImagenes));

    if (mb_strlen($titulo, 'UTF-8') < 2 || $progreso === false || $progreso < 0 || $progreso > 100) {
        $errorBorrador = 'Indica un título válido y un progreso entre 0 y 100.';
        $borrador = [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'progreso' => max(0, (int)$progreso),
            'ruta_archivo' => $stringRutas
        ];
    } else {
        if ($idBorrador) {
            $stmtGuardar = $pdo->prepare(
                'UPDATE borrador_obra
                 SET titulo = :titulo, descripcion = :descripcion, progreso = :progreso, ruta_archivo = :ruta
                 WHERE id_borrador = :id AND id_artista = :artista'
            );
            $stmtGuardar->execute([
                ':titulo' => $titulo,
                ':descripcion' => $descripcion ?: null,
                ':progreso' => $progreso,
                ':ruta' => $stringRutas ?: null,
                ':id' => $idBorrador,
                ':artista' => $idArtista,
            ]);
        } else {
            $stmtGuardar = $pdo->prepare(
                'INSERT INTO borrador_obra (id_artista, id_categoria, titulo, descripcion, progreso, ruta_archivo)
                 VALUES (:artista, :categoria, :titulo, :descripcion, :progreso, :ruta)'
            );
            $stmtGuardar->execute([
                ':artista' => $idArtista,
                ':categoria' => $idCategoria,
                ':titulo' => $titulo,
                ':descripcion' => $descripcion ?: null,
                ':progreso' => $progreso,
                ':ruta' => $stringRutas ?: null,
            ]);
        }
        header('Location: borradores.php');
        exit;
    }
}

require_once __DIR__ . '/../artista/header.php';
include_once __DIR__ . '/../artista/sidebar.php';
?>

<style>
html, body {
    height: 100% !important;
    margin: 0;
    padding: 0;
    overflow: hidden !important;
    background-image: url('<?= $project_base ?>app/includes/madera.jpg') !important;
    background-size: cover !important;
    background-position: center !important;
    background-attachment: fixed !important;
    background-repeat: no-repeat !important;
}

.main-content-wrapper {
    position: fixed;
    top: 4.25rem;
    left: 260px;
    width: calc(100% - 260px);
    height: calc(100vh - 4.25rem);
    box-sizing: border-box;
    padding: 2.5rem 1.5rem;
    overflow-y: auto;
    display: flex;
    justify-content: center;
    align-items: flex-start;
}

.form-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1.25rem;
    padding: 2rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(8px);
    width: 100%;
    max-width: 680px;
    margin: 0 auto 3rem auto;
}

.form-label-custom {
    display: block;
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 0.5rem;
}

.form-control-custom {
    width: 100%;
    background: var(--bg-body);
    border: 1px solid var(--border-subtle);
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--text-main);
    font-size: 0.925rem;
    box-sizing: border-box;
    outline: none;
    font-family: inherit;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control-custom:focus {
    border-color: var(--brand-orange);
    box-shadow: 0 0 0 3px rgba(237, 119, 69, 0.15);
}

.range-slider {
    accent-color: var(--brand-orange);
    width: 100%;
    cursor: pointer;
}

.upload-area {
    border: 2px dashed var(--border-subtle);
    border-radius: 0.75rem;
    padding: 1.25rem;
    text-align: center;
    background: var(--bg-body);
    cursor: pointer;
    transition: border-color 0.2s;
}

.upload-area:hover {
    border-color: var(--brand-orange);
}
</style>

<div class="main-content-wrapper">
    <div class="form-card">
        
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin: 0;">
                    <?= $idBorrador ? '✏️ Editar Borrador' : '📝 Nuevo Borrador' ?>
                </h1>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                    Disciplina: <strong style="color: var(--brand-orange); text-transform: capitalize;"><?= htmlspecialchars($tipoArte) ?></strong>
                </p>
            </div>
            <a href="borradores.php" style="background: var(--hover-item); color: var(--text-muted); padding: 0.5rem 0.85rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.825rem; font-weight: 600; border: 1px solid var(--border-subtle);">
                ✕ Volver
            </a>
        </div>

        <?php if ($errorBorrador): ?>
            <p role="alert" style="color: #ef4444; font-size: 0.875rem; background: rgba(239, 68, 68, 0.1); padding: 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(239, 68, 68, 0.2); margin-bottom: 1.25rem;"><?= htmlspecialchars($errorBorrador, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div>
                <label for="titulo_borrador" class="form-label-custom">Título</label>
                <input id="titulo_borrador" type="text" name="titulo" maxlength="160" required 
                       value="<?= htmlspecialchars($borrador['titulo'], ENT_QUOTES, 'UTF-8') ?>" 
                       class="form-control-custom" placeholder="Escribe el título de tu obra...">
            </div>

            <div>
                <label for="input-imagenes" class="form-label-custom">Imágenes / Bocetos de Referencia</label>
                <div class="upload-area" onclick="document.getElementById('input-imagenes').click();">
                    <!-- Atributo multiple añadido aquí -->
                    <input type="file" name="imagenes[]" id="input-imagenes" accept="image/*" multiple style="display: none;" onchange="previewImágenes(this)">
                    <div id="preview-container" style="display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center; align-items: center;">
                        <?php if (!empty($borrador['ruta_archivo'])): ?>
                            <?php foreach (explode(',', $borrador['ruta_archivo']) as $img): ?>
                                <?php if (trim($img)): ?>
                                    <img src="<?= htmlspecialchars(trim($img), ENT_QUOTES, 'UTF-8') ?>" alt="Vista previa" style="height: 100px; width: 100px; border-radius: 0.5rem; object-fit: cover;">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="width: 100%; padding: 1rem;">
                                <span style="font-size: 2rem; display: block; margin-bottom: 0.25rem;">📷</span>
                                <span style="font-size: 0.85rem; color: var(--text-muted);">Haz clic para seleccionar una o varias imágenes</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div>
                <label for="descripcion_borrador" class="form-label-custom">Descripción</label>
                <textarea id="descripcion_borrador" name="descripcion" rows="4" class="form-control-custom" 
                          placeholder="Describe detalles, ideas o notas..."><?= htmlspecialchars($borrador['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                    <label for="progreso_borrador" class="form-label-custom" style="margin-bottom: 0;">Progreso</label>
                    <span style="font-size: 0.85rem; font-weight: 700; color: var(--brand-orange);">
                        <output id="progreso-valor"><?= (int)$borrador['progreso'] ?></output>%
                    </span>
                </div>
                <input id="progreso_borrador" type="range" name="progreso" min="0" max="100" step="5" 
                       value="<?= (int)$borrador['progreso'] ?>" 
                       oninput="document.getElementById('progreso-valor').value=this.value" 
                       class="range-slider">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 0.5rem; border-top: 1px solid var(--border-subtle); padding-top: 1.25rem;">
                <a href="borradores.php" class="btn btn-neutral btn-sm" style="text-decoration: none; display: inline-flex; align-items: center;">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary btn-sm">
                    💾 Guardar borrador
                </button>
            </div>
        </form>

    </div>
</div>

<script>
function previewImágenes(input) {
    const container = document.getElementById('preview-container');
    container.innerHTML = '';
    if (input.files) {
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.cssText = 'height: 100px; width: 100px; border-radius: 0.5rem; object-fit: cover;';
                container.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }
}
</script>


