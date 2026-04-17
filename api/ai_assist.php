<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

if (!is_logged_in()) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';
$titulo = $_POST['titulo'] ?? '';
$codigo = $_POST['codigo'] ?? '';

function call_gemini_api_with_fallback($prompt) {
    $keys = [GEMINI_API_KEY, GEMINI_API_KEY_SECONDARY];
    $models = ['gemini-2.0-flash', 'gemini-2.0-flash-lite', 'gemini-2.5-flash'];
    
    $data = ["contents" => [["parts" => [["text" => $prompt]]]]];
    $jsonData = json_encode($data);
    
    $lastError = '';
    $lastResponse = '';
    $lastHttpCode = 0;
    
    foreach ($models as $model) {
        foreach ($keys as $apiKey) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $apiKey;
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $lastError = $error;
            $lastResponse = $response;
            $lastHttpCode = $httpCode;

            if (!$error && $httpCode >= 200 && $httpCode < 300) {
                $result = json_decode($response, true);
                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    return [
                        'success' => true,
                        'text' => $result['candidates'][0]['content']['parts'][0]['text']
                    ];
                }
            }
        }
    }
    
    return [
        'success' => false,
        'error' => 'All models and keys failed.',
        'curl_error' => $lastError,
        'http_code' => $lastHttpCode,
        'raw_response' => $lastResponse
    ];
} 

