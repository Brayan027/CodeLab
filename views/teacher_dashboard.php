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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="animate-in" style="margin-top: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <h1>Panel de Control Docente</h1>
        <div style="display: flex; gap: 10px;">
            <a href="<?= BASE_URL ?>views/research_dashboard.php" class="btn btn-outline" style="border-color: #10b981; color: #10b981; font-weight: 700;">
                <i class="fas fa-microscope"></i> Panel de Investigación (Tesis)
            </a>
            <button onclick="document.getElementById('modalGrupo').style.display='flex'" class="btn btn-primary">
                <i class="fas fa-plus"></i> Crear Nuevo Grupo
            </button>
        </div>
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
                                <th style="padding: 12px; text-align: center;">Rutas</th>
                                <th style="padding: 12px; text-align: center;">Foro (Preg/Resp)</th>
                                <th style="padding: 12px; text-align: center;">Uso IA</th>
                                <th style="padding: 12px; text-align: center;" title="Veces que ha copiado código de otros">Código Reutilizado</th>
                                <th style="padding: 12px; text-align: center;" title="Evaluación positiva a la IA">Likes IA</th>
                                <th style="padding: 12px; text-align: center;" title="Análisis automatizado basado en métricas">Perfil de Desempeño</th>
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
                                        (SELECT COUNT(*) FROM uso_ia_logs WHERE usuario_id = u.id) as total_ia,
                                        (SELECT COUNT(*) FROM metricas_reutilizacion WHERE usuario_id = u.id) as total_copias,
                                        (SELECT SUM(fue_util) FROM evaluacion_ia WHERE usuario_id = u.id) as likes_ia,
                                        (SELECT COUNT(*) FROM rutas WHERE creador_id = u.id) as total_rutas,
                                        (SELECT COUNT(*) FROM ruta_paso_votos v JOIN pasos_ruta p ON v.paso_id = p.id JOIN rutas r ON p.ruta_id = r.id WHERE r.creador_id = u.id) as votos_rutas
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
                                        <td style="padding: 12px; text-align: center;">
                                            <span style="font-weight: bold; color: var(--primary-color);"><?= $est->total_rutas ?></span><br>
                                            <span style="font-size: 0.75rem; color: var(--text-secondary);"><?= $est->votos_rutas ?> likes</span>
                                        </td>
                                        <td style="padding: 12px; text-align: center;"><?= $est->total_preguntas ?> / <?= $est->total_respuestas ?></td>
                                        <td style="padding: 12px; text-align: center;">
                                            <span style="background: rgba(99, 102, 241, 0.1); color: var(--secondary-color); padding: 4px 10px; border-radius: 12px; font-weight: 600;">
                                                <?= $est->total_ia ?> usos
                                            </span>
                                        </td>
                                        <td style="padding: 12px; text-align: center;">
                                            <strong><?= $est->total_copias ?: 0 ?></strong> <i class="far fa-copy" style="color:var(--text-secondary); font-size: 0.8rem;"></i>
                                        </td>
                                        <td style="padding: 12px; text-align: center;">
                                            <span style="color:#10b981; font-weight:bold;"><?= $est->likes_ia ?: 0 ?> <i class="fas fa-thumbs-up"></i></span>
                                        </td>
                                        <td style="padding: 12px; text-align: center;">
                                            <?php
                                            // ALGORITMO DE ANÁLISIS VERÍDICO (Basado 100% en BD)
                                            $perfil = "Equilibrado";
                                            $color = "#6366f1";
                                            $icon = "fa-user-check";
                                            $desc = "Participación regular y balanceada.";

                                            $total_actividad = $est->total_snippets + $est->total_preguntas + $est->total_respuestas + $est->total_rutas;
                                            
                                            if ($total_actividad == 0 && $est->total_ia == 0) {
                                                $perfil = "Inactivo";
                                                $color = "#94a3b8";
                                                $icon = "fa-user-slash";
                                                $desc = "Sin actividad en la plataforma.";
                                            } elseif ($est->total_snippets > 2 && $est->total_ia == 0 && $est->total_respuestas == 0) {
                                                $perfil = "Caja Negra";
                                                $color = "#f59e0b"; // Naranja
                                                $icon = "fa-user-secret";
                                                $desc = "Aporta código pero no interactúa con el ecosistema (posible IA externa).";
                                            } elseif ($est->total_ia > ($est->total_snippets * 2) && $est->total_ia > 2) {
                                                $perfil = "Dependiente IA";
                                                $color = "#f43f5e";
                                                $icon = "fa-robot";
                                                $desc = "Uso de IA superior a la producción de código propio.";
                                            } elseif ($est->total_rutas >= 1 && $est->votos_rutas >= 1) {
                                                $perfil = "Mentor Pro";
                                                $color = "#f59e0b";
                                                $icon = "fa-graduation-cap";
                                                $desc = "Genera contenido educativo valorado por sus pares.";
                                            } elseif ($est->total_snippets >= 1 && $est->total_ia <= $est->total_snippets) {
                                                $perfil = "Autónomo";
                                                $color = "#10b981";
                                                $icon = "fa-user-ninja";
                                                $desc = "Alta capacidad de resolución con poca asistencia externa.";
                                            } elseif (($est->total_preguntas + $est->total_respuestas) > 5) {
                                                $perfil = "Colaborador";
                                                $color = "#06b6d4";
                                                $icon = "fa-comments";
                                                $desc = "Pilar de la comunidad en el foro.";
                                            } elseif ($est->total_copias > $est->total_snippets) {
                                                $perfil = "Recolector";
                                                $color = "#8b5cf6";
                                                $icon = "fa-box-open";
                                                $desc = "Alta tasa de reutilización de código de terceros.";
                                            }
                                            ?>
                                            <div style="background: <?= $color ?>22; color: <?= $color ?>; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: bold; display: inline-flex; align-items: center; gap: 6px; border: 1px solid <?= $color ?>44;" title="<?= $desc ?>">
                                                <i class="fas <?= $icon ?>"></i> <?= $perfil ?>
                                            </div>
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

                <!-- SECCIÓN DE GRÁFICOS ANALÍTICOS (ObjE2 y ObjE3) -->
                <?php if (!empty($estudiantes)): 
                    // Preparar datos para los gráficos
                    $nombres = json_encode(array_column($estudiantes, 'nombre_completo'));
                    $uso_ia = json_encode(array_column($estudiantes, 'total_ia'));
                    $snippets = json_encode(array_column($estudiantes, 'total_snippets'));
                    $rutas = json_encode(array_column($estudiantes, 'total_rutas'));
                    $copias = json_encode(array_column($estudiantes, 'total_copias'));
                    
                    // Preparar datos temporales (IA en la última semana)
                    $estudiante_ids = array_column($estudiantes, 'id');
                    $ids_str = implode(',', $estudiante_ids);
                    $sql_time = "SELECT DATE(fecha) as dia, COUNT(*) as cantidad FROM uso_ia_logs WHERE usuario_id IN ($ids_str) AND fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(fecha) ORDER BY dia ASC";
                    $stmt_t = $pdo->query($sql_time);
                    $time_rows = $stmt_t->fetchAll();
                    $time_labels = json_encode(array_column($time_rows, 'dia'));
                    $time_data = json_encode(array_column($time_rows, 'cantidad'));
                ?>
                    <h3 style="margin-top: 40px; margin-bottom: 20px;"><i class="fas fa-chart-pie" style="color: var(--primary-color);"></i> Analíticas de este Grupo</h3>
                    
                    <?php 
                        // Análisis Agregado del Grupo (IA Manual)
                        $perfiles_conteo = [
                            'Inactivo' => 0,
                            'Dependiente IA' => 0,
                            'Mentor Pro' => 0,
                            'Autónomo' => 0,
                            'Colaborador' => 0,
                            'Recolector' => 0,
                            'Equilibrado' => 0
                        ];

                        foreach ($estudiantes as $est_analisis) {
                            $total_act = $est_analisis->total_snippets + $est_analisis->total_preguntas + $est_analisis->total_respuestas + $est_analisis->total_rutas;
                            if ($total_act == 0 && $est_analisis->total_ia == 0) $perfiles_conteo['Inactivo']++;
                            elseif ($est_analisis->total_ia > ($est_analisis->total_snippets * 2) && $est_analisis->total_ia > 2) $perfiles_conteo['Dependiente IA']++;
                            elseif ($est_analisis->total_rutas >= 1 && $est_analisis->votos_rutas >= 1) $perfiles_conteo['Mentor Pro']++;
                            elseif ($est_analisis->total_snippets >= 1 && $est_analisis->total_ia <= $est_analisis->total_snippets) $perfiles_conteo['Autónomo']++;
                            elseif (($est_analisis->total_preguntas + $est_analisis->total_respuestas) > 5) $perfiles_conteo['Colaborador']++;
                            elseif ($est_analisis->total_copias > $est_analisis->total_snippets) $perfiles_conteo['Recolector']++;
                            else $perfiles_conteo['Equilibrado']++;
                        }

                        $total_est = count($estudiantes);
                        $dep_ia_perc = round(($perfiles_conteo['Dependiente IA'] / $total_est) * 100);
                        $auto_perc = round(($perfiles_conteo['Autónomo'] / $total_est) * 100);

                        // Calcular altura dinámica: si son 40 estudiantes, el gráfico debe ser alto para no aplastarse
                        $px_por_estudiante = 45; 
                        $chart_height = max(300, count($estudiantes) * $px_por_estudiante); 
                    ?>

                    <!-- Panel de Diagnóstico de Grupo -->
                    <div class="glass-card" style="margin-bottom: 30px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(16, 185, 129, 0.05)); border-left: 5px solid var(--secondary-color);">
                        <h4 style="margin-bottom: 15px;"><i class="fas fa-stethoscope"></i> Diagnóstico del Ecosistema</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                            <div style="text-align: center; padding: 15px; border-right: 1px solid var(--glass-border);">
                                <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);"><?= $auto_perc ?>%</div>
                                <div style="font-size: 0.8rem; color: var(--text-secondary);">Índice de Autonomía</div>
                            </div>
                            <div style="text-align: center; padding: 15px; border-right: 1px solid var(--glass-border);">
                                <div style="font-size: 1.5rem; font-weight: bold; color: #f43f5e;"><?= $dep_ia_perc ?>%</div>
                                <div style="font-size: 0.8rem; color: var(--text-secondary);">Riesgo de Dependencia IA</div>
                            </div>
                            <div style="padding: 10px;">
                                <p style="font-size: 0.85rem; line-height: 1.4;">
                                    <strong>Resumen:</strong> 
                                    <?php if ($dep_ia_perc > 30): ?>
                                        Se detecta un uso crítico de la IA. Se recomienda asignar retos sin asistencia digital para fortalecer la autonomía.
                                    <?php elseif ($auto_perc > 40): ?>
                                        Grupo con alta capacidad resolutiva. Se pueden asignar proyectos de mayor complejidad técnica.
                                    <?php else: ?>
                                        Ecosistema equilibrado. La mayoría de los estudiantes combinan herramientas de IA con lógica propia.
                                    <?php endif; ?>
                                </p>
                                <button onclick="document.getElementById('modalCriterios').style.display='flex'" style="background:none; border:none; color:var(--primary-color); font-size:0.75rem; cursor:pointer; padding:0; text-decoration:underline;">Ver criterios de análisis verídico</button>
                            </div>
                        </div>
                    </div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr; gap: 30px;">
                        
                        <!-- Panel de Comparación de Alumnos (HORIZONTAL PARA MUCHOS ALUMNOS) -->
                        <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; border: 1px solid var(--glass-border); overflow-x: auto;">
                            <h4 style="text-align: center; margin-bottom: 20px; font-size: 1.1rem; color: var(--text-primary);">Producción vs Dependencia de IA por Estudiante</h4>
                            <div style="height: <?= $chart_height ?>px; min-width: 600px;">
                                <canvas id="chart_compare_<?= $grupo->id ?>"></canvas>
                            </div>
                        </div>

                        <!-- Panel de Actividad en el Tiempo -->
                        <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; border: 1px solid var(--glass-border);">
                            <h4 style="text-align: center; margin-bottom: 20px; font-size: 1.1rem; color: var(--text-primary);">Uso Crítico de la IA (Últimos 7 Días)</h4>
                            <div style="height: 350px;">
                                <canvas id="chart_time_<?= $grupo->id ?>"></canvas>
                            </div>
                        </div>
                    </div>

                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            // Gráfico de Barras HORIZONTALES: Comparación de estudiantes sin aglomerarse
                            new Chart(document.getElementById('chart_compare_<?= $grupo->id ?>'), {
                                type: 'bar',
                                data: {
                                    labels: <?= $nombres ?>,
                                    datasets: [
                                        {
                                            label: 'Rutas Creadas',
                                            data: <?= $rutas ?>,
                                            backgroundColor: 'rgba(245, 158, 11, 0.8)',
                                            borderColor: 'rgb(245, 158, 11)',
                                            borderWidth: 1
                                        },
                                        {
                                            label: 'Snippets Creados',
                                            data: <?= $snippets ?>,
                                            backgroundColor: 'rgba(56, 189, 248, 0.8)',
                                            borderColor: 'rgb(56, 189, 248)',
                                            borderWidth: 1
                                        },
                                        {
                                            label: 'Consultas a IA',
                                            data: <?= $uso_ia ?>,
                                            backgroundColor: 'rgba(99, 102, 241, 0.8)',
                                            borderColor: 'rgb(99, 102, 241)',
                                            borderWidth: 1
                                        },
                                        {
                                            label: 'Códigos Reutilizados (Copias)',
                                            data: <?= $copias ?>,
                                            backgroundColor: 'rgba(16, 185, 129, 0.8)',
                                            borderColor: 'rgb(16, 185, 129)',
                                            borderWidth: 1
                                        }
                                    ]
                                },
                                options: { 
                                    indexAxis: 'y', // Transforma las barras a horizontales para lectura infinita
                                    maintainAspectRatio: false, // Permite tener una altura dinámica
                                    responsive: true, 
                                    scales: { 
                                        x: { beginAtZero: true, ticks: { precision: 0 } },
                                        y: { ticks: { autoSkip: false } } // Nunca oculta el nombre del estudiante
                                    },
                                    plugins: { legend: { position: 'top' } }
                                }
                            });

                            // Gráfico de Líneas: Evolución Temporal
                            const tLabels = <?= $time_labels ?>;
                            const tData = <?= $time_data ?>;
                            new Chart(document.getElementById('chart_time_<?= $grupo->id ?>'), {
                                type: 'line',
                                data: {
                                    labels: tLabels.length > 0 ? tLabels : ['Sin Datos'],
                                    datasets: [{
                                        label: 'Interacciones Totales con IA',
                                        data: tData.length > 0 ? tData : [0],
                                        fill: true,
                                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                        borderColor: 'rgb(239, 68, 68)',
                                        tension: 0.4,
                                        borderWidth: 3,
                                        pointRadius: 5
                                    }]
                                },
                                options: { 
                                    maintainAspectRatio: false,
                                    responsive: true, 
                                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                                    plugins: { legend: { position: 'top' } }
                                }
                            });
                        });
                    </script>
                <?php endif; ?>
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
<!-- Modal para Criterios de Análisis -->
<div id="modalCriterios" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center; padding: 20px;">
    <div class="glass-card" style="max-width: 500px; width: 100%; background: white;">
        <h3>Criterios de Análisis Verídico</h3>
        <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 20px;">El sistema categoriza a los alumnos en tiempo real cruzando datos de la base de datos:</p>
        <ul style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 10px; padding-left: 20px;">
            <li><strong>Caja Negra:</strong> Aportes > 2 pero 0 interacción con IA interna y foros (Posible IA externa).</li>
            <li><strong>Autónomo:</strong> Producción de código ≥ Consultas a la IA.</li>
            <li><strong>Dependiente IA:</strong> Consultas a la IA > 2x Producción de código.</li>
            <li><strong>Mentor Pro:</strong> Ha creado rutas que han recibido feedback positivo.</li>
            <li><strong>Colaborador:</strong> Alta tasa de respuestas y preguntas en el foro (>5).</li>
            <li><strong>Recolector:</strong> Tasa de copiado de código superior a su creación original.</li>
        </ul>
        <button onclick="document.getElementById('modalCriterios').style.display='none'" class="btn btn-primary" style="margin-top: 20px; width: 100%;">Entendido</button>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
