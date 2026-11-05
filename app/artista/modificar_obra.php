<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(['artista']);
require_once __DIR__ . '/../includes/catalogo_obras.php';
require_once __DIR__ . '/../includes/notificaciones.php';

$project_base = '/poliart/';
$paginasOrigen = [
    'perfil' => 'perfil.php',
    'proyectos' => 'proyectos.php',
];
$origen = $_POST['origen'] ?? $_GET['origen'] ?? 'proyectos';
if (!is_string($origen) || !isset($paginasOrigen[$origen])) {
    $origen = 'proyectos';
}

$obraId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$pdo = getDB();
$stmtObra = $pdo->prepare(
    'SELECT o.id_obra AS id, o.id_artista, o.titulo, o.precio, o.moneda, o.estado_venta,
            o.reservada_por, ru.nombre_usuario AS reservada_por_usuario,
            o.estado_publicacion, o.descripcion, o.ruta_imagen,
           o.permite_colaboracion, c.id_categoria, c.nombre AS categoria,
           EXISTS(
               SELECT 1 FROM reporte_contenido r
               WHERE r.id_obra = o.id_obra AND r.estado = \'resuelto\'
           ) AS retiro_por_reporte
     FROM obra o
     INNER JOIN artista a ON a.id_artista = o.id_artista
     LEFT JOIN usuario ru ON ru.id_usuario = o.reservada_por
     INNER JOIN categoria_obra c ON c.id_categoria = o.id_categoria
     WHERE o.id_obra = :id AND a.id_usuario = :id_usuario
     LIMIT 1'
);
$stmtObra->execute([':id' => $obraId ?: 0, ':id_usuario' => getUserId()]);
$obra = $stmtObra->fetch();
if (!$obra) {
    http_response_code(404);
    exit('No se encontró una obra tuya con ese identificador.');
}
$obra['estado'] = $obra['estado_publicacion'] === 'oculta' ? 'oculta' : $obra['estado_venta'];
$obra['imagen'] = is_string($obra['ruta_imagen'])
    && strpos($obra['ruta_imagen'], '/poliart/uploads/obras/') === 0
    ? $obra['ruta_imagen']
    : '';
