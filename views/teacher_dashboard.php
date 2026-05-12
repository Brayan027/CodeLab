<?php
require_once __DIR__ . '/../includes/header.php';

// Bloqueo de seguridad: Solo docentes
if (!is_logged_in() || $_SESSION['rol'] !== 'docente') {
    redirect('index.php');
}

$docente_id = $_SESSION['user_id'];
$mensaje = '';

// 1. Lógica para Crear Grupo
if (isset($_POST['crear_grupo'])) {
    $nombre = sanitize($_POST['nombre_grupo']);
    $codigo = strtoupper(substr(md5(uniqid()), 0, 6));
    $stmt = $pdo->prepare("INSERT INTO grupos (nombre, codigo_invitacion, docente_id) VALUES (?, ?, ?)");
    if ($stmt->execute([$nombre, $codigo, $docente_id])) {
        $mensaje = "¡Grupo '$nombre' creado! Código: <strong>$codigo</strong>";
    }
}

// 2. Lógica para Eliminar Estudiante
if (isset($_GET['eliminar_estudiante']) && isset($_GET['grupo_id'])) {
    $est_id = $_GET['eliminar_estudiante'];
    $grp_id = $_GET['grupo_id'];
    $check = $pdo->prepare("SELECT id FROM grupos WHERE id = ? AND docente_id = ?");
    $check->execute([$grp_id, $docente_id]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM grupo_estudiantes WHERE grupo_id = ? AND estudiante_id = ?")->execute([$grp_id, $est_id]);
        $mensaje = "Estudiante eliminado correctamente.";
    }
}

// 3. Obtener Grupos Académicos
$stmt = $pdo->prepare("SELECT * FROM grupos WHERE docente_id = ? ORDER BY fecha_creacion DESC");
$stmt->execute([$docente_id]);
$grupos = $stmt->fetchAll();

// 4. Obtener Grupos de Investigación para el select
$res_groups = $pdo->query("SELECT * FROM investigacion_grupos")->fetchAll();
?>

