<?php
require_once __DIR__ . '/../includes/header.php';

$ruta_id = $_GET['id'] ?? null;
if (!$ruta_id) redirect('views/learning_routes.php');

// Obtener detalles de la ruta y creador
$stmt = $pdo->prepare("SELECT r.*, u.usuario, u.nombre_completo FROM rutas r JOIN usuarios u ON r.creador_id = u.id WHERE r.id = ?");
$stmt->execute([$ruta_id]);
$ruta = $stmt->fetch();

if (!$ruta) die("Ruta no encontrada.");

// Privacidad
if ($ruta->privacidad == 'privado' && (!is_logged_in() || $_SESSION['user_id'] != $ruta->creador_id)) {
    die("Esta ruta es privada.");
}

// Obtener pasos con conteo de votos y comentarios
$stmt = $pdo->prepare("SELECT p.*, 
    (SELECT COUNT(*) FROM ruta_paso_votos WHERE paso_id = p.id) as votos,
    (SELECT COUNT(*) FROM ruta_paso_comentarios WHERE paso_id = p.id) as num_comentarios
    FROM pasos_ruta p WHERE p.ruta_id = ? ORDER BY p.orden ASC");
$stmt->execute([$ruta_id]);
$pasos = $stmt->fetchAll();

// Manejar Voto (Like) vía POST
if (isset($_POST['toggle_voto'])) {
    $paso_id_voto = (int)$_POST['paso_id'];
    $user_id = $_SESSION['user_id'] ?? 0;
    if ($user_id > 0) {
        $stmt = $pdo->prepare("SELECT id FROM ruta_paso_votos WHERE paso_id = ? AND usuario_id = ?");
        $stmt->execute([$paso_id_voto, $user_id]);
        if ($stmt->fetch()) {
            $pdo->prepare("DELETE FROM ruta_paso_votos WHERE paso_id = ? AND usuario_id = ?")->execute([$paso_id_voto, $user_id]);
        } else {
            $pdo->prepare("INSERT INTO ruta_paso_votos (paso_id, usuario_id) VALUES (?, ?)")->execute([$paso_id_voto, $user_id]);
        }
        header("Location: " . $_SERVER['REQUEST_URI'] . "#paso-" . $paso_id_voto);
        exit;
    }
}

// Manejar Comentario vía POST
if (isset($_POST['post_comentario'])) {
    $paso_id_com = (int)$_POST['paso_id'];
    $contenido = sanitize($_POST['contenido']);
    $user_id = $_SESSION['user_id'] ?? 0;
    if ($user_id > 0 && !empty($contenido)) {
        $stmt = $pdo->prepare("INSERT INTO ruta_paso_comentarios (paso_id, usuario_id, contenido) VALUES (?, ?, ?)");
        $stmt->execute([$paso_id_com, $user_id, $contenido]);
        header("Location: " . $_SERVER['REQUEST_URI'] . "#paso-" . $paso_id_com);
        exit;
    }
}
?>

<div class="animate-in" style="margin-top: 40px; display: grid; grid-template-columns: 320px 1fr; gap: 30px; align-items: start;">
    
    <!-- Sidebar Izquierdo (sticky) -->
    <aside style="position: sticky; top: 100px; height: calc(100vh - 120px); overflow-y: auto; padding-right: 10px;">
        <!-- Card de Información de la Ruta -->
        <div class="glass-card" style="margin-bottom: 20px; border-left: 5px solid var(--primary-color);">
            <h1 style="font-size: 1.5rem; margin-bottom: 10px;"><?= htmlspecialchars($ruta->titulo) ?></h1>
            <p style="font-size: 0.85rem; color: var(--text-secondary);">Por <strong><?= htmlspecialchars($ruta->nombre_completo) ?></strong></p>
            <?php if (is_logged_in() && $_SESSION['user_id'] == $ruta->creador_id): ?>
                <a href="<?= BASE_URL ?>views/edit_route.php?id=<?= $ruta_id ?>" class="btn btn-outline" style="font-size: 0.75rem; margin-top: 15px; width: 100%; display: block; text-align: center;">
                    <i class="fas fa-edit"></i> Editar Ruta
                </a>
            <?php endif; ?>
        </div>

        <div class="glass-card" style="padding: 15px;">
            <h4 style="margin-bottom: 15px; font-size: 0.9rem; text-transform: uppercase; color: var(--text-secondary); letter-spacing: 1px;">Contenido de la Ruta</h4>
            <nav style="display: flex; flex-direction: column; gap: 8px;">
                <?php foreach ($pasos as $p): ?>
                    <a href="#paso-<?= $p->id ?>" class="nav-step-link">
                        <span class="step-num"><?= $p->orden ?></span>
                        <span class="step-txt"><?= htmlspecialchars($p->titulo) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </aside>

    <!-- Contenido Principal -->
    <main style="display: flex; flex-direction: column; gap: 30px;">
        <div class="glass-card">
             <p style="font-size: 1.1rem; line-height: 1.7;"><?= nl2br(htmlspecialchars($ruta->descripcion)) ?></p>
        </div>

        <!-- Lista de Pasos -->
        <div style="display: flex; flex-direction: column; gap: 40px; margin-bottom: 60px;">
            <?php foreach ($pasos as $index => $p): ?>
                <div id="paso-<?= $p->id ?>" class="glass-card animate-in" style="animation-delay: <?= $index * 0.1 ?>s; overflow: hidden; scroll-margin-top: 100px;">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
                        <span style="background: var(--secondary-color); color: #fff; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 0.8rem;">PASO <?= $p->orden ?></span>
                        <h2 style="margin: 0; font-size: 1.6rem;"><?= htmlspecialchars($p->titulo) ?></h2>
                    </div>
                    
                    <div style="margin-bottom: 25px; line-height: 1.8; font-size: 1.1rem;">
                        <?= nl2br(htmlspecialchars($p->contenido)) ?>
                    </div>

                <?php if ($p->codigo_snippet): ?>
                    <div style="position: relative; margin-top: 20px;">
                        <div style="background: #1e293b; padding: 20px; border-radius: 12px; overflow-x: auto; overflow-y: auto; max-height: 350px; border: 1px solid rgba(0,0,0,0.1);">
                            <pre style="margin: 0; white-space: pre; overflow-x: auto;"><code style="font-family: 'Fira Code', monospace; color: #e2e8f0; font-size: 0.85rem; line-height: 1.6;"><?= htmlspecialchars($p->codigo_snippet) ?></code></pre>
                        </div>
                        <button onclick="copyCode(this)" style="position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.1); border: none; color: #fff; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 0.8rem;">
                            <i class="far fa-copy"></i> Copiar
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Acciones Rápidas -->
                <div style="margin-top: 30px; display: flex; gap: 20px; border-top: 1px solid var(--glass-border); padding-top: 20px;">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="paso_id" value="<?= $p->id ?>">
                        <button type="submit" name="toggle_voto" class="btn btn-outline" style="font-size: 0.8rem; color: var(--primary-color);">
                            <i class="fas fa-thumbs-up"></i> Útil (<?= $p->votos ?>)
                        </button>
                    </form>
                    
                    <button onclick="toggleComments(<?= $p->id ?>)" class="btn btn-outline" style="font-size: 0.8rem;">
                        <i class="far fa-comment"></i> Comentar (<?= $p->num_comentarios ?>)
                    </button>
                    
                    <button onclick="askAI(<?= $p->id ?>)" class="btn btn-primary" style="font-size: 0.8rem; background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));">
                        <i class="fas fa-robot"></i> Explicar con IA
                    </button>
                </div>

                <!-- Sección de Comentarios Desplegable -->
                <div id="comments-<?= $p->id ?>" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 1px dashed var(--glass-border);">
                    <form method="POST" style="margin-bottom: 20px;">
                        <input type="hidden" name="paso_id" value="<?= $p->id ?>">
                        <textarea name="contenido" class="form-control" rows="2" placeholder="Comparte tu duda o aporte..." required style="font-size: 0.9rem;"></textarea>
                        <div style="text-align: right; margin-top: 10px;">
                            <button type="submit" name="post_comentario" class="btn btn-primary" style="font-size: 0.75rem;">Enviar</button>
                        </div>
                    </form>
                    
                    <?php
                    $stmt_com = $pdo->prepare("SELECT c.*, u.usuario FROM ruta_paso_comentarios c JOIN usuarios u ON c.usuario_id = u.id WHERE c.paso_id = ? ORDER BY c.fecha_comentario DESC");
                    $stmt_com->execute([$p->id]);
                    $lista_coms = $stmt_com->fetchAll();
                    ?>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach($lista_coms as $lc): ?>
                            <div style="font-size: 0.85rem; background: rgba(0,0,0,0.02); padding: 10px; border-radius: 8px;">
                                <strong>@<?= $lc->usuario ?>:</strong> <?= htmlspecialchars($lc->contenido) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    </main>
</div>

<style>
.nav-step-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    text-decoration: none;
    color: var(--text-primary);
    border-radius: 10px;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    margin-bottom: 5px;
}

