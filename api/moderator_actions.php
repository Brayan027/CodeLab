<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in() || !in_array($_SESSION['rol'], ['monitor', 'admin'])) {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado. Se requieren permisos de Monitor o Admin.']);
    exit;
}

$tipo = $_POST['tipo'] ?? '';
$item_id = intval($_POST['item_id'] ?? 0);
$motivo = sanitize($_POST['motivo'] ?? 'Incumplimiento de normas');

// Debug log
file_put_contents(__DIR__ . '/debug_moderator.txt', "Tipo: $tipo, ID: $item_id, Motivo: $motivo, User: " . $_SESSION['user_id'] . "\n", FILE_APPEND);

if (!$tipo || !$item_id) {
    echo json_encode(['success' => false, 'error' => 'Datos insuficientes.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $tabla = '';
    $accion_log = '';
    $detalle_item = '';

    switch ($tipo) {
        case 'pregunta':
            $tabla = 'foro_preguntas';
            $accion_log = 'eliminacion_pregunta';
            $stmt = $pdo->prepare("SELECT titulo FROM foro_preguntas WHERE id = ?");
            $stmt->execute([$item_id]);
            $detalle_item = $stmt->fetchColumn();
            break;
        case 'respuesta':
            $tabla = 'foro_respuestas';
            $accion_log = 'eliminacion_respuesta';
            $stmt = $pdo->prepare("SELECT contenido FROM foro_respuestas WHERE id = ?");
            $stmt->execute([$item_id]);
            $detalle_item = substr(strip_tags($stmt->fetchColumn()), 0, 100);
            break;
        case 'snippet':
            $tabla = 'snippets';
            $accion_log = 'eliminacion_snippet';
            $stmt = $pdo->prepare("SELECT titulo FROM snippets WHERE id = ?");
            $stmt->execute([$item_id]);
            $detalle_item = $stmt->fetchColumn();
            break;
        case 'ruta':
            $tabla = 'rutas';
            $accion_log = 'eliminacion_ruta';
            $stmt = $pdo->prepare("SELECT titulo FROM rutas WHERE id = ?");
            $stmt->execute([$item_id]);
            $detalle_item = $stmt->fetchColumn();
            break;
        default:
            throw new Exception("Tipo de contenido no válido.");
    }

    if (!$detalle_item) {
        throw new Exception("El contenido ya no existe.");
    }

    // Ejecutar eliminación
    $stmt_del = $pdo->prepare("DELETE FROM $tabla WHERE id = ?");
    $stmt_del->execute([$item_id]);

    // Registrar en logs
    $stmt_log = $pdo->prepare("INSERT INTO moderacion_logs (moderador_id, accion, item_id, detalle) VALUES (?, ?, ?, ?)");
    $log_text = "Motivo: $motivo | Contenido: $detalle_item";
    $stmt_log->execute([$_SESSION['user_id'], $accion_log, $item_id, $log_text]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Contenido eliminado y acción registrada.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
