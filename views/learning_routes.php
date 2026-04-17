<?php
require_once __DIR__ . '/../includes/header.php';

// Lógica de búsqueda y filtros
$search_query = $_GET['search'] ?? '';
$filter_mine = isset($_GET['mine']) && $_GET['mine'] == '1';
$filter_favs = isset($_GET['view']) && $_GET['view'] == 'favs';
$user_id = $_SESSION['user_id'] ?? 0;
$params = [];

$sql = "SELECT DISTINCT r.*, u.usuario, u.nombre_completo, u.rol,
        (SELECT COUNT(*) FROM pasos_ruta WHERE ruta_id = r.id) as total_pasos,
        (SELECT COUNT(*) FROM ruta_paso_votos v JOIN pasos_ruta p ON v.paso_id = p.id WHERE p.ruta_id = r.id) as total_votos,
        (SELECT COUNT(*) FROM ruta_paso_comentarios rpc JOIN pasos_ruta pr ON rpc.paso_id = pr.id WHERE pr.ruta_id = r.id) as total_comentarios,
        (SELECT COUNT(*) FROM rutas_favoritas WHERE ruta_id = r.id AND usuario_id = ?) as is_fav
        FROM rutas r 
        JOIN usuarios u ON r.creador_id = u.id 
        LEFT JOIN rutas_favoritas rf ON r.id = rf.ruta_id
        WHERE 1=1";

$params[] = $user_id;

if ($filter_mine) {
    $sql .= " AND r.creador_id = ?";
    $params[] = $user_id;
} elseif ($filter_favs) {
    $sql .= " AND rf.usuario_id = ?";
    $params[] = $user_id;
} else {
    $sql .= " AND (r.privacidad = 'publico' OR r.creador_id = ?)";
    $params[] = $user_id;
}

if ($search_query) {
    $sql .= " AND (r.titulo LIKE ? OR r.descripcion LIKE ?)";
    $term = "%$search_query%";
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY r.fecha_creacion DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rutas = $stmt->fetchAll();
?>

<div class="animate-in" style="margin-top: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 20px;">
        <h1>Rutas de Aprendizaje</h1>
        <div style="display: flex; gap: 10px; flex-wrap: wrap; flex: 1; justify-content: flex-end;">
            <form action="" method="GET" style="display: flex; gap: 10px; max-width: 350px; width: 100%;">
                <div style="position: relative; width: 100%;">
                    <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                    <input type="text" name="search" class="form-control" placeholder="Buscar rutas..." value="<?= htmlspecialchars($search_query) ?>" style="padding-left: 40px; border-radius: 30px;">
                </div>
            </form>
            
            <?php if (is_logged_in()): ?>
                <a href="<?= BASE_URL ?>views/ai_route_generator.php" class="btn btn-outline" style="border-radius: 30px; font-size: 0.85rem;">
                    <i class="fas fa-magic"></i> IA
                </a>
                <a href="<?= BASE_URL ?>views/create_route.php" class="btn btn-primary" style="border-radius: 30px; font-size: 0.85rem;"><i class="fas fa-plus"></i> Crear</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pestañas de Filtro -->
    <div style="display: flex; gap: 15px; margin-bottom: 30px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">
        <a href="learning_routes.php" style="text-decoration: none; color: <?= !$filter_mine && !$filter_favs ? 'var(--primary-color)' : 'var(--text-secondary)' ?>; font-weight: 600; font-size: 0.9rem; border-bottom: 2px solid <?= !$filter_mine && !$filter_favs ? 'var(--primary-color)' : 'transparent' ?>; padding-bottom: 10px;">Todas las Rutas</a>
        <?php if (is_logged_in()): ?>
            <a href="?mine=1" style="text-decoration: none; color: <?= $filter_mine ? 'var(--primary-color)' : 'var(--text-secondary)' ?>; font-weight: 600; font-size: 0.9rem; border-bottom: 2px solid <?= $filter_mine ? 'var(--primary-color)' : 'transparent' ?>; padding-bottom: 10px;">Mis Rutas</a>
            <a href="?view=favs" style="text-decoration: none; color: <?= $filter_favs ? 'var(--primary-color)' : 'var(--text-secondary)' ?>; font-weight: 600; font-size: 0.9rem; border-bottom: 2px solid <?= $filter_favs ? 'var(--primary-color)' : 'transparent' ?>; padding-bottom: 10px;">Guardadas</a>
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
                    <div style="display: flex; gap: 15px; color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 20px;">
                        <span title="Pasos"><i class="fas fa-layer-group"></i> <?= $r->total_pasos ?></span>
                        <span title="Útil"><i class="far fa-thumbs-up"></i> <?= $r->total_votos ?></span>
                        <span title="Comentarios"><i class="far fa-comment"></i> <?= $r->total_comentarios ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                        <h3 style="margin: 0; flex: 1;"><?= $r->titulo ?></h3>
                        <?php if (is_logged_in()): ?>
                            <button onclick="toggleFav(<?= $r->id ?>, this)" style="background: none; border: none; cursor: pointer; color: <?= $r->is_fav ? '#ef4444' : '#cbd5e1' ?>; font-size: 1.2rem; transition: all 0.2s;">
                                <i class="<?= $r->is_fav ? 'fas' : 'far' ?> fa-heart"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px;">
                        <?= substr($r->descripcion, 0, 140) ?>...
                    </p>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--glass-border); padding-top: 15px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($r->nombre_completo) ?>&size=30" style="border-radius: 50%;">
                            <span style="font-size: 0.85rem; font-weight: 500;">
                                @<?= $r->usuario ?>
                                <?php if ($r->rol == 'docente'): ?>
                                    <i class="fas fa-certificate" style="color: #f59e0b; margin-left: 3px;" title="Docente Mentor"></i>
                                <?php endif; ?>
                            </span>
                        </div>
                        <a href="<?= BASE_URL ?>views/route_detail.php?id=<?= $r->id ?>" class="btn btn-outline" style="font-size: 0.8rem;">Ver Ruta</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleFav(rutaId, btn) {
    const icon = btn.querySelector('i');
    fetch('<?= BASE_URL ?>api/favorite_route.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `ruta_id=${rutaId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'added') {
            icon.className = 'fas fa-heart';
            btn.style.color = '#ef4444';
        } else {
            icon.className = 'far fa-heart';
            btn.style.color = '#cbd5e1';
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