$categorias = $pdo->query(
    'SELECT id_categoria, nombre FROM categoria_obra WHERE activa = 1 ORDER BY nombre'
)->fetchAll();
$mensaje_exito = '';
$error_formulario = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // Obra vendida: bloquear cualquier modificación de estado
    if ($obra['estado_venta'] === 'vendida') {
        $error_formulario = 'Esta obra ya fue vendida. Su estado no puede modificarse.';
    } else {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
    $estado = $_POST['estado'] ?? '';
    $reservadaPorUsuario = trim((string)($_POST['reservada_por_usuario'] ?? ''));
    $idCategoria = filter_input(INPUT_POST, 'categoria', FILTER_VALIDATE_INT);
    $estadosPermitidos = ['disponible', 'reservada', 'no_disponible', 'oculta'];
    // 'vendida' NO está en la lista — ese estado solo lo asigna el sistema
    // automáticamente al confirmar el pago de un pedido.

    $reservadaPor = null;
    if ($estado === 'reservada') {
        $stmtReservante = $pdo->prepare("SELECT id_usuario FROM usuario WHERE nombre_usuario = ? AND rol IN ('cliente', 'artista') LIMIT 1");
        $stmtReservante->execute([$reservadaPorUsuario]);
        $reservadaPor = $stmtReservante->fetchColumn();
    }

    if (mb_strlen($titulo, 'UTF-8') < 2 || mb_strlen($titulo, 'UTF-8') > 160
        || $precio === false || $precio === null || $precio < 0 || $precio > 9999999999.99
        || !in_array($estado, $estadosPermitidos, true) || !$idCategoria
        || !in_array($idCategoria, array_map('intval', array_column($categorias, 'id_categoria')), true)
        || ($estado === 'reservada' && (!$reservadaPorUsuario || !$reservadaPor))
        || ($estado === 'oculta' && !in_array($obra['estado_publicacion'], ['publicada', 'oculta'], true))
        || (!empty($obra['retiro_por_reporte']) && $obra['estado_publicacion'] === 'oculta' && $estado !== 'oculta')) {
        $error_formulario = 'Revisa el título, precio, categoría y estado de la obra.';
        if ($estado === 'reservada' && (!$reservadaPorUsuario || !$reservadaPor)) {
            $error_formulario = 'Debes indicar un nombre de usuario válido de cliente o artista para reservar la obra.';
        }
        if (!empty($obra['retiro_por_reporte']) && $obra['estado_publicacion'] === 'oculta' && $estado !== 'oculta') {
            $error_formulario = 'Esta obra fue retirada por moderación tras un reporte y no puede volver a publicarse desde tu perfil.';
        }
    } else {
        $estadoPublicacion = $estado === 'oculta'
            ? 'oculta'
            : (in_array($obra['estado_publicacion'], ['oculta', 'rechazada', 'pendiente'], true)
                ? 'publicada'
                : $obra['estado_publicacion']);
        $estadoVenta = $estado === 'oculta' ? $obra['estado_venta'] : $estado;
        $cambioReservante = (int)($obra['reservada_por'] ?? 0) !== (int)($reservadaPor ?: 0);
        if ($estadoVenta !== 'reservada') {
            $reservadaPor = null;
        }
        $cambioEstado = $obra['estado_publicacion'] !== $estadoPublicacion
            || $obra['estado_venta'] !== $estadoVenta;
        $stmtActualizar = $pdo->prepare(
            'UPDATE obra
             SET titulo = :titulo, descripcion = :descripcion, precio = :precio,
                 estado_publicacion = :estado_publicacion, estado_venta = :estado_venta,
                 reservada_por = :reservada_por,
                 publicada_en = CASE WHEN :estado_publicacion_fecha = \'publicada\' THEN COALESCE(publicada_en, NOW()) ELSE publicada_en END,
                 id_categoria = :categoria, permite_colaboracion = :colaboracion
             WHERE id_obra = :id AND id_artista = (
                 SELECT id_artista FROM artista WHERE id_usuario = :usuario
             )'
        );
        $stmtActualizar->execute([
            ':titulo' => $titulo,
            ':descripcion' => $descripcion ?: null,
            ':precio' => $precio,
            ':estado_publicacion' => $estadoPublicacion,
            ':estado_publicacion_fecha' => $estadoPublicacion,
            ':estado_venta' => $estadoVenta,
            ':reservada_por' => $reservadaPor ?: null,
            ':categoria' => $idCategoria,
            ':colaboracion' => isset($_POST['permite_colaboracion']) ? 1 : 0,
            ':id' => $obra['id'],
            ':usuario' => getUserId(),
        ]);
        $stmtEstadoGuardado = $pdo->prepare(
            'SELECT estado_publicacion, estado_venta, reservada_por
             FROM obra
             WHERE id_obra = :id AND id_artista = (
                 SELECT id_artista FROM artista WHERE id_usuario = :usuario
             )'
        );
        $stmtEstadoGuardado->execute([
            ':id' => $obra['id'],
            ':usuario' => getUserId(),
        ]);
        $estadoGuardado = $stmtEstadoGuardado->fetch();

        if (!$estadoGuardado
            || $estadoGuardado['estado_publicacion'] !== $estadoPublicacion
            || $estadoGuardado['estado_venta'] !== $estadoVenta) {
            $error_formulario = 'No se pudo confirmar el cambio de estado. Inténtalo de nuevo.';
        } else {
            $obra['titulo'] = $titulo;
            $obra['precio'] = $precio;
            $obra['estado'] = $estado;
            $obra['estado_publicacion'] = $estadoPublicacion;
            $obra['estado_venta'] = $estadoVenta;
            $obra['reservada_por'] = $reservadaPor;
            $obra['reservada_por_usuario'] = $reservadaPorUsuario;
            $obra['categoria'] = (string)$idCategoria;
            $obra['id_categoria'] = $idCategoria;
            $obra['descripcion'] = $descripcion;
            $obra['permite_colaboracion'] = isset($_POST['permite_colaboracion']);
            foreach ($categorias as $categoria) {
                if ((int)$categoria['id_categoria'] === $idCategoria) {
                    $obra['categoria'] = $categoria['nombre'];
                    break;
                }
            }
            if ($estadoVenta === 'reservada' && $reservadaPor && ($cambioEstado || $cambioReservante) && (int)$reservadaPor !== (int)getUserId()) {
                $mensajeReserva = 'Hola. La obra «' . $titulo . '» ha sido reservada para ti. Nos pondremos en contacto contigo para coordinar los siguientes pasos.';
                $stmtMensajeReserva = $pdo->prepare(
                    'INSERT INTO mensaje (id_emisor, id_receptor, contenido) VALUES (:emisor, :receptor, :contenido)'
                );
                $stmtMensajeReserva->execute([
                    ':emisor' => getUserId(),
                    ':receptor' => (int)$reservadaPor,
                    ':contenido' => $mensajeReserva,
                ]);
                crearNotificacion(
                    $pdo,
                    (int)$reservadaPor,
                    'reserva',
                    'Obra reservada para ti',
                    $mensajeReserva,
                    getUserId(),
                    (int)$obra['id']
                );
            }
            if ($cambioEstado) {
                $stmtDestinatarios = $pdo->prepare(
                    'SELECT DISTINCT mg.id_usuario
                     FROM me_gusta mg
                     INNER JOIN usuario u ON u.id_usuario = mg.id_usuario
                     WHERE mg.id_obra = :id_obra AND u.rol = \'cliente\'
                     UNION
                     SELECT DISTINCT s.id_seguidor
                     FROM seguimiento s
                     INNER JOIN usuario u ON u.id_usuario = s.id_seguidor
                     INNER JOIN artista a ON a.id_usuario = s.id_seguido
                     WHERE a.id_artista = :id_artista AND u.rol = \'cliente\''
                );
                $stmtDestinatarios->execute([
                    ':id_obra' => (int)$obra['id'],
                    ':id_artista' => (int)$obra['id_artista'],
                ]);
                $estadoVisible = $estadoPublicacion === 'oculta'
                    ? 'oculta'
                    : ($estadoVenta === 'reservada' ? 'reservada' : $estadoVenta);
                foreach ($stmtDestinatarios->fetchAll(PDO::FETCH_COLUMN) as $idDestinatario) {
                    crearNotificacion(
                        $pdo,
                        (int)$idDestinatario,
                        'obra_estado',
                        'Cambio en una obra que sigues',
                        'La obra «' . $titulo . '» ahora está en estado ' . $estadoVisible . '.',
                        getUserId(),
                        (int)$obra['id'],
                        '/poliart/app/cliente/notificaciones.php'
                    );
                }
            }
            $_SESSION['flash_ok'] = 'Estado y datos de la obra actualizados correctamente.';
            header('Location: /poliart/app/artista/' . $paginasOrigen[$origen]);
            exit;
        }
    }
    } // end else (obra no vendida)
}
require_once __DIR__ . '/../artista/header.php';
include_once __DIR__ . '/../artista/sidebar.php';
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

