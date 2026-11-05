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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    $idBorrador = filter_input(INPUT_POST, 'id_borrador', FILTER_VALIDATE_INT);
    if ($idBorrador) {
        $stmtDelete = $pdo->prepare(
            'DELETE FROM borrador_obra WHERE id_borrador = :id AND id_artista = :artista'
        );
        $stmtDelete->execute([':id' => $idBorrador, ':artista' => $idArtista]);
    }
    header('Location: borradores.php');
    exit;
}

$borradores = [];

try {
    $stmtBorradores = $pdo->prepare(
        'SELECT id_borrador AS id, titulo, descripcion, ruta_archivo AS imagen,
                progreso, actualizado_en
         FROM borrador_obra
         WHERE id_artista = :id_artista
         ORDER BY actualizado_en DESC'
    );
    $stmtBorradores->execute([':id_artista' => $idArtista]);
    $borradores = array_map(static function (array $borrador): array {
        $rawRuta = (string)($borrador['imagen'] ?? '');
        $primeraImagen = '';

        if ($rawRuta !== '') {
            // Separa por comas si hay múltiples imágenes y toma la primera
            $listaImagenes = explode(',', $rawRuta);
            $primeraImagen = trim($listaImagenes[0]);
        }

        $borrador['imagen'] = $primeraImagen;
        $borrador['progreso'] = (int)$borrador['progreso'] . '%';
        $borrador['fecha_modificacion'] = date('d/m/Y H:i', strtotime($borrador['actualizado_en']));
        return $borrador;
    }, $stmtBorradores->fetchAll());
} catch (PDOException $e) {
    error_log('Error al cargar borradores del artista: ' . $e->getMessage());
    $borradores = [];
    $errorCarga = true;
}

require_once __DIR__ . '/../artista/header.php';
include_once __DIR__ . '/../artista/sidebar.php';
?>

<style>
/* Bloquear scroll externo para evitar doble scrollbar */
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
}

.draft-card {
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    backdrop-filter: blur(8px);
    display: flex;
    flex-direction: column;
    transition: transform 0.2s, background-color 0.3s ease, border-color 0.3s ease;
}

.draft-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(237, 119, 69, 0.2);
}

.btn-accion-borrador {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.825rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    transition: all 0.2s;
}

