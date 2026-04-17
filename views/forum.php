<?php
require_once __DIR__ . '/../includes/header.php';

// Filtros
$filter = $_GET['view'] ?? 'recent';
$search_query = $_GET['search'] ?? '';

$sql = "SELECT DISTINCT p.*, u.usuario, u.rol,
        (SELECT COUNT(*) FROM foro_respuestas WHERE pregunta_id = p.id) as total_respuestas,
        (SELECT COUNT(*) FROM foro_vistas WHERE pregunta_id = p.id) as total_vistas,
        (SELECT COUNT(*) FROM foro_votos WHERE pregunta_id = p.id) as total_votos,
        (SELECT COUNT(*) FROM foro_respuestas WHERE pregunta_id = p.id AND es_solucion = 1) as tiene_solucion
        FROM foro_preguntas p 
        JOIN usuarios u ON p.usuario_id = u.id 
        LEFT JOIN foro_respuestas r ON p.id = r.pregunta_id
        WHERE 1=1";

$params = [];
if ($search_query) {
    $sql .= " AND (p.titulo LIKE ? OR p.contenido LIKE ? OR p.tags LIKE ? OR r.contenido LIKE ?)";
    $term = "%$search_query%";
    $params = [$term, $term, $term, $term];
}

if ($filter == 'unanswered') {
    $sql .= " HAVING total_respuestas = 0";
} elseif ($filter == 'popular') {
    $sql .= " ORDER BY total_votos DESC, p.fecha_creacion DESC";
} else {
    $sql .= " ORDER BY p.fecha_creacion DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$preguntas = $stmt->fetchAll();
?>

<div class="animate-in" style="margin-top: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1>Foro de Discusión</h1>
        <?php if (is_logged_in()): ?>
            <a href="<?= BASE_URL ?>views/ask_question.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Hacer Pregunta
            </a>
        <?php endif; ?>
    </div>

    <!-- Buscador y Filtros -->
    <div class="glass-card" style="margin-bottom: 30px; padding: 20px;">
        <form action="" method="GET" style="display: flex; gap: 15px; margin-bottom: 20px;">
            <div style="position: relative; flex: 1;">
                <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                <input type="text" name="search" class="form-control" placeholder="Buscar en preguntas, respuestas o etiquetas..." value="<?= htmlspecialchars($search_query) ?>" style="padding-left: 45px; border-radius: 30px;">
            </div>
            <button type="submit" class="btn btn-primary" style="border-radius: 30px; padding: 0 30px;">Buscar</button>
            <?php if ($search_query): ?>
                <a href="forum.php" class="btn btn-outline" style="border-radius: 30px;">Limpiar</a>
            <?php endif; ?>
        </form>

        <div style="display: flex; gap: 20px;">
            <a href="forum.php?view=recent" style="text-decoration: none; font-size: 0.9rem; color: <?= $filter == 'recent' ? 'var(--primary-color)' : 'var(--text-secondary)' ?>; font-weight: 600; border-bottom: 2px solid <?= $filter == 'recent' ? 'var(--primary-color)' : 'transparent' ?>; padding-bottom: 5px;">Recientes</a>
            <a href="forum.php?view=popular" style="text-decoration: none; font-size: 0.9rem; color: <?= $filter == 'popular' ? 'var(--primary-color)' : 'var(--text-secondary)' ?>; font-weight: 600; border-bottom: 2px solid <?= $filter == 'popular' ? 'var(--primary-color)' : 'transparent' ?>; padding-bottom: 5px;">Más Votadas</a>
            <a href="forum.php?view=unanswered" style="text-decoration: none; font-size: 0.9rem; color: <?= $filter == 'unanswered' ? 'var(--primary-color)' : 'var(--text-secondary)' ?>; font-weight: 600; border-bottom: 2px solid <?= $filter == 'unanswered' ? 'var(--primary-color)' : 'transparent' ?>; padding-bottom: 5px;">Sin Respuesta</a>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 20px; margin-bottom: 50px;">
        <?php if (empty($preguntas)): ?>
            <div class="glass-card" style="text-align: center; padding: 60px;">
                <i class="far fa-comments" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 20px;"></i>
                <p style="color: var(--text-secondary);">No se encontraron preguntas que coincidan con tu búsqueda.</p>
            </div>
        <?php else: ?>
            <?php foreach ($preguntas as $p): ?>
                <div class="glass-card forum-card" style="display: grid; grid-template-columns: 100px 1fr; gap: 25px; align-items: center; transition: all 0.3s; cursor: pointer;" onclick="window.location.href='<?= BASE_URL ?>views/forum_detail.php?id=<?= $p->id ?>'">
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 10px; border-right: 1px solid var(--glass-border); padding-right: 10px;">
                        <div style="text-align: center;">
                            <span style="display: block; font-size: 1.1rem; font-weight: 800; color: var(--text-primary);"><?= $p->total_votos ?></span>
                            <span style="font-size: 0.6rem; text-transform: uppercase; color: var(--text-secondary);">Votos</span>
                        </div>
                        <div style="text-align: center; margin: 5px 0;">
                            <span style="display: block; font-size: 0.85rem; color: var(--text-secondary);"><?= $p->total_vistas ?></span>
                            <span style="font-size: 0.55rem; text-transform: uppercase; color: var(--text-secondary);">Vistas</span>
                        </div>
                        <div style="text-align: center; width: 100%; <?= $p->tiene_solucion ? 'background: rgba(16, 185, 129, 0.1); color: #059669; padding: 8px 0; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.2);' : 'color: var(--text-secondary);' ?>">
                            <span style="display: block; font-size: 1.1rem; font-weight: 800;"><?= $p->total_respuestas ?></span>
                            <span style="font-size: 0.65rem; text-transform: uppercase;"><?= $p->tiene_solucion ? 'Resuelta' : 'Respuestas' ?></span>
                        </div>
                    </div>

                    <div>
                        <h3 style="margin-bottom: 10px;">
                            <a href="<?= BASE_URL ?>views/forum_detail.php?id=<?= $p->id ?>" style="text-decoration: none; color: var(--text-primary);">
                                <?= htmlspecialchars($p->titulo) ?>
                            </a>
                            <?php if ($p->tiene_solucion): ?>
                                <i class="fas fa-check-circle" style="color: #10b981; font-size: 1rem; margin-left: 5px;"></i>
                            <?php endif; ?>
                        </h3>
                        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 15px; line-height: 1.6;">
                            <?= substr(strip_tags($p->contenido), 0, 180) ?>...
                        </p>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <?php 
                                $tags = explode(',', $p->tags);
                                foreach ($tags as $tag): if(trim($tag)): ?>
                                    <span style="background: #f1f5f9; color: #475569; padding: 2px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 600;">#<?= trim($tag) ?></span>
                                <?php endif; endforeach; ?>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem;">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($p->usuario) ?>&size=24&background=random" style="border-radius: 50%;">
                                <span>
                                    <strong style="color: var(--primary-color);">@<?= $p->usuario ?></strong>
                                    <?php if ($p->rol == 'docente'): ?>
                                        <span style="background: #f59e0b; color: white; padding: 1px 6px; border-radius: 4px; font-size: 0.6rem; font-weight: 800; margin-left: 3px;">DOCENTE</span>
                                    <?php endif; ?>
                                </span>
                                <span style="color: var(--text-secondary);">· <?= date('d M', strtotime($p->fecha_creacion)) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.forum-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    border-color: var(--primary-color);
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
