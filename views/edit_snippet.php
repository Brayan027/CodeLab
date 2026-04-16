<?php
require_once __DIR__ . '/../includes/header.php';
if (!is_logged_in()) redirect('views/login.php');

$snippet_id = $_GET['id'] ?? null;
if (!$snippet_id) redirect('views/snippets.php');

// Obtener datos actuales
$stmt = $pdo->prepare("SELECT * FROM snippets WHERE id = ? AND usuario_id = ?");
$stmt->execute([$snippet_id, $_SESSION['user_id']]);
$snippet = $stmt->fetch();

if (!$snippet) die("No tienes permiso para editar este fragmento.");

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_snippet'])) {
    $titulo = sanitize($_POST['titulo']);
    $descripcion = sanitize($_POST['descripcion']);
    $codigo = $_POST['codigo'];
    $lenguaje = sanitize($_POST['lenguaje']);
    $privacidad = $_POST['privacidad'];

    if (empty($titulo) || empty($codigo)) {
        $error = 'El título y el código son obligatorios.';
    } else {
        $stmt = $pdo->prepare("UPDATE snippets SET titulo = ?, descripcion = ?, codigo = ?, lenguaje = ?, privacidad = ? WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$titulo, $descripcion, $codigo, $lenguaje, $privacidad, $snippet_id, $_SESSION['user_id']]);
        redirect('views/snippet_detail.php?id=' . $snippet_id);
    }
}
?>

<div class="animate-in" style="max-width: 700px; margin: 40px auto;">
    <div class="glass-card">
        <h2><i class="fas fa-edit" style="color: var(--primary-color);"></i> Editar Fragmento</h2>
        <p style="color: var(--text-secondary); margin-bottom: 25px;">Actualiza tu código o cambia su visibilidad.</p>

        <?php if ($error): ?>
            <div style="background: rgba(239,68,68,0.1); color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(239,68,68,0.2);"><?= $error ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Título</label>
                <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($snippet->titulo) ?>" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($snippet->descripcion) ?></textarea>
            </div>
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Lenguaje</label>
                    <select name="lenguaje" class="form-control">
                        <?php 
                        $langs = ['Java', 'Python', 'JavaScript', 'C++', 'PHP', 'Otro'];
                        foreach($langs as $l): ?>
                            <option value="<?= $l ?>" <?= $snippet->lenguaje == $l ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Visibilidad</label>
                    <select name="privacidad" class="form-control">
                        <option value="publico" <?= $snippet->privacidad == 'publico' ? 'selected' : '' ?>>Público</option>
                        <option value="privado" <?= $snippet->privacidad == 'privado' ? 'selected' : '' ?>>Privado</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Código</label>
                <textarea name="codigo" class="form-control" rows="12" style="font-family: monospace; background: #1e293b; color: #e2e8f0;" required><?= htmlspecialchars($snippet->codigo) ?></textarea>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="<?= BASE_URL ?>views/snippet_detail.php?id=<?= $snippet_id ?>" class="btn btn-outline" style="flex: 1; text-align: center;">Cancelar</a>
                <button type="submit" name="update_snippet" class="btn btn-primary" style="flex: 2;">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
