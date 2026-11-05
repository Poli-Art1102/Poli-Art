<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['administrador']);

$project_base = '/poliart/';

$pdo = getDB();
$errorAnuncios = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        if (isset($_POST['crear_anuncio'])) {
            $titulo = trim($_POST['titulo'] ?? '');
            $mensaje = trim($_POST['mensaje'] ?? '');
            $audiencia = $_POST['audiencia'] ?? 'todos';
            $tipo = $_POST['tipo'] ?? 'informativo';
            $audienciasValidas = ['todos', 'artistas', 'clientes'];
            $tiposValidos = ['informativo', 'importante', 'mantenimiento'];

            if ($titulo === '' || mb_strlen($titulo) > 160 || $mensaje === '') {
                throw new InvalidArgumentException('El título y el mensaje son obligatorios; el título admite hasta 160 caracteres.');
            }
            if (!in_array($audiencia, $audienciasValidas, true) || !in_array($tipo, $tiposValidos, true)) {
                throw new InvalidArgumentException('La audiencia o el tipo de anuncio no son válidos.');
            }

            $stmtInsertar = $pdo->prepare(
                'INSERT INTO anuncio_global (id_admin, titulo, mensaje, audiencia, tipo)
                 VALUES (:id_admin, :titulo, :mensaje, :audiencia, :tipo)'
            );
            $stmtInsertar->execute([
                ':id_admin' => getUserId(),
                ':titulo' => $titulo,
                ':mensaje' => $mensaje,
                ':audiencia' => $audiencia,
                ':tipo' => $tipo,
            ]);
            $idAnuncio = (int)$pdo->lastInsertId();
            $pdo->prepare(
                "INSERT INTO administrador_log (id_admin, accion, entidad, id_entidad, descripcion)
                 VALUES (:id_admin, 'crear_anuncio', 'anuncio_global', :id_anuncio, :descripcion)"
            )->execute([
                ':id_admin' => getUserId(),
                ':id_anuncio' => $idAnuncio,
                ':descripcion' => 'Se publicó el anuncio: ' . $titulo,
            ]);
        } elseif (isset($_POST['retirar_anuncio'])) {
            $idAnuncio = filter_var($_POST['id_anuncio'] ?? null, FILTER_VALIDATE_INT);
            if (!$idAnuncio || $idAnuncio < 1) {
                throw new InvalidArgumentException('El anuncio indicado no es válido.');
            }
            $stmtRetirar = $pdo->prepare('UPDATE anuncio_global SET activo = 0 WHERE id_anuncio = :id');
            $stmtRetirar->execute([':id' => $idAnuncio]);
            if ($stmtRetirar->rowCount() !== 1) {
                throw new InvalidArgumentException('El anuncio no existe o ya fue retirado.');
            }
            $pdo->prepare(
                "INSERT INTO administrador_log (id_admin, accion, entidad, id_entidad, descripcion)
                 VALUES (:id_admin, 'retirar_anuncio', 'anuncio_global', :id_anuncio, 'Se retiró un anuncio global')"
            )->execute([
                ':id_admin' => getUserId(),
                ':id_anuncio' => $idAnuncio,
            ]);
        }

        $pdo->commit();
        header('Location: anuncio_global.php');
        exit;
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errorAnuncios = $e->getMessage();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Error al guardar anuncio global: ' . $e->getMessage());
        $errorAnuncios = 'No fue posible guardar el anuncio. Verifica que la migración esté aplicada.';
    }
}

try {
    $anuncios_previos = $pdo->query(
        "SELECT id_anuncio AS id, titulo, mensaje, audiencia, tipo,
                CASE WHEN activo = 0 THEN 'Retirado'
                     WHEN expira_en IS NOT NULL AND expira_en <= NOW() THEN 'Expirado'
                     ELSE 'Activo' END AS estado,
                activo, expira_en, creado_en AS fecha
         FROM anuncio_global ORDER BY creado_en DESC"
    )->fetchAll();
} catch (PDOException $e) {
    error_log('Error al consultar anuncios globales: ' . $e->getMessage());
    $anuncios_previos = [];
    $errorAnuncios = $errorAnuncios ?: 'No se pudo cargar el historial de anuncios.';
}
require_once __DIR__ . '/../admin/header.php';
?>

<style>
.container-announcement {
    background-color: var(--bg-surface) !important;
    border: 1px solid var(--border-subtle) !important;
    border-radius: 1.25rem;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(12px);
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    box-sizing: border-box;
}

