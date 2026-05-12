<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("DESCRIBE moderacion_logs");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents(__DIR__ . '/table_info.txt', print_r($cols, true));
?>
