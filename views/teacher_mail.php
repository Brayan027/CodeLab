<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/mailer/mailer_helper.php';

if ($_SESSION['rol'] !== 'docente') {
    redirect('index.php');
}

$success = '';
$error = '';

// stray opening PHP tag removed

// Obtener los grupos del docente para el selector
$stmtGroups = $pdo->prepare("SELECT id, nombre FROM grupos WHERE docente_id = ?");
$stmtGroups->execute([$_SESSION['user_id'] ?? null]);
$grupos = $stmtGroups->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_group_mail'])) {
    $asunto = sanitize($_POST['asunto']);
    $mensaje = $_POST['mensaje'];
    $grupo_id = intval($_POST['grupo_id'] ?? 0);

    // Obtener estudiantes del grupo seleccionado
    if ($grupo_id) {
        $stmt = $pdo->prepare("SELECT u.email, u.nombre_completo FROM usuarios u JOIN grupo_estudiantes ge ON u.id = ge.estudiante_id WHERE ge.grupo_id = ?");
        $stmt->execute([$grupo_id]);
        $estudiantes = $stmt->fetchAll();
    } else {
        // Fallback: todos los estudiantes
        $stmt = $pdo->query("SELECT email, nombre_completo FROM usuarios WHERE rol = 'estudiante'");
        $estudiantes = $stmt->fetchAll();
    }

    $count = 0;
    foreach ($estudiantes as $est) {
        $cuerpo = "<h2>Mensaje de tu Docente (" . ($_SESSION['nombre'] ?? 'Docente') . ")</h2>" . $mensaje;
        if (sendEmail($est->email, $asunto, $cuerpo)) {
            $count++;
        }
    }
    $success = "Se enviaron $count correos al grupo seleccionado.";
}
?>
<div class="teacher-container animate-in">
    <?php require_once __DIR__ . '/../includes/teacher_sidebar.php'; ?>

    <main class="teacher-content">
        <div class="glass-card">
            <h2><i class="fas fa-envelope-open-text" style="color: var(--secondary-color);"></i> Enviar Correo a Estudiantes</h2>
            <p style="color: var(--text-secondary); margin-bottom: 25px;">Comunícate directamente con tus grupos de clase.</p>
            
            <?php if ($success): ?>
                <div class="glass-card" style="background: rgba(16, 185, 129, 0.1); border-color: #10b981; color: #059669; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <?php if (!empty($grupos)): ?>
                    <div class="form-group">
                        <label for="grupo_id">Selecciona el Grupo al que deseas enviar</label>
                        <select name="grupo_id" id="grupo_id" class="form-control" required style="margin-bottom:20px;">
                            <option value="" disabled selected>-- Selecciona un grupo --</option>
                            <?php foreach ($grupos as $g): ?>
                                <option value="<?= $g->id ?>"><?= htmlspecialchars($g->nombre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning" style="background: rgba(255, 193, 7, 0.2); padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                        <i class="fas fa-exclamation-triangle"></i> No tienes grupos creados.
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Asunto del Correo</label>
                    <input type="text" name="asunto" class="form-control" placeholder="Ej: Recordatorio de Tarea" required>
                </div>
                <div class="form-group">
                    <label>Mensaje / Contenido</label>
                    <textarea name="mensaje" class="form-control" rows="5" placeholder="Escribe el mensaje aquí..." required></textarea>
                </div>
                
                <button type="submit" name="send_group_mail" class="btn btn-primary" <?= empty($grupos) ? 'disabled' : '' ?> style="width: 100%; margin-top: 20px;">
                    <i class="fas fa-paper-plane"></i> Enviar Correo al Grupo
                </button>
            </form>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
