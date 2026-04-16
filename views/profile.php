<?php
require_once __DIR__ . '/../includes/header.php';

$profile_id = $_GET['id'] ?? ($_SESSION['user_id'] ?? null);
if (!$profile_id) redirect('views/login.php');

// Obtener datos del usuario del perfil
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$profile_id]);
$user_profile = $stmt->fetch();

if (!$user_profile) die("Usuario no encontrado.");

// Contar seguidores y siguiendo
$stmt = $pdo->prepare("SELECT COUNT(*) FROM seguidores WHERE siguiendo_id = ?");
$stmt->execute([$profile_id]);
$followers_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM seguidores WHERE seguidor_id = ?");
$stmt->execute([$profile_id]);
$following_count = $stmt->fetchColumn();

// Verificar si el usuario logueado lo sigue
$is_following = false;
if (is_logged_in() && $_SESSION['user_id'] != $profile_id) {
    $stmt = $pdo->prepare("SELECT * FROM seguidores WHERE seguidor_id = ? AND siguiendo_id = ?");
    $stmt->execute([$_SESSION['user_id'], $profile_id]);
    $is_following = $stmt->fetch() ? true : false;
}

// Obtener actividad (Rutas creadas)
$stmt = $pdo->prepare("SELECT * FROM rutas WHERE creador_id = ? ORDER BY fecha_creacion DESC");
$stmt->execute([$profile_id]);
$user_routes = $stmt->fetchAll();
?>

<div class="animate-in" style="margin-top: 40px;">
    <!-- Perfil Header -->
    <div class="glass-card" style="display: flex; gap: 30px; align-items: center; margin-bottom: 30px;">
        <img src="<?= BASE_URL ?>assets/img/<?= $user_profile->avatar ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user_profile->nombre_completo) ?>&background=random'" 
             style="width: 120px; height: 120px; border-radius: 50%; border: 4px solid var(--primary-color);">
        
        <div style="flex: 1;">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <h1 style="margin: 0;"><?= $user_profile->nombre_completo ?></h1>
                    <p style="color: var(--text-secondary);">@<?= $user_profile->usuario ?></p>
                </div>
                <?php if (is_logged_in()): ?>
                    <?php if ($_SESSION['user_id'] == $profile_id): ?>
                        <a href="<?= BASE_URL ?>views/edit_profile.php" class="btn btn-outline"><i class="fas fa-cog"></i> Ajustes</a>
                    <?php else: ?>
                        <button onclick="toggleFollow(<?= $profile_id ?>)" id="followBtn" class="btn <?= $is_following ? 'btn-outline' : 'btn-primary' ?>">
                            <?= $is_following ? 'Siguiendo' : 'Seguir' ?>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <p style="margin: 15px 0;"><?= $user_profile->bio ?: 'Sin biografía disponible.' ?></p>
            
            <div style="display: flex; gap: 25px; font-weight: bold;">
                <span><span id="followersCount"><?= $followers_count ?></span> <span style="color: var(--text-secondary); font-weight: normal;">Seguidores</span></span>
                <span><?= $following_count ?> <span style="color: var(--text-secondary); font-weight: normal;">Siguiendo</span></span>
            </div>
        </div>
    </div>

    <!-- Actividad Reciente -->
    <div style="display: grid; grid-template-columns: 1fr 300px; gap: 30px;">
        <div>
            <h2 style="margin-bottom: 20px;">Rutas Publicadas</h2>
            <?php if (empty($user_routes)): ?>
                <div class="glass-card" style="text-align: center; padding: 40px;">
                    <p style="color: var(--text-secondary);">No hay rutas públicas todavía.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <?php foreach ($user_routes as $r): ?>
                        <div class="glass-card">
                            <h3><a href="<?= BASE_URL ?>views/route_detail.php?id=<?= $r->id ?>" style="color: var(--text-primary); text-decoration: none;"><?= $r->titulo ?></a></h3>
                            <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 10px 0;"><?= substr($r->descripcion, 0, 120) ?>...</p>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                <i class="far fa-calendar"></i> <?= date('M Y', strtotime($r->fecha_creacion)) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="glass-card">
            <h4>Estadísticas de Aprendizaje</h4>
            <div style="margin-top: 20px; display: flex; flex-direction: column; gap: 15px;">
                <div style="display: flex; justify-content: space-between;">
                    <span>Ayudas IA</span>
                    <span style="color: var(--secondary-color);">12</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Preguntas Foro</span>
                    <span style="color: var(--primary-color);">5</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Códigos Compartidos</span>
                    <span style="color: var(--accent-color);">8</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFollow(userId) {
    const btn = document.getElementById('followBtn');
    const countEl = document.getElementById('followersCount');
    fetch('<?= BASE_URL ?>api/follow.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${userId}`
    })
    .then(res => res.json())
    .then(data => {
        let count = parseInt(countEl.textContent);
        if (data.status === 'followed') {
            btn.innerText = 'Siguiendo';
            btn.classList.replace('btn-primary', 'btn-outline');
            countEl.textContent = count + 1;
        } else {
            btn.innerText = 'Seguir';
            btn.classList.replace('btn-outline', 'btn-primary');
            countEl.textContent = Math.max(0, count - 1);
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
