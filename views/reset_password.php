<?php
require_once __DIR__ . '/../includes/header.php';

$error = '';
$success = '';
$email = $_SESSION['reset_email'] ?? '';

if (!$email) {
    redirect('views/forgot_password.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $codigo = sanitize($_POST['codigo']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($codigo) || empty($new_password)) {
        $error = 'Por favor, completa todos los campos.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($new_password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        // Verificar código
        $stmt = $pdo->prepare("SELECT id FROM password_resets WHERE email = ? AND codigo = ? AND utilizado = FALSE AND expira_en > NOW() ORDER BY fecha_creacion DESC LIMIT 1");
        $stmt->execute([$email, $codigo]);
        $reset_req = $stmt->fetch();

        if ($reset_req) {
            try {
                $pdo->beginTransaction();

                // Actualizar contraseña
                $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE email = ?");
                $stmt->execute([$password_hash, $email]);

                // Marcar código como usado
                $stmt = $pdo->prepare("UPDATE password_resets SET utilizado = TRUE WHERE id = ?");
                $stmt->execute([$reset_req->id]);

                $pdo->commit();
                $success = '¡Contraseña actualizada con éxito! Ya puedes iniciar sesión.';
                unset($_SESSION['reset_email']);
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Error al actualizar la contraseña: ' . $e->getMessage();
            }
        } else {
            $error = 'El código es incorrecto o ha expirado.';
        }
    }
}
?>

<div class="animate-in" style="max-width: 450px; margin: 80px auto;">
    <div class="glass-card" style="box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
        <h2 style="margin-bottom: 10px; text-align: center;">Restablecer Contraseña</h2>
        <p style="text-align: center; color: var(--text-secondary); margin-bottom: 25px; font-size: 0.9rem;">
            Ingresa el código enviado a <strong><?= htmlspecialchars($email) ?></strong>
        </p>
        
        <?php if ($error): ?>
            <div style="background: rgba(239, 68, 68, 0.1); color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(239, 68, 68, 0.2); font-size: 0.9rem;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="background: rgba(16, 185, 129, 0.1); color: #059669; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(16, 185, 129, 0.2); text-align: center;">
                <p style="margin-bottom: 15px; font-weight: 500;"><?= $success ?></p>
                <a href="<?= BASE_URL ?>views/login.php" class="btn btn-primary" style="display: inline-block;">Ir al Login</a>
            </div>
        <?php else: ?>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Código de Seguridad</label>
                    <input type="text" name="codigo" class="form-control" placeholder="6 dígitos" maxlength="6" required style="text-align: center; font-size: 1.5rem; letter-spacing: 5px; font-weight: bold;">
                </div>
                <div class="form-group">
                    <label>Nueva Contraseña</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Mínimo 6 caracteres" required>
                </div>
                <div class="form-group">
                    <label>Confirmar Nueva Contraseña</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repite la contraseña" required>
                </div>
                
                <button type="submit" name="reset_password" class="btn btn-primary" style="width: 100%; margin-top: 10px; padding: 12px;">
                    Actualizar Contraseña
                </button>
            </form>
        <?php endif; ?>

        <p style="margin-top: 25px; text-align: center; color: var(--text-secondary); font-size: 0.85rem;">
            ¿No recibiste el código? <a href="<?= BASE_URL ?>views/forgot_password.php" style="color: var(--primary-color);">Intentar de nuevo</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
