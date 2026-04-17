<?php
require_once __DIR__ . '/../includes/header.php';

$lenguaje_filter = $_GET['lang'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

$filter_mine = isset($_GET['mine']) && $_GET['mine'] == '1';

$filter_mine = isset($_GET['view']) && $_GET['view'] == 'mine';
$filter_favs = isset($_GET['view']) && $_GET['view'] == 'favs';
$filter_trending = isset($_GET['view']) && $_GET['view'] == 'trending';

$query = "SELECT DISTINCT s.*, u.usuario, u.rol,
          (SELECT COUNT(*) FROM snippet_comentarios WHERE snippet_id = s.id) as total_comentarios,
          (SELECT COUNT(*) FROM metricas_reutilizacion WHERE snippet_id = s.id) as total_copias,
          (SELECT COUNT(*) FROM snippets_favoritos WHERE snippet_id = s.id AND usuario_id = ?) as is_fav
          FROM snippets s 
          JOIN usuarios u ON s.usuario_id = u.id 
          LEFT JOIN snippets_favoritos sf ON s.id = sf.snippet_id
          WHERE 1=1";

$params = [$user_id];

if ($filter_mine) {
    $query .= " AND s.usuario_id = ?";
    $params[] = $user_id;
} elseif ($filter_favs) {
    $query .= " AND sf.usuario_id = ?";
    $params[] = $user_id;
} else {
    $query .= " AND (s.privacidad = 'publico' OR s.usuario_id = ?)";
    $params[] = $user_id;
}

if ($lenguaje_filter) {
    $query .= " AND s.lenguaje = ?";
    $params[] = $lenguaje_filter;
}

$search_query = $_GET['search'] ?? '';
if ($search_query) {
    $query .= " AND (s.titulo LIKE ? OR s.descripcion LIKE ? OR s.lenguaje LIKE ?)";
    $term = "%$search_query%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($filter_trending) {
    $query .= " ORDER BY total_copias DESC, s.fecha_creacion DESC";
} else {
    $query .= " ORDER BY s.fecha_creacion DESC";
}
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$snippets = $stmt->fetchAll();

// Filtro de lenguajes dinámico basado en visibilidad
$langs_stmt = $pdo->prepare("SELECT DISTINCT lenguaje FROM snippets WHERE privacidad='publico' OR usuario_id = ?");
$langs_stmt->execute([$user_id]);
$langs = $langs_stmt->fetchAll();
?>

<div class="animate-in" style="margin-top: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1><i class="fas fa-code" style="color: var(--primary-color);"></i> Repositorio de Código</h1>
        <?php if (is_logged_in()): ?>
            <a href="<?= BASE_URL ?>views/create_snippet.php" class="btn btn-primary"><i class="fas fa-plus"></i> Subir Código</a>
        <?php endif; ?>
    </div>

    <!-- Pestañas de Filtro -->
    <div style="display: flex; gap: 15px; margin-bottom: 25px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">
        <a href="snippets.php" style="text-decoration: none; color: <?= !isset($_GET['view']) ? 'var(--primary-color)' : 'var(--text-secondary)' ?>; font-weight: 600; font-size: 0.9rem; border-bottom: 2px solid <?= !isset($_GET['view']) ? 'var(--primary-color)' : 'transparent' ?>; padding-bottom: 10px;">Explorar</a>
        <a href="?view=trending" style="text-decoration: none; color: <?= $filter_trending ? 'var(--primary-color)' : 'var(--text-secondary)' ?>; font-weight: 600; font-size: 0.9rem; border-bottom: 2px solid <?= $filter_trending ? 'var(--primary-color)' : 'transparent' ?>; padding-bottom: 10px;">🔥 Más Usados</a>
        <?php if (is_logged_in()): ?>
            <a href="?view=mine" style="text-decoration: none; color: <?= $filter_mine ? 'var(--primary-color)' : 'var(--text-secondary)' ?>; font-weight: 600; font-size: 0.9rem; border-bottom: 2px solid <?= $filter_mine ? 'var(--primary-color)' : 'transparent' ?>; padding-bottom: 10px;">Mis Snippets</a>
            <a href="?view=favs" style="text-decoration: none; color: <?= $filter_favs ? 'var(--primary-color)' : 'var(--text-secondary)' ?>; font-weight: 600; font-size: 0.9rem; border-bottom: 2px solid <?= $filter_favs ? 'var(--primary-color)' : 'transparent' ?>; padding-bottom: 10px;">Guardados</a>
        <?php endif; ?>
    </div>

    <!-- Filtros y Buscador -->
    <div class="glass-card" style="margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <a href="<?= BASE_URL ?>views/snippets.php" class="btn <?= !$lenguaje_filter && !isset($_GET['mine']) ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 0.85rem;">Todos</a>
                <?php if (is_logged_in()): ?>
                    <a href="<?= BASE_URL ?>views/snippets.php?mine=1" class="btn <?= isset($_GET['mine']) ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 0.85rem;"><i class="fas fa-folder-open" style="margin-right: 5px;"></i> Mis Snippets</a>
                <?php endif; ?>
                <?php foreach ($langs as $l): ?>
                    <a href="<?= BASE_URL ?>views/snippets.php?lang=<?= urlencode($l->lenguaje) ?>" class="btn <?= $lenguaje_filter == $l->lenguaje ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 0.85rem;"><?= $l->lenguaje ?></a>
                <?php endforeach; ?>
            </div>
            
            <form action="" method="GET" style="display: flex; gap: 10px; flex: 1; max-width: 400px;">
                <?php if ($lenguaje_filter): ?>
                    <input type="hidden" name="lang" value="<?= htmlspecialchars($lenguaje_filter) ?>">
                <?php endif; ?>
                <?php if (isset($_GET['mine'])): ?>
                    <input type="hidden" name="mine" value="1">
                <?php endif; ?>
                <div style="position: relative; width: 100%;">
                    <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                    <input type="text" name="search" class="form-control" placeholder="Buscar palabras clave..." value="<?= htmlspecialchars($search_query) ?>" style="padding-left: 40px; border-radius: 30px;">
                </div>
                <button type="submit" class="btn btn-primary" style="border-radius: 30px; padding: 0 20px;">Buscar</button>
            </form>
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
                        <div>
                            <span style="background: rgba(59,130,246,0.1); color: var(--primary-color); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;"><?= $s->lenguaje ?></span>
                            <?php if ($s->privacidad == 'privado'): ?>
                                <span style="background: rgba(15, 23, 42, 0.1); color: var(--text-primary); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; margin-left: 5px;">
                                    <i class="fas fa-lock" style="font-size: 0.7rem;"></i> Privado
                                </span>
                            <?php endif; ?>
                        </div>
                        <span style="font-size: 0.75rem; color: var(--text-secondary);"><?= date('d M Y', strtotime($s->fecha_creacion)) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <h3 style="margin: 0; flex: 1;"><?= $s->titulo ?></h3>
                        <?php if (is_logged_in()): ?>
                            <button onclick="toggleSnippetFav(<?= $s->id ?>, this)" style="background: none; border: none; cursor: pointer; color: <?= $s->is_fav ? '#ef4444' : '#cbd5e1' ?>; font-size: 1.1rem; transition: all 0.2s;">
                                <i class="<?= $s->is_fav ? 'fas' : 'far' ?> fa-heart"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 15px; flex: 1;"><?= substr($s->descripcion, 0, 100) ?>...</p>
                    <div style="background: #1e293b; padding: 12px; border-radius: 8px; margin-bottom: 15px; max-height: 120px; overflow: hidden; position: relative;">
                        <pre style="margin: 0;"><code style="font-family: monospace; color: #e2e8f0; font-size: 0.8rem;">
                            <?php 
                            $preview_code = $s->codigo;
                            $blocks = json_decode($s->codigo, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($blocks)) {
                                // Buscar el primer bloque de código para la previsualización
                                $found = false;
                                foreach($blocks as $b) {
                                    if ($b['type'] === 'code') {
                                        $preview_code = $b['value'];
                                        $found = true;
                                        break;
                                    }
                                }
                                // Si no hay código, mostrar el primer texto
                                if (!$found && count($blocks) > 0) {
                                    $preview_code = $blocks[0]['value'];
                                }
                            }
                            echo htmlspecialchars(substr($preview_code, 0, 200)) . (strlen($preview_code) > 200 ? '...' : '');
                            ?>
                        </code></pre>
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 30px; background: linear-gradient(transparent, #1e293b);"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.03);">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($s->usuario) ?>&size=24&background=random" style="border-radius: 50%;">
                            <span style="font-weight: 600;">@<?= htmlspecialchars($s->usuario) ?></span>
                            <?php if ($s->rol == 'docente'): ?>
                                <span style="background: #fef3c7; color: #d97706; padding: 1px 6px; border-radius: 4px; font-size: 0.6rem; font-weight: 800; border: 1px solid #fcd34d;">DOCENTE</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?= BASE_URL ?>views/snippet_detail.php?id=<?= $s->id ?>" class="btn btn-outline" style="font-size: 0.75rem; padding: 6px 15px; border-radius: 20px;">Ver más</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleSnippetFav(snippetId, btn) {
    const icon = btn.querySelector('i');
    fetch('<?= BASE_URL ?>api/favorite_snippet.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `snippet_id=${snippetId}`
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