<style>
    .teacher-container { display: flex; gap: 30px; margin-top: 30px; min-height: 80vh; }
    .teacher-sidebar { width: 260px; flex-shrink: 0; }
    .teacher-content { flex: 1; }
    .sidebar-nav { list-style: none; padding: 0; position: sticky; top: 100px; }
    .sidebar-nav li { margin-bottom: 10px; }
    .sidebar-nav a, .sidebar-nav button { 
        display: flex; align-items: center; gap: 12px; padding: 15px 20px; 
        border-radius: 12px; color: var(--text-primary); text-decoration: none;
        background: var(--glass-bg); border: 1px solid var(--glass-border);
        width: 100%; text-align: left; cursor: pointer; transition: 0.3s;
        font-weight: 500;
    }
    .sidebar-nav a:hover, .sidebar-nav button:hover, .sidebar-nav .active {
        background: var(--primary-color); color: white; border-color: var(--primary-color);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
</style>

<div class="teacher-container animate-in">
    <!-- Sidebar Izquierda -->
    <?php require_once __DIR__ . '/../includes/teacher_sidebar.php'; ?>

    <!-- Contenido Principal -->
    <main class="teacher-content">
        <!-- Sub-Navegación Interna del Dashboard (Tabs) -->
        <div style="display: flex; gap: 15px; margin-bottom: 25px;">
            <button onclick="switchTab('resumen', this)" class="btn active-tab-btn" id="btn-tab-resumen" style="padding: 10px 25px; border-radius: 30px; border: 1px solid var(--glass-border); background: var(--glass-bg); cursor: pointer;">
                <i class="fas fa-chart-pie"></i> Resumen
            </button>
            <button onclick="switchTab('grupos', this)" class="btn" id="btn-tab-grupos" style="padding: 10px 25px; border-radius: 30px; border: 1px solid var(--glass-border); background: var(--glass-bg); cursor: pointer;">
                <i class="fas fa-users"></i> Grupos
            </button>
        </div>

        <style>
            .active-tab-btn { background: var(--primary-color) !important; color: white !important; border-color: var(--primary-color) !important; }
        </style>

        <?php if ($mensaje): ?>
            <div class="glass-card" style="background: rgba(16, 185, 129, 0.1); border-color: #10b981; color: #059669; margin-bottom: 25px;">
                <i class="fas fa-check-circle"></i> <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <!-- TAB: RESUMEN GLOBAL -->
        <div id="tab-resumen" class="tab-content active">
            <div class="glass-card">
                <h2><i class="fas fa-chart-line" style="color: var(--secondary-color);"></i> Analítica Comparativa</h2>
                <p style="color: var(--text-secondary); margin-bottom: 30px;">Comparación del rendimiento y uso de IA entre todos tus grupos.</p>
                
                <?php if (empty($grupos)): ?>
                    <p style="text-align: center; padding: 40px;">No hay datos suficientes para mostrar analíticas.</p>
                <?php else: ?>
                    <div style="height: 400px;">
                        <canvas id="globalGroupChart"></canvas>
                    </div>
                    <?php
                    $global_labels = []; $global_data_ia = []; $global_data_prod = [];
                    foreach ($grupos as $g) {
                        $global_labels[] = $g->nombre;
                        $stmt_m = $pdo->prepare("SELECT 
                            (SELECT COUNT(*) FROM uso_ia_logs l JOIN grupo_estudiantes ge ON l.usuario_id = ge.estudiante_id WHERE ge.grupo_id = ?) as ia,
                            (SELECT COUNT(*) FROM snippets s JOIN grupo_estudiantes ge ON s.usuario_id = ge.estudiante_id WHERE ge.grupo_id = ?) +
                            (SELECT COUNT(*) FROM foro_preguntas p JOIN grupo_estudiantes ge ON p.usuario_id = ge.estudiante_id WHERE ge.grupo_id = ?) +
                            (SELECT COUNT(*) FROM foro_respuestas r JOIN grupo_estudiantes ge ON r.usuario_id = ge.estudiante_id WHERE ge.grupo_id = ?) as prod");
                        $stmt_m->execute([$g->id, $g->id, $g->id, $g->id]);
                        $met = $stmt_m->fetch();
                        $global_data_ia[] = $met->ia; $global_data_prod[] = $met->prod;
                    }
                    ?>
                    <script>
                        document.addEventListener("DOMContentLoaded", () => {
                            new Chart(document.getElementById('globalGroupChart'), {
                                type: 'bar',
                                data: {
                                    labels: <?= json_encode($global_labels) ?>,
                                    datasets: [
                                        { label: 'Producción Propia', data: <?= json_encode($global_data_prod) ?>, backgroundColor: '#10b981' },
                                        { label: 'Uso de IA', data: <?= json_encode($global_data_ia) ?>, backgroundColor: '#6366f1' }
                                    ]
                                },
                                options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
                            });
                        });
                    </script>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB: MIS GRUPOS -->
        <div id="tab-grupos" class="tab-content">
            <?php if (empty($grupos)): ?>
                <div class="glass-card" style="text-align: center; padding: 50px;">
                    <i class="fas fa-users-slash" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 20px;"></i>
                    <p>Aún no has creado ningún grupo.</p>
                </div>
            <?php else: ?>
                <?php foreach ($grupos as $grupo): ?>
                    <div class="glass-card" style="margin-bottom: 30px;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px; border-bottom: 1px solid var(--glass-border); padding-bottom: 15px;">
                            <div>
                                <h3 style="margin: 0;"><?= $grupo->nombre ?></h3>
                                <p style="font-size: 0.85rem; color: var(--text-secondary);">Código: <strong style="color: var(--primary-color);"><?= $grupo->codigo_invitacion ?></strong></p>
                            </div>
                        </div>

                        <!-- Tabla de Estudiantes (Simplificada en la vista principal) -->
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                                <thead>
                                    <tr style="text-align: left; border-bottom: 1px solid var(--glass-border); color: var(--text-secondary);">
                                        <th style="padding: 10px;">Estudiante</th>
                                        <th style="padding: 10px; text-align: center;">Snippets</th>
                                        <th style="padding: 10px; text-align: center;">Foro</th>
                                        <th style="padding: 10px; text-align: center;">IA</th>
                                        <th style="padding: 10px; text-align: center;">Investigación</th>
                                        <th style="padding: 10px; text-align: center;">Desempeño</th>
                                        <th style="padding: 10px; text-align: center;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt_est = $pdo->prepare("SELECT u.id, u.nombre_completo, u.usuario, u.investigacion_grupo_id,
                                        (SELECT COUNT(*) FROM snippets WHERE usuario_id = u.id) as s,
                                        (SELECT COUNT(*) FROM foro_preguntas WHERE usuario_id = u.id) + (SELECT COUNT(*) FROM foro_respuestas WHERE usuario_id = u.id) as f,
                                        (SELECT COUNT(*) FROM uso_ia_logs WHERE usuario_id = u.id) as ia
                                        FROM usuarios u JOIN grupo_estudiantes ge ON u.id = ge.estudiante_id WHERE ge.grupo_id = ?");
                                    $stmt_est->execute([$grupo->id]);
                                    $ests = $stmt_est->fetchAll();
                                    foreach ($ests as $e): 
                                        $perf_color = ($e->ia > $e->s * 2) ? '#f43f5e' : '#10b981';
                                    ?>
                                        <tr style="border-bottom: 1px solid var(--glass-border);">
                                            <td style="padding: 10px;">
                                                <strong><?= $e->nombre_completo ?></strong><br>
                                                <span style="font-size: 0.75rem; color: var(--text-secondary);">@<?= $e->usuario ?></span>
                                            </td>
                                            <td style="padding: 10px; text-align: center;"><?= $e->s ?></td>
                                            <td style="padding: 10px; text-align: center;"><?= $e->f ?></td>
                                            <td style="padding: 10px; text-align: center;"><?= $e->ia ?></td>
                                            <td style="padding: 10px; text-align: center;">
                                                <select onchange="updateResearchGroup(<?= $e->id ?>, this.value)" style="font-size: 0.75rem; padding: 4px; border-radius: 6px; border: 1px solid var(--glass-border); width: 120px;">
                                                    <option value="">Sin Asignar</option>
                                                    <?php foreach ($res_groups as $rg): ?>
                                                        <option value="<?= $rg->id ?>" <?= $e->investigacion_grupo_id == $rg->id ? 'selected' : '' ?>><?= $rg->nombre ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td style="padding: 10px; text-align: center;">
                                                <span style="color: <?= $perf_color ?>; font-weight: bold; font-size: 0.75rem; text-transform: uppercase;">
                                                    <?= ($e->ia > $e->s * 2) ? 'Dependiente' : 'Autónomo' ?>
                                                </span>
                                            </td>
                                            <td style="padding: 10px; text-align: center;">
                                                <div style="display: flex; gap: 10px; justify-content: center;">
                                                    <button onclick="openSuspendModal(<?= $e->id ?>, '<?= addslashes($e->nombre_completo) ?>')" class="btn" title="Suspender" style="background: none; border: none; color: #f59e0b; padding: 0;">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                    <a href="?eliminar_estudiante=<?= $e->id ?>&grupo_id=<?= $grupo->id ?>" onclick="return confirm('¿Eliminar del grupo?')" style="color: #f43f5e;" title="Eliminar del grupo">
                                                        <i class="fas fa-user-minus"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal para Nuevo Grupo -->
<div id="modalGrupo" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center; padding: 20px;">
    <div class="glass-card" style="max-width: 400px; width: 100%; background: white;">
        <h3>Nuevo Grupo</h3>
        <form method="POST" style="margin-top: 20px;">
            <div class="form-group">
                <label>Nombre del Grupo</label>
                <input type="text" name="nombre_grupo" class="form-control" placeholder="Ej: Algoritmos I" required>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" name="crear_grupo" class="btn btn-primary" style="flex: 1;">Crear</button>
                <button type="button" onclick="document.getElementById('modalGrupo').style.display='none'" class="btn btn-outline" style="flex: 1;">Cerrar</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tabName, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.teacher-content button').forEach(b => b.classList.remove('active-tab-btn'));
    
    document.getElementById('tab-' + tabName).classList.add('active');
    btn.classList.add('active-tab-btn');
}

function updateResearchGroup(estId, resGroupId) {
    const formData = new FormData();
    formData.append('estudiante_id', estId);
    formData.append('investigacion_grupo_id', resGroupId);

    fetch('<?= BASE_URL ?>api/assign_research_group.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Asignación actualizada');
        } else {
            showToast(data.error || 'Error al asignar', 'info');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Error de conexión', 'info');
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

