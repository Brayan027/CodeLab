<?php require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeLab | Plataforma Colaborativa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container nav-container">
            <nav>
                <a href="<?= BASE_URL ?>index.php" class="logo">CodeLab</a>
                <ul class="nav-links">
                    <li><a href="<?= BASE_URL ?>views/forum.php"><i class="far fa-comments"></i> Foros</a></li>
                    <li><a href="<?= BASE_URL ?>views/learning_routes.php"><i class="fas fa-map-signs"></i> Rutas</a></li>
                    <li><a href="<?= BASE_URL ?>views/snippets.php"><i class="fas fa-code"></i> Repositorio</a></li>
                    <li><a href="<?= BASE_URL ?>views/users.php"><i class="fas fa-users"></i> Comunidad</a></li>
                    
                    <?php if (is_logged_in()): ?>
                        <li><a href="<?= BASE_URL ?>views/ai_mentor.php" style="color: var(--secondary-color);"><i class="fas fa-brain"></i> IA</a></li>
                        
                        <!-- Notificaciones -->
                        <li style="position: relative;" id="notif-wrapper">
                            <a href="#" id="notif-bell" style="position: relative;">
                                <i class="fas fa-bell"></i>
                                <span id="notif-count" style="display: none; position: absolute; top: -8px; right: -8px; background: #ef4444; color: #fff; font-size: 0.65rem; padding: 2px 6px; border-radius: 10px; border: 2px solid #fff; font-weight: bold;">0</span>
                            </a>
                            <div id="notif-dropdown" class="glass-card" style="display: none; position: absolute; top: 40px; right: 0; width: 320px; max-height: 400px; overflow-y: auto; z-index: 1000; padding: 15px; background: white; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid var(--glass-border); border-radius: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                                    <h4 style="margin:0; font-size: 0.9rem;">Notificaciones</h4>
                                    <button onclick="markAllRead()" style="background: none; border: none; color: var(--primary-color); font-size: 0.75rem; cursor: pointer; font-weight: 600;">Marcar todo leído</button>
                                </div>
                                <div id="notif-list" style="display: flex; flex-direction: column; gap: 10px;">
                                    <p style="text-align: center; color: var(--text-secondary); font-size: 0.8rem; padding: 20px;">Cargando...</p>
                                </div>
                            </div>
                        </li>

                        <?php if ($_SESSION['rol'] == 'docente'): ?>
                            <li style="border-left: 1px solid var(--glass-border); padding-left: 10px; margin-left: 5px;">
                                <a href="<?= BASE_URL ?>views/teacher_dashboard.php" style="color: var(--secondary-color); font-weight: 700;" title="Panel Docente"><i class="fas fa-chalkboard-teacher"></i> Docente</a>
                            </li>
                            <li>
                                <a href="<?= BASE_URL ?>views/research_dashboard.php" style="color: #10b981; font-weight: 700;" title="Panel de Investigación"><i class="fas fa-microscope"></i> Investigación</a>
                            </li>
                        <?php endif; ?>
                        
                        <li style="border-left: 1px solid var(--glass-border); padding-left: 10px; margin-left: 5px;">
                            <a href="<?= BASE_URL ?>views/chat.php" title="Chat"><i class="far fa-paper-plane"></i></a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>views/profile.php" title="Mi Perfil">
                                <img src="<?= BASE_URL ?>assets/img/<?= $_SESSION['avatar'] ?? 'default_avatar.png' ?>" 
                                     onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'User') ?>&background=random'" 
                                     alt="Perfil" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color);">
                            </a>
                        </li>
                        <li><a href="<?= BASE_URL ?>api/logout.php" title="Salir" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i></a></li>
                    <?php else: ?>
                        <li style="margin-left: 10px;"><a href="<?= BASE_URL ?>views/login.php">Entrar</a></li>
                        <li><a href="<?= BASE_URL ?>views/register.php" class="btn btn-primary" style="padding: 8px 16px;">Crear Cuenta</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">

<style>
    .notif-item { display: flex; gap: 10px; padding: 10px; border-radius: 10px; text-decoration: none; color: inherit; transition: all 0.2s; border-left: 3px solid transparent; }
    .notif-item:hover { background: #f8fafc; }
    .notif-item.unread { background: rgba(59, 130, 246, 0.05); border-left-color: var(--primary-color); }
    .notif-item img { width: 35px; height: 35px; border-radius: 50%; flex-shrink: 0; }
    .notif-content { font-size: 0.8rem; line-height: 1.4; }
    .notif-time { font-size: 0.7rem; color: #94a3b8; margin-top: 4px; }
</style>

<script>
const NOTIF_URL = '<?= BASE_URL ?>';

function toggleNotif() {
    const dropdown = document.getElementById('notif-dropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    if (dropdown.style.display === 'block') loadNotifications();
}

document.getElementById('notif-bell')?.addEventListener('click', (e) => {
    e.preventDefault();
    toggleNotif();
});

// Cerrar al hacer clic fuera
document.addEventListener('click', (e) => {
    const wrapper = document.getElementById('notif-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        document.getElementById('notif-dropdown').style.display = 'none';
    }
});

function loadNotifications() {
    fetch(NOTIF_URL + 'api/notifications.php?action=list')
    .then(res => res.json())
    .then(data => {
        const list = document.getElementById('notif-list');
        list.innerHTML = '';
        if (data.length === 0) {
            list.innerHTML = '<p style="text-align:center; padding:20px; color:#94a3b8; font-size:0.8rem;">No tienes notificaciones.</p>';
            return;
        }
        data.forEach(n => {
            const item = document.createElement('a');
            item.href = NOTIF_URL + 'api/notifications.php?action=read&id=' + n.id + '&url=' + encodeURIComponent(n.url);
            item.className = 'notif-item ' + (n.leido ? '' : 'unread');
            item.innerHTML = `
                <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(n.emisor_nombre)}&background=random">
                <div class="notif-content">
                    <strong>${n.emisor_nombre}</strong> ${n.mensaje}
                    <div class="notif-time">${n.fecha_hace}</div>
                </div>
            `;
            list.appendChild(item);
        });
    });
}

function checkUnread() {
    fetch(NOTIF_URL + 'api/notifications.php?action=count')
    .then(res => res.json())
    .then(data => {
        const badge = document.getElementById('notif-count');
        if (data.count > 0) {
            badge.innerText = data.count > 9 ? '9+' : data.count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    });
}

function markAllRead() {
    fetch(NOTIF_URL + 'api/notifications.php?action=mark_all')
    .then(() => {
        loadNotifications();
        checkUnread();
    });
}

<?php if (is_logged_in()): ?>
    checkUnread();
    setInterval(checkUnread, 10000); // Revisar cada 10 seg
<?php endif; ?>
</script>
