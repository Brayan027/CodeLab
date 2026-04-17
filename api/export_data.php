<?php
require_once __DIR__ . '/../includes/functions.php';

// Solo accesible para docentes/investigadores
if (!is_logged_in() || $_SESSION['rol'] != 'docente') {
    die("No autorizado");
}

// Limpiar cualquier salida previa para evitar errores en el archivo
if (ob_get_length()) ob_clean();

$filename = "codelab_data_export_" . date('Y-m-d_His') . ".csv";

// Cabeceras para descarga de CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);
header('Pragma: no-cache');
header('Expires: 0');

// Crear puntero de archivo para la salida
$output = fopen('php://output', 'w');

// Añadir el BOM de UTF-8 para que Excel lo reconozca correctamente
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Añadir cabecera de las columnas
fputcsv($output, ['ID_LOG', 'ID_USUARIO', 'USUARIO', 'ROL', 'ACCION', 'CONTEXTO', 'FECHA']);

// Obtener todos los logs de actividad con datos de usuario
$stmt = $pdo->query("
    SELECT l.id, l.usuario_id, u.usuario, u.rol, l.accion, l.titulo_conctexto, l.fecha 
    FROM uso_ia_logs l 
    JOIN usuarios u ON l.usuario_id = u.id 
    ORDER BY l.fecha DESC
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
