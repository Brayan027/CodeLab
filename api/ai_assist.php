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

// Tu API Key de Google Gemini
$apiKey = 'AIzaSyAV-4PsDgZq_XJUpLklTe5xc6-KwrYUCxE'; 

if ($action == 'explain' && !empty($codigo)) {
    // Definimos el prompt
    $prompt = "Actúa como un profesor experto en programación. Analiza el siguiente fragmento de código titulado '$titulo' y proporciona una explicación clara, lógica y educativa en formato HTML (usa etiquetas como <h4>, <p>, <ul>, <li>, <strong>). Menciona posibles errores o mejores prácticas. El código es:\n\n$codigo";

    // Usando el modelo estable: gemini-1.5-flash
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

    $data = [
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        echo json_encode(['error' => 'Error de conexión (cURL): ' . $error]);
        exit;
    }

    $result = json_decode($response, true);
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $explanation = $result['candidates'][0]['content']['parts'][0]['text'];
        
        // REGISTRO PARA EL DOCENTE: Guardamos que el estudiante usó la IA
        $stmt = $pdo->prepare("INSERT INTO uso_ia_logs (usuario_id, accion, titulo_conctexto) VALUES (?, 'explicacion_codigo', ?)");
        $stmt->execute([$_SESSION['user_id'], $titulo]);

        echo json_encode(['explanation' => $explanation]);
    } else {
        // Enviamos TODO lo que respondió Google para saber qué pasa
        echo json_encode([
            'error' => 'La IA no pudo procesar la solicitud.',
            'debug_code' => $httpCode,
            'api_message' => $result['error']['message'] ?? 'No hay mensaje de error específico.',
            'raw_response' => $response // Esto nos dirá todo
        ]);
    }
} else {
    echo json_encode(['error' => 'Acción no válida o código vacío']);
}
?>
