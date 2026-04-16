<?php
require_once __DIR__ . '/../includes/header.php';

// Obtener todas las rutas públicas
$stmt = $pdo->query("
    SELECT r.*, u.usuario, u.nombre_completo,
    (SELECT COUNT(*) FROM pasos_ruta WHERE ruta_id = r.id) as total_pasos
    FROM rutas r 
    JOIN usuarios u ON r.creador_id = u.id 
    WHERE r.privacidad = 'publico'
    ORDER BY r.fecha_creacion DESC
");
$rutas = $stmt->fetchAll();
?>

<div class="animate-in" style="margin-top: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1>Explorar Rutas de Aprendizaje</h1>
        <?php if (is_logged_in()): ?>
            <a href="<?= BASE_URL ?>views/create_route.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Ruta</a>
        <?php endif; ?>
    </div>

    <?php if (empty($rutas)): ?>
        <div class="glass-card" style="text-align: center; padding: 60px;">
            <i class="fas fa-map-signs" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 20px;"></i>
            <p style="color: var(--text-secondary);">Aún no hay rutas públicas. ¡Comienza subiendo tu conocimiento sobre Java!</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px;">
            <?php foreach ($rutas as $r): ?>
                <div class="glass-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <span style="background: rgba(88, 166, 255, 0.1); color: var(--primary-color); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">
                            <?= $r->total_pasos ?> Pasos
                        </span>
                    </div>
                    <h3 style="margin-bottom: 10px;"><?= $r->titulo ?></h3>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px;">
                        <?= substr($r->descripcion, 0, 140) ?>...
                    </p>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--glass-border); padding-top: 15px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($r->nombre_completo) ?>&size=30" style="border-radius: 50%;">
                            <span style="font-size: 0.85rem; font-weight: 500;">@<?= $r->usuario ?></span>
                        </div>
                        <a href="<?= BASE_URL ?>views/route_detail.php?id=<?= $r->id ?>" class="btn btn-outline" style="font-size: 0.8rem;">Ver Ruta</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
