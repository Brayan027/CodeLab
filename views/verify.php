<?php
require_once __DIR__ . '/../includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify'])) {
    $email = sanitize($_POST['email']);
    $codigo = sanitize($_POST['codigo']);

    $stmt = $pdo->prepare("SELECT v.*, u.id as user_id FROM verificaciones_email v JOIN usuarios u ON v.usuario_id = u.id WHERE u.email = ? AND v.codigo = ? AND v.utilizado = 0 AND v.expira_en > NOW()");
    $stmt->execute([$email, $codigo]);
    $verificacion = $stmt->fetch();

    if ($verificacion) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE usuarios SET email_verificado = 1 WHERE id = ?");
        $stmt->execute([$verificacion['user_id']]);
        
        $stmt = $pdo->prepare("UPDATE verificaciones_email SET utilizado = 1 WHERE id = ?");
        $stmt->execute([$verificacion['id']]);
        
        $pdo->commit();
        $success = '¡Email verificado correctamente! Ya puedes iniciar sesión.';
    } else {
        $error = 'El código es inválido o ha expirado.';
    }
}
?>
<div class="animate-in" style="max-width: 450px; margin: 60px auto;">
    <div class="glass-card">
        <h2 style="margin-bottom: 25px; text-align: center;">Verificar Correo</h2>
        <?php if ($error): ?><div style="color: #dc2626; margin-bottom: 15px;"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div style="color: #059669; margin-bottom: 15px;"><?= $success ?></div><?php endif; ?>
        <form action="" method="POST">
            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="form-group"><label>Código de Verificación</label><input type="text" name="codigo" class="form-control" required></div>
            <button type="submit" name="verify" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Verificar</button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
