<?php
require_once __DIR__ . '/../includes/header.php';

$pregunta_id = (int)($_GET['id'] ?? 0);
if (!$pregunta_id) redirect('views/forum.php');

$current_user_id = is_logged_in() ? $_SESSION['user_id'] : 0;

// Registrar Vista Única por IP
$user_ip = $_SERVER['REMOTE_ADDR'];
try {
    $stmt_view = $pdo->prepare("INSERT IGNORE INTO foro_vistas (pregunta_id, ip_address) VALUES (?, ?)");
    $stmt_view->execute([$pregunta_id, $user_ip]);
} catch (PDOException $e) {}

// Obtener la pregunta con total de vistas
$stmt = $pdo->prepare("SELECT p.*, u.usuario, u.rol,
        (SELECT COUNT(*) FROM foro_vistas WHERE pregunta_id = p.id) as total_vistas
        FROM foro_preguntas p JOIN usuarios u ON p.usuario_id = u.id WHERE p.id = ?");
$stmt->execute([$pregunta_id]);
$pregunta = $stmt->fetch();

if (!$pregunta) die("Pregunta no encontrada.");

// Conteo de votos de la pregunta
$stmt = $pdo->prepare("SELECT COUNT(*) FROM foro_votos WHERE pregunta_id = ?");
$stmt->execute([$pregunta_id]);
$total_votos_p = $stmt->fetchColumn();

// Voto del usuario actual
$usuario_voto_p = 0;
if ($current_user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM foro_votos WHERE pregunta_id = ? AND usuario_id = ?");
    $stmt->execute([$pregunta_id, $current_user_id]);
    $usuario_voto_p = $stmt->fetchColumn();
}

// Manejar nueva respuesta
$answer_error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['contenido']) || isset($_POST['submit_answer']))) {
    // Re-verificamos sesión justo aquí
    $session_user_id = $_SESSION['user_id'] ?? null;
    
    if (!$session_user_id) {
        $answer_error = "Sesión perdida. Por favor, vuelve a iniciar sesión.";
    } else {
        $target_pregunta_id = (int)($_POST['pregunta_id_hidden'] ?? $pregunta_id);
        $contenido = trim($_POST['contenido'] ?? '');
        
        if (!empty($contenido) && $target_pregunta_id > 0) {
            try {
                $stmt = $pdo->prepare("INSERT INTO foro_respuestas (pregunta_id, usuario_id, contenido) VALUES (?, ?, ?)");
                $stmt->execute([$target_pregunta_id, $session_user_id, $contenido]);
                
                // Registro de actividad para el docente
                $stmt_log = $pdo->prepare("INSERT INTO uso_ia_logs (usuario_id, accion, titulo_conctexto) VALUES (?, 'foro_respuesta', ?)");
                $stmt_log->execute([$session_user_id, "Respuesta a pregunta #$target_pregunta_id"]);

                header("Location: " . BASE_URL . "views/forum_detail.php?id=" . $target_pregunta_id . "&success=1");
                exit;
            } catch (PDOException $e) {
                $answer_error = "Error de base de datos: " . $e->getMessage();
            }
        } else {
            $answer_error = "Por favor, escribe un contenido válido.";
        }
    }
}

// Obtener respuestas
$stmt = $pdo->prepare("SELECT r.*, u.usuario, u.rol FROM foro_respuestas r JOIN usuarios u ON r.usuario_id = u.id WHERE r.pregunta_id = ? ORDER BY r.es_solucion DESC, r.fecha_respuesta ASC");
$stmt->execute([$pregunta_id]);
$respuestas = $stmt->fetchAll();
?>

