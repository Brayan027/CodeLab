<?php
ob_start('stripAccentsFromOutput');
session_start();
date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/../config/db.php';


// Verificar suspensión si está logueado

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT suspendido_hasta FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $suspendido_hasta = $stmt->fetchColumn();
    
    if ($suspendido_hasta && strtotime($suspendido_hasta) > time()) {
        $fecha_libre = date('d/m/Y H:i', strtotime($suspendido_hasta));
        session_destroy();
        die("<h3>Tu cuenta ha sido suspendida</h3><p>Debido a múltiples reportes de contenido inapropiado, tu acceso ha sido restringido hasta el: <b>$fecha_libre</b></p><a href='login.php'>Volver</a>");
    }
}


// BASE_URL - método infalible basado en SCRIPT_NAME
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = $_SERVER['SCRIPT_NAME'];
$base = dirname($script_name);
// Si estamos en views/, api/, bin/, subimos un nivel
$known_subdirs = ['views', 'api', 'bin', 'includes'];
if (in_array(basename($base), $known_subdirs)) {
    $base = dirname($base);
}
$base = rtrim(str_replace('\\', '/', $base), '/') . '/';
if (!defined('BASE_URL')) {
    define('BASE_URL', $protocol . "://" . $host . $base);
}

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
    // Elimina tildes antes de sanitizar
    $data = removeAccents($data);
    return htmlspecialchars(strip_tags(trim($data)));
}

// Elimina tildes y caracteres especiales de forma robusta (UTF-8)
function removeAccents($string) {
    if (!$string) return "";
    
    $search  = array('á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ','ü','Ü','à','è','ì','ò','ù','À','È','Ì','Ò','Ù','â','ê','î','ô','û','Â','Ê','Î','Ô','Û','ã','õ','Ã','Õ');
    $replace = array('a','e','i','o','u','A','E','I','O','U','n','N','u','U','a','e','i','o','u','A','E','I','O','U','a','e','i','o','u','A','E','I','O','U','a','o','A','O');
    
    // También manejar versiones mal codificadas comunes (A³, etc)
    $mangled = array('Ã¡'=>'a', 'Ã©'=>'e', 'Ã­'=>'i', 'Ã³'=>'o', 'Ãº'=>'u', 'Ã±'=>'n', 'Ã¼'=>'u');
    $string = strtr($string, $mangled);

    return str_replace($search, $replace, $string);
}

// Callback para limpiar acentos del output HTML
function stripAccentsFromOutput($buffer) {
    // Si es una respuesta JSON (comienza con { o [), no añadir comentarios ni procesar
    $trimmed = trim($buffer);
    if ((strpos($trimmed, '{') === 0) || (strpos($trimmed, '[') === 0)) {
        return $buffer;
    }
    return removeAccents($buffer);
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

function notify_forum_subscribers($pdo, $pregunta_id, $contenido, $autor_id, $tipo = 'respuesta') {
    require_once __DIR__ . "/mailer/mailer_helper.php";
    
    // Obtener información de la pregunta
    $stmt = $pdo->prepare("SELECT p.titulo, u.email as autor_email, u.nombre_completo as autor_nombre FROM foro_preguntas p JOIN usuarios u ON p.usuario_id = u.id WHERE p.id = ?");
    $stmt->execute([$pregunta_id]);
    $pregunta = $stmt->fetch();
    
    if (!$pregunta) return;

    // Obtener suscriptores (excluyendo al que realizó la acción)
    $stmt = $pdo->prepare("SELECT u.email, u.nombre_completo FROM foro_suscripciones s JOIN usuarios u ON s.usuario_id = u.id WHERE s.pregunta_id = ? AND s.usuario_id != ?");
    $stmt->execute([$pregunta_id, $autor_id]);
    $suscriptores = $stmt->fetchAll();

    error_log("Notificando suscriptores para pregunta #$pregunta_id. Encontrados: " . count($suscriptores));

    $tipo_label = ($tipo == 'comentario') ? 'comentario' : 'respuesta';
    $asunto = "Nueva $tipo_label en: " . $pregunta->titulo;
    $url = BASE_URL . "views/forum_detail.php?id=" . $pregunta_id;
    
    foreach ($suscriptores as $sub) {
        $cuerpo = "<h3>Hola {$sub->nombre_completo}!</h3>
                  <p>Alguien ha publicado una <b>$tipo_label</b> en una pregunta que sigues en CodeLab.</p>
                  <p><b>Pregunta:</b> {$pregunta->titulo}</p>
                  <p><b>Contenido:</b> " . substr(strip_tags($contenido), 0, 100) . "...</p>
                  <p><a href='$url'>Ver en el foro</a></p>";
        sendEmail($sub->email, $asunto, $cuerpo);
    }
}

/**
 * Procesa el contenido para detectar bloques de código ```lenguaje ... ```
 */
function parseContent($text) {
    // Escapar HTML básico primero
    $text = htmlspecialchars($text);
    
    // Detectar bloques de código: ```lenguaje [contenido] ```
    $text = preg_replace_callback('/```(\w*)\n?(.*?)```/s', function($matches) {
        $lang = !empty($matches[1]) ? $matches[1] : 'text';
        $code = trim($matches[2]);
        return '<div class="code-block"><pre class="line-numbers"><code class="language-' . $lang . '">' . $code . '</code></pre></div>';
    }, $text);
    
    // Convertir saltos de línea fuera de los bloques (aproximado)
    // Para simplificar, si no hay bloques, usamos nl2br. Si hay, es más complejo.
    // Usaremos nl2br pero el regex de arriba devuelve HTML que no debería ser nl2br-ed en exceso.
    return nl2br($text);
}

/**
 * Obtiene la actividad diaria del usuario para el gráfico de contribuciones
 */
function getUserContributionData($pdo, $user_id) {
    $sql = "SELECT fecha, SUM(cantidad) as total FROM (
                SELECT DATE(fecha_creacion) as fecha, COUNT(*) as cantidad FROM foro_preguntas WHERE usuario_id = ? GROUP BY fecha
                UNION ALL
                SELECT DATE(fecha_respuesta) as fecha, COUNT(*) as cantidad FROM foro_respuestas WHERE usuario_id = ? GROUP BY fecha
                UNION ALL
                SELECT DATE(fecha_creacion) as fecha, COUNT(*) as cantidad FROM snippets WHERE usuario_id = ? GROUP BY fecha
                UNION ALL
                SELECT DATE(fecha_creacion) as fecha, COUNT(*) as cantidad FROM rutas WHERE creador_id = ? GROUP BY fecha
                UNION ALL
                SELECT DATE(fecha_comentario) as fecha, COUNT(*) as cantidad FROM foro_respuesta_comentarios WHERE usuario_id = ? GROUP BY fecha
            ) AS actividad 
            WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
            GROUP BY fecha ORDER BY fecha ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
    $results = $stmt->fetchAll();
    
    $activity = [];
    foreach ($results as $row) {
        $activity[$row->fecha] = (int)$row->total;
    }
    return $activity;
}

/**
 * Retorna el nombre legible de un rol
 */
function get_role_name($rol) {
    switch ($rol) {
        case 'admin': return 'Administrador';
        case 'docente': return 'Docente';
        case 'monitor': return 'Monitor';
        case 'estudiante': return 'Estudiante';
        default: return ucfirst($rol);
    }
}
?>