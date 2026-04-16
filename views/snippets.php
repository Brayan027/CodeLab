<?php
require_once __DIR__ . '/../includes/header.php';

$lenguaje_filter = $_GET['lang'] ?? '';
$query = "SELECT s.*, u.usuario FROM snippets s JOIN usuarios u ON s.usuario_id = u.id WHERE s.privacidad = 'publico'";
$params = [];
if ($lenguaje_filter) {
    $query .= " AND s.lenguaje = ?";
    $params[] = $lenguaje_filter;
}
$query .= " ORDER BY s.fecha_creacion DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$snippets = $stmt->fetchAll();

$langs = $pdo->query("SELECT DISTINCT lenguaje FROM snippets WHERE privacidad='publico'")->fetchAll();
?>

<div class="animate-in" style="margin-top: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1><i class="fas fa-code" style="color: var(--primary-color);"></i> Repositorio de Código</h1>
        <?php if (is_logged_in()): ?>
            <a href="<?= BASE_URL ?>views/create_snippet.php" class="btn btn-primary"><i class="fas fa-plus"></i> Subir Código</a>
        <?php endif; ?>
    </div>

    <!-- Filtros -->
    <div class="glass-card" style="margin-bottom: 30px;">
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="<?= BASE_URL ?>views/snippets.php" class="btn <?= !$lenguaje_filter ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 0.85rem;">Todos</a>
            <?php foreach ($langs as $l): ?>
                <a href="<?= BASE_URL ?>views/snippets.php?lang=<?= urlencode($l->lenguaje) ?>" class="btn <?= $lenguaje_filter == $l->lenguaje ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 0.85rem;"><?= $l->lenguaje ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($snippets)): ?>
        <div class="glass-card" style="text-align: center; padding: 60px;">
            <i class="fas fa-laptop-code" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 20px;"></i>
            <p style="color: var(--text-secondary);">Aún no hay fragmentos de código. ¡Sé el primero en compartir!</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 25px;">
            <?php foreach ($snippets as $s): ?>
                <div class="glass-card" style="display: flex; flex-direction: column;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span style="background: rgba(59,130,246,0.1); color: var(--primary-color); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;"><?= $s->lenguaje ?></span>
                        <span style="font-size: 0.75rem; color: var(--text-secondary);"><?= date('d M Y', strtotime($s->fecha_creacion)) ?></span>
                    </div>
                    <h3 style="margin-bottom: 8px;"><?= $s->titulo ?></h3>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 15px; flex: 1;"><?= substr($s->descripcion, 0, 100) ?>...</p>
                    <div style="background: #1e293b; padding: 12px; border-radius: 8px; margin-bottom: 15px; max-height: 120px; overflow: hidden;">
                        <pre style="margin: 0;"><code style="font-family: monospace; color: #e2e8f0; font-size: 0.8rem;"><?= htmlspecialchars(substr($s->codigo, 0, 200)) ?></code></pre>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.85rem;">@<?= $s->usuario ?></span>
                        <a href="<?= BASE_URL ?>views/snippet_detail.php?id=<?= $s->id ?>" class="btn btn-outline" style="font-size: 0.8rem;">Ver Completo</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
