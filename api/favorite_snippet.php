<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!is_logged_in() || !isset($_POST['snippet_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$snippet_id = (int)$_POST['snippet_id'];

// Verificar si ya es favorita
$stmt = $pdo->prepare("SELECT id FROM snippets_favoritos WHERE usuario_id = ? AND snippet_id = ?");
$stmt->execute([$user_id, $snippet_id]);
$fav = $stmt->fetch();

if ($fav) {
    // Quitar de favoritas
    $pdo->prepare("DELETE FROM snippets_favoritos WHERE id = ?")->execute([$fav->id]);
    echo json_encode(['status' => 'removed']);
} else {
    // Añadir a favoritas
    $pdo->prepare("INSERT INTO snippets_favoritos (usuario_id, snippet_id) VALUES (?, ?)")->execute([$user_id, $snippet_id]);
    
    // Notificar al dueño del snippet
    $stmt_owner = $pdo->prepare("SELECT usuario_id, titulo FROM snippets WHERE id = ?");
    $stmt_owner->execute([$snippet_id]);
    $snippet = $stmt_owner->fetch();
    if ($snippet) {
        add_notification($pdo, $snippet->usuario_id, 'like', "ha guardado tu código como favorito: " . $snippet->titulo, "views/snippet_detail.php?id=$snippet_id");
    }
    
    echo json_encode(['status' => 'added']);
}
?>
