<?php
ob_start();
session_start();
date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/../config/db.php';

// BASE_URL - mÃ©todo infalible basado en SCRIPT_NAME
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

function get_setting($pdo, $clave) {
    $stmt = $pdo->prepare("SELECT valor FROM app_settings WHERE clave = ?");
    $stmt->execute([$clave]);
    return $stmt->fetchColumn();
}

function verify_secret_code($pdo, $code) {
    $stored = get_setting($pdo, 'secret_code_docente');
    return $code === $stored;
}

function notify_forum_subscribers($pdo, $pregunta_id, $contenido_respuesta, $autor_respuesta_id) {
    require_once __DIR__ . "/mailer/mailer_helper.php";
    
    // Obtener información de la pregunta
    $stmt = $pdo->prepare("SELECT p.titulo, u.email as autor_email, u.nombre_completo as autor_nombre FROM foro_preguntas p JOIN usuarios u ON p.usuario_id = u.id WHERE p.id = ?");
    $stmt->execute([$pregunta_id]);
    $pregunta = $stmt->fetch();
    
    if (!$pregunta) return;

    // Obtener suscriptores (excluyendo al que respondió)
    $stmt = $pdo->prepare("SELECT u.email, u.nombre_completo FROM forum_suscripciones s JOIN usuarios u ON s.usuario_id = u.id WHERE s.pregunta_id = ? AND s.usuario_id != ?");
    $stmt->execute([$pregunta_id, $autor_respuesta_id]);
    $suscriptores = $stmt->fetchAll();

    $asunto = "Nueva respuesta en: " . $pregunta->titulo;
    $url = BASE_URL . "views/forum_detail.php?id=" . $pregunta_id;
    
    foreach ($suscriptores as $sub) {
        $cuerpo = "<h3>Hola {$sub->nombre_completo}!</h3>
                  <p>Alguien ha respondido a una pregunta que sigues en CodeLab.</p>
                  <p><b>Pregunta:</b> {$pregunta->titulo}</p>
                  <p><b>Respuesta:</b> " . substr(strip_tags($contenido_respuesta), 0, 100) . "...</p>
                  <p><a href='$url'>Ver respuesta completa</a></p>";
        sendEmail($sub->email, $asunto, $cuerpo);
    }
}
?>