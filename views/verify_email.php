<?php
require_once __DIR__ . '/../includes/header.php';

$error = '';
$success = '';
$email = sanitize($_GET['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resend'])) {
    $email = sanitize($_POST['email']);
    
    $stmt = $pdo->prepare("SELECT id, nombre_completo FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        require_once __DIR__ . '/../includes/mailer/mailer_helper.php';
        
        $codigo_nuevo = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt = $pdo->prepare("INSERT INTO verificaciones_email (usuario_id, codigo, expira_en) VALUES (?, ?, ?)");
        $stmt->execute([$user->id, $codigo_nuevo, $expira]);

        $asunto = "Nuevo código de verificación - CodeLab";
        $cuerpo = "<h1>Hola {$user->nombre_completo}!</h1><p>Has solicitado un nuevo código de verificación: <b>$codigo_nuevo</b></p>";
        
        if (sendEmail($email, $asunto, $cuerpo)) {
            $success = 'Se ha enviado un nuevo código a tu correo.';
        } else {
            $error = 'Error al enviar el correo. Inténtalo más tarde.';
        }
    } else {
        $error = 'Correo no encontrado.';
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify'])) {
    $codigo = sanitize($_POST['codigo']);
    $email = sanitize($_POST['email']);

    if (empty($codigo) || empty($email)) {
        $error = 'Por favor, ingresa el código y el correo.';
    } else {
        // Buscar el usuario
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Verificar el código usando el tiempo de PHP para evitar líos de zona horaria
            $ahora = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("SELECT * FROM verificaciones_email WHERE usuario_id = ? AND codigo = ? AND utilizado = 0 AND expira_en > ? ORDER BY fecha_creacion DESC LIMIT 1");
            $stmt->execute([$user->id, $codigo, $ahora]);
            $verificacion = $stmt->fetch();

            if ($verificacion) {
                // Marcar como verificado
                $pdo->beginTransaction();
                try {
                    $pdo->prepare("UPDATE usuarios SET email_verificado = 1 WHERE id = ?")->execute([$user->id]);
                    $pdo->prepare("UPDATE verificaciones_email SET utilizado = 1 WHERE id = ?")->execute([$verificacion->id]);
                    $pdo->commit();
                    $success = '¡Correo verificado con éxito! Ya puedes iniciar sesión.';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Error al procesar la verificación: ' . $e->getMessage();
                }
            } else {
                $error = 'El código es incorrecto o ha expirado.';
            }
        } else {
            $error = 'El correo electrónico no está registrado.';
        }
    }
}
?>

<div class="animate-in" style="max-width: 400px; margin: 80px auto;">
    <div class="glass-card">
        <h2 style="margin-bottom: 25px; text-align: center;">Verificar Correo</h2>
        <p style="text-align: center; color: var(--text-secondary); margin-bottom: 25px;">
            Ingresa el código de 6 dígitos enviado a tu correo.
        </p>
        
        <?php if ($error): ?>
            <div style="background: rgba(239, 68, 68, 0.1); color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(239, 68, 68, 0.2);">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if ($success && strpos($success, 'verificado') !== false): ?>
            <div style="background: rgba(16, 185, 129, 0.1); color: #059669; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(16, 185, 129, 0.2);">
                <?= $success ?>
                <div style="margin-top: 15px;">
                    <a href="login.php" class="btn btn-primary" style="display: block; text-align: center;">Ir al Login</a>
                </div>
            </div>
        <?php else: ?>
            <?php if ($success): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: #059669; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(16, 185, 129, 0.2);">
                    <?= $success ?>
                </div>
            <?php endif; ?>
            
            <form action="" method="POST">

                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" value="<?= htmlspecialchars($email) ?>" class="form-control" disabled>
                </div>
                <div class="form-group">
                    <label>Código de Verificación</label>
                    <input type="text" name="codigo" class="form-control" placeholder="000000" maxlength="6" required style="text-align: center; font-size: 1.5rem; letter-spacing: 5px;">
                </div>
                <button type="submit" name="verify" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Verificar Ahora</button>
            </form>
            
            <div style="margin-top: 25px; border-top: 1px solid var(--glass-border); padding-top: 20px; text-align: center;">
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 10px;">¿No recibiste el código?</p>
                <form action="" method="POST">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                    <button type="submit" name="resend" style="background: none; border: none; color: var(--primary-color); font-weight: 600; cursor: pointer; font-size: 0.9rem;">
                        <i class="fas fa-sync-alt"></i> Enviar código de nuevo
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
