<?php 
require_once __DIR__ . '/functions.php'; 

// Sincronizar el rol de la sesión con la base de datos para evitar desajustes visuales
if (is_logged_in()) {
    $stmt_role = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
    $stmt_role->execute([$_SESSION['user_id']]);
    $real_role = $stmt_role->fetchColumn();
    if ($real_role) {
        $_SESSION['rol'] = $real_role;
    }
}
?>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    <style>
    @media (max-width: 1100px) {
        .nav-text-hide { display: none; }
    }
    </style>
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
                        <?php if ($_SESSION['rol'] == 'admin'): ?>
                            <li style="border-left: 1px solid var(--glass-border); padding-left: 15px; margin-left: 10px; display: flex; align-items: center;">
                                <a href="<?= BASE_URL ?>views/admin_dashboard.php" style="color: #ef4444; font-weight: 800; display: flex; align-items: center; gap: 5px; font-size: 0.85rem;" title="Panel Administrador">
                                    <i class="fas fa-user-shield"></i> <span class="nav-text-hide">ADMIN</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if ($_SESSION['rol'] == 'docente'): ?>
                            <li style="border-left: 1px solid var(--glass-border); padding-left: 15px; margin-left: 10px; display: flex; align-items: center;">
                                <a href="<?= BASE_URL ?>views/teacher_dashboard.php" style="color: #f59e0b; font-weight: 800; display: flex; align-items: center; gap: 5px; font-size: 0.85rem;" title="Panel Docente">
                                    <i class="fas fa-chalkboard-teacher"></i> <span class="nav-text-hide">DOCENTE</span>
                                </a>
                            </li>
                            <li style="margin-left: 5px;"><a href="<?= BASE_URL ?>views/teacher_mail.php" title="Enviar correos" style="color: #f59e0b;"><i class="fas fa-envelope"></i></a></li>
                        <?php endif; ?>

                        <?php if ($_SESSION['rol'] == 'monitor'): ?>
                            <li style="border-left: 1px solid var(--glass-border); padding-left: 15px; margin-left: 10px; display: flex; align-items: center;">
                                <a href="<?= BASE_URL ?>views/monitor_dashboard.php" style="color: #8b5cf6; font-weight: 800; display: flex; align-items: center; gap: 5px; font-size: 0.85rem;" title="Panel Monitor">
                                    <i class="fas fa-eye"></i> <span class="nav-text-hide">MONITOR</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li><a href="<?= BASE_URL ?>views/chat.php" title="Chat"><i class="far fa-paper-plane"></i></a></li>
                        <li>
                            <a href="<?= BASE_URL ?>views/profile.php" style="display: flex; align-items: center; gap: 10px; background: var(--glass-bg); padding: 5px 12px; border-radius: 30px; border: 1px solid var(--glass-border); text-decoration: none;">
                                <img src="<?= BASE_URL ?>assets/img/<?= $_SESSION['avatar'] ?? 'default_avatar.png' ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['usuario'] ?? 'User') ?>'" alt="Perfil" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                                    <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-primary);"><?= explode(' ', $_SESSION['nombre'] ?? 'Usuario')[0] ?></span>
                                    <?php 
                                        $role_data = [
                                            'admin' => ['name' => 'Admin', 'color' => '#ef4444'],
                                            'docente' => ['name' => 'Docente', 'color' => '#f59e0b'],
                                            'monitor' => ['name' => 'Monitor', 'color' => '#8b5cf6'],
                                            'estudiante' => ['name' => 'Estudiante', 'color' => '#3b82f6']
                                        ];
                                        $curr_role = $role_data[$_SESSION['rol'] ?? 'estudiante'];
                                    ?>
                                    <span style="font-size: 0.55rem; text-transform: uppercase; font-weight: 800; color: <?= $curr_role['color'] ?>;"><?= $curr_role['name'] ?></span>
                                </div>
                            </a>
                        </li>
                        <li><a href="<?= BASE_URL ?>api/logout.php" title="Salir" style="color: #ef4444; margin-left: 5px;"><i class="fas fa-sign-out-alt"></i></a></li>
                    <?php else: ?>
                        <li><a href="<?= BASE_URL ?>views/login.php">Entrar</a></li>
                        <li><a href="<?= BASE_URL ?>views/register.php" class="btn btn-primary">Crear Cuenta</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">
