<?php
require_once 'includes/functions.php';
$stmt = $pdo->query("SELECT * FROM foro_suscripciones");
$subs = $stmt->fetchAll();
echo "Total de suscripciones en la BD: " . count($subs) . "\n";
foreach ($subs as $s) {
    echo "Usuario ID: {$s->usuario_id}, Pregunta ID: {$s->pregunta_id}\n";
}
?>
