<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);
require_once __DIR__ . '/../artista/header.php';
include_once __DIR__ . '/../artista/sidebar.php';
?>
<style>
    html, body { height: 100%; overflow: hidden; }
    .tipo-obra-page { flex: 1; min-width: 0; height: calc(100vh - 4.25rem); overflow-y: auto; padding: 2rem; box-sizing: border-box; display: grid; place-items: center; }
    .tipo-obra-panel { width: min(650px, 100%); padding: 2rem 2.5rem; border: 1px solid var(--border-subtle); border-radius: 1.25rem; background: rgba(77, 34, 15, .96); box-shadow: 0 20px 55px rgba(45, 18, 8, .35); }
    .tipo-obra-title { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-subtle); color: var(--text-main); }
    .tipo-obra-title h1 { margin: 0; font-size: 1.55rem; }
    .tipo-obra-list { display: grid; gap: .85rem; margin-top: 1.25rem; }
    .tipo-obra-option { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; border: 1px solid rgba(237, 119, 69, .55); border-radius: .95rem; background: rgba(44, 20, 11, .65); color: var(--text-main); text-decoration: none; transition: transform .2s ease, background .2s ease, border-color .2s ease; }
    .tipo-obra-option:hover { transform: translateY(-2px); background: rgba(237, 119, 69, .16); border-color: var(--brand-orange); }
    .tipo-obra-icon { width: 2.5rem; text-align: center; font-size: 1.7rem; }
    .tipo-obra-option strong, .tipo-obra-option span { display: block; }
    .tipo-obra-option strong { font-size: 1rem; }
    .tipo-obra-option span { margin-top: .25rem; color: var(--text-muted); font-size: .82rem; }
    @media (max-width: 650px) { .tipo-obra-page { padding: 1rem; } .tipo-obra-panel { padding: 1.25rem; } }
</style>
<div class="tipo-obra-page">
    <section class="tipo-obra-panel" aria-labelledby="titulo-seleccion-obra">
        <div class="tipo-obra-title">
            <h1 id="titulo-seleccion-obra">🚀 Publicar Nueva Obra</h1>
            <a href="proyectos.php" aria-label="Cerrar" style="color:var(--text-muted);font-size:1.8rem;text-decoration:none;">×</a>
        </div>
        <div class="tipo-obra-list">
            <a class="tipo-obra-option" href="subir_obra.php?tipo=dibujo"><span class="tipo-obra-icon">🎨</span><span><strong>Ilustración, Pintura &amp; Arte Visual</strong><span>Lienzos, arte digital, acuarelas y bocetos.</span></span></a>
            <a class="tipo-obra-option" href="subir_obra.php?tipo=musica"><span class="tipo-obra-icon">🎵</span><span><strong>Música, Pistas &amp; Videoclips</strong><span>Composiciones, canciones, maquetas y videos.</span></span></a>
            <a class="tipo-obra-option" href="subir_obra.php?tipo=escenicas"><span class="tipo-obra-icon">🎭</span><span><strong>Artes Escénicas &amp; Teatro</strong><span>Obras de teatro, performances y danza en vivo.</span></span></a>
            <a class="tipo-obra-option" href="subir_obra.php?tipo=escritura"><span class="tipo-obra-icon">✍️</span><span><strong>Libros &amp; Literatura</strong><span>Novelas, cuentos, poemarios y ensayos.</span></span></a>
            <a class="tipo-obra-option" href="subir_obra.php?tipo=escultura"><span class="tipo-obra-icon">🗿</span><span><strong>Escultura &amp; Modelo 3D</strong><span>Modelado, piezas 3D y piezas artesanales.</span></span></a>
        </div>
    </section>
</div>

