-- 1. Tablas para verificación de correo y suscripciones
CREATE TABLE IF NOT EXISTS verificaciones_email (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    codigo VARCHAR(10) NOT NULL,
    expira_en DATETIME NOT NULL,
    utilizado BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Añadir campos necesarios a la tabla usuarios
ALTER TABLE usuarios ADD COLUMN email_verificado BOOLEAN DEFAULT FALSE;
ALTER TABLE usuarios ADD COLUMN secret_code_active VARCHAR(50) DEFAULT NULL;

-- Tabla para suscripciones a foros
CREATE TABLE IF NOT EXISTS forum_suscripciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    pregunta_id INT NOT NULL,
    fecha_suscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (pregunta_id) REFERENCES forum_preguntas(id) ON DELETE CASCADE,
    UNIQUE KEY (usuario_id, pregunta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para configuraciones globales (ej. código secreto docente)
CREATE TABLE IF NOT EXISTS app_settings (
    clave VARCHAR(50) PRIMARY KEY,
    valor TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO app_settings (clave, valor) VALUES ('secret_code_docente', '###De34?');
