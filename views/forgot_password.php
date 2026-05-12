<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/mailer/mailer_helper.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_reset'])) {
    $email = sanitize($_POST['email']);

    if (empty($email)) {
        $error = 'Por favor, ingresa tu correo electrónico.';
    } else {
        // Verificar si el usuario existe
        $stmt = $pdo->prepare("SELECT id, nombre_completo FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $codigo = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

            try {
                $stmt = $pdo->prepare("INSERT INTO password_resets (email, codigo, expira_en) VALUES (?, ?, ?)");
                $stmt->execute([$email, $codigo, $expira]);

                $asunto = "Recuperación de Contraseña - CodeLab";
                $cuerpo = "
                    <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px;'>
                        <h2 style='color: #2563eb;'>Hola, {$user->nombre_completo}</h2>
                        <p>Hemos recibido una solicitud para restablecer tu contraseña en CodeLab.</p>
                        <p>Tu código de seguridad es:</p>
                        <div style='background: #f1f5f9; padding: 20px; text-align: center; border-radius: 8px;'>
                            <span style='font-size: 2rem; font-weight: bold; letter-spacing: 5px; color: #1e293b;'>$codigo</span>
                        </div>
                        <p style='margin-top: 20px;'>Este código expirará en 1 hora. Si no solicitaste este cambio, puedes ignorar este correo.</p>
                        <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                        <p style='font-size: 0.8rem; color: #64748b;'>Atentamente,<br>El equipo de CodeLab</p>
                    </div>
                ";

                if (sendEmail($email, $asunto, $cuerpo)) {
                    $_SESSION['reset_email'] = $email;
                    redirect('views/reset_password.php');
                } else {
                    $error = 'No pudimos enviar el correo. Por favor, inténtalo más tarde.';
                }
            } catch (PDOException $e) {
                $error = 'Ocurrió un error en el sistema. Inténtalo de nuevo.';
            }
        } else {
            // No revelamos si el email existe o no por seguridad, pero en este contexto educativo podemos ser amigables
            $error = 'No encontramos ninguna cuenta asociada a ese correo.';
        }
    }
}
?>

<div class="animate-in" style="max-width: 450px; margin: 80px auto;">
    <div class="glass-card" style="box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="width: 70px; height: 70px; background: rgba(59, 130, 246, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                <i class="fas fa-key" style="font-size: 2rem; color: var(--primary-color);"></i>
            </div>
            <h2>¿Olvidaste tu contraseña?</h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">No te preocupes, te enviaremos un código para restablecerla.</p>
        </div>

        <?php if ($error): ?>
            <div style="background: rgba(239, 68, 68, 0.1); color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(239, 68, 68, 0.2); font-size: 0.9rem;">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Tu Correo Electrónico</label>
                <div style="position: relative;">
                    <i class="far fa-envelope" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                    <input type="email" name="email" class="form-control" placeholder="nombre@ejemplo.com" required style="padding-left: 45px;">
                </div>
            </div>
            
            <button type="submit" name="request_reset" class="btn btn-primary" style="width: 100%; margin-top: 10px; padding: 12px;">
                Enviar código de seguridad
            </button>
        </form>

        <p style="margin-top: 25px; text-align: center; color: var(--text-secondary); font-size: 0.9rem;">
            ¿Recordaste tu contraseña? <a href="<?= BASE_URL ?>views/login.php" style="color: var(--primary-color); font-weight: bold;">Volver al inicio</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
