<?php
require_once __DIR__ . '/../includes/header.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $usuario_email = sanitize($_POST['usuario_email']);
    $password = $_POST['password'];

    if (empty($usuario_email) || empty($password)) {
        $error = 'Por favor, completa todos los campos.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? OR email = ?");
        $stmt->execute([$usuario_email, $usuario_email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user->password)) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['nombre'] = $user->nombre_completo;
            $_SESSION['usuario'] = $user->usuario;
            $_SESSION['rol'] = $user->rol; // Guardamos el rol
            redirect('index.php');
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>

<div class="animate-in" style="max-width: 400px; margin: 80px auto;">
    <div class="glass-card">
        <h2 style="margin-bottom: 25px; text-align: center;">Iniciar Sesión</h2>
        
        <?php if ($error): ?>
            <div style="background: rgba(255, 0, 0, 0.2); padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(255, 0, 0, 0.3);">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Usuario o Correo</label>
                <input type="text" name="usuario_email" class="form-control" required>
            </div>
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label>Contraseña</label>
                    <a href="<?= BASE_URL ?>views/forgot_password.php" style="font-size: 0.75rem; color: var(--primary-color);">¿Olvidaste tu contraseña?</a>
                </div>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Entrar</button>
        </form>

        <p style="margin-top: 25px; text-align: center; color: var(--text-secondary); font-size: 0.9rem;">
            ¿No tienes cuenta? <a href="<?= BASE_URL ?>views/register.php" style="color: var(--primary-color);">Regístrate aquí</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
