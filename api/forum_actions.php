<?php
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in() || !isset($_GET['action'])) {
    redirect('views/forum.php');
}

$action = $_GET['action'];
$user_id = $_SESSION['user_id'];

if ($action == 'comment') {
    $respuesta_id = (int)$_POST['respuesta_id'];
    $pregunta_id = (int)$_POST['pregunta_id'];
    $contenido = sanitize($_POST['contenido']);
    
    if (!empty($contenido)) {
        $stmt = $pdo->prepare("INSERT INTO foro_respuesta_comentarios (respuesta_id, usuario_id, contenido) VALUES (?, ?, ?)");
        $stmt->execute([$respuesta_id, $user_id, $contenido]);
        
        // Notificar al autor de la respuesta
        $stmt_owner = $pdo->prepare("SELECT usuario_id FROM foro_respuestas WHERE id = ?");
        $stmt_owner->execute([$respuesta_id]);
        $owner = $stmt_owner->fetch();
        if ($owner) {
            add_notification($pdo, $owner->usuario_id, 'comentario', "ha comentado tu respuesta en el foro", "views/forum_detail.php?id=$pregunta_id");
        }
    }
    redirect("views/forum_detail.php?id=$pregunta_id");
}
?>