if ($action == 'explain' && !empty($codigo)) {
    // Definimos el prompt
    $prompt = "Actúa como un profesor experto en programación. Analiza el siguiente fragmento de código titulado '$titulo' y proporciona una explicación clara, lógica y educativa en formato HTML (usa etiquetas como <h4>, <p>, <ul>, <li>, <strong>). Menciona posibles errores o mejores prácticas. El código es:\n\n$codigo";

    $apiResult = call_gemini_api_with_fallback($prompt);

    if ($apiResult['success']) {
        $explanation = $apiResult['text'];
        
        // REGISTRO PARA EL DOCENTE: Guardamos que el estudiante usó la IA
        $stmt = $pdo->prepare("INSERT INTO uso_ia_logs (usuario_id, accion, titulo_conctexto) VALUES (?, 'explicacion_codigo', ?)");
        $stmt->execute([$_SESSION['user_id'], $titulo]);

        echo json_encode(['explanation' => $explanation]);
    } else {
        echo json_encode([
            'error' => 'La IA no pudo procesar la solicitud con ninguna clave o modelo.',
            'debug_code' => $apiResult['http_code'],
            'curl_error' => $apiResult['curl_error'],
            'raw_response' => $apiResult['raw_response']
        ]);
    }
} elseif ($action == 'generate_route' && !empty($titulo)) {
    // Prompt estratégico para generar JSON
    $prompt = "Genera una ruta de aprendizaje técnica y profesional sobre: '$titulo'. 
    Responde ÚNICAMENTE con un objeto JSON (sin markdown, sin texto extra) con esta estructura:
    {
      \"titulo\": \"Nombre de la ruta\",
      \"descripcion\": \"Descripción breve\",
      \"pasos\": [
        {
          \"titulo\": \"Paso 1\",
          \"contenido\": \"Explicación detallada\",
          \"codigo\": \"Código de ejemplo o vacío\"
        }
      ]
    }
    Crea al menos 3 o 4 pasos significativos.";

    $apiResult = call_gemini_api_with_fallback($prompt);

    if ($apiResult['success']) {
        $rawText = $apiResult['text'];
        
        // Buscamos el primer '{' y el último '}' para extraer solo el JSON
        if (preg_match('/\{[\s\S]*\}/', $rawText, $matches)) {
            $cleanJson = $matches[0];
            
            // Registrar actividad
            $stmt = $pdo->prepare("INSERT INTO uso_ia_logs (usuario_id, accion, titulo_conctexto) VALUES (?, 'generacion_ruta', ?)");
            $stmt->execute([$_SESSION['user_id'], $titulo]);

            echo $cleanJson; 
        } else {
             echo json_encode(['error' => 'Formato de respuesta inválido de la IA.', 'raw' => $rawText]);
        }
    } else {
        echo json_encode([
            'error' => 'No se pudo generar la ruta por un problema técnico de la IA.',
            'debug_msg' => 'Google no devolvió texto válido con ninguna clave/modelo.',
            'raw_response' => $apiResult['raw_response']
        ]);
    }
} elseif ($action == 'mentor_insights') {
    // Generador de Feedback global (Mentor)
    $codigo_contexto = $_POST['contexto'] ?? '';
    
    if (empty($codigo_contexto)) {
        echo json_encode(['error' => 'No tienes suficiente código en tu repositorio para analizar. Sube más fragmentos para que la IA pueda ayudarte.']);
        exit;
    }

    $prompt = "Eres un Mentor Técnico Senior de Software. He aquí un resumen de los códigos y lenguajes que ha escrito tu estudiante en su repositorio web:
    
    $codigo_contexto
    
    Por favor, analiza esta información y devuelve ÚNICAMENTE un objeto JSON con la siguiente estructura (sin markdown adicional, puramente el objeto JSON):
    {
      \"fortalezas\": \"Breve resumen de lo que el estudiante domina o hace inteligentemente.\",
      \"areas_mejora\": \"Sugerencias técnicas constructivas y específicas para mejorar la calidad de su código.\",
      \"retos\": [
         \"Un reto de programación personalizado basándose en lo que ya sabe.\",
         \"Otro reto un poco más difícil.\"
      ],
      \"preguntas_tecnicas\": [
         \"Pregunta de entrevista sobre algo específico en su código.\",
         \"Otra pregunta técnica teórica.\"
      ]
    }";

    $apiResult = call_gemini_api_with_fallback($prompt);
    
    if ($apiResult['success']) {
        $rawText = $apiResult['text'];
        if (preg_match('/\{[\s\S]*\}/', $rawText, $matches)) {
            $cleanJson = $matches[0];
            $snippets_count = $_POST['cantidad'] ?? 0;
            
            // Guardar en la base de datos (Caché)
            $stmtC = $pdo->prepare("INSERT INTO ai_mentor_cache (usuario_id, mentor_data, snippets_count) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE mentor_data=VALUES(mentor_data), snippets_count=VALUES(snippets_count), ultima_actualizacion=CURRENT_TIMESTAMP");
            $stmtC->execute([$_SESSION['user_id'], $cleanJson, $snippets_count]);

            echo $cleanJson;
        } else {
            echo json_encode(['error' => 'Formato de respuesta inválido de la IA.']);
        }
    } else {
        echo json_encode([
            'error' => 'La IA no pudo procesar tu perfil en este momento.',
            'debug_code' => $apiResult['http_code'],
            'curl_error' => $apiResult['curl_error'],
            'raw_response' => $apiResult['raw_response']
        ]);
    }
} elseif ($action == 'analyze_forum') {
    $pregunta_texto = $_POST['pregunta'] ?? '';
    $respuestas_texto = $_POST['respuestas'] ?? '';

    if (empty($pregunta_texto) || empty($respuestas_texto)) {
        echo json_encode(['error' => 'No hay suficientes datos para analizar.']);
        exit;
    }

    $prompt = "Actúa como un experto en tecnología. Se hizo la siguiente pregunta en un foro:\n\"$pregunta_texto\"\n\nY las siguientes respuestas dadas por la comunidad:\n$respuestas_texto\n\nPor favor, analiza brevemente las respuestas, indica cuáles son correctas, corrige errores si los hay y da una conclusión definitiva. Devuelve todo en formato HTML (usa <p>, <ul>, <strong>). No uses markdown.";

    $apiResult = call_gemini_api_with_fallback($prompt);

    if ($apiResult['success']) {
        $stmt = $pdo->prepare("INSERT INTO uso_ia_logs (usuario_id, accion, titulo_conctexto) VALUES (?, 'analisis_foro', 'Análisis de respuestas en foro')");
        $stmt->execute([$_SESSION['user_id']]);

        echo json_encode(['analysis' => $apiResult['text']]);
    } else {
        echo json_encode([
            'error' => 'La IA no pudo procesar el foro en este momento.',
            'debug_code' => $apiResult['http_code'],
            'curl_error' => $apiResult['curl_error'],
            'raw_response' => $apiResult['raw_response']
        ]);
    }
} else {
    echo json_encode(['error' => 'Acción no válida o datos insuficientes']);
}
?>
