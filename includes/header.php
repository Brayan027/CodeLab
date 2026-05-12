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
    <script>
    function toggleTheme() {
        const root = document.documentElement;
        const theme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        document.getElementById('theme-icon').className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
    document.addEventListener('DOMContentLoaded', () => {
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', theme);
        const icon = document.getElementById('theme-icon');
        if(icon) icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    });
    </script>
</head>
<body>
    <header>
        <div class="container nav-container">
            <nav>
                <a href="<?= BASE_URL ?>index.php" class="logo">CodeLab</a>
                <ul class="nav-links">
                    <li><button onclick="toggleTheme()" class="theme-toggle" style="background:none; border:none; color:var(--text-primary); cursor:pointer;"><i id="theme-icon" class="fas fa-moon"></i></button></li>
                    <li><a href="<?= BASE_URL ?>views/forum.php"><i class="far fa-comments"></i> Foros</a></li>
                    <li><a href="<?= BASE_URL ?>views/learning_routes.php"><i class="fas fa-map-signs"></i> Rutas</a></li>
                    <li><a href="<?= BASE_URL ?>views/snippets.php"><i class="fas fa-code"></i> Repositorio</a></li>
                    <li><a href="<?= BASE_URL ?>views/users.php"><i class="fas fa-users"></i> Comunidad</a></li>
                    
                    <?php if (is_logged_in()): ?>
                        <li><a href="<?= BASE_URL ?>views/ai_mentor.php" style="color: var(--secondary-color);"><i class="fas fa-brain"></i> IA</a></li>
                        <li style="position: relative;" id="notif-wrapper">
                            <a href="#" id="notif-bell" style="position: relative;">
                                <i class="fas fa-bell"></i>
                                <span id="notif-count" style="display: none; position: absolute; top: -8px; right: -8px; background: #ef4444; color: #fff; font-size: 0.65rem; padding: 2px 6px; border-radius: 10px; border: 2px solid #fff; font-weight: bold;">0</span>
                            </a>
                            <div id="notif-dropdown" class="glass-card" style="display: none; position: absolute; top: 40px; right: 0; width: 320px; max-height: 400px; overflow-y: auto; z-index: 1000; padding: 15px; background: white; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid var(--glass-border); border-radius: 16px;">
                                <div id="notif-list">Cargando...</div>
                            </div>
                        </li>
                        <?php if ($_SESSION['rol'] == 'docente'): ?>
                            <li style="border-left: 1px solid var(--glass-border); padding-left: 10px; margin-left: 5px;">
                                <a href="<?= BASE_URL ?>views/teacher_dashboard.php" style="color: var(--secondary-color); font-weight: 700;" title="Panel Docente"><i class="fas fa-chalkboard-teacher"></i> Docente</a>
                            </li>
                            <li><a href="<?= BASE_URL ?>views/teacher_mail.php" title="Enviar correos"><i class="fas fa-envelope"></i></a></li>
                        <?php endif; ?>
                        <li><a href="<?= BASE_URL ?>views/chat.php" title="Chat"><i class="far fa-paper-plane"></i></a></li>
                        <li>
                            <a href="<?= BASE_URL ?>views/profile.php" title="Mi Perfil">
                                <img src="<?= BASE_URL ?>assets/img/<?= $_SESSION['avatar'] ?? 'default_avatar.png' ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['usuario'] ?? 'User') ?>'" alt="Perfil" style="width: 32px; height: 32px; border-radius: 50%;">
                            </a>
                        </li>
                        <li><a href="<?= BASE_URL ?>api/logout.php" title="Salir" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i></a></li>
                    <?php else: ?>
                        <li><a href="<?= BASE_URL ?>views/login.php">Entrar</a></li>
                        <li><a href="<?= BASE_URL ?>views/register.php" class="btn btn-primary">Crear Cuenta</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">
