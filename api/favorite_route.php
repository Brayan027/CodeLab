<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!is_logged_in() || !isset($_POST['ruta_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$ruta_id = (int)$_POST['ruta_id'];

// Verificar si ya es favorita
$stmt = $pdo->prepare("SELECT id FROM rutas_favoritas WHERE usuario_id = ? AND ruta_id = ?");
$stmt->execute([$user_id, $ruta_id]);
$fav = $stmt->fetch();

if ($fav) {
    // Quitar de favoritas
    $pdo->prepare("DELETE FROM rutas_favoritas WHERE id = ?")->execute([$fav->id]);
    echo json_encode(['status' => 'removed']);
} else {
    // Añadir a favoritas
    $pdo->prepare("INSERT INTO rutas_favoritas (usuario_id, ruta_id) VALUES (?, ?)")->execute([$user_id, $ruta_id]);
    
    // Notificar al dueño de la ruta
    $stmt_owner = $pdo->prepare("SELECT creador_id, titulo FROM rutas WHERE id = ?");
    $stmt_owner->execute([$ruta_id]);
    $ruta = $stmt_owner->fetch();
    if ($ruta) {
        add_notification($pdo, $ruta->creador_id, 'like', "ha guardado tu ruta como favorita: " . $ruta->titulo, "views/route_detail.php?id=$ruta_id");
    }
    
    echo json_encode(['status' => 'added']);
}
?>
