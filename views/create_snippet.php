<?php
require_once __DIR__ . '/../includes/header.php';
if (!is_logged_in()) redirect('views/login.php');

$error = '';
$parent_id = $_GET['fork_id'] ?? null;
$initial_blocks = [];
$initial_title = '';
$initial_desc = '';
$initial_lang = 'Java';

if ($parent_id) {
    $stmt = $pdo->prepare("SELECT * FROM snippets WHERE id = ?");
    $stmt->execute([$parent_id]);
    $parent = $stmt->fetch();
    if ($parent) {
        $initial_title = "Mejora de: " . $parent->titulo;
        $initial_desc = "Basado en el aporte original de @" . $parent->id . ". ";
        $initial_blocks = json_decode($parent->codigo, true);
        $initial_lang = $parent->lenguaje;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_snippet'])) {
    $titulo = sanitize($_POST['titulo']);
    $descripcion = sanitize($_POST['descripcion']);
    $codigo_json = $_POST['snippet_contenido_json'];
    $lenguaje = sanitize($_POST['lenguaje']);
    $privacidad = $_POST['privacidad'];
    $submitted_parent_id = $_POST['parent_id'] ?: null;

    if (empty($titulo) || empty($codigo_json)) {
        $error = 'El título y el contenido son obligatorios.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO snippets (titulo, descripcion, codigo, lenguaje, usuario_id, privacidad, parent_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titulo, $descripcion, $codigo_json, $lenguaje, $_SESSION['user_id'], $privacidad, $submitted_parent_id]);
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

        <style>
            .block-item { background: #f8fafc; border: 1px solid var(--glass-border); border-radius: 10px; padding: 15px; margin-bottom: 12px; position: relative; }
            .block-item .remove-block { position: absolute; top: 10px; right: 10px; color: #ef4444; cursor: pointer; font-size: 0.9rem; opacity: 0.6; }
            .block-label { font-size: 0.75rem; font-weight: bold; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 8px; display: flex; align-items: center; gap: 5px; }
            .add-block-btns { display: flex; gap: 10px; margin-top: 10px; margin-bottom: 20px; }
            .btn-add-block { flex: 1; padding: 10px; border: 2px dashed var(--glass-border); background: #fff; border-radius: 8px; cursor: pointer; font-size: 0.85rem; color: var(--text-secondary); }
            .btn-add-block:hover { border-color: var(--primary-color); color: var(--primary-color); }
        </style>

        <form action="" method="POST">
            <input type="hidden" name="parent_id" value="<?= $parent_id ?>">
            <div class="form-group">
                <label>Título</label>
                <input type="text" name="titulo" class="form-control" placeholder="Ej. Búsqueda Binaria" value="<?= htmlspecialchars($initial_title) ?>" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3" placeholder="¿Qué hace este código?"><?= htmlspecialchars($initial_desc) ?></textarea>
            </div>
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Lenguaje</label>
                    <select name="lenguaje" class="form-control">
                        <?php 
                        $langs = ["Java", "Python", "JavaScript", "Node.js", "Express", "SQL", "PHP", "C++", "C#", "TypeScript", "HTML/CSS", "React", "Kotlin", "Swift", "Go", "Otro"];
                        foreach ($langs as $l): ?>
                            <option value="<?= $l ?>" <?= $l == $initial_lang ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
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
                <label>Estructura del Fragmento (Añade bloques de texto y código)</label>
                <div id="snippet-blocks-container">
                    <!-- Los bloques se añadirán aquí dinámicamente -->
                </div>
                <div class="add-block-btns">
                    <button type="button" class="btn-add-block" onclick="addBlock('text')">
                        <i class="fas fa-align-left"></i> + Texto
                    </button>
                    <button type="button" class="btn-add-block" onclick="addBlock('code')">
                        <i class="fas fa-code"></i> + Código
                    </button>
                </div>
                <input type="hidden" name="snippet_contenido_json" id="snippet_json">
            </div>
            <button type="submit" name="create_snippet" class="btn btn-primary" style="width: 100%;">Publicar Fragmento</button>
        </form>
    </div>
</div>

<script>
function addBlock(type, value = '') {
    const container = document.getElementById('snippet-blocks-container');
    const block = document.createElement('div');
    block.className = 'block-item';
    block.dataset.type = type;
    
    const icon = type === 'text' ? 'fa-align-left' : 'fa-code';
    const label = type === 'text' ? 'Explicación' : 'Bloque de Código';
    
    block.innerHTML = `
        <div class="block-label"><i class="fas ${icon}"></i> ${label}</div>
        <span class="remove-block" onclick="this.parentElement.remove(); updateJSON()"><i class="fas fa-trash"></i></span>
        <textarea class="form-control" rows="${type === 'text' ? 4 : 8}" 
            style="${type === 'code' ? 'font-family: monospace; background: #1e293b; color: #e2e8f0;' : ''}"
            placeholder="${type === 'text' ? 'Explica esta parte...' : '// Código...'}"
            oninput="updateJSON()">${value}</textarea>
    `;
    container.appendChild(block);
    updateJSON();
}

function updateJSON() {
    const blocks = [];
    document.querySelectorAll('.block-item').forEach(item => {
        blocks.push({ type: item.dataset.type, value: item.querySelector('textarea').value });
    });
    document.getElementById('snippet_json').value = JSON.stringify(blocks);
}

// Cargar datos iniciales (para Forks)
<?php if (!empty($initial_blocks)): ?>
    const initial = <?= json_encode($initial_blocks) ?>;
    initial.forEach(b => addBlock(b.type, b.value));
<?php else: ?>
    // Inicializar con un bloque de código por defecto
    addBlock('code');
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
