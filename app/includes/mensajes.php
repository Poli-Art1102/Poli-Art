<?php
$pdoMensajes = getDB();
$idUsuarioActual = getUserId();
$idContacto = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT) ?: 0;
$errorMensajes = false;
$conversaciones = [];
$contacto = null;
$mensajes = [];

try {
    if (!$idContacto && isset($_GET['artista_id'])) {
        $stmtArtista = $pdoMensajes->prepare(
            'SELECT id_usuario FROM artista WHERE id_artista = :id_artista LIMIT 1'
        );
        $stmtArtista->execute([':id_artista' => (int)$_GET['artista_id']]);
        $idContacto = (int)$stmtArtista->fetchColumn();
    }

    $stmtConversaciones = $pdoMensajes->prepare(
        'SELECT u.id_usuario, u.nombre_usuario, u.nombre, u.apellido, u.avatar_path,
                a.especialidad, z.nombre AS zona, m.contenido AS ultimo_mensaje,
                m.nombre_adjunto AS ultimo_adjunto, m.creado_en
         FROM (
             SELECT participantes.id_contacto, MAX(participantes.id_mensaje) AS id_mensaje
             FROM (
                 SELECT id_receptor AS id_contacto, id_mensaje
                 FROM mensaje WHERE id_emisor = :emisor_enviado
                 UNION ALL
                 SELECT id_emisor AS id_contacto, id_mensaje
                 FROM mensaje WHERE id_receptor = :receptor_recibido
             ) participantes
             GROUP BY participantes.id_contacto
         ) recientes
         INNER JOIN mensaje m ON m.id_mensaje = recientes.id_mensaje
         INNER JOIN usuario u ON u.id_usuario = recientes.id_contacto
         LEFT JOIN artista a ON a.id_usuario = u.id_usuario
         LEFT JOIN zona z ON z.id_zona = u.id_zona
         ORDER BY m.creado_en DESC'
    );
    $stmtConversaciones->execute([
        ':emisor_enviado' => $idUsuarioActual,
        ':receptor_recibido' => $idUsuarioActual,
    ]);
    $conversaciones = $stmtConversaciones->fetchAll();

    if (!$idContacto && $conversaciones) {
        $idContacto = (int)$conversaciones[0]['id_usuario'];
    }

    if ($idContacto && $idContacto !== $idUsuarioActual) {
        $stmtContacto = $pdoMensajes->prepare(
            "SELECT u.id_usuario, u.nombre_usuario, u.nombre, u.apellido, u.avatar_path,
                    a.especialidad, z.nombre AS zona
             FROM usuario u
             LEFT JOIN artista a ON a.id_usuario = u.id_usuario
             LEFT JOIN zona z ON z.id_zona = u.id_zona
             WHERE u.id_usuario = :id_usuario AND u.estado = 'activo'
               AND u.rol IN ('cliente', 'artista')
             LIMIT 1"
        );
        $stmtContacto->execute([':id_usuario' => $idContacto]);
        $contacto = $stmtContacto->fetch();

        if ($contacto) {
            $existe = false;
            foreach ($conversaciones as $conversacion) {
                if ((int)$conversacion['id_usuario'] === $idContacto) {
                    $existe = true;
                    break;
                }
            }
            if (!$existe) {
                $contacto['ultimo_mensaje'] = '';
                $contacto['ultimo_adjunto'] = null;
                $contacto['creado_en'] = null;
                array_unshift($conversaciones, $contacto);
            }

            $pdoMensajes->prepare(
                'UPDATE mensaje SET leido_en = NOW()
                 WHERE id_emisor = :emisor AND id_receptor = :receptor AND leido_en IS NULL'
            )->execute([':emisor' => $idContacto, ':receptor' => $idUsuarioActual]);

            $stmtHistorial = $pdoMensajes->prepare(
                'SELECT id_mensaje, id_emisor, contenido, ruta_adjunto,
                        nombre_adjunto, tipo_adjunto, creado_en
                 FROM mensaje
                 WHERE (id_emisor = :emisor AND id_receptor = :receptor)
                    OR (id_emisor = :contacto_emisor AND id_receptor = :usuario_receptor)
                 ORDER BY id_mensaje DESC
                 LIMIT 200'
            );
            $stmtHistorial->execute([
                ':emisor' => $idUsuarioActual,
                ':receptor' => $idContacto,
                ':contacto_emisor' => $idContacto,
                ':usuario_receptor' => $idUsuarioActual,
            ]);
            $mensajes = array_reverse($stmtHistorial->fetchAll());
        }
    }
} catch (PDOException $e) {
    error_log('Error al consultar conversaciones: ' . $e->getMessage());
    $errorMensajes = true;
}

