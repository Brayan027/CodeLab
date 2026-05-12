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

        // Notificación al dueño del snippet
        $stmt_owner = $pdo->prepare("SELECT usuario_id, titulo FROM snippets WHERE id = ?");
        $stmt_owner->execute([$snippet_id]);
        $owner = $stmt_owner->fetch();
        if ($owner) {
            add_notification($pdo, $owner->usuario_id, 'comentario', "ha comentado en tu snippet: " . $owner->titulo, "views/snippet_detail.php?id=$snippet_id");
        }
    }
}

$stmt = $pdo->prepare("SELECT s.*, u.usuario, u.nombre_completo, u.rol,
              (SELECT usuario FROM usuarios WHERE id = (SELECT usuario_id FROM snippets WHERE id = s.parent_id)) as parent_usuario,
              (SELECT id FROM snippets WHERE id = s.parent_id) as parent_id_val
              FROM snippets s 
              JOIN usuarios u ON s.usuario_id = u.id 
              WHERE s.id = ?");
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
                <?php if ($snippet->parent_id_val): ?>
                    <div style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6; padding: 6px 15px; border-radius: 8px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 10px; border: 1px solid rgba(139, 92, 246, 0.2);">
                        <i class="fas fa-code-branch"></i> 
                        Mejora de la versión original por <a href="<?= BASE_URL ?>views/snippet_detail.php?id=<?= $snippet->parent_id_val ?>" style="font-weight: bold; color: inherit;">@<?= $snippet->parent_usuario ?></a>
                    </div>
                <?php endif; ?>
                <p style="color: var(--text-secondary); margin-top: 5px;">
                    Por <strong><?= $snippet->nombre_completo ?></strong> (@<?= $snippet->usuario ?>)
                    <?php if (isset($snippet->rol) && $snippet->rol == 'docente'): ?>
                        <span style="background: linear-gradient(45deg, #f59e0b, #d97706); color: white; padding: 2px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; margin-left: 8px; box-shadow: 0 2px 5px rgba(217, 119, 6, 0.2);">
                            <i class="fas fa-certificate"></i> DOCENTE MENTOR
                        </span>
                    <?php endif; ?>
                    · <?= date('d M Y', strtotime($snippet->fecha_creacion)) ?>
                </p>
            </div>
            
            <?php if (is_logged_in()): ?>
                <div style="display: flex; gap: 10px;">
                    <?php if ($_SESSION['user_id'] == $snippet->usuario_id): ?>
                        <a href="<?= BASE_URL ?>views/edit_snippet.php?id=<?= $snippet->id ?>" class="btn btn-outline" style="color: var(--primary-color);"><i class="fas fa-edit"></i></a>
                        <form method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este fragmento?');">
                            <button type="submit" name="delete_snippet" class="btn btn-outline" style="color: #ef4444;"><i class="fas fa-trash"></i></button>
                        </form>
                    <?php elseif (in_array($_SESSION['rol'], ['monitor', 'admin'])): ?>
                        <button onclick="deleteSnippetByModerator(<?= $snippet->id ?>)" class="btn btn-outline" style="color: #ef4444;" title="Eliminar como Moderador"><i class="fas fa-trash"></i></button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($snippet->descripcion): ?>
            <p style="margin-top: 20px; line-height: 1.7;"><?= nl2br($snippet->descripcion) ?></p>
        <?php endif; ?>
    </div>

    <!-- Contenido del Fragmento (Bloques Dinámicos) -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <?php
        $is_json = false;
        $blocks = json_decode($snippet->codigo, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($blocks)) {
            $is_json = true;
        }

        if ($is_json):
            foreach ($blocks as $b):
                if ($b['type'] === 'text'): ?>
                    <div class="glass-card" style="line-height: 1.8; font-size: 1.05rem;">
                        <?= nl2br(htmlspecialchars($b['value'])) ?>
                    </div>
                <?php elseif ($b['type'] === 'code'): ?>
                    <div class="glass-card" style="padding: 0; overflow: hidden; border: 1px solid var(--glass-border);">
                        <div style="padding: 10px 20px; background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;"><?= $snippet->lenguaje ?></span>
                            <button onclick="copyBlock(this)" class="btn btn-outline" style="font-size: 0.75rem; padding: 4px 12px;"><i class="far fa-copy"></i> Copiar</button>
                        </div>
                        <div style="background: #1e293b; padding: 20px; overflow-x: auto;">
                            <pre style="margin: 0;"><code class="snippet-code-block" style="font-family: 'Fira Code', monospace; color: #e2e8f0; line-height: 1.6; font-size: 0.9rem;"><?= htmlspecialchars($b['value']) ?></code></pre>
                        </div>
                    </div>
                <?php endif;
            endforeach;
        else:
            // Renderizado antiguo (Compatibilidad)
            ?>
            <div class="glass-card" style="padding: 0; overflow: hidden; border: 1px solid var(--glass-border);">
                <div style="padding: 10px 20px; background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;"><?= $snippet->lenguaje ?></span>
                    <button onclick="copyBlock(this)" class="btn btn-outline" style="font-size: 0.75rem; padding: 4px 12px;"><i class="far fa-copy"></i> Copiar</button>
                </div>
                <div style="background: #1e293b; padding: 20px; overflow-x: auto;">
                    <pre style="margin: 0;"><code class="snippet-code-block" style="font-family: 'Fira Code', monospace; color: #e2e8f0; line-height: 1.6; font-size: 0.9rem;"><?= htmlspecialchars($snippet->codigo) ?></code></pre>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Botón IA y Fork -->
    <div style="margin-top: 30px; text-align: center; display: flex; justify-content: center; gap: 15px;">
        <button onclick="askAI('<?= addslashes($snippet->titulo) ?>')" class="btn btn-primary" style="background: linear-gradient(45deg, var(--primary-color), var(--secondary-color)); padding: 14px 40px; font-weight: bold; border: none; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2);">
            <i class="fas fa-robot"></i> Analizar con Profe IA
        </button>
        
        <?php if (is_logged_in() && $_SESSION['user_id'] != $snippet->usuario_id): ?>
            <a href="<?= BASE_URL ?>views/create_snippet.php?fork_id=<?= $snippet->id ?>" class="btn btn-outline" style="padding: 14px 30px; font-weight: bold; border-color: var(--secondary-color); color: var(--secondary-color);">
                <i class="fas fa-code-branch"></i> Clonar y Mejorar
            </a>
        <?php endif; ?>
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
function copyBlock(btn) {
    const code = btn.parentElement.nextElementSibling.querySelector('code').innerText;
    navigator.clipboard.writeText(code).then(() => {
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copiado';
        btn.style.color = '#10b981';
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.style.color = '';
        }, 2000);
        
        // Métrica silenciosa para la tesis: se registra que este código fue reutilizado
        const formData = new FormData();
        formData.append('action', 'registrar_copia');
        formData.append('snippet_id', <?= $snippet->id ?>);
        fetch('<?= BASE_URL ?>api/metrics.php', { method: 'POST', body: formData });
    });
}

