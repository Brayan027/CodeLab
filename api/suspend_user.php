<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in() || !in_array($_SESSION['rol'], ['docente', 'monitor', 'admin'])) {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

$user_id = intval($_POST['usuario_id'] ?? 0);
$dias = intval($_POST['dias'] ?? 0);

if (!$user_id || $dias < 0) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

try {
    $pdo->beginTransaction();

    $fecha_suspension = ($dias > 0) ? date('Y-m-d H:i:s', strtotime("+$dias days")) : null;
    $stmt = $pdo->prepare("UPDATE usuarios SET suspendido_hasta = ? WHERE id = ?");
    $stmt->execute([$fecha_suspension, $user_id]);

    // Registrar en logs de moderación
    $stmt_log = $pdo->prepare("INSERT INTO moderacion_logs (moderador_id, accion, item_id, detalle) VALUES (?, 'suspension', ?, ?)");
    $detalle = ($dias > 0) ? "Suspendido por $dias días." : "Suspensión levantada.";
    $stmt_log->execute([$_SESSION['user_id'], $user_id, $detalle]);

    $pdo->commit();

    $msg = ($dias > 0) ? "Usuario suspendido por $dias días." : "Suspensión levantada.";
    echo json_encode(['success' => true, 'message' => $msg]);
} catch (PDOException $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Error al procesar la suspensión: ' . $e->getMessage()]);
}