$nombreContacto = $contacto
    ? trim($contacto['nombre'] . ' ' . $contacto['apellido'])
    : '';
$avatarContacto = $contacto && is_string($contacto['avatar_path'])
    && strpos($contacto['avatar_path'], '/poliart/uploads/avatars/') === 0
    ? $contacto['avatar_path']
    : '';
$inicialesContacto = $contacto
    ? mb_strtoupper(mb_substr($nombreContacto !== '' ? $nombreContacto : ($contacto['nombre_usuario'] ?? 'U'), 0, 2, 'UTF-8'), 'UTF-8')
    : '';
?>
<style>
.messages-page {
    flex: 1;
    min-width: 0;
    min-height: calc(100vh - 4.25rem);
    box-sizing: border-box;
    padding: clamp(1rem, 3vw, 2.5rem);
    background: linear-gradient(135deg, rgba(237, 119, 69, .08), transparent 55%);
}

.messages-shell {
    display: flex;
    width: 100%;
    max-width: 1420px;
    height: min(760px, calc(100vh - 8rem));
    min-height: 460px;
    margin: 0 auto;
    overflow: hidden;
    border: 1px solid var(--border-subtle);
    border-radius: 1.25rem;
    background: var(--bg-surface);
    box-shadow: 0 24px 70px rgba(60, 25, 12, .18);
}

.messages-list-panel {
    display: flex;
    flex-direction: column;
    flex: 0 0 340px;
    min-width: 0;
    background: color-mix(in srgb, var(--bg-surface) 90%, var(--bg-nav));
    border-right: 1px solid var(--border-subtle);
}

.messages-list-heading {
    padding: 1.5rem 1.25rem 1rem;
}

.messages-list-heading h1 {
    margin: 0;
    color: var(--text-main);
    font-size: 1.4rem;
    font-weight: 850;
}

.messages-list-heading p {
    margin: .35rem 0 1rem;
    color: var(--text-muted);
    font-size: .85rem;
}

.messages-search {
    width: 100%;
    box-sizing: border-box;
    padding: .75rem 1rem;
    border: 1px solid var(--border-subtle);
    border-radius: .8rem;
    outline: none;
    color: var(--text-main);
    background: var(--hover-item);
    font: inherit;
}

.messages-search:focus,
.message-input:focus {
    border-color: var(--brand-orange);
    box-shadow: 0 0 0 3px rgba(237, 119, 69, .15);
}

.messages-conversation-list {
    flex: 1;
    overflow-y: auto;
    padding: 0 .65rem .75rem;
}

.messages-conversation {
    display: flex;
    align-items: center;
    gap: .8rem;
    padding: .85rem;
    margin-top: .3rem;
    border: 1px solid transparent;
    border-radius: .9rem;
    color: var(--text-main);
    text-decoration: none;
    transition: background .18s ease, border-color .18s ease, transform .18s ease;
}

.messages-conversation[hidden] {
    display: none;
}

.messages-conversation:hover {
    background: var(--hover-item);
    transform: translateX(2px);
}

.messages-conversation.active {
    border-color: rgba(237, 119, 69, .35);
    background: rgba(237, 119, 69, .13);
}

.message-avatar {
    display: inline-flex;
    flex: 0 0 2.8rem;
    width: 2.8rem;
    height: 2.8rem;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border: 2px solid rgba(237, 119, 69, .35);
    border-radius: 50%;
    background: linear-gradient(135deg, var(--brand-orange), var(--brand-pink));
    color: #fff;
    font-size: .85rem;
    font-weight: 850;
}

.message-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.messages-conversation-copy {
    min-width: 0;
    flex: 1;
}

