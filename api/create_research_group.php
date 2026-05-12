<?php
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in() || $_SESSION['rol'] != 'docente') {
    die("Acceso denegado");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = sanitize($_POST['nombre']);
    $descripcion = sanitize($_POST['descripcion']);

    if ($nombre) {
        $stmt = $pdo->prepare("INSERT INTO investigacion_grupos (nombre, descripcion) VALUES (?, ?)");
        $stmt->execute([$nombre, $descripcion]);
    }
}

redirect('views/research_dashboard.php');
