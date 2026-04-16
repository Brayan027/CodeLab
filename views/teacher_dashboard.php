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
    $codigo = strtoupper(substr(md5(uniqid()), 0, 6)); // Código de 6 caracteres
    
    $stmt = $pdo->prepare("INSERT INTO grupos (nombre, codigo_invitacion, docente_id) VALUES (?, ?, ?)");
    if ($stmt->execute([$nombre, $codigo, $docente_id])) {
        $mensaje = "¡Grupo '$nombre' creado! Código: <strong>$codigo</strong>";
    }
}

// 2. Lógica para Eliminar Estudiante del Grupo
if (isset($_GET['eliminar_estudiante']) && isset($_GET['grupo_id'])) {
    $est_id = $_GET['eliminar_estudiante'];
    $grp_id = $_GET['grupo_id'];
    
    // Verificar que el grupo pertenece al docente
    $check = $pdo->prepare("SELECT id FROM grupos WHERE id = ? AND docente_id = ?");
    $check->execute([$grp_id, $docente_id]);
    if ($check->fetch()) {
        $del = $pdo->prepare("DELETE FROM grupo_estudiantes WHERE grupo_id = ? AND estudiante_id = ?");
        $del->execute([$grp_id, $est_id]);
        $mensaje = "Estudiante eliminado del grupo correctamente.";
    }
}

// 3. Obtener Grupos del Docente
$stmt = $pdo->prepare("SELECT * FROM grupos WHERE docente_id = ? ORDER BY fecha_creacion DESC");
$stmt->execute([$docente_id]);
$grupos = $stmt->fetchAll();
?>

<div class="animate-in" style="margin-top: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1>Panel de Control Docente</h1>
        <button onclick="document.getElementById('modalGrupo').style.display='flex'" class="btn btn-primary">
            <i class="fas fa-plus"></i> Crear Nuevo Grupo
        </button>
    </div>

    <?php if ($mensaje): ?>
        <div class="glass-card" style="background: rgba(16, 185, 129, 0.1); border-color: var(--accent-color); color: var(--accent-color); margin-bottom: 20px;">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

    <?php if (empty($grupos)): ?>
        <div class="glass-card" style="text-align: center; padding: 50px;">
            <i class="fas fa-users-slash" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 20px;"></i>
            <p>Aún no has creado ningún grupo. ¡Crea el primero para invitar a tus alumnos!</p>
        </div>
    <?php else: ?>
        <?php foreach ($grupos as $grupo): ?>
            <div class="glass-card" style="margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--glass-border); padding-bottom: 15px; margin-bottom: 20px;">
                    <div>
                        <h2 style="margin: 0;"><?= $grupo->nombre ?></h2>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Código de invitación: <strong style="color: var(--primary-color); font-size: 1.1rem;"><?= $grupo->codigo_invitacion ?></strong></p>
                    </div>
                </div>

                <h3>Estudiantes en este grupo</h3>
                <div style="overflow-x: auto; margin-top: 15px;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="color: var(--text-secondary); border-bottom: 1px solid var(--glass-border);">
                                <th style="padding: 12px;">Estudiante</th>
                                <th style="padding: 12px; text-align: center;">Snippets (Código)</th>
                                <th style="padding: 12px; text-align: center;">Foro (Preg/Resp)</th>
                                <th style="padding: 12px; text-align: center;">Uso IA</th>
                                <th style="padding: 12px; text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Obtener estudiantes y sus métricas
                            $sql_est = "SELECT u.id, u.nombre_completo, u.usuario, 
                                        (SELECT COUNT(*) FROM snippets WHERE usuario_id = u.id) as total_snippets,
                                        (SELECT COUNT(*) FROM foro_preguntas WHERE usuario_id = u.id) as total_preguntas,
                                        (SELECT COUNT(*) FROM foro_respuestas WHERE usuario_id = u.id) as total_respuestas,
                                        (SELECT COUNT(*) FROM uso_ia_logs WHERE usuario_id = u.id) as total_ia
                                        FROM usuarios u 
                                        JOIN grupo_estudiantes ge ON u.id = ge.estudiante_id 
                                        WHERE ge.grupo_id = ?";
                            $stmt_est = $pdo->prepare($sql_est);
                            $stmt_est->execute([$grupo->id]);
                            $estudiantes = $stmt_est->fetchAll();

                            if (empty($estudiantes)): ?>
                                <tr>
                                    <td colspan="5" style="padding: 20px; text-align: center; color: var(--text-secondary);">No hay estudiantes registrados con este código.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($estudiantes as $est): ?>
                                    <tr style="border-bottom: 1px solid var(--glass-border);">
                                        <td style="padding: 12px;">
                                            <strong><?= $est->nombre_completo ?></strong><br>
                                            <span style="font-size: 0.8rem; color: var(--text-secondary);">@<?= $est->usuario ?></span>
                                        </td>
                                        <td style="padding: 12px; text-align: center;"><?= $est->total_snippets ?></td>
                                        <td style="padding: 12px; text-align: center;"><?= $est->total_preguntas ?> / <?= $est->total_respuestas ?></td>
                                        <td style="padding: 12px; text-align: center;">
                                            <span style="background: rgba(99, 102, 241, 0.1); color: var(--secondary-color); padding: 4px 10px; border-radius: 12px; font-weight: 600;">
                                                <?= $est->total_ia ?> usos
                                            </span>
                                        </td>
                                        <td style="padding: 12px; text-align: center;">
                                            <a href="?eliminar_estudiante=<?= $est->id ?>&grupo_id=<?= $grupo->id ?>" 
                                               onclick="return confirm('¿Seguro que quieres eliminar a este estudiante del grupo?')"
                                               style="color: #ef4444; font-size: 0.9rem; text-decoration: none;">
                                               <i class="fas fa-user-minus"></i> Eliminar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal para Crear Grupo -->
<div id="modalGrupo" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; padding: 20px;">
    <div class="glass-card" style="max-width: 400px; width: 100%; background: white;">
        <h3>Crear Nuevo Grupo</h3>
        <form method="POST" style="margin-top: 20px;">
            <div class="form-group">
                <label>Nombre del Grupo / Materia</label>
                <input type="text" name="nombre_grupo" class="form-control" placeholder="Ej: Programación III - A" required>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" name="crear_grupo" class="btn btn-primary" style="flex: 1;">Crear Grupo</button>
                <button type="button" onclick="document.getElementById('modalGrupo').style.display='none'" class="btn btn-outline" style="flex: 1;">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
