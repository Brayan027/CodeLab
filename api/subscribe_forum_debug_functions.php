<?php
header('Content-Type: application/json');
require_once 'includes/functions.php';
echo json_encode(['status' => 'debug', 'message' => 'Functions included']);
exit;
?>