.nav-step-link:hover {
    background: rgba(59, 130, 246, 0.05);
    border-color: rgba(59, 130, 246, 0.2);
    transform: translateX(5px);
}

.nav-step-link .step-num {
    background: #f1f5f9;
    color: var(--text-secondary);
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 0.75rem;
    font-weight: bold;
    flex-shrink: 0;
}

.nav-step-link:hover .step-num {
    background: var(--primary-color);
    color: #fff;
}

.nav-step-link .step-txt {
    font-size: 0.9rem;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Efecto suave de scroll */
html {
    scroll-behavior: smooth;
}

/* Scrollbar personalizado para el sidebar */
aside::-webkit-scrollbar {
    width: 4px;
}
aside::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>

<!-- Modal IA -->
<div id="aiModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px); z-index:1000; align-items:center; justify-content:center;">
    <div class="glass-card" style="max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; background: #fff;">
        <h3><i class="fas fa-brain" style="color: var(--secondary-color);"></i> Análisis de CodeLab AI</h3>
        <div id="aiContent" style="margin-top: 20px; line-height: 1.6;">
            Cargando análisis...
        </div>
        <button onclick="document.getElementById('aiModal').style.display='none'; document.body.style.overflow='';" class="btn btn-primary" style="margin-top: 20px; width: 100%;">Cerrar</button>
    </div>
</div>

<!-- Almacenar datos de pasos en JS de forma segura -->
<script>
const stepsData = {};
<?php foreach ($pasos as $p): ?>
stepsData[<?= $p->id ?>] = {
    titulo: <?= json_encode($p->titulo, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    codigo: <?= json_encode($p->codigo_snippet ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
};
<?php endforeach; ?>

function copyCode(btn) {
    const code = btn.previousElementSibling.innerText;
    navigator.clipboard.writeText(code);
    btn.innerHTML = '<i class="fas fa-check"></i> Copiado';
    setTimeout(() => { btn.innerHTML = '<i class="far fa-copy"></i> Copiar'; }, 2000);
}

function askAI(stepId) {
    const step = stepsData[stepId];
    if (!step) return;

    const modal = document.getElementById('aiModal');
    const content = document.getElementById('aiContent');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    content.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Gemini está analizando tu código...</p>';
    
    fetch('<?= BASE_URL ?>api/ai_assist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=explain&titulo=${encodeURIComponent(step.titulo)}&codigo=${encodeURIComponent(step.codigo)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            content.innerHTML = `<div style="padding: 15px; background: #fef2f2; border-radius: 10px; border: 1px solid #fee2e2; color: #dc2626; font-size: 0.9rem;">
                <p><strong><i class="fas fa-exclamation-triangle"></i> Error de la IA:</strong> ${data.error}</p>
                <p style="margin-top: 5px; font-size: 0.8rem; opacity: 0.8;">API Message: ${data.api_message || 'N/A'}</p>
            </div>`;
        } else {
            content.innerHTML = `<div class="ai-response-animate">${data.explanation}</div>`;
        }
    })
    .catch((err) => {
        content.innerHTML = `<p style="color: red;">Error crítico de conexión: ${err.message}</p>`;
    });
}

// Cerrar modal con Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.getElementById('aiModal').style.display = 'none';
        document.body.style.overflow = '';
    }
});
document.getElementById('aiModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
        document.body.style.overflow = '';
    }
});

// Resaltar paso activo en el sidebar al hacer scroll
const observerOptions = {
    root: null,
    threshold: 0.5,
    rootMargin: "-100px 0px -50% 0px"
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            // Quitar clase active de todos
            document.querySelectorAll('.nav-step-link').forEach(link => {
                link.style.background = '';
                link.style.borderColor = 'transparent';
                link.querySelector('.step-num').style.background = '#f1f5f9';
                link.querySelector('.step-num').style.color = 'var(--text-secondary)';
            });

            // Añadir al activo
            const id = entry.target.getAttribute('id');
            const activeLink = document.querySelector(`.nav-step-link[href="#${id}"]`);
            if (activeLink) {
                activeLink.style.background = 'rgba(59, 130, 246, 0.1)';
                activeLink.style.borderColor = 'rgba(59, 130, 246, 0.3)';
                const activeNum = activeLink.querySelector('.step-num');
                activeNum.style.background = 'var(--primary-color)';
                activeNum.style.color = '#fff';
            }
        }
    });
}, observerOptions);

document.querySelectorAll('.glass-card[id^="paso-"]').forEach(step => {
    observer.observe(step);
});

function toggleComments(pasoId) {
    const el = document.getElementById(`comments-${pasoId}`);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
