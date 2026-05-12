<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit();
}

$user_id = $_SESSION['user_id'];
$pregunta_id = $_POST['pregunta_id'] ?? null;

if (!$pregunta_id) {
    echo json_encode(['status' => 'error', 'message' => 'ID de pregunta faltante']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id FROM foro_suscripciones WHERE usuario_id = ? AND pregunta_id = ?");
    $stmt->execute([$user_id, $pregunta_id]);
    
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM foro_suscripciones WHERE usuario_id = ? AND pregunta_id = ?");
        $stmt->execute([$user_id, $pregunta_id]);
        echo json_encode(['status' => 'success', 'action' => 'unsubscribed']);
    } else {
        $stmt = $pdo->prepare("INSERT INTO foro_suscripciones (usuario_id, pregunta_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $pregunta_id]);
        echo json_encode(['status' => 'success', 'action' => 'subscribed']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
