<?php
require_once __DIR__ . '/../includes/header.php';

$lenguaje_filter = $_GET['lang'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

$filter_mine = isset($_GET['mine']) && $_GET['mine'] == '1';

$filter_mine = isset($_GET['view']) && $_GET['view'] == 'mine';
$filter_favs = isset($_GET['view']) && $_GET['view'] == 'favs';
$filter_trending = isset($_GET['view']) && $_GET['view'] == 'trending';

$query = "SELECT DISTINCT s.*, u.usuario, u.rol, u.nombre_completo, u.avatar,
          (SELECT COUNT(*) FROM snippet_comentarios WHERE snippet_id = s.id) as total_comentarios,
          (SELECT COUNT(*) FROM metricas_reutilizacion WHERE snippet_id = s.id) as total_copias,
          (SELECT COUNT(*) FROM snippets WHERE parent_id = s.id) as total_forks,
          (SELECT COUNT(*) FROM snippets_favoritos WHERE snippet_id = s.id AND usuario_id = ?) as is_fav,
          (SELECT COUNT(*) FROM snippets_favoritos WHERE snippet_id = s.id) as total_favoritos
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

    <!-- Pestañas de Filtro y Buscador -->
    <div class="glass-card" style="margin-bottom: 30px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 15px; padding: 15px 25px;">
        <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            <a href="?view=all" style="text-decoration: none; font-size: 0.9rem; font-weight: 600; padding-bottom: 5px; border-bottom: 2px solid <?= !$filter_mine && !$filter_favs && !$filter_trending ? 'var(--primary-color)' : 'transparent' ?>; color: <?= !$filter_mine && !$filter_favs && !$filter_trending ? 'var(--primary-color)' : 'var(--text-secondary)' ?>;">
                <i class="fas fa-globe"></i> Explorar Todos
            </a>
            <a href="?view=trending" style="text-decoration: none; font-size: 0.9rem; font-weight: 600; padding-bottom: 5px; border-bottom: 2px solid <?= $filter_trending ? 'var(--primary-color)' : 'transparent' ?>; color: <?= $filter_trending ? 'var(--primary-color)' : 'var(--text-secondary)' ?>;">
                <i class="fas fa-fire" style="color: #ef4444;"></i> Tendencia
            </a>
            <?php if (is_logged_in()): ?>
                <a href="?view=mine" style="text-decoration: none; font-size: 0.9rem; font-weight: 600; padding-bottom: 5px; border-bottom: 2px solid <?= $filter_mine ? 'var(--primary-color)' : 'transparent' ?>; color: <?= $filter_mine ? 'var(--primary-color)' : 'var(--text-secondary)' ?>;">
                    <i class="fas fa-user"></i> Mis Snippets
                </a>
                <a href="?view=favs" style="text-decoration: none; font-size: 0.9rem; font-weight: 600; padding-bottom: 5px; border-bottom: 2px solid <?= $filter_favs ? 'var(--primary-color)' : 'transparent' ?>; color: <?= $filter_favs ? 'var(--primary-color)' : 'var(--text-secondary)' ?>;">
                    <i class="fas fa-heart"></i> Guardados
                </a>
            <?php endif; ?>
        </div>

        <form action="" method="GET" style="display: flex; gap: 10px; max-width: 400px; flex: 1;">
            <?php if (isset($_GET['view'])): ?>
                <input type="hidden" name="view" value="<?= htmlspecialchars($_GET['view']) ?>">
            <?php endif; ?>
            <div style="position: relative; width: 100%;">
                <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                <input type="text" name="search" class="form-control" placeholder="Buscar palabras clave..." value="<?= htmlspecialchars($search_query) ?>" style="padding-left: 40px; border-radius: 30px;">
            </div>
            <button type="submit" class="btn btn-primary" style="border-radius: 30px; padding: 0 20px;">Buscar</button>
        </form>
    </div>

    <?php if (empty($snippets)): ?>
        <div class="glass-card" style="text-align: center; padding: 60px;">
            <i class="fas fa-laptop-code" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 20px;"></i>
            <p style="color: var(--text-secondary);">Aún no hay fragmentos de código. ¡Sé el primero en compartir!</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 25px;">
            <?php 
                $lang_colors = [
                    'Java' => '#f89820',
                    'PHP' => '#777bb4',
                    'JavaScript' => '#f7df1e',
                    'SQL' => '#336791',
                    'Python' => '#3776ab',
                    'HTML' => '#e34f26',
                    'CSS' => '#1572b6',
                    'C++' => '#00599c',
                    'C#' => '#239120',
                    'Ruby' => '#cc342d',
                    'Go' => '#00add8'
                ];
                foreach ($snippets as $s): 
                    $color = $lang_colors[$s->lenguaje] ?? 'var(--primary-color)';
            ?>
                <div class="glass-card" style="display: flex; flex-direction: column;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <span style="background: <?= $color ?>20; color: <?= $color ?>; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; border: 1px solid <?= $color ?>40;">
                                <?= htmlspecialchars($s->lenguaje) ?>
                            </span>
                            <?php if ($s->privacidad == 'privado'): ?>
                                <span style="background: rgba(15, 23, 42, 0.1); color: var(--text-primary); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                    <i class="fas fa-lock" style="font-size: 0.7rem;"></i> Privado
                                </span>
                            <?php endif; ?>
                        </div>
                        <span style="font-size: 0.7rem; color: var(--text-secondary);"><?= date('d M, Y', strtotime($s->fecha_creacion)) ?></span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <h3 style="margin: 0; flex: 1; font-size: 1.1rem;">
                            <a href="<?= BASE_URL ?>views/snippet_detail.php?id=<?= $s->id ?>" style="text-decoration: none; color: var(--text-primary);">
                                <?= htmlspecialchars($s->titulo) ?>
                            </a>
                        </h3>
                        <?php if (is_logged_in()): ?>
                            <button onclick="toggleSnippetFav(<?= $s->id ?>, this)" style="background: none; border: none; cursor: pointer; color: <?= $s->is_fav ? '#ef4444' : '#cbd5e1' ?>; font-size: 1.2rem; transition: all 0.2s; margin-left: 10px;">
                                <i class="<?= $s->is_fav ? 'fas' : 'far' ?> fa-heart"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 15px; flex: 1; line-height: 1.5;"><?= htmlspecialchars(substr($s->descripcion, 0, 100)) ?>...</p>
                    
                    <?php 
                        $preview_code = $s->codigo;
                        $blocks = json_decode($s->codigo, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($blocks)) {
                            $found = false;
                            foreach($blocks as $b) {
                                if ($b['type'] === 'code') {
                                    $preview_code = $b['value'];
                                    $found = true; break;
                                }
                            }
                            if (!$found && count($blocks) > 0) $preview_code = $blocks[0]['value'];
                        }
                    ?>
                    
                    <div style="background: #0f172a; padding: 15px; border-radius: 8px; margin-bottom: 15px; max-height: 150px; overflow: hidden; position: relative; border: 1px solid #334155;">
                        <pre style="margin: 0;"><code style="font-family: 'Fira Code', monospace; color: #e2e8f0; font-size: 0.75rem;">
<?= htmlspecialchars(substr($preview_code, 0, 250)) ?><?= strlen($preview_code) > 250 ? "\n..." : '' ?>
                        </code></pre>
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 50px; background: linear-gradient(transparent, #0f172a);"></div>
                        
                        <button onclick="copyToClipboard(`<?= htmlspecialchars($preview_code, ENT_QUOTES) ?>`, this)" 
                                style="position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.1); border: none; color: #fff; border-radius: 5px; padding: 5px 10px; cursor: pointer; font-size: 0.7rem; transition: background 0.2s;" title="Copiar código">
                            <i class="far fa-copy"></i>
                        </button>
                    </div>
                    
                    <!-- Stats Section -->
                    <div style="display: flex; gap: 15px; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 15px;">
                        <div style="display: flex; align-items: center; gap: 5px; color: var(--text-secondary); font-size: 0.8rem;" title="Me Gusta / Favoritos">
                            <i class="fas fa-heart" style="color: #ef4444;"></i> <strong><?= $s->total_favoritos ?></strong>
                        </div>
                        <div style="display: flex; align-items: center; gap: 5px; color: var(--text-secondary); font-size: 0.8rem;" title="Copias / Forks">
                            <i class="fas fa-code-branch" style="color: #10b981;"></i> <strong><?= $s->total_copias + $s->total_forks ?></strong>
                        </div>
                        <a href="<?= BASE_URL ?>views/snippet_detail.php?id=<?= $s->id ?>#comentarios" style="display: flex; align-items: center; gap: 5px; color: var(--text-secondary); font-size: 0.8rem; text-decoration: none; transition: color 0.2s;" title="Comentarios" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-secondary)'">
                            <i class="fas fa-comment" style="color: #3b82f6;"></i> <strong><?= $s->total_comentarios ?></strong>
                        </a>
                        <a href="<?= BASE_URL ?>views/snippet_detail.php?id=<?= $s->id ?>" style="display: flex; align-items: center; gap: 5px; color: var(--text-secondary); font-size: 0.8rem; text-decoration: none; margin-left: auto; transition: color 0.2s;" title="Ver snippet" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-secondary)'">
                            <i class="fas fa-eye" style="color: #a855f7;"></i> <strong>Ver</strong>
                        </a>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <img src="<?= BASE_URL ?>assets/img/<?= $s->avatar ?? 'default.png' ?>" 
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($s->nombre_completo ?? $s->usuario) ?>&size=30&background=random'" 
                                 style="border-radius: 50%; width: 28px; height: 28px; object-fit: cover;">
                            <span style="font-weight: 600; font-size: 0.8rem; color: var(--text-primary);">
                                <?= htmlspecialchars($s->nombre_completo ?? $s->usuario) ?>
                            </span>
                            <?php if ($s->rol == 'docente'): ?>
                                <span style="background: #fef3c7; color: #d97706; padding: 2px 6px; border-radius: 4px; font-size: 0.6rem; font-weight: 800; border: 1px solid #fcd34d;" title="Autoridad Académica">DOCENTE</span>
                            <?php endif; ?>
                        </div>
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

function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const icon = btn.querySelector('i');
        icon.className = 'fas fa-check';
        icon.style.color = '#10b981';
        setTimeout(() => {
            icon.className = 'far fa-copy';
            icon.style.color = '';
        }, 2000);
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
