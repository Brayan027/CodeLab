<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión.']);
    exit;
}

$usuario_id = $_SESSION['user_id'];
$pregunta_id = intval($_POST['pregunta_id'] ?? 0);

if (!$pregunta_id) {
    echo json_encode(['success' => false, 'error' => 'ID de pregunta no válido.']);
    exit;
}

// Verificar si ya está guardado
$stmt = $pdo->prepare("SELECT id FROM foro_guardados WHERE usuario_id = ? AND pregunta_id = ?");
$stmt->execute([$usuario_id, $pregunta_id]);
$exist = $stmt->fetch();

if ($exist) {
    // Eliminar de guardados
    $stmt = $pdo->prepare("DELETE FROM foro_guardados WHERE usuario_id = ? AND pregunta_id = ?");
    $stmt->execute([$usuario_id, $pregunta_id]);
    echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Eliminado de tus guardados.']);
} else {
    // Añadir a guardados
    $stmt = $pdo->prepare("INSERT INTO foro_guardados (usuario_id, pregunta_id) VALUES (?, ?)");
    $stmt->execute([$usuario_id, $pregunta_id]);
    echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Guardado en tus favoritos.']);
}
