<?php
require_once __DIR__ . '/../includes/header.php';

if (!is_logged_in()) redirect('views/login.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ask'])) {
    $titulo = sanitize($_POST['titulo']);
    $contenido = sanitize($_POST['contenido']);
    $tags = sanitize($_POST['tags']);
    $usuario_id = $_SESSION['user_id'];

    if (empty($titulo) || empty($contenido)) {
        $error = 'Por favor, llena los campos básicos.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO foro_preguntas (titulo, contenido, tags, usuario_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$titulo, $contenido, $tags, $usuario_id]);
            $pregunta_id = $pdo->lastInsertId();
            redirect("/views/forum_detail.php?id=$pregunta_id");
        } catch (PDOException $e) {
            $error = 'Error al publicar: ' . $e->getMessage();
        }
    }
}
?>

<div class="animate-in" style="max-width: 800px; margin: 40px auto;">
    <div class="glass-card">
        <h2><i class="fas fa-question-circle" style="color: var(--primary-color);"></i> Nueva Pregunta al Foro</h2>
        <p style="color: var(--text-secondary); margin-bottom: 30px;">Describe tu problema de programación y recibe ayuda técnica o de nuestra IA.</p>
        
        <?php if ($error): ?>
            <div style="background: rgba(255, 0, 0, 0.2); padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Título Descriptivo</label>
                <input type="text" name="titulo" class="form-control" placeholder="Ej. Error al declarar un ArrayList en Java" required>
            </div>
            <div class="form-group">
                <label>Explicación del Problema</label>
                <div style="margin-bottom: 5px;">
                    <button type="button" onclick="openCodeModal('contenido-pregunta')" class="btn" style="padding: 4px 10px; font-size: 0.75rem; background: #e2e8f0; border: 1px solid #cbd5e1; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-code"></i> Insertar Bloque de Código
                    </button>
                </div>
                <textarea name="contenido" id="contenido-pregunta" class="form-control" rows="10" placeholder="Incluye el error y lo que has intentado..." required></textarea>
            </div>
            <div class="form-group">
                <label>Etiquetas (Separadas por comas)</label>
                <input type="text" name="tags" class="form-control" placeholder="ej. java, ciclos, errores">
            </div>
            <div style="display: flex; gap: 20px; margin-top: 30px;">
                <button type="submit" name="ask" class="btn btn-primary" style="flex: 1;">Publicar Pregunta</button>
                <a href="<?= BASE_URL ?>views/forum.php" class="btn btn-outline" style="flex: 1; text-align: center;">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
function insertCodeBlock(id) { openCodeModal(id); }
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
