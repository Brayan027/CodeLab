<?php
require_once __DIR__ . '/../includes/header.php';

// Bloqueo de seguridad: Solo monitores y admins
if (!is_logged_in() || ($_SESSION['rol'] !== 'monitor' && $_SESSION['rol'] !== 'admin')) {
    redirect('index.php');
}

$monitor_id = $_SESSION['user_id'];

// Obtener estadísticas rápidas
$total_estudiantes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'estudiante'")->fetchColumn();
$preguntas_pendientes = $pdo->query("SELECT COUNT(*) FROM foro_preguntas p LEFT JOIN foro_respuestas r ON p.id = r.pregunta_id WHERE r.id IS NULL")->fetchColumn();
$snippets_recientes = $pdo->query("SELECT COUNT(*) FROM snippets WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();

// Obtener últimas preguntas sin respuesta
$stmt = $pdo->prepare("SELECT p.*, u.nombre_completo, u.usuario 
                       FROM foro_preguntas p 
                       JOIN usuarios u ON p.usuario_id = u.id 
                       WHERE (SELECT COUNT(*) FROM foro_respuestas WHERE pregunta_id = p.id) = 0 
                       ORDER BY p.fecha_creacion DESC LIMIT 5");
$stmt->execute();
$pendientes = $stmt->fetchAll();

// Obtener actividad reciente de estudiantes
$stmt = $pdo->prepare("SELECT u.nombre_completo, u.usuario, 'snippet' as tipo, s.titulo as detalle, s.fecha_creacion as fecha, s.id as ref_id
                       FROM snippets s JOIN usuarios u ON s.usuario_id = u.id
                       UNION ALL
                       SELECT u.nombre_completo, u.usuario, 'pregunta' as tipo, p.titulo as detalle, p.fecha_creacion as fecha, p.id as ref_id
                       FROM foro_preguntas p JOIN usuarios u ON p.usuario_id = u.id
                       ORDER BY fecha DESC LIMIT 10");
$stmt->execute();
$actividad = $stmt->fetchAll();
?>

<div class="animate-in" style="margin-top: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h1><i class="fas fa-eye" style="color: #8b5cf6;"></i> Panel de Monitoreo</h1>
            <p style="color: var(--text-secondary);">Supervisión de interacciones y apoyo a la comunidad.</p>
        </div>
        <div style="display: flex; gap: 15px;">
            <div class="glass-card" style="padding: 10px 20px; text-align: center; min-width: 120px;">
                <div style="font-size: 1.5rem; font-weight: 800; color: #8b5cf6;"><?= $total_estudiantes ?></div>
                <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--text-secondary);">Estudiantes</div>
            </div>
            <div class="glass-card" style="padding: 10px 20px; text-align: center; min-width: 120px;">
                <div style="font-size: 1.5rem; font-weight: 800; color: #f59e0b;"><?= $preguntas_pendientes ?></div>
                <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--text-secondary);">Sin Respuesta</div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 350px; gap: 30px;">
        <div style="display: flex; flex-direction: column; gap: 30px;">
            <!-- Preguntas que necesitan atención -->
            <section>
                <h2 style="margin-bottom: 20px; font-size: 1.25rem;"><i class="far fa-question-circle"></i> Preguntas esperando apoyo</h2>
                <?php if (empty($pendientes)): ?>
                    <div class="glass-card" style="text-align: center; padding: 40px;">
                        <p style="color: var(--text-secondary);">¡Excelente! No hay preguntas sin respuesta en este momento.</p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php foreach ($pendientes as $p): ?>
                            <div class="glass-card" style="border-left: 4px solid #f59e0b;">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div>
                                        <h3 style="font-size: 1.1rem; margin-bottom: 5px;">
                                            <a href="<?= BASE_URL ?>views/forum_detail.php?id=<?= $p->id ?>" style="color: var(--text-primary); text-decoration: none;"><?= htmlspecialchars($p->titulo) ?></a>
                                        </h3>
                                        <p style="font-size: 0.85rem; color: var(--text-secondary);">
                                            Publicado por <strong><?= $p->nombre_completo ?></strong> (@<?= $p->usuario ?>) • <?= date('d/m H:i', strtotime($p->fecha_creacion)) ?>
                                        </p>
                                    </div>
                                    <a href="<?= BASE_URL ?>views/forum_detail.php?id=<?= $p->id ?>" class="btn btn-primary" style="font-size: 0.8rem; padding: 6px 12px;">Responder</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Herramientas de Monitor -->
            <section>
                <h2 style="margin-bottom: 20px; font-size: 1.25rem;"><i class="fas fa-tools"></i> Herramientas Rápidas</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                    <a href="<?= BASE_URL ?>views/users.php" class="glass-card" style="text-decoration: none; display: flex; align-items: center; gap: 15px; transition: 0.3s;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(59, 130, 246, 0.1); color: var(--primary-color); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: var(--text-primary);">Ver Usuarios</div>
                            <div style="font-size: 0.75rem; color: var(--text-secondary);">Gestionar comunidad</div>
                        </div>
                    </a>
                    <a href="<?= BASE_URL ?>views/forum.php" class="glass-card" style="text-decoration: none; display: flex; align-items: center; gap: 15px; transition: 0.3s;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; display: flex; align-items: center; justify-content: center;">
                            <i class="far fa-comments"></i>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: var(--text-primary);">Moderar Foro</div>
                            <div style="font-size: 0.75rem; color: var(--text-secondary);">Revisar hilos</div>
                        </div>
                    </a>
                </div>
            </section>
        </div>

        <!-- Barra lateral de actividad -->
        <aside>
            <div class="glass-card" style="padding: 20px;">
                <h3 style="margin-bottom: 20px; font-size: 1rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">Actividad Reciente</h3>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <?php foreach ($actividad as $act): ?>
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <div style="width: 30px; height: 30px; border-radius: 50%; background: <?= $act->tipo == 'snippet' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(99, 102, 241, 0.1)' ?>; color: <?= $act->tipo == 'snippet' ? '#10b981' : '#6366f1' ?>; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; flex-shrink: 0;">
                                <i class="fas <?= $act->tipo == 'snippet' ? 'fa-code' : 'fa-question' ?>"></i>
                            </div>
                            <div style="font-size: 0.85rem;">
                                <div style="color: var(--text-primary);">
                                    <strong><?= $act->nombre_completo ?></strong> creó un <?= $act->tipo ?>: 
                                    <a href="<?= BASE_URL ?>views/<?= $act->tipo == 'snippet' ? 'snippet_detail' : 'forum_detail' ?>.php?id=<?= $act->ref_id ?>" style="color: var(--primary-color); text-decoration: none;">"<?= htmlspecialchars(substr($act->detalle, 0, 30)) ?>..."</a>
                                </div>
                                <div style="color: var(--text-secondary); font-size: 0.75rem; margin-top: 4px;">
                                    <?= date('d M, H:i', strtotime($act->fecha)) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