.btn-toggle-form {
    background: linear-gradient(135deg, var(--brand-orange) 0%, #ea580c 100%) !important;
    color: #ffffff !important;
    border: none;
    padding: 0.75rem 1.4rem;
    border-radius: 0.75rem;
    font-weight: 800;
    font-size: 0.88rem;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-toggle-form:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(249, 115, 22, 0.45);
}

.form-box {
    background-color: var(--hover-item) !important;
    border: 1px solid var(--border-subtle) !important;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
    display: none; /* Oculto inicialmente */
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.form-control {
    background-color: var(--bg-surface) !important;
    border: 1px solid var(--border-subtle) !important;
    color: var(--text-main) !important;
    padding: 0.75rem 1rem;
    border-radius: 0.75rem;
    width: 100%;
    outline: none;
    font-size: 0.9rem;
    box-sizing: border-box;
}

.form-control:focus {
    border-color: var(--brand-orange) !important;
}

.btn-publish {
    background: linear-gradient(135deg, var(--brand-orange) 0%, #ea580c 100%) !important;
    color: #ffffff !important;
    border: none;
    padding: 0.85rem 1.75rem;
    border-radius: 0.75rem;
    font-weight: 800;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
}

.btn-publish:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(249, 115, 22, 0.45);
}

.announcement-card {
    background-color: var(--hover-item) !important;
    border: 1px solid var(--border-subtle) !important;
    border-radius: 1rem;
    padding: 1.25rem;
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.badge-status {
    font-size: 0.75rem;
    font-weight: 800;
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
}

.badge-activo {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.badge-expirado {
    background: rgba(156, 163, 175, 0.15);
    color: #9ca3af;
    border: 1px solid rgba(156, 163, 175, 0.3);
}

.badge-type {
    background: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    color: var(--brand-orange);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.2rem 0.55rem;
    border-radius: 0.5rem;
}
</style>

<div style="width: 100%; padding: 0 2rem; box-sizing: border-box;">
    
    <div class="container-announcement">
        
        <!-- ENCABEZADO -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <a href="<?= $project_base ?>app/admin/index.php" style="text-decoration: none; color: var(--brand-orange); font-weight: 700; font-size: 0.85rem;">
                    &larr; Volver al Panel Principal
                </a>
                <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin: 0.5rem 0 0 0;">
                    📢 Publicar Anuncio Global
                </h1>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0.25rem 0 0 0;">
                    Envía avisos informativos, novedades o alertas a la comunidad de Poli-Art Medellín
                </p>
            </div>

            <!-- BOTÓN PARA DESPLEGAR EL FORMULARIO -->
            <button class="btn-toggle-form" onclick="toggleFormulario()">
                <span>✍️ Redactar Anuncio</span>
            </button>
        </div>

        <!-- FORMULARIO DE CREACIÓN (SE MUESTRA AL DAR CLIC EN REDACTAR) -->
        <div class="form-box" id="formRedactar">
            <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin: 0 0 1.25rem 0;">
                📝 Nuevo Comunicado
            </h3>

            <form method="POST">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.4rem;">
                            Título del Anuncio
                        </label>
                        <input type="text" id="titulo" name="titulo" maxlength="160" class="form-control" placeholder="Ej: Nueva funcionalidad de búsqueda" required>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.4rem;">
                            Audiencia Destino
                        </label>
                        <select id="audiencia" class="form-control">
                            <option value="todos">Toda la Comunidad (Artistas + Clientes)</option>
                            <option value="artistas">Solo Artistas</option>
                            <option value="clientes">Solo Clientes / Compradores</option>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.4rem;">
                            Nivel de Importancia
                        </label>
                        <select id="tipo" class="form-control">
                            <option value="informativo">Informativo</option>
                            <option value="importante">Importante</option>
                            <option value="mantenimiento">Mantenimiento</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.4rem;">
                        Mensaje del Anuncio
                    </label>
                    <textarea id="mensaje" name="mensaje" class="form-control" rows="4" placeholder="Escribe aquí el contenido detallado del mensaje para la plataforma..." required></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" onclick="toggleFormulario()" class="btn btn-neutral">
                        Cancelar
                    </button>
                    <button type="submit" name="crear_anuncio" value="1" class="btn-publish">
                        🚀 Publicar Anuncio Ahora
                    </button>
                </div>
            </form>
        </div>
        <?php if ($errorAnuncios !== ''): ?>
            <p role="alert" style="color:#dc2626;"><?= htmlspecialchars($errorAnuncios, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <!-- HISTORIAL DE ANUNCIOS -->
        <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin: 0 0 1rem 0;">
            📜 Historial de Anuncios Emitidos
        </h3>

        <div id="listaAnuncios">
            <?php foreach ($anuncios_previos as$anuncio): ?>
                <div class="announcement-card">
                    <div style="flex: 1; min-width: 280px;">
                        <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.4rem;">
                            <span class="badge-type"><?= htmlspecialchars(ucfirst($anuncio['tipo']), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge-status <?= $anuncio['estado'] === 'Activo' ? 'badge-activo' : 'badge-expirado' ?>">
                            <?= htmlspecialchars($anuncio['estado'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">
                                👥 Para: <strong><?= htmlspecialchars(ucfirst($anuncio['audiencia']), ENT_QUOTES, 'UTF-8') ?></strong>
                            </span>
                        </div>
                        <h4 style="font-size: 1rem; font-weight: 800; color: var(--text-main); margin: 0 0 0.35rem 0;">
                            <?= htmlspecialchars($anuncio['titulo'], ENT_QUOTES, 'UTF-8') ?>
                        </h4>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0; line-height: 1.4;">
                            <?= htmlspecialchars($anuncio['mensaje'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 0.5rem;">
                            🕒 Publicado el <?= htmlspecialchars($anuncio['fecha'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <?php if ($anuncio['estado'] === 'Activo'): ?>
                            <form method="POST">
                                <input type="hidden" name="id_anuncio" value="<?= (int)$anuncio['id'] ?>">
                                <button name="retirar_anuncio" value="1" class="btn btn-danger btn-sm">
                                    🗑️ Retirar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

</div>

<script>
function toggleFormulario() {
    const form = document.getElementById('formRedactar');
    if (form.style.display === 'block') {
        form.style.display = 'none';
    } else {
        form.style.display = 'block';
        form.scrollIntoView({ behavior: 'smooth' });
    }
}

</script>

