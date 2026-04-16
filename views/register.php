<?php
require_once __DIR__ . '/../includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $nombre = sanitize($_POST['nombre']);
    $usuario = sanitize($_POST['usuario']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($nombre) || empty($usuario) || empty($email) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } else {
        $rol = $_POST['rol'] ?? 'estudiante';
        // Encriptar contraseña
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_completo, usuario, email, password, rol) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $usuario, $email, $password_hash, $rol]);
            $success = '¡Cuenta creada con éxito! Ya puedes iniciar sesión.';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'El usuario o el email ya están registrados.';
            } else {
                $error = 'Error al registrar: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="animate-in" style="max-width: 450px; margin: 60px auto;">
    <div class="glass-card">
        <h2 style="margin-bottom: 25px; text-align: center;">Únete a CodeLab</h2>
        
        <?php if ($error): ?>
            <div style="background: rgba(239, 68, 68, 0.1); color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(239, 68, 68, 0.2);">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="background: rgba(16, 185, 129, 0.1); color: #059669; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(16, 185, 129, 0.2);">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="nombre" class="form-control" placeholder="Ej. Juan Pérez" required>
            </div>
            <div class="form-group">
                <label>Nombre de Usuario</label>
                <input type="text" name="usuario" class="form-control" placeholder="ej. juan_dev" required>
            </div>
            <div class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" name="email" class="form-control" placeholder="correo@ejemplo.com" required>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label>¿Quién eres?</label>
                <select name="rol" class="form-control" required style="cursor: pointer;">
                    <option value="estudiante">Soy Estudiante</option>
                    <option value="docente">Soy Docente / Investigador</option>
                </select>
            </div>
            <button type="submit" name="register" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Crear Cuenta</button>
        </form>

        <p style="margin-top: 25px; text-align: center; color: var(--text-secondary); font-size: 0.9rem;">
            ¿Ya tienes cuenta? <a href="<?= BASE_URL ?>views/login.php" style="color: var(--primary-color);">Inicia sesión aquí</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
