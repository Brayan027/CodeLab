<?php
require_once __DIR__ . '/../includes/header.php';

$snippet_id = $_GET['id'] ?? null;
if (!$snippet_id) redirect('views/snippets.php');

// Manejar eliminación
if (isset($_POST['delete_snippet']) && is_logged_in()) {
    $stmt = $pdo->prepare("DELETE FROM snippets WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$snippet_id, $_SESSION['user_id']]);
    redirect('views/snippets.php');
}

// Manejar nuevo comentario
if (isset($_POST['add_comment']) && is_logged_in()) {
    $contenido = sanitize($_POST['comentario']);
    if (!empty($contenido)) {
        $stmt = $pdo->prepare("INSERT INTO snippet_comentarios (snippet_id, usuario_id, contenido) VALUES (?, ?, ?)");
        $stmt->execute([$snippet_id, $_SESSION['user_id'], $contenido]);
        
        // Registrar actividad
        $stmt_log = $pdo->prepare("INSERT INTO uso_ia_logs (usuario_id, accion, titulo_conctexto) VALUES (?, 'snippet_comentario', ?)");
        $stmt_log->execute([$_SESSION['user_id'], "Comentó en snippet #$snippet_id"]);
    }
}

$stmt = $pdo->prepare("SELECT s.*, u.usuario, u.nombre_completo FROM snippets s JOIN usuarios u ON s.usuario_id = u.id WHERE s.id = ?");
$stmt->execute([$snippet_id]);
$snippet = $stmt->fetch();
if (!$snippet) die("Snippet no encontrado.");

if ($snippet->privacidad == 'privado' && (!is_logged_in() || $_SESSION['user_id'] != $snippet->usuario_id)) {
    die("Este fragmento es privado.");
}

// Obtener comentarios
$stmt = $pdo->prepare("SELECT c.*, u.usuario FROM snippet_comentarios c JOIN usuarios u ON c.usuario_id = u.id WHERE c.snippet_id = ? ORDER BY c.fecha_comentario DESC");
$stmt->execute([$snippet_id]);
$comentarios = $stmt->fetchAll();
?>

<div class="animate-in" style="margin-top: 40px; max-width: 900px; margin-left: auto; margin-right: auto;">
    <div class="glass-card" style="margin-bottom: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div>
                <span style="background: rgba(59,130,246,0.1); color: var(--primary-color); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;"><?= $snippet->lenguaje ?></span>
                <h1 style="margin-top: 10px;"><?= $snippet->titulo ?></h1>
                <p style="color: var(--text-secondary); margin-top: 5px;">Por <strong><?= $snippet->nombre_completo ?></strong> (@<?= $snippet->usuario ?>) · <?= date('d M Y', strtotime($snippet->fecha_creacion)) ?></p>
            </div>
            
            <?php if (is_logged_in() && $_SESSION['user_id'] == $snippet->usuario_id): ?>
                <div style="display: flex; gap: 10px;">
                    <a href="<?= BASE_URL ?>views/edit_snippet.php?id=<?= $snippet->id ?>" class="btn btn-outline" style="color: var(--primary-color);"><i class="fas fa-edit"></i></a>
                    <form method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este fragmento?');">
                        <button type="submit" name="delete_snippet" class="btn btn-outline" style="color: #ef4444;"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            <?php endif; ?>
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
    <div id="aiModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); z-index:1000; align-items:center; justify-content:center; padding: 20px;">
        <div class="glass-card" style="max-width: 700px; width: 100%; max-height: 85vh; overflow-y: auto; background: white; border: none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-brain" style="color: var(--secondary-color);"></i> 
                    Análisis de CodeLab AI
                </h3>
                <button onclick="document.getElementById('aiModal').style.display='none'" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary);">&times;</button>
            </div>
            
            <div id="aiContent" style="line-height: 1.7; color: #334155; font-size: 1.05rem;">
                <!-- Aquí se carga el contenido -->
            </div>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9; text-align: right;">
                <button onclick="document.getElementById('aiModal').style.display='none'" class="btn btn-primary" style="padding: 10px 25px;">Entendido</button>
            </div>
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
    content.innerHTML = `
        <div style="text-align: center; padding: 40px 0;">
            <i class="fas fa-spinner fa-spin" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 20px;"></i>
            <p style="font-weight: 500; color: var(--text-secondary);">El Profe IA está analizando tu código...</p>
        </div>
    `;

    const formData = new FormData();
    formData.append('action', 'explain');
    formData.append('titulo', titulo);
    formData.append('codigo', codigo);

    fetch('<?= BASE_URL ?>api/ai_assist.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            content.innerHTML = `
                <div style="padding: 20px; background: #fef2f2; border-radius: 12px; border: 1px solid #fee2e2; color: #b91c1c;">
                    <p><strong><i class="fas fa-exclamation-circle"></i> Error:</strong> ${data.error}</p>
                    ${data.api_message ? `<p style="font-size: 0.85rem; margin-top: 10px; opacity: 0.8;">Mensaje técnico: ${data.api_message}</p>` : ''}
                    ${data.debug_code ? `<p style="font-size: 0.85rem; opacity: 0.8;">Código HTTP: ${data.debug_code}</p>` : ''}
                    ${data.raw_response ? `<details style="margin-top:10px; font-size:0.7rem;"><summary>Ver respuesta cruda</summary><pre style="white-space:pre-wrap; background:#000; color:#fff; padding:10px; margin-top:5px;">${data.raw_response}</pre></details>` : ''}
                </div>
            `;
        } else {
            // Renderizamos la respuesta de la IA
            content.innerHTML = `
                <div class="ai-response-animate">
                    ${data.explanation}
                </div>
            `;
        }
    })
    .catch(err => {
        content.innerHTML = `<p style="color: red;">Error crítico al conectar con el servidor.</p>`;
        console.error(err);
    });
}
</script>

<style>
/* Estilos para el contenido generado por la IA */
#aiContent h4 { color: var(--primary-color); margin-top: 20px; margin-bottom: 10px; }
#aiContent p { margin-bottom: 15px; }
#aiContent ul { margin-bottom: 15px; padding-left: 20px; }
#aiContent li { margin-bottom: 8px; }
#aiContent strong { color: var(--text-primary); }

.ai-response-animate {
    animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

    <!-- Comentarios y Feedback -->
    <div style="margin-top: 50px;">
        <h3 style="margin-bottom: 25px;"><i class="far fa-comments"></i> Comunidad y Mejoras</h3>
        
        <?php if (is_logged_in()): ?>
            <div class="glass-card" style="margin-bottom: 30px;">
                <form method="POST">
                    <textarea name="comentario" class="form-control" rows="3" placeholder="Sugiere una mejora o deja tu feedback..." required></textarea>
                    <div style="text-align: right; margin-top: 15px;">
                        <button type="submit" name="add_comment" class="btn btn-primary" style="font-size: 0.85rem;">Publicar Comentario</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-secondary); margin-bottom: 30px;">
                <a href="<?= BASE_URL ?>views/login.php" style="color: var(--primary-color); font-weight: bold;">Inicia sesión</a> para comentar este código.
            </p>
        <?php endif; ?>

        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php if (empty($comentarios)): ?>
                <p style="text-align: center; color: var(--text-secondary); font-style: italic;">Nadie ha comentado todavía. ¡Inicia la conversación!</p>
            <?php else: ?>
                <?php foreach ($comentarios as $c): ?>
                    <div class="glass-card" style="background: rgba(255, 255, 255, 0.3);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.85rem;">
                            <strong style="color: var(--primary-color);">@<?= htmlspecialchars($c->usuario) ?></strong>
                            <span style="color: var(--text-secondary);"><?= date('d M, Y H:i', strtotime($c->fecha_comentario)) ?></span>
                        </div>
                        <p style="font-size: 0.95rem; line-height: 1.6;"><?= nl2br(htmlspecialchars($c->contenido)) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
