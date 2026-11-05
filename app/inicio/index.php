<?php
require_once __DIR__ . '/header.php';
?>

<style>
    .inicio-scroll-area > .inicio-contenido {
        flex: 1 0 auto;
    }

    :root {
        --brand-orange: #ED7745;
        --brand-yellow: #EDCE45;
        --bg-card: rgba(253, 235, 230, 0.92);
        --border-subtle: #F5C7B8;
        --text-main: #2C140B;
        --text-muted: #7A4B3A;
        --hover-item: #F7D4C8;
    }
    html.dark {
        --brand-orange: #FF9D73;
        --brand-yellow: #EDCE45;
        --bg-card: rgba(77, 34, 15, 0.92);
        --border-subtle: #853F22;
        --text-main: #FFF1EB;
        --text-muted: #E0C0B3;
        --hover-item: #4D220F;
    }

    .hero-section {
        background-color: var(--bg-card);
        border: 1px solid var(--border-subtle);
        border-radius: 1.25rem;
        padding: 3.5rem 2rem;
        text-align: center;
        backdrop-filter: blur(14px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.12);
    }

    .category-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-subtle);
        padding: 1.5rem 1rem;
        border-radius: 1rem;
        text-decoration: none;
        text-align: center;
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        backdrop-filter: blur(10px);
        display: block;
    }
    .category-card:hover {
        transform: translateY(-4px);
        border-color: var(--brand-orange);
        box-shadow: 0 8px 24px rgba(237,119,69,0.2);
    }

    .obra-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-subtle);
        border-radius: 1rem;
        overflow: hidden;
        backdrop-filter: blur(10px);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .obra-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(237,119,69,0.18);
    }

    .btn-hero-primary {
        background: linear-gradient(135deg, var(--brand-orange), #d45f2e);
        color: #fff;
        font-weight: 700;
        padding: 0.75rem 1.5rem;
        border-radius: 0.75rem;
        text-decoration: none;
        box-shadow: 0 4px 16px rgba(237,119,69,0.35);
        transition: opacity 0.2s, transform 0.15s;
        display: inline-block;
    }
    .btn-hero-primary:hover { opacity: 0.9; transform: translateY(-1px); }

    .btn-hero-secondary {
        background-color: var(--hover-item);
        color: var(--text-main);
        font-weight: 700;
        padding: 0.75rem 1.5rem;
        border-radius: 0.75rem;
        text-decoration: none;
        border: 1px solid var(--border-subtle);
        transition: border-color 0.2s, background 0.2s;
        display: inline-block;
    }
    .btn-hero-secondary:hover { border-color: var(--brand-orange); }

    .section-title {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 1rem;
        margin-top: 0;
    }
</style>

<div class="inicio-contenido" style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 2rem 1.5rem; display: flex; flex-direction: column; gap: 2.5rem; box-sizing: border-box;">

    <!-- HERO -->
    <section class="hero-section">
        <div style="font-size: 3.5rem; margin-bottom: 0.75rem;">🎨</div>
        <h1 style="font-size: 2.25rem; font-weight: 800; color: var(--text-main); margin: 0 0 0.75rem 0; line-height: 1.2;">
            Plataforma de Arte Digital<br>
            <span style="background: linear-gradient(135deg, var(--brand-orange), var(--brand-yellow)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Poli-Art</span>
        </h1>
        <p style="color: var(--text-muted); font-size: 1rem; max-width: 36rem; margin: 0 auto 2rem auto; line-height: 1.6;">
            Explora literatura, pintura, música, esculturas y artes escénicas creadas por la comunidad de Medellín.
        </p>
       
    </section>

    <!-- CATEGORÍAS -->
    <section>
        <h2 class="section-title">🗂️ Categorías de Arte</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem;">

            <a href="<?= $project_base ?>app/inicio/artes/escritura.php" class="category-card">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📚</div>
                <h3 style="color: var(--text-main); font-size: 0.9rem; font-weight: 700; margin: 0;">Libros y Escritura</h3>
            </a>

            <a href="<?= $project_base ?>app/inicio/artes/dibujo_pintura.php" class="category-card">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🎨</div>
                <h3 style="color: var(--text-main); font-size: 0.9rem; font-weight: 700; margin: 0;">Dibujo y Pintura</h3>
            </a>

            <a href="<?= $project_base ?>app/inicio/artes/musica.php" class="category-card">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🎵</div>
                <h3 style="color: var(--text-main); font-size: 0.9rem; font-weight: 700; margin: 0;">Música</h3>
            </a>

            <a href="<?= $project_base ?>app/inicio/artes/escultura.php" class="category-card">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🗿</div>
                <h3 style="color: var(--text-main); font-size: 0.9rem; font-weight: 700; margin: 0;">Esculturas</h3>
            </a>

            <a href="<?= $project_base ?>app/inicio/artes/artes_escenicas.php" class="category-card">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🎭</div>
                <h3 style="color: var(--text-main); font-size: 0.9rem; font-weight: 700; margin: 0;">Artes Escénicas</h3>
            </a>

        </div>
    </section>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
