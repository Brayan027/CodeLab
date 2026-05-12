<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para reportar.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit();
}

$reportero_id = $_SESSION['user_id'];
$tipo = sanitize($_POST['tipo'] ?? ''); // 'pregunta' o 'respuesta'
$item_id = intval($_POST['item_id'] ?? 0);
$motivo = sanitize($_POST['motivo'] ?? 'Contenido inapropiado');

if (!$item_id || !in_array($tipo, ['pregunta', 'respuesta'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit();
}

try {
    // 1. Verificar si ya reportó este item
    $stmt = $pdo->prepare("SELECT id FROM reportes WHERE reportero_id = ? AND tipo = ? AND item_id = ?");
    $stmt->execute([$reportero_id, $tipo, $item_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ya has reportado este contenido.']);
        exit();
    }

    // 2. Insertar el reporte
    $stmt = $pdo->prepare("INSERT INTO reportes (reportero_id, tipo, item_id, motivo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$reportero_id, $tipo, $item_id, $motivo]);

    // 3. Contar reportes para este item
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reportes WHERE tipo = ? AND item_id = ?");
    $stmt->execute([$tipo, $item_id]);
    $report_count = $stmt->fetchColumn();

    $action_taken = "";

    // 4. Si llega a 10 reportes, borrar y penalizar
    if ($report_count >= 10) {
        // Obtener el dueño del contenido
        if ($tipo == 'pregunta') {
            $stmt = $pdo->prepare("SELECT usuario_id, titulo FROM foro_preguntas WHERE id = ?");
            $stmt->execute([$item_id]);
            $item = $stmt->fetch();
            $owner_id = $item->usuario_id ?? null;
            $title = $item->titulo ?? "tu pregunta";
        } else {
            $stmt = $pdo->prepare("SELECT usuario_id FROM foro_respuestas WHERE id = ?");
            $stmt->execute([$item_id]);
            $owner_id = $stmt->fetchColumn();
            $title = "tu respuesta";
        }

        if ($owner_id) {
            // Eliminar el item
            if ($tipo == 'pregunta') {
                $pdo->prepare("DELETE FROM foro_preguntas WHERE id = ?")->execute([$item_id]);
            } else {
                $pdo->prepare("DELETE FROM foro_respuestas WHERE id = ?")->execute([$item_id]);
            }

            // Incrementar strikes al dueño
            $pdo->prepare("UPDATE usuarios SET strikes = strikes + 1 WHERE id = ?")->execute([$owner_id]);

            // Verificar si debe suspenderse
            $stmt = $pdo->prepare("SELECT strikes, email FROM usuarios WHERE id = ?");
            $stmt->execute([$owner_id]);
            $owner = $stmt->fetch();

            if ($owner->strikes >= 3) {
                $suspension_fin = date('Y-m-d H:i:s', strtotime('+10 days'));
                $pdo->prepare("UPDATE usuarios SET suspendido_hasta = ?, strikes = 0 WHERE id = ?")->execute([$suspension_fin, $owner_id]);
                $msg = "Tu contenido '$title' fue eliminado por reportes. Has alcanzado 3 strikes y tu cuenta ha sido suspendida por 10 días.";
            } else {
                $msg = "Tu contenido '$title' fue eliminado por haber recibido 10 reportes de la comunidad. Tienes {$owner->strikes} strike(s).";
            }

            // Notificar al usuario (podemos usar la función add_notification si existe)
            if (function_exists('add_notification')) {
                add_notification($pdo, $owner_id, 'sistema', $msg);
            }
            
            // Enviar correo (si mailer está disponible)
            require_once __DIR__ . "/../includes/mailer/mailer_helper.php";
            sendEmail($owner->email, "Contenido Eliminado - CodeLab", $msg);

            $action_taken = " El contenido fue eliminado por exceso de reportes.";
        }
    }

    echo json_encode(['success' => true, 'message' => 'Reporte enviado correctamente.' . $action_taken]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al procesar el reporte: ' . $e->getMessage()]);
}
?>
