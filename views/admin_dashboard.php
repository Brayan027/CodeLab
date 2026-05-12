<?php
require_once __DIR__ . '/../includes/header.php';

// Bloqueo de seguridad: Solo admins
if (!is_logged_in() || $_SESSION['rol'] !== 'admin') {
    redirect('index.php');
}

$admin_id = $_SESSION['user_id'];

// Estadísticas globales
$total_users = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$total_snippets = $pdo->query("SELECT COUNT(*) FROM snippets")->fetchColumn();
$total_questions = $pdo->query("SELECT COUNT(*) FROM foro_preguntas")->fetchColumn();
$total_routes = $pdo->query("SELECT COUNT(*) FROM rutas")->fetchColumn();

// Usuarios recientes
$recent_users = $pdo->query("SELECT * FROM usuarios ORDER BY fecha_registro DESC LIMIT 10")->fetchAll();
?>

<div class="animate-in" style="margin-top: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h1><i class="fas fa-user-shield" style="color: #ef4444;"></i> Panel de Administración</h1>
            <p style="color: var(--text-secondary);">Control total de la plataforma CodeLab.</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
        <div class="glass-card" style="text-align: center;">
            <div style="font-size: 2rem; font-weight: 800; color: var(--primary-color);"><?= $total_users ?></div>
            <div style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.8rem;">Usuarios</div>
        </div>
        <div class="glass-card" style="text-align: center;">
            <div style="font-size: 2rem; font-weight: 800; color: #10b981;"><?= $total_snippets ?></div>
            <div style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.8rem;">Snippets</div>
        </div>
        <div class="glass-card" style="text-align: center;">
            <div style="font-size: 2rem; font-weight: 800; color: #f59e0b;"><?= $total_questions ?></div>
            <div style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.8rem;">Preguntas</div>
        </div>
        <div class="glass-card" style="text-align: center;">
            <div style="font-size: 2rem; font-weight: 800; color: #8b5cf6;"><?= $total_routes ?></div>
            <div style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.8rem;">Rutas</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
        <!-- Columna Izquierda: Usuarios Suspendidos -->
        <div class="glass-card">
            <h3><i class="fas fa-user-slash" style="color: #f59e0b;"></i> Usuarios Suspendidos</h3>
            <div style="overflow-x: auto; margin-top: 20px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid var(--glass-border); color: var(--text-secondary);">
                            <th style="padding: 12px;">Usuario</th>
                            <th style="padding: 12px;">Expira</th>
                            <th style="padding: 12px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $suspended = $pdo->query("SELECT id, nombre_completo, usuario, suspendido_hasta FROM usuarios WHERE suspendido_hasta > NOW()")->fetchAll();
                        foreach ($suspended as $s):
                        ?>
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <td style="padding: 12px;">
                                    <strong><?= htmlspecialchars($s->nombre_completo) ?></strong><br>
                                    <span style="color: var(--text-secondary);">@<?= $s->usuario ?></span>
                                </td>
                                <td style="padding: 12px; color: #ef4444; font-weight: bold;">
                                    <?= date('d/m/Y', strtotime($s->suspendido_hasta)) ?>
                                </td>
                                <td style="padding: 12px;">
                                    <button onclick="liftSuspension(<?= $s->id ?>, '<?= addslashes($s->usuario) ?>')" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.7rem; color: #10b981; border-color: #10b981;">Levantar</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($suspended)): ?>
                            <tr><td colspan="3" style="padding: 20px; text-align: center; color: var(--text-secondary);">No hay usuarios suspendidos actualmente.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Columna Derecha: Reportes de Comunidad -->
        <div class="glass-card">
            <h3><i class="fas fa-flag" style="color: #ef4444;"></i> Reportes Recientes</h3>
            <div style="overflow-x: auto; margin-top: 20px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid var(--glass-border); color: var(--text-secondary);">
                            <th style="padding: 12px;">Tipo</th>
                            <th style="padding: 12px;">Motivo</th>
                            <th style="padding: 12px;">Reportero</th>
                            <th style="padding: 12px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $reports = $pdo->query("SELECT r.*, u.usuario as reportero FROM reportes r JOIN usuarios u ON r.reportero_id = u.id ORDER BY r.fecha DESC LIMIT 10")->fetchAll();
                            foreach ($reports as $rep):
                            ?>
                                <tr style="border-bottom: 1px solid var(--glass-border);">
                                    <td style="padding: 12px;"><span style="text-transform: capitalize; font-weight: bold;"><?= $rep->tipo ?></span></td>
                                    <td style="padding: 12px; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($rep->motivo) ?></td>
                                    <td style="padding: 12px;">@<?= $rep->reportero ?></td>
                                    <td style="padding: 12px;">
                                        <a href="<?= BASE_URL ?>views/<?= $rep->tipo == 'pregunta' ? 'forum_detail' : 'forum_detail' ?>.php?id=<?= $rep->item_id ?>" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.7rem;">Ver</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($reports)): ?>
                                <tr><td colspan="4" style="padding: 20px; text-align: center; color: var(--text-secondary);">No hay reportes pendientes.</td></tr>
                            <?php endif; ?>
                        <?php } catch (Exception $e) { ?>
                            <tr><td colspan="4" style="padding: 20px; text-align: center; color: var(--text-secondary);">Tabla de reportes no encontrada.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <!-- Usuarios Recientes -->
        <div class="glass-card">
            <h3><i class="fas fa-users-cog"></i> Usuarios Recientes</h3>
            <div style="overflow-x: auto; margin-top: 20px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid var(--glass-border); color: var(--text-secondary);">
                            <th style="padding: 12px;">Usuario</th>
                            <th style="padding: 12px;">Rol</th>
                            <th style="padding: 12px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $u): ?>
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <td style="padding: 12px;">
                                    <strong><?= htmlspecialchars($u->nombre_completo) ?></strong><br>
                                    <span style="color: var(--text-secondary);">@<?= $u->usuario ?></span>
                                </td>
                                <td style="padding: 12px;">
                                    <span style="font-size: 0.65rem; background: var(--glass-bg); padding: 3px 7px; border-radius: 8px; text-transform: uppercase; border: 1px solid var(--glass-border);">
                                        <?= $u->rol ?>
                                    </span>
                                </td>
                                <td style="padding: 12px;">
                                    <a href="<?= BASE_URL ?>views/profile.php?id=<?= $u->id ?>" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.7rem;">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Historial de Moderación -->
        <div class="glass-card">
            <h3><i class="fas fa-history"></i> Historial de Moderación</h3>
            <div style="overflow-x: auto; margin-top: 20px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid var(--glass-border); color: var(--text-secondary);">
                            <th style="padding: 12px;">Moderador</th>
                            <th style="padding: 12px;">Acción</th>
                            <th style="padding: 12px;">Detalle</th>
                            <th style="padding: 12px;">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt_logs = $pdo->query("SELECT l.*, u.usuario as moderador FROM moderacion_logs l JOIN usuarios u ON l.moderador_id = u.id ORDER BY l.fecha DESC LIMIT 15");
                        $logs = $stmt_logs->fetchAll();
                        foreach ($logs as $log):
                        ?>
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <td style="padding: 12px;"><strong>@<?= $log->moderador ?></strong></td>
                                <td style="padding: 12px;">
                                    <span style="font-size: 0.65rem; font-weight: bold; text-transform: uppercase;">
                                        <?= str_replace('eliminacion_', 'Borró ', $log->accion) ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; font-size: 0.75rem; color: var(--text-secondary);">
                                    <?= htmlspecialchars($log->detalle) ?>
                                </td>
                                <td style="padding: 12px; font-size: 0.7rem; white-space: nowrap;">
                                    <?= date('d/m H:i', strtotime($log->fecha)) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="4" style="padding: 20px; text-align: center; color: var(--text-secondary);">Sin acciones registradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function liftSuspension(userId, username) {
    if (!confirm(`¿Deseas levantar la suspensión de @${username}?`)) return;

    const formData = new FormData();
    formData.append('usuario_id', userId);
    formData.append('dias', 0); // 0 días levanta la suspensión

    fetch('<?= BASE_URL ?>api/suspend_user.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.error);
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
