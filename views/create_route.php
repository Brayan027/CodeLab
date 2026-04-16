<?php
require_once __DIR__ . '/../includes/header.php';

if (!is_logged_in()) {
    redirect('views/login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_route'])) {
    $titulo = sanitize($_POST['titulo']);
    $descripcion = sanitize($_POST['descripcion']);
    $privacidad = $_POST['privacidad'];
    $usuario_id = $_SESSION['user_id'];

    if (empty($titulo)) {
        $error = 'El título es obligatorio.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO rutas (titulo, descripcion, privacidad, creador_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$titulo, $descripcion, $privacidad, $usuario_id]);
            $ruta_id = $pdo->lastInsertId();
            
            // Redirigir para añadir pasos
            redirect("/views/edit_route.php?id=$ruta_id");
        } catch (PDOException $e) {
            $error = 'Error al crear la ruta: ' . $e->getMessage();
        }
    }
}
?>

<div class="animate-in" style="max-width: 600px; margin: 40px auto;">
    <div class="glass-card">
        <h2 style="margin-bottom: 25px;"><i class="fas fa-plus-circle" style="color: var(--primary-color);"></i> Crear Nueva Ruta</h2>
        
        <?php if ($error): ?>
            <div style="background: rgba(255, 0, 0, 0.2); padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Título de la Ruta</label>
                <input type="text" name="titulo" class="form-control" placeholder="Ej. Fundamentos de Java" required>
            </div>
            <div class="form-group">
                <label>Descripción General</label>
                <textarea name="descripcion" class="form-control" rows="4" placeholder="¿De qué trata esta ruta?"></textarea>
            </div>
            <div class="form-group">
                <label>Visibilidad</label>
                <select name="privacidad" class="form-control">
                    <option value="publico">Público (Todos pueden verla)</option>
                    <option value="privado">Privado (Sólo tú)</option>
                </select>
            </div>
            <button type="submit" name="create_route" class="btn btn-primary" style="width: 100%;">Crear y Añadir Pasos</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
