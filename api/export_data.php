<?php
require_once __DIR__ . '/../includes/functions.php';

// Solo accesible para docentes/investigadores
if (!is_logged_in() || $_SESSION['rol'] != 'docente') {
    die("No autorizado");
}

// Respetar filtros de fecha si existen
$start = $_GET['start'] ?? '2000-01-01';
$end = $_GET['end'] ?? date('Y-m-d');

// Limpiar cualquier salida previa
if (ob_get_length()) ob_clean();

$filename = "codelab_research_dataset_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

// Cabeceras detalladas para análisis estadístico
fputcsv($output, [
    'ID_EVENTO', 
    'FECHA', 
    'USUARIO_ID', 
    'USUARIO_NOMBRE', 
    'ROL', 
    'GRUPO_INVESTIGACION', 
    'CATEGORIA', 
    'ACCION_TIPO', 
    'DESCRIPCION'
]);

// 1. Logs de IA
$stmt_ia = $pdo->prepare("
    SELECT l.id, l.fecha, l.usuario_id, u.nombre_completo, u.rol, g.nombre as grupo, 'IA_GEN' as cat, l.accion, l.titulo_conctexto 
    FROM uso_ia_logs l 
    JOIN usuarios u ON l.usuario_id = u.id 
    LEFT JOIN investigacion_grupos g ON u.investigacion_grupo_id = g.id
    WHERE DATE(l.fecha) BETWEEN ? AND ?
");
$stmt_ia->execute([$start, $end]);
while ($row = $stmt_ia->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);

// 2. Reutilización de Código
$stmt_reuso = $pdo->prepare("
    SELECT m.id, m.fecha_copia as fecha, m.usuario_id, u.nombre_completo, u.rol, g.nombre as grupo, 'REUSO_CODIGO' as cat, 'copia_snippet' as accion, s.titulo 
    FROM metricas_reutilizacion m
    JOIN usuarios u ON m.usuario_id = u.id 
    JOIN snippets s ON m.snippet_id = s.id
    LEFT JOIN investigacion_grupos g ON u.investigacion_grupo_id = g.id
    WHERE DATE(m.fecha_copia) BETWEEN ? AND ?
");
$stmt_reuso->execute([$start, $end]);
while ($row = $stmt_reuso->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);

// 3. Producción de Foro (Preguntas)
$stmt_foro = $pdo->prepare("
    SELECT p.id, p.fecha_creacion as fecha, p.usuario_id, u.nombre_completo, u.rol, g.nombre as grupo, 'FORO_COLAB' as cat, 'pregunta' as accion, p.titulo 
    FROM foro_preguntas p
    JOIN usuarios u ON p.usuario_id = u.id 
    LEFT JOIN investigacion_grupos g ON u.investigacion_grupo_id = g.id
    WHERE DATE(p.fecha_creacion) BETWEEN ? AND ?
");
$stmt_foro->execute([$start, $end]);
while ($row = $stmt_foro->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);

fclose($output);
exit;
