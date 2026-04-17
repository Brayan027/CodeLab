<?php
require_once __DIR__ . '/../includes/header.php';

// Solo accesible para docentes/investigadores
if (!is_logged_in() || $_SESSION['rol'] != 'docente') {
    redirect('views/login.php');
}

// 1. Estadísticas Generales
$stats = [
    'usuarios' => $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
    'snippets' => $pdo->query("SELECT COUNT(*) FROM snippets")->fetchColumn(),
    'copias' => $pdo->query("SELECT COUNT(*) FROM metricas_reutilizacion")->fetchColumn(),
    'ia_consultas' => $pdo->query("SELECT COUNT(*) FROM uso_ia_logs WHERE accion = 'ia_explicacion'")->fetchColumn(),
    'forks' => $pdo->query("SELECT COUNT(*) FROM snippets WHERE parent_id IS NOT NULL")->fetchColumn()
];

// 2. Uso de IA vs Colaboración Humana (Logs)
$logs_stmt = $pdo->query("SELECT accion, COUNT(*) as total FROM uso_ia_logs GROUP BY accion");
$logs_data = $logs_stmt->fetchAll();

// 3. Top Alumnos Colaboradores (Más copiados/forkeados)
$top_colab = $pdo->query("
    SELECT u.nombre_completo, COUNT(m.id) as veces_reutilizado 
    FROM usuarios u 
    JOIN snippets s ON u.id = s.usuario_id 
    JOIN metricas_reutilizacion m ON s.id = m.snippet_id 
    GROUP BY u.id 
    ORDER BY veces_reutilizado DESC 
    LIMIT 5
")->fetchAll();

?>

<div class="animate-in" style="margin-top: 40px; margin-bottom: 60px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
        <div>
            <h1 style="margin: 0; font-size: 2.2rem;">Panel de Investigación Académica</h1>
            <p style="color: var(--text-secondary);">Métricas en tiempo real para análisis de tesis y comportamiento del estudiante.</p>
        </div>
        <a href="<?= BASE_URL ?>api/export_data.php" class="btn btn-primary" style="background: #10b981; border: none; padding: 12px 30px;">
            <i class="fas fa-file-excel"></i> Exportar Datos para Excel/SPSS
        </a>
    </div>

    <!-- Grid de Resumen -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
        <div class="glass-card" style="text-align: center; border-bottom: 4px solid var(--primary-color);">
            <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700;">Estudiantes</div>
            <div style="font-size: 2.5rem; font-weight: 800; margin: 10px 0;"><?= $stats['usuarios'] ?></div>
        </div>
        <div class="glass-card" style="text-align: center; border-bottom: 4px solid #f59e0b;">
            <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700;">Consultas IA</div>
            <div style="font-size: 2.5rem; font-weight: 800; margin: 10px 0;"><?= $stats['ia_consultas'] ?></div>
        </div>
        <div class="glass-card" style="text-align: center; border-bottom: 4px solid #10b981;">
            <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700;">Código Reutilizado</div>
            <div style="font-size: 2.5rem; font-weight: 800; margin: 10px 0;"><?= $stats['copias'] ?></div>
        </div>
        <div class="glass-card" style="text-align: center; border-bottom: 4px solid #8b5cf6;">
            <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700;">Forks (Evolución)</div>
            <div style="font-size: 2.5rem; font-weight: 800; margin: 10px 0;"><?= $stats['forks'] ?></div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <!-- Distribución de Actividades -->
        <div class="glass-card">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-chart-pie" style="color: var(--primary-color);"></i> Distribución de Actividades</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid var(--glass-border);">
                        <th style="padding: 10px;">Acción</th>
                        <th style="padding: 10px;">Frecuencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs_data as $l): ?>
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.02);">
                            <td style="padding: 10px; font-weight: 500;"><?= ucwords(str_replace('_', ' ', $l->accion)) ?></td>
                            <td style="padding: 10px;"><span style="background: rgba(59,130,246,0.1); color: var(--primary-color); padding: 2px 10px; border-radius: 10px; font-weight: bold;"><?= $l->total ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Top Estudiantes Colaborativos -->
        <div class="glass-card">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-medal" style="color: #f59e0b;"></i> Top Estudiantes Influyentes</h3>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 15px;">Estudiantes cuyo código ha sido más reutilizado por otros compañeros.</p>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($top_colab as $i => $u): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f8fafc; border-radius: 12px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-weight: 800; color: #94a3b8;">#<?= $i+1 ?></span>
                            <strong><?= $u->nombre_completo ?></strong>
                        </div>
                        <span style="font-weight: 700; color: #10b981;"><?= $u->veces_reutilizado ?> reutilizaciones</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Logs de Actividad Crudos -->
    <div class="glass-card" style="margin-top: 30px;">
        <h3 style="margin-bottom: 20px;"><i class="fas fa-history"></i> Registro Detallado (Últimos 20 eventos)</h3>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <tr style="background: #f1f5f9; text-align: left;">
                    <th style="padding: 12px;">Usuario</th>
                    <th style="padding: 12px;">Acción</th>
                    <th style="padding: 12px;">Contexto</th>
                    <th style="padding: 12px;">Fecha</th>
                </tr>
                <?php
                $raw_logs = $pdo->query("SELECT l.*, u.usuario FROM uso_ia_logs l JOIN usuarios u ON l.usuario_id = u.id ORDER BY l.fecha DESC LIMIT 20")->fetchAll();
                foreach ($raw_logs as $rl): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px;">@<?= $rl->usuario ?></td>
                        <td style="padding: 10px;"><code><?= $rl->accion ?></code></td>
                        <td style="padding: 10px;"><?= $rl->titulo_conctexto ?></td>
                        <td style="padding: 10px; color: var(--text-secondary);"><?= $rl->fecha ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