<div class="animate-in" style="margin-top: 40px; display: grid; grid-template-columns: 1fr 300px; gap: 40px;">
    <div>
        <div style="display: flex; gap: 20px; align-items: start;">
        <!-- Columna de Votos para la Pregunta -->
        <div style="display: flex; flex-direction: column; align-items: center; gap: 10px;">
            <button onclick="vote(<?= $pregunta->id ?>, null)" class="btn-vote <?= isset($usuario_voto_p) && $usuario_voto_p > 0 ? 'active' : '' ?>" id="vbtn-p-<?= $pregunta->id ?>">
                <i class="fas fa-chevron-up" style="font-size: 1.5rem;"></i>
            </button>
            <span id="vcount-p-<?= $pregunta->id ?>" style="font-size: 1.5rem; font-weight: 800; color: var(--text-secondary);"><?= $total_votos_p ?></span>
        </div>

        <!-- Pregunta Principal -->
        <div class="glass-card" style="flex: 1; margin-bottom: 30px; background: rgba(255, 255, 255, 0.5);">
            <h1 style="font-size: 2rem; margin-bottom: 10px;"><?= htmlspecialchars($pregunta->titulo) ?></h1>
            <div style="margin-bottom: 20px; color: var(--text-secondary); font-size: 0.9rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 15px;">
                Publicado por <a href="<?= BASE_URL ?>views/profile.php?id=<?= $pregunta->usuario_id ?>" style="color: var(--primary-color); font-weight: 600;">@<?= htmlspecialchars($pregunta->usuario) ?></a> 
                <?php if ($pregunta->rol == 'docente'): ?>
                    <span style="background: #f59e0b; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.65rem; font-weight: 800; vertical-align: middle; margin-left: 5px;">DOCENTE</span>
                <?php endif; ?>
                · <?= date('d M, Y', strtotime($pregunta->fecha_creacion)) ?>
            </div>
            <div style="font-size: 1.1rem; line-height: 1.7;">
                <?= nl2br(htmlspecialchars($pregunta->contenido)) ?>
            </div>
            <div style="margin-top: 15px; font-size: 0.8rem; color: var(--text-secondary);">
                <i class="far fa-eye"></i> <?= $pregunta->total_vistas ?> vistas 
            </div>
            <div style="margin-top: 25px; display: flex; justify-content: space-between; align-items: center;">
                <span style="background: rgba(59,130,246,0.1); color: var(--primary-color); padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">#<?= htmlspecialchars($pregunta->tags) ?></span>
                
                <?php if ($current_user_id): ?>
                    <a href="#form-respuesta" class="btn btn-primary" style="font-size: 0.9rem;">
                        <i class="fas fa-reply"></i> Responder a esta pregunta
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <h2 style="margin: 40px 0 25px;">Respuestas (<?= count($respuestas) ?>)</h2>
    
    <div style="display: flex; flex-direction: column; gap: 25px; margin-bottom: 50px;">
        <?php foreach ($respuestas as $r): 
            // Obtener votos de la respuesta de forma segura
            $stmt_v = $pdo->prepare("SELECT COUNT(*) FROM foro_votos WHERE respuesta_id = ?");
            $stmt_v->execute([$r->id]);
            $votos_r = $stmt_v->fetchColumn();

            $voted_r = 0;
            if ($current_user_id) {
                $stmt_uv = $pdo->prepare("SELECT COUNT(*) FROM foro_votos WHERE respuesta_id = ? AND usuario_id = ?");
                $stmt_uv->execute([$r->id, $current_user_id]);
                $voted_r = $stmt_uv->fetchColumn();
            }
        ?>
            <div style="display: flex; gap: 20px; align-items: start;">
                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <button onclick="vote(null, <?= $r->id ?>)" class="btn-vote <?= $voted_r > 0 ? 'active' : '' ?>" id="vbtn-r-<?= $r->id ?>">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                    <span id="vcount-r-<?= $r->id ?>" style="font-weight: 800; color: var(--text-secondary);"><?= $votos_r ?></span>
                    
                    <?php if ($current_user_id == $pregunta->usuario_id): ?>
                        <button onclick="markSolve(<?= $r->id ?>)" style="background: none; border: none; cursor: pointer; color: <?= $r->es_solucion ? 'var(--accent-color)' : '#cbd5e1' ?>; font-size: 1.5rem; margin-top: 5px;">
                            <i class="<?= $r->es_solucion ? 'fas' : 'far' ?> fa-check-circle"></i>
                        </button>
                    <?php elseif ($r->es_solucion): ?>
                        <i class="fas fa-check-circle" style="color: var(--accent-color); font-size: 1.5rem; margin-top: 5px;"></i>
                    <?php endif; ?>
                </div>

                <div class="glass-card" style="flex: 1; border-left: 4px solid <?= $r->es_solucion ? 'var(--accent-color)' : 'transparent' ?>; background: <?= $r->es_solucion ? 'rgba(16, 185, 129, 0.05)' : 'white' ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; font-size: 0.9rem;">
                        <div>
                            <a href="<?= BASE_URL ?>views/profile.php?id=<?= $r->usuario_id ?>" style="color: var(--primary-color); font-weight: 700; text-decoration: none;">@<?= htmlspecialchars($r->usuario) ?></a>
                            <?php if ($r->rol == 'docente'): ?>
                                <span style="background: #f59e0b; color: white; padding: 1px 6px; border-radius: 4px; font-size: 0.6rem; font-weight: 800; margin-left: 4px;">DOCENTE</span>
                            <?php endif; ?>
                        </div>
                        <span style="color: var(--text-secondary);"><?= date('d M, Y H:i', strtotime($r->fecha_respuesta)) ?></span>
                    </div>
                    <div style="line-height: 1.7; margin-bottom: 20px;">
                        <?= nl2br(htmlspecialchars($r->contenido)) ?>
                    </div>

                    <!-- Comentarios de la Respuesta -->
                    <div style="border-top: 1px solid var(--glass-border); padding-top: 15px;">
                        <?php
                        $stmt_c = $pdo->prepare("SELECT c.*, u.usuario FROM foro_respuesta_comentarios c JOIN usuarios u ON c.usuario_id = u.id WHERE c.respuesta_id = ? ORDER BY c.fecha_comentario ASC");
                        $stmt_c->execute([$r->id]);
                        $comentarios = $stmt_c->fetchAll();
                        foreach ($comentarios as $c):
                        ?>
                            <div style="font-size: 0.8rem; margin-bottom: 10px; padding-left: 10px; border-left: 2px solid #e2e8f0;">
                                <span style="font-weight: 600; color: var(--primary-color);">@<?= $c->usuario ?></span>: 
                                <?= htmlspecialchars($c->contenido) ?>
                                <span style="color: #94a3b8; font-size: 0.7rem; margin-left: 5px;"><?= date('d M', strtotime($c->fecha_comentario)) ?></span>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($current_user_id): ?>
                            <form action="<?= BASE_URL ?>api/forum_actions.php?action=comment" method="POST" style="margin-top: 10px;">
                                <input type="hidden" name="respuesta_id" value="<?= $r->id ?>">
                                <input type="hidden" name="pregunta_id" value="<?= $pregunta_id ?>">
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" name="contenido" placeholder="Añadir un comentario..." style="flex: 1; font-size: 0.75rem; border: none; background: #f8fafc; padding: 5px 12px; border-radius: 20px;">
                                    <button type="submit" style="background: none; border: none; color: var(--primary-color); cursor: pointer;"><i class="fas fa-paper-plane"></i></button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Formulario de Respuesta -->
    <div id="form-respuesta" class="glass-card" style="box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 2px solid var(--primary-color); margin-top: 30px;">
        <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-reply" style="color: var(--primary-color);"></i> Tu Respuesta Técnica
        </h3>

        <?php if (!$current_user_id): ?>
            <div style="text-align: center; padding: 20px; background: rgba(59, 130, 246, 0.05); border-radius: 12px; border: 1px dashed var(--primary-color);">
                <p style="margin-bottom: 15px;">Debes estar identificado para participar.</p>
                <a href="<?= BASE_URL ?>views/login.php" class="btn btn-primary">Iniciar Sesión para Responder</a>
            </div>
        <?php else: ?>
            <?php if ($answer_error): ?>
                <div style="background: rgba(239, 68, 68, 0.1); color: #dc2626; padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid rgba(239, 68, 68, 0.2);">
                    <?= $answer_error ?>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>views/forum_detail.php?id=<?= $pregunta_id ?>" method="POST">
                <input type="hidden" name="pregunta_id_hidden" value="<?= $pregunta_id ?>">
                <textarea name="contenido" class="form-control" rows="6" placeholder="Aporta una solución clara o sugerencia..." required style="resize: vertical;"></textarea>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="submit" name="submit_answer" class="btn btn-primary" style="padding: 12px 40px; font-size: 1rem;">Publicar Respuesta</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    </div>

    <!-- Barra Lateral -->
    <aside>
        <div class="glass-card" style="padding: 20px; position: sticky; top: 100px;">
            <h4 style="margin-bottom: 20px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">
                <i class="fas fa-fire" style="color: #ef4444;"></i> Populares
            </h4>
            <?php
            $stmt_pop = $pdo->prepare("
                SELECT p.id, p.titulo, (SELECT COUNT(*) FROM foro_vistas WHERE pregunta_id = p.id) as vistas
                FROM foro_preguntas p
                ORDER BY vistas DESC
                LIMIT 5
            ");
            $stmt_pop->execute();
            $populares = $stmt_pop->fetchAll();
            foreach ($populares as $p_pop):
            ?>
                <div style="margin-bottom: 15px;">
                    <a href="forum_detail.php?id=<?= $p_pop->id ?>" style="text-decoration: none; color: var(--text-primary); font-size: 0.85rem; font-weight: 500; display: block; margin-bottom: 3px;">
                        <?= htmlspecialchars($p_pop->titulo) ?>
                    </a>
                    <span style="font-size: 0.75rem; color: var(--text-secondary);"><?= $p_pop->vistas ?> vistas</span>
                </div>
            <?php endforeach; ?>

            <div style="margin-top: 30px; padding: 15px; background: rgba(59,130,246,0.05); border-radius: 12px; border: 1px dashed var(--primary-color); font-size: 0.8rem; text-align: center;">
                <p>¿No encuentras la solución? Pregunta a la comunidad.</p>
                <a href="ask_question.php" style="color: var(--primary-color); font-weight: 700;">Hacer Pregunta</a>
            </div>
        </div>
    </aside>
</div>

<style>
.btn-vote {
    background: transparent;
    border: 2px solid #e2e8f0;
    color: #94a3b8;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.btn-vote:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
    background: rgba(59, 130, 246, 0.05);
}
.btn-vote.active {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}
</style>

<script>
function vote(pId, rId) {
    const params = new URLSearchParams();
    if (pId) params.append('pregunta_id', pId);
    if (rId) params.append('respuesta_id', rId);

    fetch('<?= BASE_URL ?>api/vote.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }
        // Recargamos para actualizar conteos (o podrías hacerlo con JS puro para más suavidad)
        location.reload();
    });
}

function markSolve(rId) {
    fetch('<?= BASE_URL ?>api/solve.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `respuesta_id=${rId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) alert(data.error);
        else location.reload();
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
