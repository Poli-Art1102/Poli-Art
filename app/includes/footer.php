<?php
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$requestPath = is_string($requestPath) ? $requestPath : '';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
$requestPathNormalizado = '/' . trim($requestPath, '/');
$moduloSolicitado = strtolower(trim((string) ($_GET['module'] ?? '')));
$esInicioPrincipal = $moduloSolicitado === ''
    && ($requestPathNormalizado === $basePath
    || $requestPathNormalizado === $basePath . '/index.php');
$esInicio = preg_match('~/inicio(?:/|$)~', $requestPath) === 1 || $esInicioPrincipal;
$rutaInicio = rtrim($requestPath, '/');
?>

<?php if ($esInicio): ?>
<?php if (false): ?>
    <footer class="poliart-footer<?= $esInicioPrincipal ? ' poliart-footer--home' : '' ?>">
        <div class="poliart-footer__inner">
            <div>
                <strong>ðŸŽ¨ Poli-Art</strong>
                <span>Plataforma de arte digital de MedellÃ­n</span>
            </div>
            <div>
                <span>Â© 2026 Poli-Art</span>
            </div>
        </div>
    </footer>
    <style>
<?php endif; ?>
<?php endif; ?>
    <footer class="poliart-footer<?= $esInicioPrincipal ? ' poliart-footer--home' : '' ?>">
        <div class="poliart-footer__inner">
            <div>
                <strong>🎨 Poli-Art</strong>
                <span>Plataforma de arte digital de Medellín</span>
            </div>
            <div>
<a href="<?= $project_base ?>app/inicio/terminos.php">Términos y condiciones</a> <span>© 2026 Poli-Art</span>
            </div>
        </div>
    </footer>
    <style>
        .poliart-footer {
            width: 100%;
            box-sizing: border-box;
            margin-top: 2rem;
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--header-border) !important;
            background: var(--header-bg) !important;
            color: var(--header-text) !important;
        }

        .poliart-footer--home {
            margin-top: 0;
            flex: 0 0 auto;
        }

        .poliart-footer__inner {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            box-sizing: border-box;
        }

        .poliart-footer strong {
            display: block;
            color: var(--brand-orange);
            font-size: 1.15rem;
        }

        .poliart-footer span {
            display: block;
            margin-top: 0.3rem;
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .poliart-footer__inner > div:last-child {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .poliart-footer a {
            color: var(--brand-orange);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .poliart-footer a:hover {
            text-decoration: underline;
        }
    </style>
<?php if ($esInicio): ?>
    </div>
<?php endif; ?>

<script src="<?= $project_base ?>public/assets/js/password-visibility.js"></script>
<script src="<?= $project_base ?>public/assets/js/app.js"></script>
</body>
</html>
