<?php
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Manejar envío de mensaje
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['destinatario_id'], $_POST['mensaje'])) {
    $destinatario_id = (int)$_POST['destinatario_id'];
    $mensaje = sanitize($_POST['mensaje']);

    if (!empty($mensaje)) {
        $stmt = $pdo->prepare("INSERT INTO chat_mensajes (remitente_id, destinatario_id, mensaje) VALUES (?, ?, ?)");
        $stmt->execute([$current_user_id, $destinatario_id, $mensaje]);
        echo json_encode(['status' => 'sent']);
    }
    exit;
}

// Manejar obtención de mensajes
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['destinatario_id'])) {
    $destinatario_id = (int)$_GET['destinatario_id'];

    $stmt = $pdo->prepare("
        SELECT * FROM chat_mensajes 
        WHERE (remitente_id = ? AND destinatario_id = ?) 
        OR (remitente_id = ? AND destinatario_id = ?)
        ORDER BY fecha_envio ASC
    ");
    $stmt->execute([$current_user_id, $destinatario_id, $destinatario_id, $current_user_id]);
    $messages = $stmt->fetchAll();

    echo json_encode($messages);
    exit;
}
?>