.btn-continuar {
    background: linear-gradient(135deg, var(--brand-orange) 0%, #ea580c 100%);
    color: #ffffff;
    border: none;
    box-shadow: 0 4px 10px rgba(237, 119, 69, 0.3);
}

.btn-continuar:hover {
    background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
}

.btn-eliminar {
    background-color: var(--hover-item);
    color: #ef4444;
    border: 1px solid var(--border-subtle);
}

.btn-eliminar:hover {
    background-color: rgba(239, 68, 68, 0.15);
}

.option-art-btn {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.85rem 1rem;
    background-color: var(--hover-item);
    border: 1px solid var(--border-subtle);
    border-radius: 0.75rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.option-art-btn:hover {
    background-color: var(--bg-body);
    border-color: var(--brand-orange);
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(237, 119, 69, 0.15);
}

@keyframes modalFadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>

<div class="main-content-wrapper">
    <div style="max-width: 1000px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.75rem;">

        <!-- ENCABEZADO Y BOTÓN QUE ABRE EL MODAL DE SELECCIÓN DE ARTE -->
        <div style="background-color: var(--bg-surface); padding: 1.5rem 2rem; border-radius: 1rem; border: 1px solid var(--border-subtle); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; transition: background-color 0.3s ease, border-color 0.3s ease;">
            <div>
                <h1 style="font-size: 1.875rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    📝 Mis Borradores
                </h1>
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.3rem;">
                    Continúa editando tus obras y proyectos guardados antes de publicarlos oficialmente.
                </p>
            </div>
            
            <button onclick="abrirModalTipoArte('borrador')" class="btn btn-primary">
                ➕ Nuevo Borrador
            </button>
        </div>

        <!-- LISTADO DE BORRADORES -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php if (!empty($errorCarga)): ?>
                <p role="alert" style="color: var(--text-main);">No fue posible cargar tus borradores.</p>
            <?php elseif (empty($borradores)): ?>
                <p style="color: var(--text-muted);">Aún no tienes borradores guardados.</p>
            <?php endif; ?>

            <?php foreach ($borradores as $b): ?>
                <div class="draft-card">
                    <div style="position: relative; width: 100%; height: 180px; background-color: var(--hover-item); overflow: hidden;">
                        <?php if (!empty($b['imagen'])): ?>
                            <img src="<?= htmlspecialchars($b['imagen'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($b['titulo'], ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="height: 100%; display: grid; place-items: center; color: var(--text-muted);">Sin imagen</div>
                        <?php endif; ?>
                        <span style="position: absolute; top: 10px; right: 10px; background-color: rgba(0, 0, 0, 0.75); color: #ffffff; font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 0.375rem;">
                            Progreso: <?= $b['progreso'] ?>
                        </span>
                    </div>

                    <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem; flex-grow: 1; justify-content: space-between;">
                        <div>
                            <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;"><?= $b['fecha_modificacion'] ?></span>
                            <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin: 0.2rem 0;">
                                <?= htmlspecialchars($b['titulo']) ?>
                            </h3>
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0; line-height: 1.4;">
                                <?= htmlspecialchars($b['descripcion']) ?>
                            </p>
                        </div>

                        <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem; border-top: 1px solid var(--border-subtle); padding-top: 0.85rem;">
                            <a href="subir_borrador.php?id=<?= (int)$b['id'] ?>" class="btn-accion-borrador btn-continuar" style="flex: 1; justify-content: center;">
                                ✏️ Continuar
                            </a>
                            <form method="POST" onsubmit="return confirm('¿Deseas eliminar este borrador?');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_borrador" value="<?= (int)$b['id'] ?>">
                                <button type="submit" class="btn-accion-borrador btn-eliminar">Borrar</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<!-- MODAL INTERACTIVO DE SELECCIÓN DE TIPO DE ARTE -->
<div id="modal-tipo-arte" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(6px); z-index: 9999; align-items: center; justify-content: center;">
    
    <div style="background: var(--bg-surface); border: 1px solid var(--border-subtle); width: 90%; max-width: 520px; border-radius: 1rem; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.3); display: flex; flex-direction: column; gap: 1.25rem; animation: modalFadeIn 0.25s ease; transition: background-color 0.3s ease, border-color 0.3s ease;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.75rem;">
            <h2 id="modal-titulo" style="font-size: 1.35rem; font-weight: 800; color: var(--text-main); margin: 0;">
                🎨 Selecciona el Tipo de Arte
            </h2>
            <button onclick="cerrarModalTipoArte()" aria-label="Cerrar" style="background: transparent; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">×</button>
        </div>

        <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0;">
            Elige la disciplina artística para adaptar el formulario y los campos específicos:
        </p>

        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            
            <a id="opt-dibujo" href="subir_borrador.php?tipo=dibujo" class="option-art-btn">
                <span style="font-size: 1.5rem;">🎨</span>
                <div>
                    <strong style="display: block; color: var(--text-main); font-size: 0.95rem;">Ilustración, Pintura & Arte Visual</strong>
                    <span style="font-size: 0.775rem; color: var(--text-muted);">Imágenes, lienzos, diseño digital y bocetos.</span>
                </div>
            </a>

            <a id="opt-musica" href="subir_borrador.php?tipo=musica" class="option-art-btn">
                <span style="font-size: 1.5rem;">🎵</span>
                <div>
                    <strong style="display: block; color: var(--text-main); font-size: 0.95rem;">Música, Pistas & Audio</strong>
                    <span style="font-size: 0.775rem; color: var(--text-muted);">Canciones, videoclips, sesiones en vivo y maquetas.</span>
                </div>
            </a>

            <a id="opt-escenicas" href="subir_borrador.php?tipo=escenicas" class="option-art-btn">
                <span style="font-size: 1.5rem;">🎭</span>
                <div>
                    <strong style="display: block; color: var(--text-main); font-size: 0.95rem;">Artes Escénicas & Teatro</strong>
                    <span style="font-size: 0.775rem; color: var(--text-muted);">Obras teatrales, danza, performances y videos.</span>
                </div>
            </a>

            <a id="opt-escritura" href="subir_borrador.php?tipo=escritura" class="option-art-btn">
                <span style="font-size: 1.5rem;">✍️</span>
                <div>
                    <strong style="display: block; color: var(--text-main); font-size: 0.95rem;">Libros & Literatura</strong>
                    <span style="font-size: 0.775rem; color: var(--text-muted);">Novelas, cuentos, ensayos y poemarios.</span>
                </div>
            </a>

            <a id="opt-escultura" href="subir_borrador.php?tipo=escultura" class="option-art-btn">
                <span style="font-size: 1.5rem;">🗿</span>
                <div>
                    <strong style="display: block; color: var(--text-main); font-size: 0.95rem;">Escultura & Modelo 3D</strong>
                    <span style="font-size: 0.775rem; color: var(--text-muted);">Modelado, piezas 3D y artesanías.</span>
                </div>
            </a>

        </div>

    </div>

</div>

<script>
function abrirModalTipoArte(modo = 'borrador') {
    const modal = document.getElementById('modal-tipo-arte');
    const titulo = document.getElementById('modal-titulo');
    
    const destinoBase = modo === 'obra' ? 'subir_obra.php' : 'subir_borrador.php';
    titulo.textContent = modo === 'obra' ? '🎨 Publicar Nueva Obra' : '📝 Crear Nuevo Borrador';

    document.getElementById('opt-dibujo').href = destinoBase + '?tipo=dibujo';
    document.getElementById('opt-musica').href = destinoBase + '?tipo=musica';
    document.getElementById('opt-escenicas').href = destinoBase + '?tipo=escenicas';
    document.getElementById('opt-escritura').href = destinoBase + '?tipo=escritura';
    document.getElementById('opt-escultura').href = destinoBase + '?tipo=escultura';

    modal.style.display = 'flex';
}

function cerrarModalTipoArte() {
    document.getElementById('modal-tipo-arte').style.display = 'none';
}
</script>
