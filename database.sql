-- ============================================================
--  SISTEMA DE CONTROL DE ACCESO - SALA DE SISTEMAS
--  Base de Datos: sala_sistemas
--  Autor: Sistema de Control de Acceso
-- ============================================================

CREATE DATABASE IF NOT EXISTS sala_sistemas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sala_sistemas;

-- ============================================================
-- TABLA: usuarios
-- Almacena información de estudiantes y profesores
-- RESTRICCIONES IMPLEMENTADAS:
--   1. PRIMARY KEY (id_usuario)
--   2. NOT NULL en nombre, apellido, tipo_usuario
--   3. UNIQUE en numero_identificacion
--   4. CHECK en tipo_usuario (solo 'estudiante' o 'profesor')
--   5. DEFAULT en estado
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario          INT             AUTO_INCREMENT,
    numero_identificacion VARCHAR(20)   NOT NULL,                          -- RESTRICCIÓN 2: NOT NULL
    nombre              VARCHAR(100)    NOT NULL,                          -- RESTRICCIÓN 2: NOT NULL
    apellido            VARCHAR(100)    NOT NULL,                          -- RESTRICCIÓN 2: NOT NULL
    tipo_usuario        ENUM('estudiante','profesor') NOT NULL,            -- RESTRICCIÓN 4: CHECK implícito con ENUM
    programa_facultad   VARCHAR(150)    DEFAULT 'No especificado',         -- RESTRICCIÓN 5: DEFAULT
    email               VARCHAR(150)    NULL,
    telefono            VARCHAR(20)     NULL,
    estado              ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    fecha_registro      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_usuarios   PRIMARY KEY (id_usuario),                    -- RESTRICCIÓN 1: PRIMARY KEY
    CONSTRAINT uq_identificacion UNIQUE (numero_identificacion)            -- RESTRICCIÓN 3: UNIQUE
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: actividades_catalogo
-- Catálogo de actividades permitidas en la sala
-- RESTRICCIONES IMPLEMENTADAS:
--   - PRIMARY KEY (id_actividad)
--   - NOT NULL en nombre_actividad
-- ============================================================
CREATE TABLE IF NOT EXISTS actividades_catalogo (
    id_actividad        INT             AUTO_INCREMENT,
    nombre_actividad    VARCHAR(150)    NOT NULL,                          -- RESTRICCIÓN 2: NOT NULL
    descripcion         TEXT            NULL,
    aplica_para         ENUM('estudiante','profesor','ambos') NOT NULL DEFAULT 'ambos',
    CONSTRAINT pk_actividades PRIMARY KEY (id_actividad)                  -- RESTRICCIÓN 1: PRIMARY KEY
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: registros_acceso
-- Registra entradas y salidas de la sala
-- RESTRICCIONES IMPLEMENTADAS:
--   - PRIMARY KEY (id_registro)
--   - FOREIGN KEY referenciando usuarios y actividades_catalogo
--   - NOT NULL en id_usuario, tipo_movimiento
-- ============================================================
CREATE TABLE IF NOT EXISTS registros_acceso (
    id_registro         INT             AUTO_INCREMENT,
    id_usuario          INT             NOT NULL,                          -- RESTRICCIÓN 2: NOT NULL
    id_actividad        INT             NOT NULL,                          -- RESTRICCIÓN 2: NOT NULL
    tipo_movimiento     ENUM('entrada','salida') NOT NULL,                 -- RESTRICCIÓN 4: ENUM
    fecha_hora          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observaciones       TEXT            NULL,
    equipo_asignado     VARCHAR(50)     NULL,
    registrado_por      VARCHAR(100)    DEFAULT 'Sistema',
    CONSTRAINT pk_registros    PRIMARY KEY (id_registro),                 -- RESTRICCIÓN 1: PRIMARY KEY
    CONSTRAINT fk_usuario      FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_actividad    FOREIGN KEY (id_actividad)
        REFERENCES actividades_catalogo(id_actividad) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: administradores
-- Administradores del sistema
-- ============================================================
CREATE TABLE IF NOT EXISTS administradores (
    id_admin            INT             AUTO_INCREMENT,
    username            VARCHAR(50)     NOT NULL UNIQUE,
    password_hash       VARCHAR(255)    NOT NULL,
    nombre_completo     VARCHAR(200)    NOT NULL,
    email               VARCHAR(150)    NOT NULL,
    ultimo_acceso       TIMESTAMP       NULL,
    CONSTRAINT pk_admins PRIMARY KEY (id_admin)
) ENGINE=InnoDB;

-- ============================================================
-- DATOS INICIALES
-- ============================================================

-- Administrador por defecto (password: admin123)
INSERT INTO administradores (username, password_hash, nombre_completo, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Principal', 'admin@sala.edu.co');

-- Catálogo de actividades
INSERT INTO actividades_catalogo (nombre_actividad, descripcion, aplica_para) VALUES
('Trabajo de grado / Tesis',         'Desarrollo y avance de trabajo de grado',        'estudiante'),
('Clase magistral',                  'Dictado de clase por parte del docente',          'profesor'),
('Práctica de laboratorio',          'Práctica de laboratorio de programación',         'ambos'),
('Investigación académica',          'Consulta e investigación bibliográfica',          'ambos'),
('Desarrollo de proyectos',          'Desarrollo de proyectos de software',             'estudiante'),
('Preparación de material docente',  'Preparación de guías y material de enseñanza',   'profesor'),
('Examen / Evaluación',              'Presentación o aplicación de evaluación',         'ambos'),
('Consulta de bases de datos',       'Acceso a recursos y bases de datos académicas',   'ambos'),
('Diseño gráfico / Multimedia',      'Trabajo con herramientas de diseño',              'ambos'),
('Práctica libre',                   'Uso libre del equipo con fines académicos',       'estudiante');

-- Usuarios de ejemplo
INSERT INTO usuarios (numero_identificacion, nombre, apellido, tipo_usuario, programa_facultad, email) VALUES
('1061234567', 'Carlos',    'Rodríguez',  'estudiante', 'Ingeniería de Sistemas',         'carlos.rodriguez@uni.edu.co'),
('1071234568', 'Ana',       'González',   'estudiante', 'Ingeniería de Sistemas',         'ana.gonzalez@uni.edu.co'),
('1081234569', 'Luis',      'Martínez',   'estudiante', 'Tecnología en Redes',            'luis.martinez@uni.edu.co'),
('1091234570', 'María',     'López',      'profesor',   'Facultad de Ingeniería',         'maria.lopez@uni.edu.co'),
('1001234571', 'Jorge',     'Pérez',      'profesor',   'Facultad de Ciencias Exactas',   'jorge.perez@uni.edu.co'),
('1011234572', 'Sofía',     'Ramírez',    'estudiante', 'Ingeniería de Sistemas',         'sofia.ramirez@uni.edu.co'),
('1021234573', 'Andrés',    'Torres',     'estudiante', 'Tecnología en Sistemas',         'andres.torres@uni.edu.co'),
('1031234574', 'Patricia',  'Vargas',     'profesor',   'Facultad de Ingeniería',         'patricia.vargas@uni.edu.co');

-- Registros de acceso de ejemplo
INSERT INTO registros_acceso (id_usuario, id_actividad, tipo_movimiento, observaciones, equipo_asignado) VALUES
(1, 3,  'entrada', 'Práctica de POO',               'PC-01'),
(2, 5,  'entrada', 'Avance proyecto semestral',      'PC-02'),
(4, 2,  'entrada', 'Clase Bases de Datos',           'PC-DOCENTE'),
(1, 3,  'salida',  'Práctica completada',            'PC-01'),
(3, 8,  'entrada', 'Consulta IEEE',                  'PC-03'),
(5, 2,  'entrada', 'Clase Algoritmos',               'PC-DOCENTE'),
(6, 7,  'entrada', 'Examen parcial Redes',           'PC-05'),
(2, 5,  'salida',  'Proyecto guardado',              'PC-02'),
(7, 1,  'entrada', 'Avance trabajo de grado',        'PC-04'),
(8, 6,  'entrada', 'Preparación parcial',            'PC-DOCENTE');


-- ============================================================
-- PROCEDIMIENTO ALMACENADO
-- Registra una entrada o salida validando reglas de negocio
-- ============================================================
DELIMITER $$

CREATE PROCEDURE sp_registrar_acceso(
    IN  p_id_usuario        INT,
    IN  p_id_actividad      INT,
    IN  p_tipo_movimiento   VARCHAR(10),
    IN  p_observaciones     TEXT,
    IN  p_equipo            VARCHAR(50),
    OUT p_resultado         VARCHAR(200)
)
BEGIN
    DECLARE v_existe_usuario    INT DEFAULT 0;
    DECLARE v_estado_usuario    VARCHAR(20);
    DECLARE v_ultimo_movimiento VARCHAR(10);
    DECLARE v_aplica_actividad  VARCHAR(20);
    DECLARE v_tipo_usuario      VARCHAR(20);

    -- Verificar que el usuario existe y está activo
    SELECT COUNT(*), estado, tipo_usuario
    INTO v_existe_usuario, v_estado_usuario, v_tipo_usuario
    FROM usuarios
    WHERE id_usuario = p_id_usuario;

    IF v_existe_usuario = 0 THEN
        SET p_resultado = 'ERROR: Usuario no encontrado en el sistema.';
    ELSEIF v_estado_usuario = 'inactivo' THEN
        SET p_resultado = 'ERROR: El usuario se encuentra inactivo.';
    ELSE
        -- Verificar que la actividad aplica para el tipo de usuario
        SELECT aplica_para INTO v_aplica_actividad
        FROM actividades_catalogo
        WHERE id_actividad = p_id_actividad;

        IF v_aplica_actividad != 'ambos' AND v_aplica_actividad != v_tipo_usuario THEN
            SET p_resultado = CONCAT('ERROR: La actividad no aplica para el tipo de usuario (', v_tipo_usuario, ').');
        ELSE
            -- Verificar lógica entrada/salida (no puede haber dos entradas seguidas sin salida)
            SELECT tipo_movimiento INTO v_ultimo_movimiento
            FROM registros_acceso
            WHERE id_usuario = p_id_usuario
            ORDER BY fecha_hora DESC, id_registro DESC
            LIMIT 1;

            IF p_tipo_movimiento = 'entrada' AND v_ultimo_movimiento = 'entrada' THEN
                SET p_resultado = 'ADVERTENCIA: Se registra entrada aunque el último movimiento también fue entrada. Registro guardado.';
            ELSEIF p_tipo_movimiento = 'salida' AND (v_ultimo_movimiento IS NULL OR v_ultimo_movimiento = 'salida') THEN
                SET p_resultado = 'ADVERTENCIA: Se registra salida aunque no hay entrada previa registrada. Registro guardado.';
            ELSE
                SET p_resultado = 'OK: Registro exitoso.';
            END IF;

            -- Insertar el registro en cualquier caso (con o sin advertencia)
            INSERT INTO registros_acceso (id_usuario, id_actividad, tipo_movimiento, observaciones, equipo_asignado)
            VALUES (p_id_usuario, p_id_actividad, p_tipo_movimiento, p_observaciones, p_equipo);
        END IF;
    END IF;
END$$

DELIMITER ;


-- ============================================================
-- ============================================================
--  CONSULTAS ESPECIALES (UNIÓN, INTERSECCIÓN, DIFERENCIA)
--  Estas consultas están embebidas en el PHP pero se documentan
--  aquí para sustentación académica.
-- ============================================================

-- ============================================================
-- A) UNIÓN
-- Obtiene TODOS los usuarios (estudiantes Y profesores) que han
-- registrado algún movimiento en la sala, unificando en un solo
-- resultado sin duplicados.
-- Uso en la aplicación: Reporte "Todos los usuarios con actividad".
-- ============================================================
-- SELECT u.nombre, u.apellido, 'estudiante' AS tipo, u.programa_facultad
-- FROM usuarios u
-- INNER JOIN registros_acceso r ON u.id_usuario = r.id_usuario
-- WHERE u.tipo_usuario = 'estudiante'
-- UNION
-- SELECT u.nombre, u.apellido, 'profesor' AS tipo, u.programa_facultad
-- FROM usuarios u
-- INNER JOIN registros_acceso r ON u.id_usuario = r.id_usuario
-- WHERE u.tipo_usuario = 'profesor'
-- ORDER BY tipo, apellido;


-- ============================================================
-- B) INTERSECCIÓN (simulada con INNER JOIN / IN)
-- MySQL no soporta INTERSECT directamente; se simula con IN/EXISTS.
-- Obtiene usuarios que tienen TANTO entradas COMO salidas registradas,
-- es decir, personas que han completado al menos un ciclo entrada-salida.
-- Uso en la aplicación: Reporte "Usuarios con ciclo completo".
-- ============================================================
-- SELECT DISTINCT u.id_usuario, u.nombre, u.apellido, u.tipo_usuario
-- FROM usuarios u
-- WHERE u.id_usuario IN (
--     SELECT id_usuario FROM registros_acceso WHERE tipo_movimiento = 'entrada'
-- )
-- AND u.id_usuario IN (
--     SELECT id_usuario FROM registros_acceso WHERE tipo_movimiento = 'salida'
-- )
-- ORDER BY u.apellido;


-- ============================================================
-- C) DIFERENCIA (simulada con NOT IN / NOT EXISTS)
-- MySQL no soporta EXCEPT directamente; se simula con NOT IN.
-- Obtiene usuarios registrados en el sistema que NUNCA han
-- ingresado a la sala (sin ningún registro de acceso).
-- Uso en la aplicación: Reporte "Usuarios sin actividad en sala".
-- ============================================================
-- SELECT u.id_usuario, u.nombre, u.apellido, u.tipo_usuario, u.programa_facultad
-- FROM usuarios u
-- WHERE u.id_usuario NOT IN (
--     SELECT DISTINCT id_usuario FROM registros_acceso
-- )
-- ORDER BY u.tipo_usuario, u.apellido;
