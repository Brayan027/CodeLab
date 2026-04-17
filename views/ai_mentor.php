<?php
require_once __DIR__ . '/../includes/header.php';
if (!is_logged_in()) redirect('views/login.php');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT titulo, lenguaje, descripcion, codigo FROM snippets WHERE usuario_id = ?");
$stmt->execute([$user_id]);
$snippets = $stmt->fetchAll();

$cantidad_actual = count($snippets);
$has_code = $cantidad_actual > 0;
$contexto_codigo = "";

if ($has_code) {
    foreach ($snippets as $s) {
        $contexto_codigo .= "Lenguaje: {$s->lenguaje} | Título: {$s->titulo}\nCódigo:\n" . substr($s->codigo, 0, 150) . "...\n\n";
    }
}

// Consultar la caché de la IA
$stmtCache = $pdo->prepare("SELECT mentor_data, snippets_count FROM ai_mentor_cache WHERE usuario_id = ?");
$stmtCache->execute([$user_id]);
$cache = $stmtCache->fetch();

$needs_update = false;
$cached_data = null;

if ($cache) {
    $cached_data = json_decode($cache->mentor_data, true);
    if ($cache->snippets_count < $cantidad_actual) {
        $needs_update = true; // El usuario ha subido nuevo código 
    }
} else {
    $needs_update = true; // Nunca se ha generado
}
?>

