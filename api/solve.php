<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$respuesta_id = $_POST['respuesta_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$respuesta_id) {
    echo json_encode(['error' => 'Falta ID de respuesta']);
    exit;
}

// 1. Obtener la respuesta y la pregunta asociada
$stmt = $pdo->prepare("SELECT r.*, p.usuario_id as autor_pregunta FROM foro_respuestas r JOIN foro_preguntas p ON r.pregunta_id = p.id WHERE r.id = ?");
$stmt->execute([$respuesta_id]);
$respuesta = $stmt->fetch();

if (!$respuesta) {
    echo json_encode(['error' => 'Respuesta no encontrada']);
    exit;
}

// 2. Verificar que el usuario que intenta marcar es el autor de la pregunta
if ($respuesta->autor_pregunta != $user_id) {
    echo json_encode(['error' => 'Solo el autor de la pregunta puede marcar la solución']);
    exit;
}

// 3. Desmarcar cualquier otra respuesta de esa pregunta como solución
$stmt = $pdo->prepare("UPDATE foro_respuestas SET es_solucion = FALSE WHERE pregunta_id = ?");
$stmt->execute([$respuesta->pregunta_id]);

// 4. Marcar la seleccionada
$stmt = $pdo->prepare("UPDATE foro_respuestas SET es_solucion = TRUE WHERE id = ?");
$stmt->execute([$respuesta_id]);

echo json_encode(['status' => 'success']);
?>
