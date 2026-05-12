<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/mailer/mailer_helper.php';

if ($_SESSION['rol'] !== 'docente') {
    redirect('index.php');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_group_mail'])) {
    $asunto = sanitize($_POST['asunto']);
    $mensaje = $_POST['mensaje'];
    
    // En un sistema real buscaríamos estudiantes por curso, aquí mandamos a todos los estudiantes de la DB como "grupo"
    $stmt = $pdo->query("SELECT email, nombre_completo FROM usuarios WHERE rol = 'estudiante'");
    $estudiantes = $stmt->fetchAll();

    $count = 0;
    foreach ($estudiantes as $est) {
        $cuerpo = "<h2>Mensaje de tu Docente (" . $_SESSION['nombre_completo'] . ")</h2>" . $mensaje;
        if (sendEmail($est['email'], $asunto, $cuerpo)) {
            $count++;
        }
    }
    $success = "Se enviaron $count correos a los estudiantes.";
}
?>
<div class="glass-card" style="margin-top: 30px;">
    <h2>Enviar Correo a Estudiantes</h2>
    <?php if ($success): ?><div style="color: green;"><?= $success ?></div><?php endif; ?>
    <form action="" method="POST">
        <div class="form-group"><label>Asunto</label><input type="text" name="asunto" class="form-control" required></div>
        <div class="form-group"><label>Mensaje</label><textarea name="mensaje" class="form-control" rows="5" required></textarea></div>
        <button type="submit" name="send_group_mail" class="btn btn-primary">Enviar a todos los estudiantes</button>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