.messages-conversation-copy strong,
.messages-conversation-copy span {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.messages-conversation-copy strong {
    font-size: .9rem;
}

.messages-conversation-copy span {
    margin-top: .25rem;
    color: var(--text-muted);
    font-size: .8rem;
}

.messages-chat {
    display: flex;
    flex: 1;
    flex-direction: column;
    min-width: 0;
    background:
        radial-gradient(circle at 50% 30%, rgba(237, 119, 69, .07), transparent 45%),
        var(--bg-surface);
}

.messages-chat-heading {
    display: flex;
    align-items: center;
    gap: .85rem;
    min-height: 5.1rem;
    padding: .75rem 1.5rem;
    border-bottom: 1px solid var(--border-subtle);
    background: rgba(255, 255, 255, .12);
}

.messages-chat-heading strong {
    display: block;
    color: var(--text-main);
    font-size: .98rem;
}

.messages-chat-heading span {
    display: block;
    margin-top: .2rem;
    color: var(--text-muted);
    font-size: .8rem;
}

.message-list {
    flex: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: .75rem;
    padding: 1.5rem clamp(1rem, 4vw, 2.5rem);
    scroll-behavior: smooth;
}

.message-bubble {
    max-width: min(72%, 620px);
    padding: .8rem 1rem .55rem;
    border: 1px solid var(--border-subtle);
    border-radius: 1rem 1rem 1rem .25rem;
    background: var(--hover-item);
    color: var(--text-main);
    box-shadow: 0 4px 14px rgba(60, 25, 12, .06);
    overflow-wrap: anywhere;
}

.message-bubble.mine {
    align-self: flex-end;
    border-color: transparent;
    border-radius: 1rem 1rem .25rem 1rem;
    background: linear-gradient(135deg, var(--brand-orange), #e96a37);
    color: #fff;
}

.message-bubble small {
    display: block;
    margin-top: .45rem;
    opacity: .75;
    font-size: .68rem;
    text-align: right;
}

.messages-composer {
    display: flex;
    align-items: flex-end;
    gap: .7rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--border-subtle);
    background: rgba(255, 255, 255, .12);
}

.message-file-input {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    clip-path: inset(50%);
}

.message-attach {
    display: grid;
    flex: 0 0 2.9rem;
    width: 2.9rem;
    height: 2.9rem;
    place-items: center;
    border: 1px solid var(--border-subtle);
    border-radius: .9rem;
    color: var(--text-main);
    background: var(--hover-item);
    font-size: 1.15rem;
    cursor: pointer;
}

.message-attach:hover,
.message-attach:focus-within {
    border-color: var(--brand-orange);
    background: rgba(237, 119, 69, .15);
}

.message-file-name {
    max-width: 9rem;
    overflow: hidden;
    color: var(--text-muted);
    font-size: .75rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.message-input {
    flex: 1;
    min-width: 0;
    min-height: 2.9rem;
    max-height: 8rem;
    box-sizing: border-box;
    resize: none;
    overflow-y: hidden;
    padding: .85rem 1rem;
    border: 1px solid var(--border-subtle);
    border-radius: 1rem;
    outline: none;
    color: var(--text-main);
    background: var(--hover-item);
    font: inherit;
    transition: border-color .18s ease, box-shadow .18s ease;
}

.message-send {
    min-height: 2.9rem;
    padding: 0 1.15rem;
    border: 0;
    border-radius: .9rem;
    background: linear-gradient(135deg, var(--brand-orange), #e96a37);
    color: #fff;
    font: inherit;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 5px 14px rgba(237, 119, 69, .25);
}

.message-send:hover {
    filter: brightness(1.05);
    transform: translateY(-1px);
}

.message-send:disabled {
    opacity: .6;
    cursor: wait;
    transform: none;
}

.messages-empty {
    display: flex;
    flex: 1;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: var(--text-muted);
    text-align: center;
}

.messages-empty-icon {
    display: grid;
    width: 5rem;
    height: 5rem;
    margin-bottom: 1rem;
    place-items: center;
    border-radius: 1.5rem;
    background: rgba(237, 119, 69, .14);
    font-size: 2.2rem;
}

.messages-empty h2 {
    margin: 0 0 .4rem;
    color: var(--text-main);
    font-size: 1.25rem;
}

.messages-empty p {
    max-width: 24rem;
    margin: 0;
    line-height: 1.6;
}

.messages-error {
    padding: 1rem;
    color: #b91c1c;
}

@media (max-width: 850px) {
    .messages-page {
        padding: 0;
        min-height: calc(100vh - 4.25rem);
    }

    .messages-shell {
        height: calc(100vh - 4.25rem);
        min-height: 0;
        border-radius: 0;
    }

    .messages-list-panel {
        flex-basis: 290px;
    }
}

@media (max-width: 620px) {
    .messages-list-panel {
        flex-basis: 76px;
    }

    .messages-list-heading {
        padding: 1rem .5rem .75rem;
        text-align: center;
    }

    .messages-list-heading h1 {
        font-size: .75rem;
    }

    .messages-list-heading p,
    .messages-search,
    .messages-conversation-copy {
        display: none;
    }

    .messages-conversation-list {
        padding: 0 .45rem;
    }

    .messages-conversation {
        justify-content: center;
        padding: .55rem .2rem;
    }

    .message-avatar {
        flex-basis: 2.55rem;
        width: 2.55rem;
        height: 2.55rem;
    }

    .messages-chat-heading {
        min-height: 4.3rem;
        padding: .6rem .8rem;
    }

    .message-list {
        padding: 1rem .75rem;
    }

    .message-bubble {
        max-width: 88%;
    }

    .messages-composer {
        gap: .45rem;
        padding: .7rem;
    }

    .message-file-name {
        display: none;
    }

    .message-send {
        padding: 0 .8rem;
    }
}
</style>
<div class="messages-page">
<div class="messages-shell">
    <aside class="messages-list-panel">
        <div class="messages-list-heading">
<h1>Mensajes</h1>
            <p>Tus conversaciones en Poli-Art</p>
            <input class="messages-search" id="messages-search" type="search" placeholder="Buscar conversación..." aria-label="Buscar conversación">
        </div>
        <nav class="messages-conversation-list" id="messages-conversation-list" aria-label="Conversaciones">
        <?php foreach ($conversaciones as $conversacion): ?>
            <?php
            $nombre = trim($conversacion['nombre'] . ' ' . $conversacion['apellido']);
            $avatarConversacion = is_string($conversacion['avatar_path'])
                && strpos($conversacion['avatar_path'], '/poliart/uploads/avatars/') === 0
                ? $conversacion['avatar_path']
                : '';
            $inicialesConversacion = mb_strtoupper(mb_substr($nombre !== '' ? $nombre : ($conversacion['nombre_usuario'] ?? 'U'), 0, 2, 'UTF-8'), 'UTF-8');
            ?>
            <a class="messages-conversation <?= (int)$conversacion['id_usuario'] === $idContacto ? 'active' : '' ?>"
               href="?usuario_id=<?= (int)$conversacion['id_usuario'] ?>"
               title="<?= htmlspecialchars($nombre !== '' ? $nombre : ($conversacion['nombre_usuario'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8') ?>"
               aria-label="Conversación con <?= htmlspecialchars($nombre !== '' ? $nombre : ($conversacion['nombre_usuario'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8') ?>"
               data-search="<?= htmlspecialchars(mb_strtolower($nombre . ' ' . ($conversacion['nombre_usuario'] ?? ''), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>"
               aria-current="<?= (int)$conversacion['id_usuario'] === $idContacto ? 'page' : 'false' ?>">
                <span class="message-avatar">
                    <?php if ($avatarConversacion !== ''): ?>
                        <img src="<?= htmlspecialchars($avatarConversacion, ENT_QUOTES, 'UTF-8') ?>" alt="">
                    <?php else: ?>
                        <?= htmlspecialchars($inicialesConversacion, ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </span>
                <span class="messages-conversation-copy">
                    <strong><?= htmlspecialchars($nombre !== '' ? $nombre : ($conversacion['nombre_usuario'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= !empty($conversacion['ultimo_adjunto']) ? '📎 ' . htmlspecialchars($conversacion['ultimo_adjunto'], ENT_QUOTES, 'UTF-8') : htmlspecialchars($conversacion['ultimo_mensaje'] ?? 'Inicia la conversación', ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            </a>
        <?php endforeach; ?>
        <?php if (!$conversaciones): ?>
            <p style="padding:1rem;color:var(--text-muted);font-size:.85rem;line-height:1.5;">
                Aún no tienes conversaciones. Visita el perfil de un artista para iniciar una.
            </p>
        <?php endif; ?>
        </nav>
    </aside>
    <section class="messages-chat">
        <?php if ($errorMensajes): ?>
            <p class="messages-error" role="alert">No fue posible cargar tus mensajes.</p>
        <?php elseif (!$contacto): ?>
            <div class="messages-empty">
                <div class="messages-empty-icon" aria-hidden="true">💬</div>
                <h2>Tus conversaciones, en un solo lugar</h2>
                <p>Cuando inicies una conversación con un artista o cliente, aparecerá aquí para que puedas continuarla.</p>
            </div>
        <?php else: ?>
            <header class="messages-chat-heading">
                <span class="message-avatar">
                    <?php if ($avatarContacto !== ''): ?>
                        <img src="<?= htmlspecialchars($avatarContacto, ENT_QUOTES, 'UTF-8') ?>" alt="">
                    <?php else: ?>
                        <?= htmlspecialchars($inicialesContacto, ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </span>
                <div>
                    <strong><?= htmlspecialchars($nombreContacto !== '' ? $nombreContacto : ($contacto['nombre_usuario'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= htmlspecialchars($contacto['especialidad'] ?: ($contacto['zona'] ?: 'Conversación privada'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </header>
            <div id="message-list" class="message-list" aria-live="polite">
                <?php foreach ($mensajes as $mensaje): ?>
                    <div class="message-bubble <?= (int)$mensaje['id_emisor'] === $idUsuarioActual ? 'mine' : '' ?>">
                        <div><?= nl2br(htmlspecialchars($mensaje['contenido'], ENT_QUOTES, 'UTF-8')) ?></div>
                        <?php if (!empty($mensaje['ruta_adjunto'])): ?>
                            <?php $urlAdjunto = '../includes/actions/adjunto_mensaje.php?id=' . (int)$mensaje['id_mensaje']; ?>
                            <?php if (strpos((string)$mensaje['tipo_adjunto'], 'image/') === 0): ?>
                                <a href="<?= $urlAdjunto ?>" target="_blank" rel="noopener noreferrer">
                                    <img src="<?= $urlAdjunto ?>&amp;inline=1" alt="<?= htmlspecialchars($mensaje['nombre_adjunto'], ENT_QUOTES, 'UTF-8') ?>" style="display:block;max-width:min(100%,360px);max-height:320px;margin-top:.5rem;border-radius:.7rem;object-fit:contain;">
                                </a>
                            <?php else: ?>
                                <a href="<?= $urlAdjunto ?>" style="display:inline-flex;align-items:center;gap:.5rem;margin-top:.5rem;padding:.55rem .7rem;border-radius:.6rem;background:rgba(255,255,255,.2);color:inherit;text-decoration:underline;" aria-label="Descargar <?= htmlspecialchars($mensaje['nombre_adjunto'], ENT_QUOTES, 'UTF-8') ?>">
                                    📎 <?= htmlspecialchars($mensaje['nombre_adjunto'], ENT_QUOTES, 'UTF-8') ?>
                                    <small>(<?= number_format(((int)$mensaje['tamano_adjunto']) / 1048576, 2) ?> MB)</small>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <small><?= htmlspecialchars(date('d/m/Y H:i', strtotime($mensaje['creado_en'])), ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (getUserRole() === 'artista'): ?>
                <div class="message-quick-replies" aria-label="Mensajes predeterminados">
                    <button type="button" class="message-quick-reply" data-message="Hola, gracias por tu interés en mi obra. ¿Cómo deseas continuar?">Agradecer interés</button>
                    <button type="button" class="message-quick-reply" data-message="Hola, podemos coordinar contigo la entrega y los detalles de la obra por este chat.">Coordinar entrega</button>
                </div>
            <?php endif; ?>
            <form id="message-form" class="messages-composer" enctype="multipart/form-data">
                <input type="hidden" name="usuario_id" value="<?= (int)$idContacto ?>">
                <input id="message-file" class="message-file-input" type="file" name="archivo" accept="image/jpeg,image/png,image/gif,image/webp,application/pdf,text/plain,.docx,.xlsx,.pptx">
                <label class="message-attach" for="message-file" title="Adjuntar imagen o archivo" aria-label="Adjuntar imagen o archivo">📎</label>
                <span id="message-file-name" class="message-file-name"></span>
                <textarea class="message-input" name="contenido" maxlength="5000" rows="1" placeholder="Escribe un mensaje..."></textarea>
                <button class="message-send" type="submit">Enviar <span aria-hidden="true">➤</span></button>
            </form>
            <p id="message-error" class="messages-error" role="alert" style="display:none;margin:0;padding:0 1.25rem 1rem;"></p>
        <?php endif; ?>
    </section>
</div>
</div>
<?php if ($contacto): ?>
<script>
const messageForm = document.getElementById('message-form');
const messageList = document.getElementById('message-list');
const messageError = document.getElementById('message-error');
const messageInput = messageForm.elements.contenido;
const messageFile = document.getElementById('message-file');
const messageFileName = document.getElementById('message-file-name');
document.querySelectorAll('.message-quick-reply').forEach(button => {
    button.addEventListener('click', () => {
        messageInput.value = button.dataset.message || '';
        resizeMessageInput();
        messageInput.focus();
    });
});
const resizeMessageInput = () => {
    const maxHeight = Number.parseFloat(getComputedStyle(messageInput).maxHeight);
    messageInput.style.height = 'auto';
    messageInput.style.height = `${Math.min(messageInput.scrollHeight, maxHeight)}px`;
    messageInput.style.overflowY = messageInput.scrollHeight > maxHeight ? 'auto' : 'hidden';
};
messageInput.addEventListener('input', resizeMessageInput);
resizeMessageInput();
messageFile.addEventListener('change', () => {
    const file = messageFile.files[0];
    messageFileName.textContent = file ? file.name : '';
    messageFileName.title = file ? file.name : '';
});
messageList.scrollTop = messageList.scrollHeight;

// Pre-cargar mensaje predeterminado si viene desde una obra (?msg=...)
const _msgParam = new URLSearchParams(window.location.search).get('msg');
if (_msgParam && messageInput) {
    messageInput.value = decodeURIComponent(_msgParam);
    resizeMessageInput();
    messageInput.focus();
    messageInput.setSelectionRange(messageInput.value.length, messageInput.value.length);
}

document.getElementById('messages-search')?.addEventListener('input', event => {
    const query = event.target.value.trim().toLocaleLowerCase();
    document.querySelectorAll('.messages-conversation').forEach(conversation => {
        conversation.hidden = !conversation.dataset.search.includes(query);
    });
});
messageForm.addEventListener('submit', async event => {
    event.preventDefault();
    const submit = messageForm.querySelector('button[type="submit"]');
    const input = messageInput;
    submit.disabled = true;
    messageError.textContent = '';
    messageError.style.display = 'none';
    try {
        if (!input.value.trim() && !messageFile.files.length) {
            throw new Error('Escribe un mensaje o selecciona un archivo.');
        }
        if (messageFile.files[0] && messageFile.files[0].size > 10 * 1024 * 1024) {
            throw new Error('El archivo debe pesar hasta 10 MB.');
        }
        const response = await fetch('../includes/actions/mensajes.php', {
            method: 'POST',
            body: new FormData(messageForm)
        });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.error || 'No se pudo enviar el mensaje.');
        const bubble = document.createElement('div');
        bubble.className = 'message-bubble mine';
        const text = document.createElement('div');
        text.textContent = result.contenido;
        if (result.adjunto) {
            const attachmentUrl = `../includes/actions/adjunto_mensaje.php?id=${encodeURIComponent(result.id)}`;
            if (result.adjunto.tipo.startsWith('image/')) {
                const link = document.createElement('a');
                link.href = attachmentUrl;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                const image = document.createElement('img');
                image.src = `${attachmentUrl}&inline=1`;
                image.alt = result.adjunto.nombre;
                image.style.cssText = 'display:block;max-width:min(100%,360px);max-height:320px;margin-top:.5rem;border-radius:.7rem;object-fit:contain';
                link.appendChild(image);
                bubble.appendChild(link);
            } else {
                const link = document.createElement('a');
                link.href = attachmentUrl;
                link.textContent = `📎 ${result.adjunto.nombre}`;
                link.style.cssText = 'display:inline-flex;margin-top:.5rem;color:inherit;text-decoration:underline';
                bubble.appendChild(link);
            }
        }
        const time = document.createElement('small');
        time.textContent = new Date(result.creado_en.replace(' ', 'T')).toLocaleString();
        if (result.contenido) bubble.insertBefore(text, bubble.firstChild);
        bubble.appendChild(time);
        messageList.appendChild(bubble);
        messageList.scrollTop = messageList.scrollHeight;
        input.value = '';
        messageFile.value = '';
        messageFileName.textContent = '';
        messageFileName.title = '';
        resizeMessageInput();
    } catch (exception) {
        messageError.textContent = exception.message || 'Ocurrió un error al enviar el mensaje.';
        messageError.style.display = 'block';
    } finally {
        submit.disabled = false;
        input.focus();
    }
});
messageForm.elements.contenido.addEventListener('keydown', event => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        messageForm.requestSubmit();
    }
});
</script>
<?php else: ?>
<script>
document.getElementById('messages-search')?.addEventListener('input', event => {
    const query = event.target.value.trim().toLocaleLowerCase();
    document.querySelectorAll('.messages-conversation').forEach(conversation => {
        conversation.hidden = !conversation.dataset.search.includes(query);
    });
});
</script>
<?php endif; ?>
