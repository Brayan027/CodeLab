<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Asegurar que la tabla tenga todas las columnas necesarias
    $pdo->exec("DROP TABLE IF EXISTS moderacion_logs");
    
    $sql = "CREATE TABLE moderacion_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        moderador_id INT NOT NULL,
        accion VARCHAR(50) NOT NULL,
        item_id INT DEFAULT NULL,
        detalle TEXT,
        fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (moderador_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "Tabla 'moderacion_logs' recreada correctamente con la columna item_id.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
