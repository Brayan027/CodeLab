<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in() || $_SESSION['rol'] != 'docente') {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

$est_id = intval($_POST['estudiante_id'] ?? 0);
$res_grp_id = $_POST['investigacion_grupo_id'] !== "" ? intval($_POST['investigacion_grupo_id']) : null;

if (!$est_id) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE usuarios SET investigacion_grupo_id = ? WHERE id = ?");
    $stmt->execute([$res_grp_id, $est_id]);
    echo json_encode(['success' => true, 'message' => 'Grupo de investigación actualizado']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error en base de datos']);
}
