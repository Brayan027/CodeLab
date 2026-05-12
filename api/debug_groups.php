<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: text/plain');

echo "Session User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Session Role: " . ($_SESSION['rol'] ?? 'NOT SET') . "\n";

try {
    $stmt = $pdo->prepare("SELECT * FROM grupos WHERE docente_id = ?");
    $stmt->execute([$_SESSION['user_id'] ?? 0]);
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Groups for this user:\n";
    print_r($grupos);

    $stmtAll = $pdo->query("SELECT * FROM grupos");
    echo "\nAll groups in DB:\n";
    print_r($stmtAll->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
