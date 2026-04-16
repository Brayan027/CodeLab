<?php
require_once __DIR__ . '/../includes/header.php';

$snippet_id = $_GET['id'] ?? null;
if (!$snippet_id) redirect('views/snippets.php');

$stmt = $pdo->prepare("SELECT s.*, u.usuario, u.nombre_completo FROM snippets s JOIN usuarios u ON s.usuario_id = u.id WHERE s.id = ?");
$stmt->execute([$snippet_id]);
$snippet = $stmt->fetch();
if (!$snippet) die("Snippet no encontrado.");

if ($snippet->privacidad == 'privado' && (!is_logged_in() || $_SESSION['user_id'] != $snippet->usuario_id)) {
    die("Este fragmento es privado.");
}
?>

<div class="animate-in" style="margin-top: 40px; max-width: 900px; margin-left: auto; margin-right: auto;">
    <div class="glass-card" style="margin-bottom: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div>
                <span style="background: rgba(59,130,246,0.1); color: var(--primary-color); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;"><?= $snippet->lenguaje ?></span>
                <h1 style="margin-top: 10px;"><?= $snippet->titulo ?></h1>
                <p style="color: var(--text-secondary); margin-top: 5px;">Por <strong><?= $snippet->nombre_completo ?></strong> (@<?= $snippet->usuario ?>) · <?= date('d M Y', strtotime($snippet->fecha_creacion)) ?></p>
            </div>
        </div>
        <?php if ($snippet->descripcion): ?>
            <p style="margin-top: 20px; line-height: 1.7;"><?= nl2br($snippet->descripcion) ?></p>
        <?php endif; ?>
    </div>

    <!-- Bloque de código -->
    <div class="glass-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 12px 20px; border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: 600; font-size: 0.9rem;"><?= $snippet->lenguaje ?></span>
            <button onclick="copySnippet()" class="btn btn-outline" style="font-size: 0.8rem; padding: 5px 15px;"><i class="far fa-copy"></i> Copiar</button>
        </div>
        <div style="background: #1e293b; padding: 20px; overflow-x: auto;">
            <pre style="margin: 0;" id="snippetCode"><code style="font-family: 'Fira Code', monospace; color: #e2e8f0; line-height: 1.6;"><?= htmlspecialchars($snippet->codigo) ?></code></pre>
        </div>
    </div>

    <!-- Botón IA -->
    <div style="margin-top: 20px; text-align: center;">
        <button onclick="askAI('<?= addslashes($snippet->titulo) ?>', document.getElementById('snippetCode').innerText)" class="btn btn-primary" style="background: linear-gradient(45deg, var(--primary-color), var(--secondary-color)); padding: 12px 30px;">
            <i class="fas fa-robot"></i> Explicar con IA
        </button>
    </div>

    <!-- Modal IA -->
    <div id="aiModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div class="glass-card" style="max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; background: #fff;">
            <h3><i class="fas fa-brain" style="color: var(--secondary-color);"></i> Análisis de CodeLab AI</h3>
            <div id="aiContent" style="margin-top: 20px; line-height: 1.6;">Cargando análisis...</div>
            <button onclick="document.getElementById('aiModal').style.display='none'" class="btn btn-primary" style="margin-top: 20px; width: 100%;">Cerrar</button>
        </div>
    </div>
</div>

<script>
function copySnippet() {
    const code = document.getElementById('snippetCode').innerText;
    navigator.clipboard.writeText(code);
    alert('¡Código copiado!');
}

function askAI(titulo, codigo) {
    const modal = document.getElementById('aiModal');
    const content = document.getElementById('aiContent');
    modal.style.display = 'flex';
    content.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Analizando...</p>';
    fetch('<?= BASE_URL ?>api/ai_assist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=explain&titulo=${encodeURIComponent(titulo)}&codigo=${encodeURIComponent(codigo)}`
    })
    .then(res => res.json())
    .then(data => { content.innerHTML = data.explanation || 'Error al obtener la explicación.'; });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
