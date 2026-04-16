<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';
$titulo = $_POST['titulo'] ?? '';
$codigo = $_POST['codigo'] ?? '';

// NOTA: Aquí iría tu clave de API de Google Gemini
$apiKey = 'TU_API_KEY_AQUI'; 

if ($action == 'explain') {
    // Definimos el prompt para la IA de forma profesional
    $prompt = "Actúa como un profesor experto en programación. Analiza el siguiente fragmento de código titulado '$titulo' y proporciona una explicación clara, lógica y educativa. Menciona posibles errores o mejores prácticas. Código:\n\n$codigo";

    // Simulación de llamada API (Si no hay API Key activa)
    // En un entorno real, usarías curl para llamar a la API de Gemini
    
    /* 
    EN RODAJE REAL:
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $apiKey;
    $data = [
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ]
    ];
    // Ejecutar CURL y devolver respuesta...
    */

    // Simulación para el prototipo "Full"
    $simulatedResponse = "<h4>Explicación del Profe IA</h4>
    <p>El código proporcionado para <strong>$titulo</strong> es una implementación estándar.</p>
    <ul>
        <li><strong>Lógica:</strong> El flujo es correcto y sigue las convenciones de programación.</li>
        <li><strong>Sugerencia:</strong> Considera validar las entradas nulas para evitar errores en tiempo de ejecución.</li>
        <li><strong>Eficiencia:</strong> La complejidad temporal es O(n), lo cual es óptimo para esta tarea.</li>
    </ul>
    <p><em>Esta respuesta fue generada automáticamente para apoyar tu aprendizaje práctico.</em></p>";

    echo json_encode(['explanation' => $simulatedResponse]);
} else {
    echo json_encode(['error' => 'Acción no válida']);
}
?>
