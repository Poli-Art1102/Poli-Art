<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin(['artista']);
require_once __DIR__ . '/../header.php';

$project_base = '/poliart/';

$libros = [];
$errorCarga = false;

try {
    $stmt = getDB()->prepare(
        'SELECT o.id_obra AS id, o.titulo, o.descripcion AS sinopsis,
                o.ruta_imagen AS archivo, c.nombre AS categoria,
                COALESCE(NULLIF(a.nombre_artistico, \'\'), CONCAT(u.nombre, \' \', u.apellido)) AS autor,
                (SELECT COUNT(*) FROM me_gusta mg WHERE mg.id_obra = o.id_obra) AS likes,
                EXISTS(
                    SELECT 1 FROM me_gusta mg
                    WHERE mg.id_obra = o.id_obra AND mg.id_usuario = :id_usuario
                ) AS liked
         FROM obra o
         INNER JOIN artista a ON a.id_artista = o.id_artista
         INNER JOIN usuario u ON u.id_usuario = a.id_usuario
         INNER JOIN categoria_obra c ON c.id_categoria = o.id_categoria
         LEFT JOIN categoria_obra categoria_padre
            ON categoria_padre.id_categoria = c.id_categoria_padre
         WHERE o.estado_publicacion = \'publicada\'
           AND (c.slug = :slug_hija OR categoria_padre.slug = :slug_padre)
         ORDER BY COALESCE(o.publicada_en, o.creado_en) DESC, o.id_obra DESC'
    );
    $stmt->execute([
        ':id_usuario' => getUserId(),
        ':slug_hija' => 'literatura-escritura',
        ':slug_padre' => 'literatura-escritura',
    ]);
    $libros = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error al cargar obras de escritura: ' . $e->getMessage());
    $errorCarga = true;
}

foreach ($libros as &$libro) {
    $nombre = trim($libro['autor']);
    preg_match_all('/(?:^|\s)(\p{L})/u', $nombre, $iniciales);
    $libro['iniciales'] = strtoupper(implode('', array_slice($iniciales[1], 0, 2)));
    $libro['likes'] = (int)$libro['likes'];
    $libro['liked'] = (bool)$libro['liked'];
    $libro['archivo'] = is_string($libro['archivo'])
        && strpos($libro['archivo'], '/poliart/uploads/obras/') === 0
        ? $libro['archivo']
        : null;
    $libro['tipo_archivo'] = strtolower(pathinfo($libro['archivo'] ?? '', PATHINFO_EXTENSION));
}
unset($libro);

function escapar(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}
?>

<style>
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

.books-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 1.5rem;
    width: 100%;
}

.book-card {
    position: relative;
    border-radius: 1rem;
    overflow: hidden;
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    cursor: pointer;
    transition: transform 0.25s ease, border-color 0.25s ease;
    display: flex;
    flex-direction: column;
}

.book-card:hover {
    transform: translateY(-6px);
    border-color: var(--brand-orange);
}

.book-cover-box {
    position: relative;
    width: 100%;
    aspect-ratio: 2 / 3;
    overflow: hidden;
    background: #000;
}

.book-cover-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.book-card:hover .book-cover-box img {
    transform: scale(1.05);
}

.read-badge {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background-color: rgba(0, 0, 0, 0.85);
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.25rem 0.55rem;
    border-radius: 0.4rem;
    backdrop-filter: blur(4px);
}

.book-info {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.book-title {
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--text-main);
    margin: 0;
    line-height: 1.3;
}

.book-author {
    font-size: 0.8rem;
    color: var(--brand-orange);
    font-weight: 700;
    margin: 0;
}

.book-meta {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.2rem;
}

.empty-state {
    grid-column: 1 / -1;
    padding: 2rem;
    border: 1px solid var(--border-subtle);
    border-radius: 1rem;
    background: var(--bg-surface);
    color: var(--text-muted);
    text-align: center;
}

.document-cover {
    width: 100%;
    height: 100%;
    min-height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, #78350f, #431407);
    color: #fff7ed;
    font-size: 1.25rem;
    font-weight: 800;
    text-align: center;
}

.document-cover-detail {
    height: 100%;
    min-height: 100%;
}

.header-banner {
    background-color: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1rem;
    padding: 1.5rem 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

/* CONTENEDOR DE FICHA DEL LIBRO IGUAL A LA FOTO */
.book-detail-container {
    display: none;
    flex-direction: column;
    gap: 2rem;
    max-width: 900px;
    margin: 0 auto;
    width: 100%;
}

.book-detail-container.active {
    display: flex;
}

.book-detail-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1.25rem;
    padding: 2.25rem;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(8px);
}

.circle-avatar-initials {
    width: 3.2rem;
    height: 3.2rem;
    border-radius: 9999px;
    background: #ec4899;
    color: #ffffff;
    font-weight: 800;
    font-size: 1.15rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

</style>

<div style="display: flex; width: 100vw; height: calc(100vh - 4.25rem); overflow: hidden; background: transparent;">

    <?php include_once __DIR__ . '/../sidebar.php'; ?>

    <div id="scroll-main-wrapper" style="flex-grow: 1; height: 100%; overflow-y: auto; padding: 2.5rem; box-sizing: border-box;">
        
        <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.75rem;">

            <!-- HEADER BANNER -->
            <div class="header-banner">
                <div>
                    <h1 style="font-size: 1.875rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.6rem;">
                        ✍️ Libros y Escritura
                    </h1>
                    <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.3rem;">
                        Novelas, poemarios, ensayos y cuentos cortos escritos por los autores de Poli-Art.
                    </p>
                </div>

                <!-- BOTÓN REGRESAR A LA BIBLIOTECA -->
                <button id="btn-volver-grid" onclick="mostrarCuadricula()" style="display: none; background: var(--hover-item); border: 1px solid var(--border-subtle); color: var(--text-main); padding: 0.65rem 1.25rem; border-radius: 0.6rem; font-weight: 700; font-size: 0.875rem; cursor: pointer;">
                    ⊞ Volver a la Biblioteca
                </button>
            </div>

            <!-- 1. VISTA CUADRÍCULA DE PORTADAS -->
            <div class="books-grid" id="grid-obras-container">
                <?php if ($errorCarga): ?>
                    <div class="empty-state">No fue posible cargar las obras. Intenta de nuevo más tarde.</div>
                <?php elseif (!$libros): ?>
                    <div class="empty-state">Todavía no hay obras de escritura publicadas.</div>
                <?php else: ?>
                    <?php foreach ($libros as $libro): ?>
                        <div class="book-card" onclick="mostrarDetalleLibro(<?= (int)$libro['id'] ?>)">
                            <div class="book-cover-box">
                                <?php if (in_array($libro['tipo_archivo'], ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)): ?>
                                    <img src="<?= escapar($libro['archivo']) ?>" alt="<?= escapar($libro['titulo']) ?>">
                                <?php else: ?>
                                    <div class="document-cover">
                                        <span><?= $libro['tipo_archivo'] === 'pdf' ? '📄 PDF' : '✍️ Obra literaria' ?></span>
                                    </div>
                                <?php endif; ?>
                                <span class="read-badge">❤️ <span data-like-count="<?= (int)$libro['id'] ?>"><?= $libro['likes'] ?></span></span>
                            </div>
                            <div class="book-info">
                                <h3 class="book-title"><?= escapar($libro['titulo']) ?></h3>
                                <p class="book-author"><?= escapar($libro['autor']) ?></p>
                                <p class="book-meta"><?= escapar($libro['categoria']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- 2. DETALLE DE LA OBRA -->
            <div class="book-detail-container" id="feed-obras-container">
                <?php foreach ($libros as $libro): ?>
                    <div class="book-detail-card" id="post-card-<?= (int)$libro['id'] ?>" style="display: none;">
                        
                        <!-- SECCIÓN SUPERIOR DE LA FICHA -->
                        <div style="display: grid; grid-template-columns: 260px 1fr; gap: 2.25rem; align-items: start;">
                            
                            <!-- PORTADA GRANDE IZQUIERDA -->
                            <div style="border-radius: 1.25rem; overflow: hidden; border: 1px solid var(--border-subtle); box-shadow: 0 12px 30px rgba(0,0,0,0.3); aspect-ratio: 2/3; background: #000;">
                                <?php if (in_array($libro['tipo_archivo'], ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)): ?>
                                    <img src="<?= escapar($libro['archivo']) ?>" alt="<?= escapar($libro['titulo']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div class="document-cover document-cover-detail">
                                        <span><?= $libro['tipo_archivo'] === 'pdf' ? '📄 Documento PDF' : '✍️ Obra literaria' ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- DATOS Y BOTONES DERECHA -->
                            <div style="display: flex; flex-direction: column;">
                                
                                <div style="display: flex; align-items: flex-start; gap: 1rem;">
                                    <div class="circle-avatar-initials">
                                        <?= $libro['iniciales'] ?>
                                    </div>
                                    <div>
                                        <span style="font-size: 0.8rem; color: #f97316; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <?= escapar($libro['categoria']) ?>
                                        </span>
                                        <h2 style="color: var(--text-main); font-size: 1.85rem; font-weight: 800; margin: 0.2rem 0; line-height: 1.2;">
                                            <?= escapar($libro['titulo']) ?>
                                        </h2>
                                        <div style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.3rem;">
                                            Escrito por <strong style="color: var(--text-main);"><?= escapar($libro['autor']) ?></strong>
                                        </div>
                                    </div>
                                </div>

                                <div style="width: 100%; height: 1px; background-color: var(--border-subtle); margin: 1.5rem 0;"></div>

                                <div>
                                    <h4 style="color: var(--text-main); font-size: 1.05rem; font-weight: 800; margin: 0 0 0.6rem 0; display: flex; align-items: center; gap: 0.4rem;">
                                        📖 Sinopsis
                                    </h4>
                                    <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.6; margin: 0;">
                                        <?= nl2br(escapar($libro['sinopsis'] ?? 'El autor no agregó una descripción.')) ?>
                                    </p>
                                </div>

                                <div style="display: flex; gap: 1rem; margin-top: 1.75rem;">
                                    <button type="button" class="like-button" data-obra-id="<?= (int)$libro['id'] ?>" aria-pressed="<?= $libro['liked'] ? 'true' : 'false' ?>" style="background: var(--hover-item); border: 1px solid var(--border-subtle); color: var(--text-main); padding: 0.7rem 1.25rem; border-radius: 0.65rem; font-weight: 700; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 0.4rem;">
                                        ❤️ <span data-like-count="<?= (int)$libro['id'] ?>"><?= $libro['likes'] ?></span> Me gusta
                                    </button>
                                    
                                    <?php if ($libro['archivo']): ?>
                                        <a href="<?= escapar($libro['archivo']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                                            📖 Abrir <?= $libro['tipo_archivo'] === 'pdf' ? 'documento' : 'archivo' ?>
                                        </a>
                                    <?php endif; ?>
                                </div>

                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>

    </div>
</div>

<script>
function mostrarDetalleLibro(libroId) {
    document.getElementById('grid-obras-container').style.display = 'none';
    const feedContainer = document.getElementById('feed-obras-container');
    feedContainer.classList.add('active');
    document.getElementById('btn-volver-grid').style.display = 'block';

    document.querySelectorAll('.book-detail-card').forEach(card => card.style.display = 'none');
    
    const selectedCard = document.getElementById('post-card-' + libroId);
    if (selectedCard) {
        selectedCard.style.display = 'block';
    }

    const wrapper = document.getElementById('scroll-main-wrapper');
    if (wrapper) wrapper.scrollTop = 0;
}

function mostrarCuadricula() {
    document.getElementById('feed-obras-container').classList.remove('active');
    document.getElementById('grid-obras-container').style.display = 'grid';
    document.getElementById('btn-volver-grid').style.display = 'none';
    
    const wrapper = document.getElementById('scroll-main-wrapper');
    if (wrapper) wrapper.scrollTop = 0;
}

document.querySelectorAll('.like-button').forEach(button => {
    button.addEventListener('click', async () => {
        const idObra = button.dataset.obraId;
        button.disabled = true;

        try {
            const response = await fetch('../../includes/actions/megusta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({ id_obra: idObra })
            });
            const result = await response.json();

            if (!response.ok || !result.ok) {
                throw new Error(result.error || 'No fue posible actualizar el me gusta.');
            }

            document.querySelectorAll('[data-like-count="' + idObra + '"]').forEach(count => {
                count.textContent = result.total;
            });
            button.setAttribute('aria-pressed', result.liked ? 'true' : 'false');
        } catch (error) {
            console.error(error);
            window.alert(error.message || 'Ocurrió un error al actualizar el me gusta.');
        } finally {
            button.disabled = false;
        }
    });
});
</script>

