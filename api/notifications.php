<?php
// api/notifications.php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action == 'count') {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notificaciones WHERE usuario_id = ? AND leido = 0");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetch());
    } catch (PDOException $e) {
        echo json_encode(['count' => 0]);
    }
} 
elseif ($action == 'list') {
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, u.nombre_completo as emisor_nombre, u.usuario as emisor_handle
            FROM notificaciones n
            JOIN usuarios u ON n.emisor_id = u.id
            WHERE n.usuario_id = ?
            ORDER BY n.fecha_creacion DESC
            LIMIT 20
        ");
        $stmt->execute([$user_id]);
        $notifs = $stmt->fetchAll();
        
        // Formatear fecha "hace X tiempo"
        foreach ($notifs as $n) {
            $n->fecha_hace = time_ago($n->fecha_creacion);
        }
        
        echo json_encode($notifs);
    } catch (PDOException $e) {
        echo json_encode([]); // Si la tabla no existe aún, devolvemos vacío
    }
} 
elseif ($action == 'read') {
    $id = $_GET['id'] ?? 0;
    $url = $_GET['url'] ?? '';
    
    $stmt = $pdo->prepare("UPDATE notificaciones SET leido = 1 WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $user_id]);
    
    if ($url) {
        // Asegurar que la URL sea absoluta para evitar 404 desde la carpeta api/
        $redirect_url = (strpos($url, 'http') === 0) ? $url : BASE_URL . ltrim($url, '/');
        header("Location: " . $redirect_url);
        exit;
    }
    echo json_encode(['success' => true]);
} 
elseif ($action == 'mark_all') {
    $stmt = $pdo->prepare("UPDATE notificaciones SET leido = 1 WHERE usuario_id = ?");
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true]);
}

function time_ago($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return "Justo ahora";
    if ($diff < 3600) return round($diff / 60) . " min";
    if ($diff < 86400) return round($diff / 3600) . " horas";
    return date('d/m', $time);
}
?>
