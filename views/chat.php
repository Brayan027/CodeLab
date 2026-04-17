<?php
require_once __DIR__ . '/../includes/header.php';

if (!is_logged_in()) redirect('views/login.php');

$current_user_id = $_SESSION['user_id'];

// Manejar Acciones (Bloquear / Archivar)
if (isset($_GET['action'])) {
    $target_id = $_GET['id_target'] ?? null;
    if ($target_id && $target_id != $current_user_id) {
        if ($_GET['action'] == 'block') {
            $pdo->prepare("INSERT IGNORE INTO usuarios_bloqueados (usuario_id, bloqueado_id) VALUES (?, ?)")->execute([$current_user_id, $target_id]);
        } elseif ($_GET['action'] == 'archive') {
            $pdo->prepare("INSERT IGNORE INTO chats_archivados (usuario_id, contacto_id) VALUES (?, ?)")->execute([$current_user_id, $target_id]);
        } elseif ($_GET['action'] == 'unarchive') {
            $pdo->prepare("DELETE FROM chats_archivados WHERE usuario_id = ? AND contacto_id = ?")->execute([$current_user_id, $target_id]);
        }
    }
    redirect('views/chat.php');
}

$search_contact = $_GET['q'] ?? '';
$show_archived = isset($_GET['view']) && $_GET['view'] == 'archived';

// Obtener lista de usuarios con los que se puede chatear (seguidos o que te siguen)
// Excluimos bloqueados y filtramos por archivados
$sql_contacts = "
    SELECT DISTINCT u.id, u.nombre_completo, u.usuario, u.avatar 
    FROM usuarios u
    JOIN seguidores s ON (u.id = s.siguiendo_id AND s.seguidor_id = ?) 
    OR (u.id = s.seguidor_id AND s.siguiendo_id = ?)
    LEFT JOIN chats_archivados ca ON u.id = ca.contacto_id AND ca.usuario_id = ?
    WHERE u.id != ? 
    AND u.id NOT IN (SELECT bloqueado_id FROM usuarios_bloqueados WHERE usuario_id = ?)
    AND u.id NOT IN (SELECT usuario_id FROM usuarios_bloqueados WHERE bloqueado_id = ?)";

if ($show_archived) {
    $sql_contacts .= " AND ca.id IS NOT NULL";
} else {
    $sql_contacts .= " AND ca.id IS NULL";
}

if ($search_contact) {
    $sql_contacts .= " AND (u.nombre_completo LIKE ? OR u.usuario LIKE ?)";
}

$stmt = $pdo->prepare($sql_contacts);
$params = [$current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id];
if ($search_contact) {
    $params[] = "%$search_contact%";
    $params[] = "%$search_contact%";
}
$stmt->execute($params);
$contacts = $stmt->fetchAll();

$destinatario_id = $_GET['id'] ?? null;

// Obtener datos del contacto seleccionado
$contacto_info = null;
if ($destinatario_id) {
    $stmt = $pdo->prepare("SELECT id, nombre_completo, usuario FROM usuarios WHERE id = ?");
    $stmt->execute([$destinatario_id]);
    $contacto_info = $stmt->fetch();
}
?>

