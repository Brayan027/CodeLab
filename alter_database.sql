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

-- Sistema de Reportes y Sanciones
CREATE TABLE IF NOT EXISTS reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reportero_id INT NOT NULL,
    tipo ENUM('pregunta', 'respuesta') NOT NULL,
    item_id INT NOT NULL,
    motivo VARCHAR(255),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reportero_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Campos para suspenciones en usuarios
ALTER TABLE usuarios ADD COLUMN strikes INT DEFAULT 0;
ALTER TABLE usuarios ADD COLUMN suspendido_hasta DATETIME DEFAULT NULL;

-- Tabla para suscripciones a foros
CREATE TABLE IF NOT EXISTS foro_suscripciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    pregunta_id INT NOT NULL,
    fecha_suscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (pregunta_id) REFERENCES foro_preguntas(id) ON DELETE CASCADE,
    UNIQUE KEY (usuario_id, pregunta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para configuraciones globales (ej. código secreto docente)
CREATE TABLE IF NOT EXISTS app_settings (
    clave VARCHAR(50) PRIMARY KEY,
    valor TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO app_settings (clave, valor) VALUES ('secret_code_docente', '###De34?');

-- Tabla para guardar foros favoritos
CREATE TABLE IF NOT EXISTS foro_guardados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    pregunta_id INT NOT NULL,
    fecha_guardado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (pregunta_id) REFERENCES foro_preguntas(id) ON DELETE CASCADE,
    UNIQUE KEY (usuario_id, pregunta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- INFRAESTRUCTURA PARA INVESTIGACIÓN (Tesis)
CREATE TABLE IF NOT EXISTS investigacion_grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50),
    descripcion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Asignar usuarios a grupos de investigación (Control vs Experimental)
ALTER TABLE usuarios ADD COLUMN investigacion_grupo_id INT NULL;
ALTER TABLE usuarios ADD CONSTRAINT fk_investigacion_grupo FOREIGN KEY (investigacion_grupo_id) REFERENCES investigacion_grupos(id);

-- Tabla para recolección de datos cualitativos (Encuestas rápidas)
CREATE TABLE IF NOT EXISTS investigacion_encuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    objetivo VARCHAR(10), -- ObjE1, ObjE2, etc.
    pregunta TEXT,
    respuesta TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Métricas avanzadas de resolución de problemas
CREATE TABLE IF NOT EXISTS investigacion_metricas_resolucion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregunta_id INT,
    tiempo_resolucion_seg INT,
    recursos_utilizados JSON, -- {'ia': true, 'foro': true, 'search': false}
    autonomia_percibida INT, -- 1 a 5
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pregunta_id) REFERENCES foro_preguntas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar grupos por defecto
INSERT IGNORE INTO investigacion_grupos (id, nombre, descripcion) VALUES 
(1, 'Grupo Experimental', 'Acceso total a IA y herramientas colaborativas'),
(2, 'Grupo Control', 'Uso limitado de IA para medir diferencia de aprendizaje');
