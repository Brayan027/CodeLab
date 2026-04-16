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
                    <li><a href="<?= BASE_URL ?>views/forum.php">Foros</a></li>
                    <li><a href="<?= BASE_URL ?>views/learning_routes.php">Rutas</a></li>
                    <li><a href="<?= BASE_URL ?>views/snippets.php">Repositorio</a></li>
                    <li><a href="<?= BASE_URL ?>views/users.php">Comunidad</a></li>
                    <?php if (is_logged_in()): ?>
                        <li><a href="<?= BASE_URL ?>views/chat.php">Chat</a></li>
                        <li><a href="<?= BASE_URL ?>views/profile.php">Mi Perfil</a></li>
                        <li><a href="<?= BASE_URL ?>api/logout.php" class="btn btn-outline">Salir</a></li>
                    <?php else: ?>
                        <li><a href="<?= BASE_URL ?>views/login.php">Iniciar Sesión</a></li>
                        <li><a href="<?= BASE_URL ?>views/register.php" class="btn btn-primary">Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">