<style>
    .chat-layout {
        margin-top: 30px;
        height: calc(100vh - 160px);
        display: flex;
        gap: 20px;
    }
    .chat-sidebar {
        width: 300px;
        display: flex;
        flex-direction: column;
    }
    .chat-sidebar h3 {
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .contacts-list {
        flex: 1;
        overflow-y: auto;
    }
    .contact-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border-radius: 12px;
        margin-bottom: 6px;
        transition: all 0.2s;
        text-decoration: none;
        color: inherit;
        border: 1px solid transparent;
    }
    .contact-item:hover {
        background: rgba(59, 130, 246, 0.06);
    }
    .contact-item.active {
        background: rgba(59, 130, 246, 0.1);
        border-color: var(--primary-color);
    }
    .contact-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .contact-name {
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 180px;
    }
    .contact-handle {
        font-size: 0.75rem;
        color: var(--text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 180px;
    }
    .chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .chat-header {
        padding: 16px 24px;
        border-bottom: 1px solid var(--glass-border);
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .chat-header img {
        width: 38px;
        height: 38px;
        border-radius: 50%;
    }
    .chat-header .header-info h4 {
        margin: 0;
        font-size: 1rem;
    }
    .chat-header .header-info span {
        font-size: 0.8rem;
        color: var(--text-secondary);
    }
    .messages-area {
        flex: 1;
        overflow-y: auto;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .msg-bubble {
        max-width: 70%;
        padding: 12px 18px;
        border-radius: 18px;
        font-size: 0.9rem;
        line-height: 1.5;
        position: relative;
        animation: fadeIn 0.2s ease-out;
    }
    .msg-sent {
        align-self: flex-end;
        background: var(--primary-color);
        color: #fff;
        border-bottom-right-radius: 4px;
    }
    .msg-received {
        align-self: flex-start;
        background: #f1f5f9;
        color: var(--text-primary);
        border-bottom-left-radius: 4px;
        border: 1px solid #e2e8f0;
    }
    .msg-time {
        font-size: 0.65rem;
        opacity: 0.7;
        margin-top: 5px;
        text-align: right;
    }
    .msg-received .msg-time {
        color: var(--text-secondary);
    }
    .chat-input-area {
        padding: 16px 24px;
        border-top: 1px solid var(--glass-border);
    }
    .chat-input-form {
        display: flex;
        gap: 10px;
    }
    .chat-input-form input {
        flex: 1;
        padding: 12px 18px;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        background: #fff;
        font-size: 0.9rem;
        outline: none;
        transition: all 0.3s;
    }
    .chat-input-form input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .chat-input-form button {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        border: none;
        background: var(--primary-color);
        color: #fff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        flex-shrink: 0;
    }
    .chat-input-form button:hover {
        background: #2563eb;
        transform: scale(1.05);
    }
    .empty-chat {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
        gap: 12px;
    }
    .empty-chat i {
        font-size: 3rem;
        opacity: 0.3;
    }
    .empty-contacts {
        text-align: center;
        padding: 30px 16px;
        color: var(--text-secondary);
        font-size: 0.9rem;
        line-height: 1.6;
    }
    .empty-contacts i {
        font-size: 2rem;
        display: block;
        margin-bottom: 12px;
        opacity: 0.3;
    }
</style>

<div class="chat-layout animate-in">
    
    <!-- Sidebar de Contactos -->
    <div class="glass-card chat-sidebar">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin:0;"><i class="fas fa-comments" style="color: var(--primary-color);"></i> Chat</h3>
            <a href="?view=<?= $show_archived ? 'all' : 'archived' ?>" style="font-size: 0.8rem; color: var(--text-secondary);">
                <i class="fas <?= $show_archived ? 'fa-arrow-left' : 'fa-archive' ?>"></i> <?= $show_archived ? 'Volver' : 'Archivados' ?>
            </a>
        </div>
        
        <!-- Buscador de Contactos -->
        <div style="margin-bottom: 15px; position: relative;">
            <form action="" method="GET">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 0.8rem; color: #94a3b8;"></i>
                <input type="text" name="q" placeholder="Buscar contacto..." value="<?= htmlspecialchars($search_contact) ?>" 
                       style="width: 100%; padding: 8px 12px 8px 35px; border-radius: 20px; border: 1px solid #e2e8f0; font-size: 0.85rem; outline: none;">
            </form>
        </div>

        <div class="contacts-list">
            <?php if (empty($contacts)): ?>
                <div class="empty-contacts">
                    <i class="fas fa-user-friends"></i>
                    <p><?= $search_contact ? 'No se encontraron contactos.' : 'No tienes chats ' . ($show_archived ? 'archivados.' : 'activos.') ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($contacts as $c): ?>
                    <a href="<?= BASE_URL ?>views/chat.php?id=<?= $c->id ?><?= $show_archived ? '&view=archived' : '' ?>" class="contact-item <?= $destinatario_id == $c->id ? 'active' : '' ?>" title="<?= htmlspecialchars($c->nombre_completo) ?> (@<?= htmlspecialchars($c->usuario) ?>)">
                        <img class="contact-avatar" src="https://ui-avatars.com/api/?name=<?= urlencode($c->nombre_completo) ?>&size=42&background=random">
                        <div style="flex: 1; min-width: 0;">
                            <div class="contact-name"><?= htmlspecialchars($c->nombre_completo) ?></div>
                            <div class="contact-handle">@<?= htmlspecialchars($c->usuario) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Área de Chat -->
    <div class="glass-card chat-main">
        <?php if ($contacto_info): ?>
            <!-- Header del chat -->
            <div class="chat-header">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($contacto_info->nombre_completo) ?>&background=random&size=38">
                <div class="header-info" style="flex: 1;">
                    <h4><?= htmlspecialchars($contacto_info->nombre_completo) ?></h4>
                    <span>@<?= htmlspecialchars($contacto_info->usuario) ?></span>
                </div>
                <div style="display: flex; gap: 10px;">
                    <?php if ($show_archived): ?>
                        <a href="?action=unarchive&id_target=<?= $destinatario_id ?>" class="btn btn-outline" style="padding: 8px 12px; font-size: 0.8rem;" title="Desarchivar">
                            <i class="fas fa-box-open"></i>
                        </a>
                    <?php else: ?>
                        <a href="?action=archive&id_target=<?= $destinatario_id ?>" class="btn btn-outline" style="padding: 8px 12px; font-size: 0.8rem;" title="Archivar Chat">
                            <i class="fas fa-archive"></i>
                        </a>
                    <?php endif; ?>
                    <a href="?action=block&id_target=<?= $destinatario_id ?>" class="btn btn-outline" style="padding: 8px 12px; font-size: 0.8rem; color: #ef4444; border-color: #fecaca;" title="Bloquear Usuario" onclick="return confirm('¿Seguro que quieres bloquear a este usuario?')">
                        <i class="fas fa-ban"></i>
                    </a>
                </div>
            </div>

            <!-- Mensajes -->
            <div class="messages-area" id="messagesContainer">
                <div style="text-align: center; color: var(--text-secondary); padding: 20px;">
                    <i class="fas fa-spinner fa-spin"></i> Cargando mensajes...
                </div>
            </div>

            <!-- Input -->
            <div class="chat-input-area">
                <form id="chatForm" class="chat-input-form">
                    <input type="hidden" name="destinatario_id" value="<?= $destinatario_id ?>">
                    <input type="text" name="mensaje" id="mensajeInput" placeholder="Escribe un mensaje..." autocomplete="off">
                    <button type="submit"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>

        <?php else: ?>
            <div class="empty-chat">
                <i class="far fa-comment-dots"></i>
                <p>Selecciona un contacto para iniciar la conversación</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($contacto_info): ?>
<script>
const BASE_URL = '<?= BASE_URL ?>';
const container = document.getElementById('messagesContainer');
const form = document.getElementById('chatForm');
const input = document.getElementById('mensajeInput');
const destinatarioId = <?= json_encode($destinatario_id) ?>;
const currentUserId = <?= json_encode($current_user_id) ?>;
let lastMessageId = 0;

function formatTime(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function appendMessage(text, isMine, date) {
    const bubble = document.createElement('div');
    bubble.className = 'msg-bubble ' + (isMine ? 'msg-sent' : 'msg-received');
    bubble.innerHTML = `
        ${escapeHtml(text)}
        <div class="msg-time">${formatTime(date)}</div>
    `;
    container.appendChild(bubble);
    container.scrollTop = container.scrollHeight;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function loadMessages() {
    fetch(BASE_URL + `api/chat.php?destinatario_id=${destinatarioId}`)
    .then(res => res.json())
    .then(data => {
        if (Array.isArray(data)) {
            container.innerHTML = '';
            if (data.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; color: var(--text-secondary); padding: 40px;">
                        <i class="far fa-hand-peace" style="font-size: 2rem; display: block; margin-bottom: 10px; opacity: 0.4;"></i>
                        <p>¡Envía el primer mensaje!</p>
                    </div>`;
                return;
            }
            data.forEach(msg => {
                appendMessage(msg.mensaje, msg.remitente_id == currentUserId, msg.fecha_envio);
                if (msg.id > lastMessageId) lastMessageId = msg.id;
            });
        }
    })
    .catch(err => {
        container.innerHTML = '<p style="text-align:center; color:var(--text-secondary);">Error al cargar mensajes.</p>';
    });
}

// Enviar mensaje
form.addEventListener('submit', (e) => {
    e.preventDefault();
    const msg = input.value.trim();
    if (!msg) return;

    const formData = new FormData(form);
    
    // Mostrar el mensaje inmediatamente
    appendMessage(msg, true, new Date());
    input.value = '';
    input.focus();

    // Guardarlo en la base de datos
    fetch(BASE_URL + 'api/chat.php', { method: 'POST', body: formData })
    .catch(err => console.error('Error enviando mensaje:', err));
});

// Polling para nuevos mensajes (cada 3 segundos)
function pollMessages() {
    fetch(BASE_URL + `api/chat.php?destinatario_id=${destinatarioId}`)
    .then(res => res.json())
    .then(data => {
        if (Array.isArray(data) && data.length > 0) {
            const newMsgs = data.filter(m => m.id > lastMessageId);
            newMsgs.forEach(msg => {
                // Solo agregar mensajes del otro usuario (los míos ya los agrego al enviar)
                if (msg.remitente_id != currentUserId) {
                    appendMessage(msg.mensaje, false, msg.fecha_envio);
                }
                if (msg.id > lastMessageId) lastMessageId = msg.id;
            });
        }
    })
    .catch(() => {});
}

// Cargar mensajes iniciales
loadMessages();

// Polling cada 3 segundos
setInterval(pollMessages, 3000);

// Focus en el input al cargar
input.focus();

// Enviar con Enter
input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        form.dispatchEvent(new Event('submit'));
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
