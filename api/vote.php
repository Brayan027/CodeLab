<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['error' => 'Inicia sesión para votar']);
    exit;
}

$user_id = $_SESSION['user_id'];
$pregunta_id = $_POST['pregunta_id'] ?? null;
$respuesta_id = $_POST['respuesta_id'] ?? null;

if (!$pregunta_id && !$respuesta_id) {
    echo json_encode(['error' => 'Falta ID de referencia']);
    exit;
}

// Verificar si ya existe el voto
if ($pregunta_id) {
    $stmt = $pdo->prepare("SELECT id FROM foro_votos WHERE usuario_id = ? AND pregunta_id = ?");
    $stmt->execute([$user_id, $pregunta_id]);
} else {
    $stmt = $pdo->prepare("SELECT id FROM foro_votos WHERE usuario_id = ? AND respuesta_id = ?");
    $stmt->execute([$user_id, $respuesta_id]);
}

$voto = $stmt->fetch();

if ($voto) {
    // Si ya votó, quitamos el voto (Toggle)
    $stmt = $pdo->prepare("DELETE FROM foro_votos WHERE id = ?");
    $stmt->execute([$voto->id]);
    echo json_encode(['status' => 'removed']);
} else {
    // Si no ha votado, añadimos el voto
    if ($pregunta_id) {
        $stmt = $pdo->prepare("INSERT INTO foro_votos (usuario_id, pregunta_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $pregunta_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO foro_votos (usuario_id, respuesta_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $respuesta_id]);
    }
    echo json_encode(['status' => 'added']);
}
?>
