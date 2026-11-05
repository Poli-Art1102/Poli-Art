<?php
require_once __DIR__ . '/db.php';

function renderizarAnunciosGlobales(string $audiencia): void
{
    if (!in_array($audiencia, ['artistas', 'clientes'], true)) {
        throw new InvalidArgumentException('Audiencia de anuncios no válida.');
    }

    try {
        $stmt = getDB()->prepare(
            "SELECT titulo, mensaje, tipo, creado_en
             FROM anuncio_global
             WHERE activo = 1 AND (expira_en IS NULL OR expira_en > NOW())
               AND audiencia IN ('todos', :audiencia)
             ORDER BY creado_en DESC
             LIMIT 3"
        );
        $stmt->execute([':audiencia' => $audiencia]);
        $anuncios = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error al cargar anuncios globales: ' . $e->getMessage());
        echo '<p role="alert" style="color:#dc2626;">No se pudieron cargar los comunicados de la plataforma.</p>';
        return;
    }

    if (!$anuncios) {
        return;
    }

    foreach ($anuncios as $anuncio): ?>
        <aside style="padding:1rem 1.25rem;border:1px solid var(--border-subtle);border-left:4px solid var(--brand-orange);border-radius:.75rem;background:var(--bg-surface);color:var(--text-main);">
            <strong><?= htmlspecialchars(ucfirst($anuncio['tipo']), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($anuncio['titulo'], ENT_QUOTES, 'UTF-8') ?></strong>
            <p style="margin:.4rem 0;color:var(--text-muted);"><?= nl2br(htmlspecialchars($anuncio['mensaje'], ENT_QUOTES, 'UTF-8')) ?></p>
            <small style="color:var(--text-muted);"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($anuncio['creado_en'])), ENT_QUOTES, 'UTF-8') ?></small>
        </aside>
    <?php endforeach;
}
