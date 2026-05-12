<?php
require_once __DIR__ . '/../includes/header.php';

// Solo accesible para docentes/investigadores
if (!is_logged_in() || $_SESSION['rol'] != 'docente') {
    redirect('views/login.php');
}

// --- CONSULTAS PARA MÉTRICAS DE INVESTIGACIÓN ---

// ObjE1: Interacción en Foro (Colaboración)
$total_preguntas = $pdo->query("SELECT COUNT(*) FROM foro_preguntas")->fetchColumn();
$total_respuestas = $pdo->query("SELECT COUNT(*) FROM foro_respuestas")->fetchColumn();
$total_comentarios = $pdo->query("SELECT COUNT(*) FROM foro_respuesta_comentarios")->fetchColumn();
$total_votos = $pdo->query("SELECT COUNT(*) FROM foro_votos")->fetchColumn();

// ObjE2: Reutilización y Eficiencia
$snippets_reutilizados = $pdo->query("SELECT COUNT(DISTINCT snippet_id) FROM metricas_reutilizacion")->fetchColumn();
$total_reutilizaciones = $pdo->query("SELECT COUNT(*) FROM metricas_reutilizacion")->fetchColumn();
$top_snippets = $pdo->query("
    SELECT s.titulo, COUNT(m.id) as total 
    FROM snippets s JOIN metricas_reutilizacion m ON s.id = m.snippet_id 
    GROUP BY s.id ORDER BY total DESC LIMIT 5
")->fetchAll();

// ObjE3: Autonomía vs IA (Métrica Tesis)
// Comparamos Snippets Creados (Autónomo) vs Logs de IA (Asistencia)
$original_snippets = $pdo->query("SELECT COUNT(*) FROM snippets WHERE parent_id IS NULL")->fetchColumn();
$ia_consultas = $pdo->query("SELECT COUNT(*) FROM uso_ia_logs WHERE accion = 'ia_explicacion'")->fetchColumn();
$ratio_autonomia = ($original_snippets + $ia_consultas > 0) ? round(($original_snippets / ($original_snippets + $ia_consultas)) * 100, 1) : 0;

// ObjE4: Engagement Mensual (Accesibilidad)
$active_days = $pdo->query("SELECT DATE(fecha) as dia, COUNT(*) as total FROM uso_ia_logs GROUP BY dia ORDER BY dia DESC LIMIT 15")->fetchAll();
?>

<div class="teacher-container animate-in">
    <?php require_once __DIR__ . '/../includes/teacher_sidebar.php'; ?>

    <main class="teacher-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1 style="margin: 0; font-size: 2.2rem;"><i class="fas fa-microscope" style="color: var(--secondary-color);"></i> Métricas de Investigación</h1>
                <p style="color: var(--text-secondary);">Análisis centrado en los objetivos de la tesis ObjE1 - ObjE4.</p>
            </div>
            <a href="<?= BASE_URL ?>api/export_data.php?start=<?= $_GET['start'] ?? '' ?>&end=<?= $_GET['end'] ?? '' ?>" class="btn btn-primary" style="background: #10b981; border: none; padding: 12px 25px;">
                <i class="fas fa-file-export"></i> Exportar Dataset (CSV)
            </a>
        </div>

        <!-- SECCIÓN 1: ObjE1 - Colaboración y Resolución de Problemas -->
        <div class="glass-card" style="margin-bottom: 30px;">
            <h3 style="margin-bottom: 20px; color: var(--primary-color); border-bottom: 2px solid rgba(59,130,246,0.1); padding-bottom: 10px;">
                ObjE1: Impacto de la Colaboración y Foro
            </h3>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                <div style="text-align: center; padding: 15px; background: #f8fafc; border-radius: 12px;">
                    <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase;">Preguntas Foro</div>
                    <div style="font-size: 1.8rem; font-weight: 800;"><?= $total_preguntas ?></div>
                </div>
                <div style="text-align: center; padding: 15px; background: #f8fafc; border-radius: 12px;">
                    <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase;">Respuestas</div>
                    <div style="font-size: 1.8rem; font-weight: 800;"><?= $total_respuestas ?></div>
                </div>
                <div style="text-align: center; padding: 15px; background: #f8fafc; border-radius: 12px;">
                    <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase;">Interacciones (Com.)</div>
                    <div style="font-size: 1.8rem; font-weight: 800;"><?= $total_comentarios ?></div>
                </div>
                <div style="text-align: center; padding: 15px; background: #f8fafc; border-radius: 12px;">
                    <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase;">Votos/Validación</div>
                    <div style="font-size: 1.8rem; font-weight: 800;"><?= $total_votos ?></div>
                </div>
            </div>
            <p style="margin-top: 15px; font-size: 0.85rem; color: var(--text-secondary); line-height: 1.4;">
                <i class="fas fa-info-circle"></i> Este objetivo mide cómo la interacción social reduce la brecha de dudas técnicas sin depender exclusivamente de la IA.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
            <!-- SECCIÓN 2: ObjE2 - Reutilización de Código -->
            <div class="glass-card">
                <h3 style="margin-bottom: 20px; color: #10b981;">ObjE2: Eficiencia por Reutilización</h3>
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 25px;">
                    <div style="width: 100px; height: 100px; border-radius: 50%; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center; border: 4px solid #10b981;">
                        <span style="font-size: 1.5rem; font-weight: 800; color: #059669;"><?= $total_reutilizaciones ?></span>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: var(--text-primary);">Total de Copias/Reuso</div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">Snippets distintos: <?= $snippets_reutilizados ?></div>
                    </div>
                </div>
                <h4 style="font-size: 0.9rem; margin-bottom: 10px;">Snippets Más Reutilizados:</h4>
                <?php foreach ($top_snippets as $ts): ?>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 8px; padding: 8px; background: #f1f5f9; border-radius: 8px;">
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;"><?= htmlspecialchars($ts->titulo) ?></span>
                        <strong style="color: #10b981;"><?= $ts->total ?> veces</strong>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- SECCIÓN 3: ObjE3 - Autonomía y Dependencia IA -->
            <div class="glass-card">
                <h3 style="margin-bottom: 20px; color: #f59e0b;">ObjE3: Índice de Autonomía</h3>
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="font-size: 3rem; font-weight: 800; color: #d97706;"><?= $ratio_autonomia ?>%</div>
                    <div style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 600;">Código Propio vs Ayuda IA</div>
                </div>
                <div style="height: 10px; background: #eee; border-radius: 5px; overflow: hidden; margin-bottom: 20px;">
                    <div style="width: <?= $ratio_autonomia ?>%; height: 100%; background: #f59e0b;"></div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.8rem;">
                    <div style="padding: 10px; border-left: 3px solid #f59e0b;">
                        <strong><?= $original_snippets ?></strong> Snippets Autónomos
                    </div>
                    <div style="padding: 10px; border-left: 3px solid #cbd5e1;">
                        <strong><?= $ia_consultas ?></strong> Consultas IA Explica
                    </div>
                </div>
                <p style="margin-top: 15px; font-size: 0.8rem; color: var(--text-secondary);">
                    *Un porcentaje alto indica que los estudiantes están creando código original sin depender de explicaciones constantes.
                </p>
            </div>
        </div>

        <!-- SECCIÓN NUEVA: Comparativa de Grupos (A/B Testing) -->
        <div class="glass-card" style="margin-bottom: 30px; border-left: 5px solid var(--secondary-color);">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-vial"></i> Comparativa Grupos de Investigación</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <?php
                $grupos_resumen = $pdo->query("
                    SELECT g.nombre, COUNT(u.id) as total_alumnos,
                    (SELECT COUNT(*) FROM snippets JOIN usuarios ON snippets.usuario_id = usuarios.id WHERE usuarios.investigacion_grupo_id = g.id) as snippets_creados
                    FROM investigacion_grupos g
                    LEFT JOIN usuarios u ON g.id = u.investigacion_grupo_id
                    GROUP BY g.id
                ")->fetchAll();
                
                foreach ($grupos_resumen as $gr):
                ?>
                    <div style="padding: 20px; background: rgba(99, 102, 241, 0.05); border-radius: 15px; border: 1px solid rgba(99, 102, 241, 0.1);">
                        <h4 style="color: var(--secondary-color); margin-bottom: 10px;"><?= $gr->nombre ?></h4>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span>Estudiantes:</span>
                            <strong><?= $gr->total_alumnos ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Snippets Propios:</span>
                            <strong><?= $gr->snippets_creados ?></strong>
                        </div>
                        <div style="margin-top: 10px; font-size: 0.75rem; color: var(--text-secondary);">
                            Prod. Media: <?= $gr->total_alumnos > 0 ? round($gr->snippets_creados / $gr->total_alumnos, 2) : 0 ?> snippets/alumno
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- SECCIÓN 4: Evolución Temporal Detallada (Líneas de Tiempo) -->
        <div class="glass-card" style="margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;"><i class="fas fa-wave-square" style="color: var(--secondary-color);"></i> Flujo de Actividad Diaria</h3>
                
                <!-- Filtro de Fechas -->
                <form method="GET" style="display: flex; gap: 10px; align-items: center; background: #f1f5f9; padding: 8px 15px; border-radius: 12px;">
                    <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary);">Desde:</span>
                    <input type="date" name="start" value="<?= $_GET['start'] ?? date('Y-m-d', strtotime('-14 days')) ?>" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; font-size: 0.85rem;">
                    <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary);">Hasta:</span>
                    <input type="date" name="end" value="<?= $_GET['end'] ?? date('Y-m-d') ?>" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; font-size: 0.85rem;">
                    <button type="submit" class="btn btn-primary" style="padding: 5px 15px; font-size: 0.85rem;">Filtrar</button>
                </form>
            </div>

            <div style="height: 350px;">
                <canvas id="researchTrendChart"></canvas>
            </div>
            <?php
            // Lógica de fechas dinámica
            $start = $_GET['start'] ?? date('Y-m-d', strtotime('-14 days'));
            $end = $_GET['end'] ?? date('Y-m-d');
            
            $period = new DatePeriod(
                new DateTime($start),
                new DateInterval('P1D'),
                (new DateTime($end))->modify('+1 day')
            );
            
            $dates = [];
            foreach ($period as $date) {
                $dates[] = $date->format("Y-m-d");
            }

            $metrics_data = [
                'ia' => [], 'reuso' => [], 'snippets' => [], 'rutas' => [],
                'preguntas' => [], 'respuestas' => []
            ];

            foreach ($dates as $date) {
                // IA
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM uso_ia_logs WHERE DATE(fecha) = ?");
                $stmt->execute([$date]);
                $metrics_data['ia'][] = $stmt->fetchColumn();

                // Reuso
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM metricas_reutilizacion WHERE DATE(fecha_copia) = ?");
                $stmt->execute([$date]);
                $metrics_data['reuso'][] = $stmt->fetchColumn();

                // Snippets
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM snippets WHERE DATE(fecha_creacion) = ?");
                $stmt->execute([$date]);
                $metrics_data['snippets'][] = $stmt->fetchColumn();

                // Rutas
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM rutas WHERE DATE(fecha_creacion) = ?");
                $stmt->execute([$date]);
                $metrics_data['rutas'][] = $stmt->fetchColumn();

                // Preguntas Foro
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM foro_preguntas WHERE DATE(fecha_creacion) = ?");
                $stmt->execute([$date]);
                $metrics_data['preguntas'][] = $stmt->fetchColumn();

                // Respuestas Foro
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM foro_respuestas WHERE DATE(fecha_respuesta) = ?");
                $stmt->execute([$date]);
                $metrics_data['respuestas'][] = $stmt->fetchColumn();
            }
            ?>
            <script>
                document.addEventListener("DOMContentLoaded", () => {
                    const ctx = document.getElementById('researchTrendChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: <?= json_encode(array_map(function($d){ return date('d/m', strtotime($d)); }, $dates)) ?>,
                            datasets: [
                                {
                                    label: 'Consultas IA',
                                    data: <?= json_encode($metrics_data['ia']) ?>,
                                    borderColor: '#6366f1',
                                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                },
                                {
                                    label: 'Código Reutilizado',
                                    data: <?= json_encode($metrics_data['reuso']) ?>,
                                    borderColor: '#10b981',
                                    backgroundColor: 'transparent',
                                    tension: 0.4
                                },
                                {
                                    label: 'Snippets Creados',
                                    data: <?= json_encode($metrics_data['snippets']) ?>,
                                    borderColor: '#f59e0b',
                                    backgroundColor: 'transparent',
                                    tension: 0.4
                                },
                                {
                                    label: 'Preguntas Foro',
                                    data: <?= json_encode($metrics_data['preguntas']) ?>,
                                    borderColor: '#ef4444',
                                    backgroundColor: 'transparent',
                                    tension: 0.4,
                                    borderDash: [5, 5]
                                },
                                {
                                    label: 'Respuestas Foro',
                                    data: <?= json_encode($metrics_data['respuestas']) ?>,
                                    borderColor: '#3b82f6',
                                    backgroundColor: 'transparent',
                                    tension: 0.4,
                                    borderDash: [5, 5]
                                }
                            ]
                        },
                        options: {
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: { position: 'top', labels: { usePointStyle: true, padding: 20 } }
                            },
                            scales: {
                                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                                x: { grid: { display: false } }
                            }
                        }
                    });
                });
            </script>
        </div>

        <!-- SECCIÓN DE GESTIÓN DE GRUPOS DE INVESTIGACIÓN -->
        <div class="glass-card" style="margin-top: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3><i class="fas fa-users-cog"></i> Configurar Grupos de Investigación</h3>
                <button onclick="document.getElementById('modalResGroup').style.display='flex'" class="btn btn-outline" style="font-size: 0.8rem; padding: 5px 15px;">+ Crear Nuevo Tipo de Grupo</button>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 20px;">Estos son los grupos que usas para clasificar a los alumnos (ej: Experimental vs Control).</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                <?php 
                $all_res_groups = $pdo->query("SELECT * FROM investigacion_grupos")->fetchAll();
                foreach ($all_res_groups as $rg): 
                ?>
                    <div style="padding: 15px; background: #fff; border: 1px solid var(--glass-border); border-radius: 12px; border-left: 4px solid var(--primary-color);">
                        <strong style="display: block; margin-bottom: 5px;"><?= htmlspecialchars($rg->nombre) ?></strong>
                        <p style="font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($rg->descripcion) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<!-- Modal para Crear Grupo de Investigación -->
<div id="modalResGroup" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:3000; align-items:center; justify-content:center; padding: 20px;">
    <div class="glass-card" style="max-width: 400px; width: 100%; background: white;">
        <h3>Nuevo Grupo de Investigación</h3>
        <form method="POST" style="margin-top: 20px;" action="<?= BASE_URL ?>api/create_research_group.php">
            <div class="form-group">
                <label>Nombre del Grupo</label>
                <input type="text" name="nombre" class="form-control" placeholder="Ej: Grupo con IA Activa" required>
            </div>
            <div class="form-group">
                <label>Descripción / Propósito</label>
                <textarea name="descripcion" class="form-control" rows="3" placeholder="Define qué diferencia a este grupo..."></textarea>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Crear Grupo</button>
                <button type="button" onclick="document.getElementById('modalResGroup').style.display='none'" class="btn btn-outline" style="flex: 1;">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