<div class="animate-in" style="max-width: 900px; margin: 40px auto; padding: 0 15px;">
    
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); width: 70px; height: 70px; border-radius: 20px; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 20px rgba(59,130,246,0.3);">
                <i class="fas fa-brain" style="font-size: 2rem; color: #fff;"></i>
            </div>
            <div>
                <h1 style="font-size: 2rem; margin-bottom: 5px; color: var(--text-primary);">Mentor IA <span style="font-size: 0.8rem; background: var(--secondary-color); color: #fff; padding: 3px 8px; border-radius: 12px; vertical-align: middle;">BETA</span></h1>
                <p style="color: var(--text-secondary); margin: 0;">Análisis inteligente y personalizado basado en tu repositorio de código.</p>
            </div>
        </div>
        <?php if ($has_code && $cached_data): ?>
            <div>
                <?php if ($needs_update): ?>
                    <button id="btn-actualizar" class="btn btn-primary" onclick="iniciarAnalisis(true)"><i class="fas fa-sync-alt"></i> Hay nuevo código. ¡Actualizar!</button>
                <?php else: ?>
                    <span style="color: var(--text-secondary); font-size: 0.9rem;"><i class="fas fa-check-circle" style="color:#10b981;"></i> Actualizado al día</span>
                    <button id="btn-actualizar" class="btn btn-outline" style="margin-left: 10px; padding: 5px 10px; font-size:0.8rem;" onclick="iniciarAnalisis(true)"><i class="fas fa-sync-alt"></i> Forzar actualización</button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Si no hay código en el repositorio -->
    <?php if (!$has_code): ?>
        <div class="glass-card" style="text-align: center; padding: 60px;">
            <i class="fas fa-folder-open" style="font-size: 4rem; color: var(--text-secondary); margin-bottom: 20px; opacity: 0.5;"></i>
            <h3>El Mentor necesita ver tu trabajo</h3>
            <p style="color: var(--text-secondary); margin-bottom: 25px; max-width: 500px; margin-left: auto; margin-right: auto;">Para poder darte sugerencias de calidad, necesitamos analizar tu código. Sube tus primeros fragmentos al repositorio para desbloquear esta función.</p>
            <a href="<?= BASE_URL ?>views/create_snippet.php" class="btn btn-primary" style="padding: 12px 30px;">Crear un Snippet</a>
        </div>
    <?php else: ?>
        
        <!-- Pantalla de carga animada -->
        <div id="mentor-loading" style="display: <?= ($cached_data) ? 'none' : 'block' ?>; text-align: center; padding: 80px 0;">
            <div style="position: relative; width: 100px; height: 100px; margin: 0 auto 30px;">
                <div style="position: absolute; width: 100%; height: 100%; border: 4px solid transparent; border-top-color: var(--primary-color); border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <div style="position: absolute; width: 80%; height: 80%; top: 10%; left: 10%; border: 4px solid transparent; border-top-color: var(--secondary-color); border-radius: 50%; animation: spin 1.5s linear infinite reverse;"></div>
                <i class="fas fa-robot" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: var(--text-primary); font-size: 1.5rem;"></i>
            </div>
            <h3 style="margin-bottom: 10px;">La IA está leyendo y procesando tu código...</h3>
            <p id="loading-msg" style="color: var(--text-secondary); max-width: 400px; margin: 0 auto;">Buscando patrones, estructuras algorítmicas y áreas de optimización para diseñar tu mapa de crecimiento.</p>
        </div>

        <!-- Tablero de Mando -->
        <div id="mentor-dashboard" style="display: <?= ($cached_data) ? 'block' : 'none' ?>;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 25px;">
                
                <div class="glass-card" style="border-top: 4px solid #10b981;">
                    <h3 style="color: #10b981; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-shield-alt"></i> Tus Fortalezas
                    </h3>
                    <p id="ment-fortalezas" style="line-height: 1.6; color: var(--text-secondary); font-size: 0.95rem;">
                        <?= $cached_data ? htmlspecialchars($cached_data['fortalezas']) : '' ?>
                    </p>
                </div>

                <div class="glass-card" style="border-top: 4px solid #f59e0b;">
                    <h3 style="color: #f59e0b; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-tools"></i> Áreas de Mejora
                    </h3>
                    <p id="ment-mejoras" style="line-height: 1.6; color: var(--text-secondary); font-size: 0.95rem;">
                        <?= $cached_data ? htmlspecialchars($cached_data['areas_mejora']) : '' ?>
                    </p>
                </div>
            </div>

            <h3 style="margin-top: 40px; margin-bottom: 20px;"><i class="fas fa-dumbbell" style="color: var(--primary-color);"></i> Retos Prácticos Recomendados</h3>
            <div id="ment-retos" style="display: flex; flex-direction: column; gap: 15px;">
                <?php if($cached_data): foreach($cached_data['retos'] as $index => $r): ?>
                    <div class="item-card">
                        <div style="font-weight: bold; color: var(--primary-color); margin-bottom: 5px; font-size: 0.85rem;">RETO <?= $index+1 ?></div>
                        <div><?= htmlspecialchars($r) ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <h3 style="margin-top: 40px; margin-bottom: 20px;"><i class="fas fa-question-circle" style="color: var(--secondary-color);"></i> Preguntas de Entrevista Técnica</h3>
            <div id="ment-preguntas" style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 50px;">
                <?php if($cached_data): foreach($cached_data['preguntas_tecnicas'] as $index => $p): ?>
                    <div class="item-card">
                        <div style="font-weight: bold; color: var(--secondary-color); margin-bottom: 5px; font-size: 0.85rem;">PREGUNTA <?= $index+1 ?></div>
                        <div><?= htmlspecialchars($p) ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <style>
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            .item-card { background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; border: 1px solid var(--glass-border); transition: all 0.3s; }
            .item-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); border-color: rgba(59,130,246,0.3); }
        </style>

        <script>
            const contextoString = <?= json_encode($contexto_codigo, JSON_HEX_TAG) ?>;
            const cantidadActual = <?= $cantidad_actual ?>;
            const tieneCache = <?= ($cached_data) ? 'true' : 'false' ?>;

            function iniciarAnalisis(esForzado = false) {
                document.getElementById('mentor-dashboard').style.display = 'none';
                document.getElementById('mentor-loading').style.display = 'block';
                if(document.getElementById('btn-actualizar')){
                    document.getElementById('btn-actualizar').style.display = 'none';
                }

                if (esForzado) {
                    document.getElementById('loading-msg').innerText = "Regenerando la memoria de la IA. Por favor, sé paciente, este proceso puede demorar unos segundos...";
                }

                const formData = new FormData();
                formData.append('action', 'mentor_insights');
                formData.append('contexto', contextoString);
                formData.append('cantidad', cantidadActual);

                fetch('<?= BASE_URL ?>api/ai_assist.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('mentor-loading').style.display = 'none';

                    if (data.error) {
                        alert('Error del Mentor IA: ' + data.error);
                        document.getElementById('mentor-dashboard').style.display = 'block';
                        return;
                    }

                    // Actualizar el DOM con la nueva info generada y guardada en caché
                    document.getElementById('mentor-dashboard').style.display = 'block';
                    document.getElementById('ment-fortalezas').innerText = data.fortalezas;
                    document.getElementById('ment-mejoras').innerText = data.areas_mejora;

                    let retosHTML = '';
                    data.retos.forEach((r, i) => {
                        retosHTML += `<div class="item-card"><div style="font-weight: bold; color: var(--primary-color); margin-bottom: 5px; font-size: 0.85rem;">RETO ${i+1}</div><div>${r}</div></div>`;
                    });
                    document.getElementById('ment-retos').innerHTML = retosHTML;

                    let preHTML = '';
                    data.preguntas_tecnicas.forEach((p, i) => {
                        preHTML += `<div class="item-card"><div style="font-weight: bold; color: var(--secondary-color); margin-bottom: 5px; font-size: 0.85rem;">PREGUNTA ${i+1}</div><div>${p}</div></div>`;
                    });
                    document.getElementById('ment-preguntas').innerHTML = preHTML;
                })
                .catch(err => {
                    document.getElementById('mentor-loading').innerHTML = '<h3 style="color:red;">Error de conexión. Intenta recargar la página.</h3>';
                });
            }

            // Si el usuario NUNCA ha generado la caché, iniciarla automáticamente
            if (!tieneCache) {
                iniciarAnalisis();
            }
        </script>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
