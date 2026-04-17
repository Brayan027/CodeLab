-- Estructura de la base de datos para CodeLab

CREATE DATABASE IF NOT EXISTS codelab_db;
USE codelab_db;

-- Tabla de Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    bio TEXT,
    avatar VARCHAR(255) DEFAULT 'default_avatar.png',
    rol ENUM('estudiante', 'docente', 'admin') DEFAULT 'estudiante',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Seguidores
CREATE TABLE IF NOT EXISTS seguidores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seguidor_id INT NOT NULL,
    siguiendo_id INT NOT NULL,
    fecha_seguimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seguidor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (siguiendo_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY (seguidor_id, siguiendo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Rutas de Aprendizaje
CREATE TABLE IF NOT EXISTS rutas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    privacidad ENUM('publico', 'privado') DEFAULT 'publico',
    creador_id INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creador_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Pasos de la Ruta
CREATE TABLE IF NOT EXISTS pasos_ruta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ruta_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    contenido TEXT,
    codigo_snippet TEXT,
    orden INT NOT NULL,
    FOREIGN KEY (ruta_id) REFERENCES rutas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Snippets (Repositorio de Código)
CREATE TABLE IF NOT EXISTS snippets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    codigo TEXT NOT NULL,
    lenguaje VARCHAR(50) NOT NULL DEFAULT 'Java',
    privacidad ENUM('publico', 'privado') DEFAULT 'publico',
    usuario_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES snippets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Foro: Preguntas
CREATE TABLE IF NOT EXISTS forum_preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT NOT NULL,
    usuario_id INT NOT NULL,
    tags VARCHAR(255),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Foro: Respuestas
CREATE TABLE IF NOT EXISTS forum_respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregunta_id INT NOT NULL,
    usuario_id INT NOT NULL,
    contenido TEXT NOT NULL,
    es_solucion BOOLEAN DEFAULT FALSE,
    fecha_respuesta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pregunta_id) REFERENCES forum_preguntas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Chat
CREATE TABLE IF NOT EXISTS chat_mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remitente_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    mensaje TEXT NOT NULL,
    leido BOOLEAN DEFAULT FALSE,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (remitente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Grupos (Creados por docentes)
CREATE TABLE IF NOT EXISTS grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    codigo_invitacion VARCHAR(10) UNIQUE NOT NULL,
    docente_id INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (docente_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Estudiantes por Grupo
CREATE TABLE IF NOT EXISTS grupo_estudiantes (
    grupo_id INT NOT NULL,
    estudiante_id INT NOT NULL,
    fecha_union TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (grupo_id, estudiante_id),
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    FOREIGN KEY (estudiante_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Historial de Uso de IA (Métricas para el docente)
CREATE TABLE IF NOT EXISTS uso_ia_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    accion VARCHAR(50) NOT NULL,
    titulo_conctexto VARCHAR(255),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Votos (Likes) para foro
CREATE TABLE IF NOT EXISTS forum_votos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    pregunta_id INT DEFAULT NULL,
    respuesta_id INT DEFAULT NULL,
    fecha_voto TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (pregunta_id) REFERENCES forum_preguntas(id) ON DELETE CASCADE,
    FOREIGN KEY (respuesta_id) REFERENCES forum_respuestas(id) ON DELETE CASCADE,
    UNIQUE KEY (usuario_id, pregunta_id),
    UNIQUE KEY (usuario_id, respuesta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Comentarios en Snippets (Feedback)
CREATE TABLE IF NOT EXISTS snippet_comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    snippet_id INT NOT NULL,
    usuario_id INT NOT NULL,
    contenido TEXT NOT NULL,
    fecha_comentario TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (snippet_id) REFERENCES snippets(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Votos en Pasos de Rutas
CREATE TABLE IF NOT EXISTS ruta_paso_votos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paso_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_voto TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paso_id) REFERENCES pasos_ruta(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY (paso_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Comentarios en Pasos de Rutas
CREATE TABLE IF NOT EXISTS ruta_paso_comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paso_id INT NOT NULL,
    usuario_id INT NOT NULL,
    contenido TEXT NOT NULL,
    fecha_comentario TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paso_id) REFERENCES pasos_ruta(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Caché del Mentor IA
-- Optimiza el sistema evitando enviar información constantemente a la IA.
CREATE TABLE IF NOT EXISTS ai_mentor_cache (
    usuario_id INT PRIMARY KEY,
    mentor_data JSON,
    snippets_count INT,
    ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- MÓDULO DE INVESTIGACIÓN: CAPTURA DE MÉTRICAS (TESIS)
-- ========================================================

-- 1. Métrica de Reutilización de Código (Para el ObjE2)
-- Guardará un registro cada vez que un estudiante hace clic en "Copiar" un código.
CREATE TABLE IF NOT EXISTS metricas_reutilizacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    snippet_id INT NOT NULL,
    usuario_id INT NOT NULL, -- Quién copió el código
    fecha_copia TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (snippet_id) REFERENCES snippets(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Modificación de Foros para "Calidad de Interacción" (ObjE1)
-- Se añade la columna es_solucion a forum_respuestas si no existe
-- (Si ya creaste tu DB, haz un ALTER TABLE en tu SQL para añadir esto:
-- ALTER TABLE forum_respuestas ADD COLUMN es_solucion BOOLEAN DEFAULT FALSE; )

-- 3. Métrica de Utilidad de la Inteligencia Artificial (ObjE3)
-- Permitirá saber si la sugerencia de la IA fue percibida como útil por el estudiante, midiendo autonomía y precisión.
CREATE TABLE IF NOT EXISTS evaluacion_ia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    contexto_solicitud VARCHAR(255), -- "Explicacion_codigo", "Mentor", "Generar_ruta", etc.
    fue_util BOOLEAN NOT NULL,       -- 1 si le sirvió, 0 si no le sirvió (Botones Like/Dislike)
    fecha_evaluacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Registro de Sesiones Prácticas (Tiempo y Errores - ObjE2)
-- Cada vez que crean o editan un código, se les puede pedir si fue "Creación propia" o "Modificación por error".
CREATE TABLE IF NOT EXISTS progreso_autonomia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo_actividad ENUM('creacion_desde_cero', 'bugfix_sin_ia', 'bugfix_con_ia', 'copiado_y_adaptado') NOT NULL,
    tiempo_estimado_minutos INT DEFAULT 0, -- Minutos que cree que se ahorró
    fecha_actividad TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Tabla para bloquear usuarios
CREATE TABLE IF NOT EXISTS usuarios_bloqueados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    bloqueado_id INT NOT NULL,
    fecha_bloqueo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (bloqueado_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY (usuario_id, bloqueado_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para archivar chats
CREATE TABLE IF NOT EXISTS chats_archivados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    contacto_id INT NOT NULL,
    fecha_archivo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (contacto_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY (usuario_id, contacto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;-- Tabla de Notificaciones
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    emisor_id INT NOT NULL,
    tipo ENUM('like', 'comentario', 'seguidor', 'mensaje', 'fork') NOT NULL,
    mensaje TEXT NOT NULL,
    url VARCHAR(255),
    leido BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (emisor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Rutas Favoritas
CREATE TABLE IF NOT EXISTS rutas_favoritas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    ruta_id INT NOT NULL,
    fecha_guardado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (ruta_id) REFERENCES rutas(id) ON DELETE CASCADE,
    UNIQUE KEY (usuario_id, ruta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Snippets Favoritos
CREATE TABLE IF NOT EXISTS snippets_favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    snippet_id INT NOT NULL,
    fecha_guardado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (snippet_id) REFERENCES snippets(id) ON DELETE CASCADE,
    UNIQUE KEY (usuario_id, snippet_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Vistas de Foro (por IP)
CREATE TABLE IF NOT EXISTS foro_vistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregunta_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    fecha_vista TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pregunta_id) REFERENCES foro_preguntas(id) ON DELETE CASCADE,
    UNIQUE KEY (pregunta_id, ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Comentarios en Respuestas
CREATE TABLE IF NOT EXISTS foro_respuesta_comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    respuesta_id INT NOT NULL,
    usuario_id INT NOT NULL,
    contenido TEXT NOT NULL,
    fecha_comentario TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (respuesta_id) REFERENCES foro_respuestas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



