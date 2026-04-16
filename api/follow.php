<?php
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in() || !isset($_POST['id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$seguidor_id = $_SESSION['user_id'];
$siguiendo_id = (int)$_POST['id'];

if ($seguidor_id == $siguiendo_id) {
    echo json_encode(['error' => 'No puedes seguirte a ti mismo']);
    exit;
}

// Verificar si ya lo sigue
$stmt = $pdo->prepare("SELECT * FROM seguidores WHERE seguidor_id = ? AND siguiendo_id = ?");
$stmt->execute([$seguidor_id, $siguiendo_id]);
$follow = $stmt->fetch();

if ($follow) {
    // Dejar de seguir
    $stmt = $pdo->prepare("DELETE FROM seguidores WHERE seguidor_id = ? AND siguiendo_id = ?");
    $stmt->execute([$seguidor_id, $siguiendo_id]);
    echo json_encode(['status' => 'unfollowed']);
} else {
    // Seguir
    $stmt = $pdo->prepare("INSERT INTO seguidores (seguidor_id, siguiendo_id) VALUES (?, ?)");
    $stmt->execute([$seguidor_id, $siguiendo_id]);
    echo json_encode(['status' => 'followed']);
}
?>
