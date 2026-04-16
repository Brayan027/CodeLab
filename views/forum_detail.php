<?php
require_once __DIR__ . '/../includes/header.php';

$pregunta_id = $_GET['id'] ?? null;
if (!$pregunta_id) redirect('views/forum.php');

// Obtener la pregunta
$stmt = $pdo->prepare("SELECT p.*, u.usuario FROM foro_preguntas p JOIN usuarios u ON p.usuario_id = u.id WHERE p.id = ?");
$stmt->execute([$pregunta_id]);
$pregunta = $stmt->fetch();

if (!$pregunta) die("Pregunta no encontrada.");

// Manejar nueva respuesta
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['answer'])) {
    if (!is_logged_in()) redirect('views/login.php');
    
    $contenido = sanitize($_POST['contenido']);
    if (!empty($contenido)) {
        $stmt = $pdo->prepare("INSERT INTO foro_respuestas (pregunta_id, usuario_id, contenido) VALUES (?, ?, ?)");
        $stmt->execute([$pregunta_id, $_SESSION['user_id'], $contenido]);
    }
}

// Obtener respuestas
$stmt = $pdo->prepare("SELECT r.*, u.usuario FROM foro_respuestas r JOIN usuarios u ON r.usuario_id = u.id WHERE r.pregunta_id = ? ORDER BY r.fecha_respuesta ASC");
$stmt->execute([$pregunta_id]);
$respuestas = $stmt->fetchAll();
?>

<div class="animate-in" style="margin-top: 40px;">
    <!-- Pregunta Principal -->
    <div class="glass-card" style="margin-bottom: 30px; background: rgba(88, 166, 255, 0.05);">
        <h1><?= $pregunta->titulo ?></h1>
        <div style="margin: 15px 0; color: var(--text-secondary); font-size: 0.9rem;">
            Publicado por <strong><?= $pregunta->usuario ?></strong> el <?= date('d M, Y', strtotime($pregunta->fecha_creacion)) ?>
        </div>
        <div style="font-size: 1.1rem; line-height: 1.6; margin-top: 20px;">
            <?= nl2br($pregunta->contenido) ?>
        </div>
        <div style="margin-top: 20px;">
            <span class="btn btn-outline" style="font-size: 0.8rem; pointer-events: none;">#<?= $pregunta->tags ?></span>
        </div>
    </div>

    <!-- Respuestas -->
    <h2 style="margin-bottom: 20px;">Respuestas (<?= count($respuestas) ?>)</h2>
    <div style="display: flex; flex-direction: column; gap: 20px; margin-bottom: 40px;">
        <?php foreach ($respuestas as $r): ?>
            <div class="glass-card" style="border-left: 3px solid <?= $r->es_solucion ? 'var(--accent-color)' : 'var(--glass-border)' ?>;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <div style="font-weight: bold; color: var(--primary-color);">@<?= $r->usuario ?></div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= date('d M, Y H:i', strtotime($r->fecha_respuesta)) ?></div>
                </div>
                <div style="line-height: 1.6;">
                    <?= nl2br($r->contenido) ?>
                </div>
                <?php if ($r->es_solucion): ?>
                    <div style="margin-top: 15px; color: var(--accent-color); font-weight: bold; font-size: 0.9rem;">
                        <i class="fas fa-check-circle"></i> Solución Aceptada
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Formulario de Respuesta -->
    <?php if (is_logged_in()): ?>
        <div class="glass-card">
            <h3 style="margin-bottom: 20px;">Tu Respuesta</h3>
            <form action="" method="POST">
                <textarea name="contenido" class="form-control" rows="6" placeholder="Escribe tu respuesta técnica aquí..." required></textarea>
                <button type="submit" name="answer" class="btn btn-primary" style="margin-top: 20px; width: 100%;">Publicar Respuesta</button>
            </form>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: var(--text-secondary);">Debes <a href="<?= BASE_URL ?>views/login.php" style="color: var(--primary-color);">iniciar sesión</a> para participar en la discusión.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
