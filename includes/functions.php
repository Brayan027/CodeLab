<?php
ob_start();
session_start();
date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/../config/db.php';

// BASE_URL - método infalible basado en SCRIPT_NAME
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base = dirname($script_name);
// Si estamos en views/, api/, bin/, subimos un nivel
$known_subdirs = ['views', 'api', 'bin', 'includes'];
if (in_array(basename($base), $known_subdirs)) {
    $base = dirname($base);
}
$base = rtrim(str_replace('\\', '/', $base), '/') . '/';
define('BASE_URL', $protocol . "://" . $host . $base);

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    // Asegurar que la URL sea absoluta
    $redirect_url = (strpos($url, 'http') === 0) ? $url : BASE_URL . ltrim($url, '/');
    header("Location: " . $redirect_url);
    exit();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function get_user_data($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function add_notification($pdo, $usuario_id, $tipo, $mensaje, $url = '') {
    if (!is_logged_in()) return;
    $emisor_id = $_SESSION['user_id'];
    if ($usuario_id == $emisor_id) return; // No notificarse a uno mismo
    
    $stmt = $pdo->prepare("INSERT INTO notificaciones (usuario_id, emisor_id, tipo, mensaje, url) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$usuario_id, $emisor_id, $tipo, $mensaje, $url]);
}
?>
