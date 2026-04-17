<?php
// api/metrics.php
// Archivo exclusivo para la recolección de MÉTODOS DE INVESTIGACIÓN (TESIS)
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action == 'registrar_copia') {
    $snippet_id = $_POST['snippet_id'] ?? 0;
    
    if ($snippet_id) {
        $stmt = $pdo->prepare("INSERT INTO metricas_reutilizacion (snippet_id, usuario_id) VALUES (?, ?)");
        $stmt->execute([$snippet_id, $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Faltan datos']);
    }
} elseif ($action == 'evaluar_ia') {
    $contexto = $_POST['contexto'] ?? 'General';
    $fue_util = $_POST['util'] ?? 1; // 1 o 0
    
    $stmt = $pdo->prepare("INSERT INTO evaluacion_ia (usuario_id, contexto_solicitud, fue_util) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $contexto, $fue_util]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Acción no válida']);
}
?>