function askAI(titulo) {
    const modal = document.getElementById('aiModal');
    const content = document.getElementById('aiContent');
    
    // Obtener todo el código de todos los bloques para el prompt
    const codeBlocks = Array.from(document.querySelectorAll('.snippet-code-block')).map(el => el.innerText).join('\n\n');
    
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
    formData.append('codigo', codeBlocks);

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
                <div style="margin-top: 20px; padding-top: 15px; border-top: 1px dashed #cbd5e1; text-align: center;">
                    <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 10px;">¿Te resultó útil esta explicación para tu aprendizaje?</p>
                    <button onclick="evaluarIA(1, this)" class="btn btn-outline" style="border-color: #10b981; color: #10b981; padding: 5px 15px; margin: 0 5px;"><i class="fas fa-thumbs-up"></i> Sí, aprendí</button>
                    <button onclick="evaluarIA(0, this)" class="btn btn-outline" style="border-color: #ef4444; color: #ef4444; padding: 5px 15px; margin: 0 5px;"><i class="fas fa-thumbs-down"></i> Fue confuso</button>
                </div>
            `;
        }
    })
    .catch(err => {
        content.innerHTML = `<p style="color: red;">Error crítico al conectar con el servidor.</p>`;
        console.error(err);
    });
}

function evaluarIA(esUtil, btnElement) {
    const formData = new FormData();
    formData.append('action', 'evaluar_ia');
    formData.append('contexto', 'explicacion_codigo');
    formData.append('util', esUtil);

    fetch('<?= BASE_URL ?>api/metrics.php', { method: 'POST', body: formData })
    .then(() => {
        btnElement.parentElement.innerHTML = '<p style="color: #10b981; font-weight:bold;"><i class="fas fa-check"></i> ¡Gracias por tu reporte! Esto ayuda a tu aprendizaje.</p>';
    });
}

function deleteSnippetByModerator(id) {
    const motivo = prompt("¿Motivo de la eliminación de este fragmento? (Se registrará en el historial)");
    if (!motivo || motivo.length < 5) return;

    if (!confirm("¿Estás completamente seguro de eliminar este fragmento?")) return;

    const formData = new FormData();
    formData.append('tipo', 'snippet');
    formData.append('item_id', id);
    formData.append('motivo', motivo);

    fetch('<?= BASE_URL ?>api/moderator_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.href = '<?= BASE_URL ?>views/snippets.php';
        } else {
            alert(data.error);
        }
    })
    .catch(err => console.error('Error:', err));
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
            <div class="glass-card" style="margin-bottom: 30px; padding: 15px;">
                <form method="POST">
                    <div style="display: flex; gap: 15px; align-items: flex-start;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-user" style="color: #94a3b8;"></i>
                        </div>
                        <div style="flex: 1;">
                            <textarea name="comentario" class="form-control" rows="2" placeholder="Escribe un comentario o sugerencia..." required style="border: none; background: #f8fafc; border-radius: 12px; padding: 12px; resize: none; font-size: 0.9rem;"></textarea>
                            <div style="text-align: right; margin-top: 10px;">
                                <button type="submit" name="add_comment" class="btn btn-primary" style="font-size: 0.8rem; border-radius: 20px; padding: 8px 20px;">Publicar</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-secondary); margin-bottom: 30px;">
                <a href="<?= BASE_URL ?>views/login.php" style="color: var(--primary-color); font-weight: bold;">Inicia sesión</a> para comentar este código.
            </p>
        <?php endif; ?>

        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php if (empty($comentarios)): ?>
                <p style="text-align: center; color: var(--text-secondary); font-style: italic; padding: 20px;">Nadie ha comentado todavía. ¡Inicia la conversación!</p>
            <?php else: ?>
                <?php foreach ($comentarios as $c): ?>
                    <div style="display: flex; gap: 12px; padding: 12px; border-bottom: 1px solid rgba(0,0,0,0.03); background: rgba(255,255,255,0.4); border-radius: 12px;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.8rem; flex-shrink: 0;">
                            <?= strtoupper(substr($c->usuario, 0, 1)) ?>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <strong style="color: var(--text-primary); font-size: 0.85rem;">@<?= htmlspecialchars($c->usuario) ?></strong>
                                <span style="color: var(--text-secondary); font-size: 0.75rem;"><?= date('d M, H:i', strtotime($c->fecha_comentario)) ?></span>
                            </div>
                            <p style="font-size: 0.9rem; line-height: 1.5; color: #475569;"><?= nl2br(htmlspecialchars($c->contenido)) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
