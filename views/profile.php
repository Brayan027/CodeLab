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

// Obtener Snippets (Públicos siempre, privados solo si es el dueño)
if (is_logged_in() && $_SESSION['user_id'] == $profile_id) {
    $stmt = $pdo->prepare("SELECT * FROM snippets WHERE usuario_id = ? ORDER BY fecha_creacion DESC");
} else {
    $stmt = $pdo->prepare("SELECT * FROM snippets WHERE usuario_id = ? AND privacidad = 'publico' ORDER BY fecha_creacion DESC");
}
$stmt->execute([$profile_id]);
$user_snippets = $stmt->fetchAll();

// Lógica para Unirse a Grupo
$grupo_msg = '';
if (is_logged_in() && $_SESSION['user_id'] == $profile_id && isset($_POST['unirse_grupo'])) {
    $codigo = strtoupper(sanitize($_POST['codigo_grupo']));
    $stmt = $pdo->prepare("SELECT id FROM grupos WHERE codigo_invitacion = ?");
    $stmt->execute([$codigo]);
    $grupo = $stmt->fetch();
    
    if ($grupo) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO grupo_estudiantes (grupo_id, estudiante_id) VALUES (?, ?)");
        $stmt->execute([$grupo->id, $profile_id]);
        $grupo_msg = "¡Te has unido al grupo correctamente!";
    } else {
        $grupo_msg = "Código de grupo no válido.";
    }
}

// Obtener estadísticas reales
$stmt = $pdo->prepare("SELECT COUNT(*) FROM uso_ia_logs WHERE usuario_id = ?");
$stmt->execute([$profile_id]);
$real_ia = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT (SELECT COUNT(*) FROM foro_preguntas WHERE usuario_id = ?) + (SELECT COUNT(*) FROM foro_respuestas WHERE usuario_id = ?) as foro");
$stmt->execute([$profile_id, $profile_id]);
$real_foro = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM snippets WHERE usuario_id = ?");
$stmt->execute([$profile_id]);
$real_snippets = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM rutas WHERE creador_id = ?");
$stmt->execute([$profile_id]);
$real_rutas = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM ruta_paso_votos v JOIN pasos_ruta p ON v.paso_id = p.id JOIN rutas r ON p.ruta_id = r.id WHERE r.creador_id = ?");
$stmt->execute([$profile_id]);
$real_votos_rutas = $stmt->fetchColumn();
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
                <div style="display: flex; flex-direction: column; gap: 20px; margin-bottom: 30px;">
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

            <h2 style="margin: 40px 0 20px;">Fragmentos de Código</h2>
            <?php if (empty($user_snippets)): ?>
                <div class="glass-card" style="text-align: center; padding: 40px;">
                    <p style="color: var(--text-secondary);">No hay fragmentos de código compartidos.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                    <?php foreach ($user_snippets as $s): ?>
                        <div class="glass-card" style="display: flex; flex-direction: column; border-top: 3px solid <?= $s->privacidad == 'privado' ? '#64748b' : 'var(--primary-color)' ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <span style="font-size: 0.7rem; font-weight: bold; text-transform: uppercase; color: var(--text-secondary);"><?= $s->lenguaje ?></span>
                                <?php if ($s->privacidad == 'privado'): ?>
                                    <i class="fas fa-lock" style="font-size: 0.8rem; color: #64748b;" title="Privado"></i>
                                <?php endif; ?>
                            </div>
                            <h3 style="font-size: 1.1rem;"><a href="<?= BASE_URL ?>views/snippet_detail.php?id=<?= $s->id ?>" style="color: var(--text-primary); text-decoration: none;"><?= $s->titulo ?></a></h3>
                            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 10px; flex: 1;"><?= substr($s->descripcion, 0, 80) ?>...</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Panel de Grupos (Solo para el dueño del perfil si es estudiante) -->
            <?php if (is_logged_in() && $_SESSION['user_id'] == $profile_id && $user_profile->rol == 'estudiante'): ?>
                <div class="glass-card" style="margin-top: 30px; border-left: 5px solid var(--secondary-color);">
                    <h3>Mis Grupos de Clase</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 20px;">Únete a un grupo usando el código proporcionado por tu docente.</p>
                    
                    <?php if ($grupo_msg): ?>
                        <p style="color: var(--primary-color); font-weight: 600; margin-bottom: 15px;"><?= $grupo_msg ?></p>
                    <?php endif; ?>

                    <form method="POST" style="display: flex; gap: 10px; margin-bottom: 20px;">
                        <input type="text" name="codigo_grupo" placeholder="Código (Ej: 3A5B1C)" class="form-control" maxlength="6" style="width: 200px;" required>
                        <button type="submit" name="unirse_grupo" class="btn btn-primary">Unirse al Grupo</button>
                    </form>

                    <?php
                    $stmt = $pdo->prepare("SELECT g.nombre, u.nombre_completo as docente FROM grupos g JOIN grupo_estudiantes ge ON g.id = ge.grupo_id JOIN usuarios u ON g.docente_id = u.id WHERE ge.estudiante_id = ?");
                    $stmt->execute([$profile_id]);
                    $sus_grupos = $stmt->fetchAll();
                    ?>
                    <?php if ($sus_grupos): ?>
                        <ul style="list-style: none; padding: 0;">
                            <?php foreach ($sus_grupos as $sg): ?>
                                <li style="padding: 10px; border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between;">
                                    <span><strong><?= $sg->nombre ?></strong></span>
                                    <span style="font-size: 0.8rem; color: var(--text-secondary);">Docente: <?= $sg->docente ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="glass-card">
            <h4>Analítica Personal</h4>
            <div style="margin-top: 20px; display: flex; flex-direction: column; gap: 15px;">
                <div style="display: flex; justify-content: space-between;">
                    <span>Ayudas IA</span>
                    <span style="color: var(--secondary-color); font-weight: bold;"><?= $real_ia ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Actividad Foro</span>
                    <span style="color: var(--primary-color); font-weight: bold;"><?= $real_foro ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Snippets Creados</span>
                    <span style="color: var(--accent-color); font-weight: bold;"><?= $real_snippets ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Rutas de Aprendizaje</span>
                    <span style="color: var(--primary-color); font-weight: bold;"><?= $real_rutas ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Likes en mis Rutas</span>
                    <span style="color: #f59e0b; font-weight: bold;"><?= $real_votos_rutas ?></span>
                </div>
            </div>
            <p style="margin-top: 20px; font-size: 0.75rem; color: var(--text-secondary); line-height: 1.4;">
                <i class="fas fa-info-circle"></i> Estas métricas ayudan a tus docentes a medir tu progreso y participación.
            </p>
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
