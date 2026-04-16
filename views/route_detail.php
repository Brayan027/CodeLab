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

// Obtener pasos
$stmt = $pdo->prepare("SELECT * FROM pasos_ruta WHERE ruta_id = ? ORDER BY orden ASC");
$stmt->execute([$ruta_id]);
$pasos = $stmt->fetchAll();
?>

<div class="animate-in" style="margin-top: 40px;">
    <!-- Encabezado de la Ruta -->
    <div class="glass-card" style="margin-bottom: 30px; border-left: 5px solid var(--primary-color);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="font-size: 2.2rem;"><?= $ruta->titulo ?></h1>
                <p style="color: var(--text-secondary); margin-top: 5px;">Por <strong><?= $ruta->nombre_completo ?></strong> (@<?= $ruta->usuario ?>)</p>
            </div>
            <?php if (is_logged_in() && $_SESSION['user_id'] == $ruta->creador_id): ?>
                <a href="<?= BASE_URL ?>views/edit_route.php?id=<?= $ruta_id ?>" class="btn btn-outline"><i class="fas fa-edit"></i> Editar</a>
            <?php endif; ?>
        </div>
        <p style="margin-top: 20px; font-size: 1.1rem;"><?= nl2br($ruta->descripcion) ?></p>
    </div>

    <!-- Lista de Pasos -->
    <div style="display: flex; flex-direction: column; gap: 40px; margin-bottom: 60px;">
        <?php foreach ($pasos as $index => $p): ?>
            <div class="glass-card animate-in" style="animation-delay: <?= $index * 0.1 ?>s; overflow: hidden;">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                    <span style="background: var(--secondary-color); color: #fff; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 0.8rem;">PASO <?= $p->orden ?></span>
                    <h3 style="margin: 0;"><?= $p->titulo ?></h3>
                </div>
                
                <div style="margin-bottom: 25px; line-height: 1.8;">
                    <?= nl2br($p->contenido) ?>
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
                    <button class="btn btn-outline" style="font-size: 0.8rem;"><i class="far fa-thumbs-up"></i> Útil</button>
                    <button class="btn btn-outline" style="font-size: 0.8rem;"><i class="far fa-comment"></i> Comentar</button>
                    <button onclick="askAI(<?= $p->id ?>)" class="btn btn-primary" style="font-size: 0.8rem; background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));">
                        <i class="fas fa-robot"></i> Explicar con IA
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

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
        content.innerHTML = data.explanation || 'Hubo un error al obtener la explicación.';
    })
    .catch(() => {
        content.innerHTML = 'Error de conexión. Intenta de nuevo.';
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