.form-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 1.25rem;
    padding: 2rem;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(10px);
}

.input-field, .select-field, .textarea-field {
    width: 100%;
    background-color: var(--hover-item);
    border: 1px solid var(--border-subtle);
    border-radius: 0.75rem;
    padding: 0.85rem 1rem;
    color: var(--text-main);
    font-size: 0.9rem;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.input-field:focus, .select-field:focus, .textarea-field:focus {
    border-color: var(--brand-orange);
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.2);
}

.label-title {
    display: block;
    color: var(--text-main);
    font-size: 0.875rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.status-badge-radio {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-subtle);
    border-radius: 0.75rem;
    background: var(--hover-item);
    cursor: pointer;
    transition: all 0.2s ease;
    flex: 1;
}

.status-badge-radio:hover {
    border-color: var(--brand-orange);
}

.status-badge-radio input[type="radio"] {
    accent-color: var(--brand-orange);
}

/* CONTENEDOR TOAST FLOTANTE */
.toast-container {
    position: fixed !important;
    top: 5.5rem !important;
    bottom: auto !important;
    left: 50% !important;
    right: auto !important;
    z-index: 10000 !important;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.toast-message {
    background-color: var(--bg-surface);
    color: var(--text-main);
    border: 1px solid var(--border-subtle);
    border-left: 4px solid #22c55e;
    padding: 0.85rem 1.25rem;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    font-weight: 700;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
    max-width: min(32rem, 90vw);
}
@media (max-width: 640px) {
    .toast-container { top: 5rem !important; width: calc(100vw - 2rem); }
    .toast-message { max-width: 100%; box-sizing: border-box; }
}
</style>

<div style="display: flex; width: 100vw; height: calc(100vh - 4.25rem); overflow: hidden; background: transparent;">

    <div style="flex-grow: 1; height: 100%; overflow-y: auto; padding: 2rem 2.5rem; box-sizing: border-box;">
        
        <div style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">

            <!-- BARRA DE ENCABEZADO CON REGRESO DINÁMICO -->
            <div style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-surface); padding: 1rem 1.5rem; border-radius: 1rem; border: 1px solid var(--border-subtle);">
                <button type="button" onclick="window.history.back();" class="btn btn-neutral">
                    ← Volver atrás
                </button>
                <h2 style="color: var(--text-main); font-size: 1.1rem; font-weight: 800; margin: 0;">
                    ✏️ Modificar Estado y Detalles de Obra
                </h2>
            </div>

            <!-- FORMULARIO DE EDICIÓN -->
            <form method="POST" action="" class="form-card">
                <input type="hidden" name="origen" value="<?= htmlspecialchars($origen, ENT_QUOTES, 'UTF-8') ?>">
                
                <div style="display: grid; grid-template-columns: 320px 1fr; gap: 2rem;">
                    
                    <!-- COLUMNA IZQUIERDA: VISTA PREVIA Y ESTADO -->
                    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                        <div>
                            <span class="label-title">Vista previa actual</span>
                            <div style="width: 100%; aspect-ratio: 1/1; border-radius: 0.85rem; overflow: hidden; border: 1px solid var(--border-subtle); background: #000;">
                                <?php renderizarVistaPreviaObra($obra['imagen'], $obra['titulo'], '100%'); ?>
                            </div>
                        </div>

                        <!-- ESTADO DE DISPONIBILIDAD DE LA OBRA -->
                        <div>
                            <span class="label-title">Estado de la Obra</span>
                            <p style="color: var(--text-muted); font-size: .8rem; margin: 0 0 .5rem;">
                                Selecciona un estado y pulsa «Guardar Cambios» para aplicarlo.
                            </p>
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                <?php if (in_array($obra['estado_publicacion'], ['pendiente', 'rechazada', 'oculta'], true)): ?>
                                    <p style="color: var(--text-muted); font-size: .85rem; margin: 0 0 .5rem;">
                                        <?php if ($obra['estado_publicacion'] === 'pendiente'): ?>
                                            Esta obra espera revisión y aún no es pública.
                                        <?php elseif ($obra['estado_publicacion'] === 'rechazada'): ?>
                                            Esta obra fue rechazada. Al guardar cambios se volverá a enviar a revisión.
                                        <?php else: ?>
                                            Esta obra está oculta. Al volver a habilitarla se enviará a revisión.
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                <label class="status-badge-radio">
                                    <input type="radio" name="estado" value="disponible" <?= $obra['estado'] === 'disponible' ? 'checked' : '' ?>>
                                    <span style="font-weight: 700; font-size: 0.85rem; color: #22c55e;">✅ Disponible para venta</span>
                                </label>

                                <label class="status-badge-radio">
                                    <input type="radio" name="estado" value="reservada" <?= $obra['estado'] === 'reservada' ? 'checked' : '' ?>>
                                    <span style="font-weight: 700; font-size: 0.85rem; color: #eab308;">⏳ Reservada</span>
                                </label>
                                <div id="reservante-field" style="display: <?= $obra['estado'] === 'reservada' ? 'block' : 'none' ?>; margin-top: .25rem;">
                                    <label class="label-title" for="reservada_por_usuario">Usuario que reservó</label>
                                    <input type="text" id="reservada_por_usuario" name="reservada_por_usuario" class="input-field"
                                           value="<?= htmlspecialchars((string)($obra['reservada_por_usuario'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="Nombre de usuario del cliente o artista"
                                           autocomplete="off" <?= $obra['estado'] === 'reservada' ? 'required' : '' ?>>
                                    <small style="display:block;color:var(--text-muted);margin-top:.3rem;">Puede ser un cliente o un artista registrado.</small>
                                </div>

                                <?php if ($obra['estado'] === 'vendida'): ?>
                                <!-- Vendida: solo lectura, no se puede cambiar manualmente -->
                                <div class="status-badge-radio" style="opacity:.6; cursor:not-allowed;">
                                    <input type="radio" name="estado" value="vendida" checked disabled>
                                    <span style="font-weight: 700; font-size: 0.85rem; color: #ef4444;">🏷️ Vendida</span>
                                </div>
                                <p style="font-size:.78rem; color:var(--text-muted); margin:.25rem 0 0; padding-left:.25rem;">
                                    Este estado lo asigna el sistema al confirmar el pago. No se puede cambiar manualmente.
                                </p>
                                <?php endif; ?>

                                <label class="status-badge-radio">
                                    <input type="radio" name="estado" value="no_disponible" <?= $obra['estado'] === 'no_disponible' ? 'checked' : '' ?>>
                                    <span style="font-weight: 700; font-size: 0.85rem; color: var(--text-muted);">🚫 No disponible para venta</span>
                                </label>
                                <?php if (in_array($obra['estado_publicacion'], ['publicada', 'oculta'], true)): ?>
                                    <label class="status-badge-radio">
                                        <input type="radio" name="estado" value="oculta" <?= $obra['estado'] === 'oculta' ? 'checked' : '' ?>>
                                        <span style="font-weight: 700; font-size: 0.85rem; color: var(--text-muted);">Ocultar de la galería</span>
                                    </label>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: CAMPOS DE INFORMACIÓN -->
                    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                        
                        <div>
                            <label class="label-title">Título de la Obra</label>
                            <input type="text" name="titulo" class="input-field" value="<?= htmlspecialchars($obra['titulo']) ?>" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label class="label-title">Precio (<?= htmlspecialchars($obra['moneda'], ENT_QUOTES, 'UTF-8') ?>)</label>
                                <input type="number" name="precio" class="input-field" value="<?= htmlspecialchars((string)$obra['precio'], ENT_QUOTES, 'UTF-8') ?>" min="0" step="0.01" required>
                            </div>

                            <div>
                                <label class="label-title">Categoría</label>
                                <select name="categoria" class="select-field">
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?= (int)$categoria['id_categoria'] ?>" <?= (int)$obra['id_categoria'] === (int)$categoria['id_categoria'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="label-title">Descripción y Detalles del Proyecto</label>
                            <textarea name="descripcion" rows="5" class="textarea-field"><?= htmlspecialchars($obra['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div>
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; color: var(--text-main); font-weight: 700; font-size: 0.875rem;">
                                <input type="checkbox" name="permite_colaboracion" style="accent-color: var(--brand-orange); width: 1.1rem; height: 1.1rem;" <?= $obra['permite_colaboracion'] ? 'checked' : '' ?>>
                                Permite solicitudes de colaboración de otros artistas
                            </label>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <button type="submit" class="btn btn-primary btn-lg btn-full" style="flex: 1;">
                                💾 Guardar Cambios
                            </button>
                            
                            <button type="button" onclick="window.history.back();" class="btn btn-neutral btn-lg">
                                Cancelar
                            </button>
                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>
</div>

<?php if ($mensaje_exito): ?>
<div class="toast-container">
    <div class="toast-message">
        <?= $mensaje_exito ?>
    </div>
</div>
<?php endif; ?>

<?php if ($error_formulario): ?>
<div class="toast-container">
    <div class="toast-message" style="border-left-color: #ef4444;">
        <?= htmlspecialchars($error_formulario, ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const radios = document.querySelectorAll('input[name="estado"]');
    const campo = document.getElementById('reservante-field');
    const usuario = document.getElementById('reservada_por_usuario');
    const actualizarReservante = () => {
        const reservada = document.querySelector('input[name="estado"]:checked')?.value === 'reservada';
        if (campo) campo.style.display = reservada ? 'block' : 'none';
        if (usuario) usuario.required = reservada;
    };
    radios.forEach(radio => radio.addEventListener('change', actualizarReservante));
    actualizarReservante();
});
</script>
<script src="../artista/artista.js"></script>

