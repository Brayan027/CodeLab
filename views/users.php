<?php
require_once __DIR__ . '/../includes/header.php';

$search = $_GET['q'] ?? '';

// Obtener usuarios (excluir al propio si está logueado)
$query = "SELECT u.*, 
    (SELECT COUNT(*) FROM seguidores WHERE siguiendo_id = u.id) as followers,
    (SELECT COUNT(*) FROM rutas WHERE creador_id = u.id AND privacidad = 'publico') as total_rutas";

if (is_logged_in()) {
    $query .= ", (SELECT COUNT(*) FROM seguidores WHERE seguidor_id = ? AND siguiendo_id = u.id) as ya_sigue";
}

$query .= " FROM usuarios u";

$params = [];
if (is_logged_in()) {
    $params[] = $_SESSION['user_id'];
}

if ($search) {
    $query .= " WHERE (u.nombre_completo LIKE ? OR u.usuario LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY followers DESC, u.fecha_registro DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<style>
    .user-card {
        background: var(--card-bg);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        padding: 24px;
        transition: all 0.3s;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    .user-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(59, 130, 246, 0.1);
        border-color: rgba(59, 130, 246, 0.2);
    }
    .user-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 3px solid var(--glass-border);
        margin-bottom: 16px;
        transition: border-color 0.3s;
    }
    .user-card:hover .user-avatar {
        border-color: var(--primary-color);
    }
    .user-name {
        font-weight: 700;
        font-size: 1.05rem;
        margin-bottom: 2px;
        color: var(--text-primary);
    }
    .user-handle {
        color: var(--text-secondary);
        font-size: 0.85rem;
        margin-bottom: 12px;
    }
    .user-bio {
        color: var(--text-secondary);
        font-size: 0.85rem;
        line-height: 1.5;
        margin-bottom: 16px;
        min-height: 40px;
    }
    .user-stats {
        display: flex;
        gap: 20px;
        margin-bottom: 18px;
        font-size: 0.85rem;
    }
    .user-stats span {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .user-stats .stat-num {
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--primary-color);
    }
    .user-stats .stat-label {
        color: var(--text-secondary);
        font-size: 0.75rem;
    }
    .follow-btn {
        width: 100%;
        padding: 10px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .follow-btn.following {
        background: transparent;
        border: 1px solid var(--primary-color);
        color: var(--primary-color);
    }
    .follow-btn.not-following {
        background: var(--primary-color);
        border: 1px solid var(--primary-color);
        color: #fff;
    }
    .follow-btn:hover {
        transform: translateY(-1px);
    }
    .follow-btn.following:hover {
        background: rgba(239, 68, 68, 0.08);
        border-color: #ef4444;
        color: #ef4444;
    }
    .search-box {
        display: flex;
        gap: 12px;
        max-width: 500px;
    }
    .search-box input {
        flex: 1;
        padding: 12px 16px;
        border: 1px solid var(--glass-border);
        border-radius: 10px;
        background: #fff;
        font-size: 0.95rem;
        outline: none;
        transition: all 0.3s;
    }
    .search-box input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
</style>

<div class="animate-in" style="margin-top: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1><i class="fas fa-users" style="color: var(--primary-color);"></i> Comunidad</h1>
            <p style="color: var(--text-secondary); margin-top: 4px;">Descubre compañeros y sigue su progreso de aprendizaje.</p>
        </div>
        <form class="search-box" method="GET">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por nombre o usuario...">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <?php if ($search): ?>
        <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <span style="color: var(--text-secondary);">Resultados para "<strong><?= htmlspecialchars($search) ?></strong>" (<?= count($users) ?>)</span>
            <a href="<?= BASE_URL ?>views/users.php" class="btn btn-outline" style="font-size: 0.8rem; padding: 4px 12px;">Limpiar</a>
        </div>
    <?php endif; ?>

    <?php if (empty($users)): ?>
        <div class="glass-card" style="text-align: center; padding: 60px;">
            <i class="fas fa-user-slash" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 20px; display: block; opacity: 0.4;"></i>
            <p style="color: var(--text-secondary);">No se encontraron usuarios.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px;">
            <?php foreach ($users as $u): ?>
                <div class="user-card animate-in">
                    <a href="<?= BASE_URL ?>views/profile.php?id=<?= $u->id ?>">
                        <img class="user-avatar" src="https://ui-avatars.com/api/?name=<?= urlencode($u->nombre_completo) ?>&background=random&size=80" alt="<?= $u->nombre_completo ?>">
                    </a>
                    <a href="<?= BASE_URL ?>views/profile.php?id=<?= $u->id ?>" style="text-decoration: none;">
                        <div class="user-name"><?= htmlspecialchars($u->nombre_completo) ?></div>
                    </a>
                    <div class="user-handle">@<?= htmlspecialchars($u->usuario) ?></div>
                    <div class="user-bio">
                        <?= $u->bio ? htmlspecialchars(mb_substr($u->bio, 0, 80)) . (mb_strlen($u->bio) > 80 ? '...' : '') : 'Sin biografía' ?>
                    </div>
                    <div class="user-stats">
                        <span>
                            <span class="stat-num" id="fc-<?= $u->id ?>"><?= $u->followers ?></span>
                            <span class="stat-label">Seguidores</span>
                        </span>
                        <span>
                            <span class="stat-num"><?= $u->total_rutas ?></span>
                            <span class="stat-label">Rutas</span>
                        </span>
                    </div>

                    <?php if (is_logged_in() && $_SESSION['user_id'] != $u->id): ?>
                        <button 
                            class="follow-btn <?= $u->ya_sigue ? 'following' : 'not-following' ?>"
                            id="fbtn-<?= $u->id ?>"
                            onclick="toggleFollow(<?= $u->id ?>)"
                            <?php if ($u->ya_sigue): ?>
                                onmouseenter="this.innerHTML='<i class=\'fas fa-user-minus\'></i> Dejar de seguir'"
                                onmouseleave="this.innerHTML='<i class=\'fas fa-check\'></i> Siguiendo'"
                            <?php endif; ?>
                        >
                            <?php if ($u->ya_sigue): ?>
                                <i class="fas fa-check"></i> Siguiendo
                            <?php else: ?>
                                <i class="fas fa-user-plus"></i> Seguir
                            <?php endif; ?>
                        </button>
                    <?php elseif (is_logged_in() && $_SESSION['user_id'] == $u->id): ?>
                        <a href="<?= BASE_URL ?>views/profile.php" class="follow-btn following" style="text-decoration: none; text-align: center;">
                            <i class="fas fa-user"></i> Mi Perfil
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>views/login.php" class="follow-btn not-following" style="text-decoration: none;">
                            <i class="fas fa-sign-in-alt"></i> Inicia sesión
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleFollow(userId) {
    const btn = document.getElementById('fbtn-' + userId);
    const countEl = document.getElementById('fc-' + userId);

    fetch('<?= BASE_URL ?>api/follow.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${userId}`
    })
    .then(res => res.json())
    .then(data => {
        let count = parseInt(countEl.textContent);
        if (data.status === 'followed') {
            btn.className = 'follow-btn following';
            btn.innerHTML = '<i class="fas fa-check"></i> Siguiendo';
            btn.setAttribute('onmouseenter', "this.innerHTML='<i class=\\'fas fa-user-minus\\'></i> Dejar de seguir'");
            btn.setAttribute('onmouseleave', "this.innerHTML='<i class=\\'fas fa-check\\'></i> Siguiendo'");
            countEl.textContent = count + 1;
        } else {
            btn.className = 'follow-btn not-following';
            btn.innerHTML = '<i class="fas fa-user-plus"></i> Seguir';
            btn.removeAttribute('onmouseenter');
            btn.removeAttribute('onmouseleave');
            countEl.textContent = Math.max(0, count - 1);
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
