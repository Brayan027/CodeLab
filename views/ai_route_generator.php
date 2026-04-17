<?php
require_once __DIR__ . '/../includes/header.php';
if (!is_logged_in()) redirect('views/login.php');

// Lógica para guardar la ruta generada (vía POST final)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_ai_route'])) {
    $data = json_decode($_POST['route_json'], true);
    if ($data) {
        $stmt = $pdo->prepare("INSERT INTO rutas (titulo, descripcion, creador_id) VALUES (?, ?, ?)");
        $stmt->execute([$data['titulo'], $data['descripcion'], $_SESSION['user_id']]);
        $ruta_id = $pdo->lastInsertId();

        foreach ($data['pasos'] as $i => $paso) {
            // Convertir formato plano de IA a bloques dinámicos JSON
            $bloques = [];
            if (!empty($paso['contenido'])) $bloques[] = ['type' => 'text', 'value' => $paso['contenido']];
            if (!empty($paso['codigo'])) $bloques[] = ['type' => 'code', 'value' => $paso['codigo']];
            
            $contenido_json = json_encode($bloques);

            $stmt = $pdo->prepare("INSERT INTO pasos_ruta (ruta_id, titulo, contenido, orden) VALUES (?, ?, ?, ?)");
            $stmt->execute([$ruta_id, $paso['titulo'], $contenido_json, $i + 1]);
        }
        redirect('views/route_detail.php?id=' . $ruta_id);
    }
}
?>

<div class="animate-in" style="max-width: 800px; margin: 40px auto;">
    <div class="glass-card" style="border-top: 5px solid var(--secondary-color);">
        <h2 style="display: flex; align-items: center; gap: 15px;">
            <i class="fas fa-magic" style="color: var(--secondary-color);"></i> 
            Generador de Rutas con IA
        </h2>
        <p style="color: var(--text-secondary); margin-bottom: 25px;">Escribe un tema y deja que la Inteligencia Artificial diseñe tu plan de aprendizaje.</p>

        <div id="setup-view">
            <div class="form-group">
                <label>¿Qué quieres aprender hoy?</label>
                <input type="text" id="topicInput" class="form-control" placeholder="Ej: Fundamentos de C++, Animaciones en CSS, Laravel desde cero..." style="font-size: 1.1rem; padding: 15px;">
            </div>
            <button onclick="generateWithAI()" class="btn btn-primary" style="width: 100%; padding: 15px; font-weight: bold; background: linear-gradient(45deg, var(--primary-color), var(--secondary-color)); border: none;">
                <i class="fas fa-sparkles"></i> Generar Ruta Mágica
            </button>
        </div>

        <!-- Vista de Carga -->
        <div id="loading-view" style="display: none; text-align: center; padding: 50px 0;">
            <i class="fas fa-robot fa-spin" style="font-size: 4rem; color: var(--secondary-color); margin-bottom: 20px;"></i>
            <h3>El Profe IA está diseñando tu ruta personalizada...</h3>
            <p style="color: var(--text-secondary);">Esto puede tardar unos segundos, estamos estructurando los mejores pasos para ti.</p>
        </div>

        <!-- Vista de Previsualización -->
        <div id="preview-view" style="display: none; margin-top: 30px;">
            <div style="padding: 20px; background: rgba(59, 130, 246, 0.05); border-radius: 12px; border: 1px solid var(--glass-border); margin-bottom: 30px;">
                <h3 id="preview-title" style="margin-bottom: 10px;"></h3>
                <p id="preview-desc" style="color: var(--text-secondary);"></p>
            </div>
            
            <div id="preview-steps" style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 30px;">
                <!-- Los pasos se cargarán aquí -->
            </div>

            <form method="POST">
                <input type="hidden" name="route_json" id="routeJsonInput">
                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="location.reload()" class="btn btn-outline" style="flex: 1;">Descartar</button>
                    <button type="submit" name="save_ai_route" class="btn btn-primary" style="flex: 2;">Confirmar y Publicar Ruta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function generateWithAI() {
    const topic = document.getElementById('topicInput').value;
    if (!topic) return alert('Por favor, escribe un tema.');

    document.getElementById('setup-view').style.display = 'none';
    document.getElementById('loading-view').style.display = 'block';

    const formData = new FormData();
    formData.append('action', 'generate_route');
    formData.append('titulo', topic);

    fetch('<?= BASE_URL ?>api/ai_assist.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            alert('Error: ' + data.error);
            location.reload();
            return;
        }

        // Mostrar previsualización
        document.getElementById('loading-view').style.display = 'none';
        document.getElementById('preview-view').style.display = 'block';
        
        document.getElementById('preview-title').innerText = data.titulo;
        document.getElementById('preview-desc').innerText = data.descripcion;
        document.getElementById('routeJsonInput').value = JSON.stringify(data);

        let stepsHtml = '';
        data.pasos.forEach((p, i) => {
            stepsHtml += `
                <div class="glass-card" style="padding: 15px; border-left: 4px solid var(--secondary-color);">
                    <div style="font-weight: bold; font-size: 0.8rem; color: var(--secondary-color); margin-bottom: 5px;">PASO ${i+1}</div>
                    <div style="font-weight: 600; margin-bottom: 5px;">${p.titulo}</div>
                    <div style="font-size: 0.85rem; color: var(--text-secondary);">${p.contenido.substring(0, 100)}...</div>
                </div>
            `;
        });
        document.getElementById('preview-steps').innerHTML = stepsHtml;
    })
    .catch(err => {
        alert('Error crítico de conexión.');
        location.reload();
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
