<?php
require_once __DIR__ . '/../includes/header.php';
if (!is_logged_in()) redirect('views/login.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_snippet'])) {
    $titulo = sanitize($_POST['titulo']);
    $descripcion = sanitize($_POST['descripcion']);
    $codigo = $_POST['codigo'];
    $lenguaje = sanitize($_POST['lenguaje']);
    $privacidad = $_POST['privacidad'];

    if (empty($titulo) || empty($codigo)) {
        $error = 'El título y el código son obligatorios.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO snippets (titulo, descripcion, codigo, lenguaje, usuario_id, privacidad) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titulo, $descripcion, $codigo, $lenguaje, $_SESSION['user_id'], $privacidad]);
        redirect('views/snippets.php');
    }
}
?>

<div class="animate-in" style="max-width: 700px; margin: 40px auto;">
    <div class="glass-card">
        <h2><i class="fas fa-code" style="color: var(--primary-color);"></i> Subir Fragmento de Código</h2>
        <p style="color: var(--text-secondary); margin-bottom: 25px;">Comparte tu código con la comunidad para que otros lo reutilicen.</p>

        <?php if ($error): ?>
            <div style="background: rgba(239,68,68,0.1); color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(239,68,68,0.2);"><?= $error ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Título</label>
                <input type="text" name="titulo" class="form-control" placeholder="Ej. Búsqueda Binaria" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3" placeholder="¿Qué hace este código?"></textarea>
            </div>
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Lenguaje</label>
                    <select name="lenguaje" class="form-control">
                        <option value="Java">Java</option>
                        <option value="Python">Python</option>
                        <option value="JavaScript">JavaScript</option>
                        <option value="C++">C++</option>
                        <option value="PHP">PHP</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Visibilidad</label>
                    <select name="privacidad" class="form-control">
                        <option value="publico">Público</option>
                        <option value="privado">Privado</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Código</label>
                <textarea name="codigo" class="form-control" rows="12" style="font-family: monospace; background: #1e293b; color: #e2e8f0;" placeholder="Pega tu código aquí..." required></textarea>
            </div>
            <button type="submit" name="create_snippet" class="btn btn-primary" style="width: 100%;">Publicar Fragmento</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
