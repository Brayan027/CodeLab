<?php
require_once __DIR__ . '/../includes/header.php';

// Obtener preguntas con info de usuarios y conteo de respuestas
$stmt = $pdo->query("
    SELECT p.*, u.usuario, 
    (SELECT COUNT(*) FROM foro_respuestas WHERE pregunta_id = p.id) as total_respuestas
    FROM foro_preguntas p 
    JOIN usuarios u ON p.usuario_id = u.id 
    ORDER BY p.fecha_creacion DESC
");
$preguntas = $stmt->fetchAll();
?>

<div class="animate-in" style="margin-top: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1>Foro de Discusión</h1>
        <?php if (is_logged_in()): ?>
            <a href="<?= BASE_URL ?>views/ask_question.php" class="btn btn-primary"><i class="fas fa-plus"></i> Hacer Pregunta</a>
        <?php endif; ?>
    </div>

    <div class="glass-card" style="margin-bottom: 30px;">
        <div style="display: flex; gap: 10px;">
            <input type="text" class="form-control" placeholder="Buscar preguntas (ej. ArrayOutOfBoundsException)..." style="flex: 1;">
            <button class="btn btn-outline">Buscar</button>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 20px;">
        <?php if (empty($preguntas)): ?>
            <div class="glass-card" style="text-align: center; padding: 60px;">
                <p style="color: var(--text-secondary);">Aún no hay preguntas. ¡Sé el primero en preguntar algo!</p>
            </div>
        <?php else: ?>
            <?php foreach ($preguntas as $p): ?>
                <div class="glass-card" style="display: flex; gap: 20px; align-items: center;">
                    <div style="text-align: center; min-width: 80px;">
                        <span style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);"><?= $p->total_respuestas ?></span>
                        <p style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase;">Respuestas</p>
                    </div>
                    <div style="flex: 1;">
                        <h3 style="margin-bottom: 8px;"><a href="<?= BASE_URL ?>views/forum_detail.php?id=<?= $p->id ?>" style="color: #fff; text-decoration: none;"><?= $p->titulo ?></a></h3>
                        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 10px;">
                            <?= substr(strip_tags($p->contenido), 0, 150) ?>...
                        </p>
                        <div style="display: flex; gap: 15px; font-size: 0.8rem; color: var(--text-secondary);">
                            <span><i class="far fa-user"></i> <?= $p->usuario ?></span>
                            <span><i class="far fa-clock"></i> <?= date('d M, Y', strtotime($p->fecha_creacion)) ?></span>
                            <?php if ($p->tags): ?>
                                <span><i class="fas fa-tag"></i> <?= $p->tags ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
