<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/mailer/mailer_helper.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $nombre = sanitize($_POST['nombre']);
    $usuario = sanitize($_POST['usuario']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $rol = $_POST['rol'] ?? 'estudiante';
    $secret_code = $_POST['secret_code'] ?? '';

    if (empty($nombre) || empty($usuario) || empty($email) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (($rol === 'docente' || $rol === 'monitor') && !verify_secret_code($pdo, $secret_code)) {
        $error = 'El código secreto para este rol es incorrecto.';
    } else {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_completo, usuario, email, password, rol) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $usuario, $email, $password_hash, $rol]);
            $user_id = $pdo->lastInsertId();

            $codigo_verificacion = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $stmt = $pdo->prepare("INSERT INTO verificaciones_email (usuario_id, codigo, expira_en) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $codigo_verificacion, $expira]);

            $asunto = "Bienvenido a CodeLab - Verifica tu correo";
            $cuerpo = "<h1>Hola $nombre!</h1><p>Gracias por unirte a CodeLab. Tu código de verificación es: <b>$codigo_verificacion</b></p><p>Ingresa este código para activar tu cuenta.</p>";
            
            if (sendEmail($email, $asunto, $cuerpo)) {
                $pdo->commit();
                redirect("views/verify_email.php?email=" . urlencode($email));
            } else {
                $pdo->rollBack();
                $error = 'Error al enviar el correo de verificación. Inténtalo de nuevo.';
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
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
                <select name="rol" id="rol-selector" class="form-control" required style="cursor: pointer;">
                    <option value="estudiante">Soy Estudiante</option>
                    <option value="monitor">Soy Monitor</option>
                    <option value="docente">Soy Docente / Investigador</option>
                </select>
            </div>
            <div id="secret-code-container" class="form-group" style="display: none;">
                <label>Código Secreto (Para Docentes)</label>
                <input type="text" name="secret_code" class="form-control" placeholder="Código de validación">
            </div>
            <button type="submit" name="register" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Crear Cuenta</button>
        </form>

        <p style="margin-top: 25px; text-align: center; color: var(--text-secondary); font-size: 0.9rem;">
            ¿Ya tienes cuenta? <a href="<?= BASE_URL ?>views/login.php" style="color: var(--primary-color);">Inicia sesión aquí</a>
        </p>
    </div>
</div>

<script>
document.getElementById('rol-selector').addEventListener('change', function() {
    const container = document.getElementById('secret-code-container');
    const label = container.querySelector('label');
    
    if (this.value === 'docente' || this.value === 'monitor') {
        container.style.display = 'block';
        label.innerText = this.value === 'docente' ? 'Código Secreto (Para Docentes)' : 'Código Secreto (Para Monitores)';
    } else {
        container.style.display = 'none';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
