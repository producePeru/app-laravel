-- ALTER TABLE `agreements`
-- ADD COLUMN `nombre` VARCHAR(255) NULL AFTER `created_id`,
-- ADD COLUMN `fecha_adenda` DATE NULL AFTER `nombre`,
-- ADD COLUMN `alternos` JSON NULL AFTER `fecha_adenda`,
-- ADD COLUMN `alternos_aliados` JSON NULL AFTER `alternos`,
-- ADD COLUMN `cuenta_plan_trabajo` TINYINT(1) NULL AFTER `alternos_aliados`;





-- CREATE TABLE archivos (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

--     nombre_original VARCHAR(255) NOT NULL,
--     nombre_archivo VARCHAR(255) NOT NULL,
--     extension VARCHAR(20) NULL,
--     mime_type VARCHAR(100) NULL,

--     ruta VARCHAR(500) NOT NULL,

--     tamanio BIGINT UNSIGNED NULL,

--     descripcion VARCHAR(500) NULL,

--     usuario_id BIGINT UNSIGNED NULL,

--     created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

--     INDEX idx_extension (extension),
--     INDEX idx_mime_type (mime_type),
--     INDEX idx_usuario_id (usuario_id)
-- );


-- CREATE TABLE archivos_ferias (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

--     archivo_id BIGINT UNSIGNED NOT NULL,
--     empresario_id BIGINT UNSIGNED NOT NULL,

--     created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

--     CONSTRAINT fk_archivos_ferias_archivo
--         FOREIGN KEY (archivo_id)
--         REFERENCES archivos(id)
--         ON DELETE CASCADE,

--     CONSTRAINT fk_archivos_ferias_empresario
--         FOREIGN KEY (empresario_id)
--         REFERENCES empresarios(id)
--         ON DELETE CASCADE,

--     UNIQUE KEY uk_archivo_empresario (archivo_id, empresario_id),

--     INDEX idx_empresario_id (empresario_id),
--     INDEX idx_archivo_id (archivo_id)
-- );














-- =========================================================
-- ESQUEMA: Gestión de Convenios Institucionales
-- =========================================================
-- Diseño normalizado a partir de un JSON de convenio.
-- 4 tablas: convenios (principal), contactos (por rol),
-- compromisos (por tipo de parte), adendas (historial).
-- =========================================================

CREATE DATABASE IF NOT EXISTS convenios_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE convenios_db;

-- ---------------------------------------------------------
-- Tabla opcional: usuarios/empleados internos de la institución
-- (responsableProduce = 41 sugiere que ya existe algo así;
--  si no la tienes, comenta el FK correspondiente más abajo)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    correo VARCHAR(255),
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 1. Tabla principal: convenios
-- ---------------------------------------------------------
CREATE TABLE convenios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institucion            VARCHAR(255) NOT NULL,
    nombre_convenio         VARCHAR(255) NOT NULL,
    objeto                  TEXT,

    fecha_emision           DATE,
    inicio_vigencia         DATE,
    vencimiento             DATE,

    tipo_renovacion         ENUM('automatico','manual','sin_renovacion') DEFAULT 'manual',
    plazo_renovacion_anios  INT DEFAULT NULL,

    status                  ENUM('VIGENTE','VENCIDO','RESUELTO','SUSPENDIDO') DEFAULT 'VIGENTE',

    meta                    TEXT,
    financiamiento          TEXT,
    plan_actividades        TEXT,
    plazo_reportes          TEXT,
    para_resolucion         TEXT,

    responsable_produce_id  INT NULL,           -- FK -> usuarios.id (interno)
    responsable_contraparte VARCHAR(255),        -- texto libre (persona externa)

    avances                 TEXT,
    observaciones           TEXT,

    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_conv_resp_produce
        FOREIGN KEY (responsable_produce_id) REFERENCES users(id)
        ON DELETE SET NULL,

    INDEX idx_status (status),
    INDEX idx_vencimiento (vencimiento)
) ENGINE=InnoDB;

-- Nota: "diasTranscurridos" NO se guarda como columna.
-- Es un valor derivado; se calcula al consultar:
--   SELECT id, DATEDIFF(CURDATE(), inicio_vigencia) AS dias_transcurridos
--   FROM convenios;

-- ---------------------------------------------------------
-- 2. Contactos (reemplaza repProduce/repContraparte/coordProduce/coordContraparte)
-- ---------------------------------------------------------
CREATE TABLE contactos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    convenio_id INT NOT NULL,
    rol         ENUM('rep_produce','rep_contraparte','coord_produce','coord_contraparte') NOT NULL,
    nombre      VARCHAR(255) NOT NULL,
    correo      VARCHAR(255),
    celular     VARCHAR(20),

    CONSTRAINT fk_contacto_convenio
        FOREIGN KEY (convenio_id) REFERENCES convenios(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_convenio_rol (convenio_id, rol)  -- un contacto por rol por convenio
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 3. Compromisos (reemplaza produce[]/contraparte[]/partes[])
-- ---------------------------------------------------------
CREATE TABLE compromisos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    convenio_id INT NOT NULL,
    tipo        ENUM('produce','contraparte','partes') NOT NULL,
    orden       INT NOT NULL DEFAULT 1,       -- posición dentro de la lista original
    descripcion TEXT NOT NULL,

    CONSTRAINT fk_compromiso_convenio
        FOREIGN KEY (convenio_id) REFERENCES convenios(id)
        ON DELETE CASCADE,

    INDEX idx_convenio_tipo (convenio_id, tipo)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 4. Adendas (llega vacío hoy, pero es un historial que crecerá)
-- ---------------------------------------------------------
CREATE TABLE adendas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    convenio_id INT NOT NULL,
    numero      INT NOT NULL,                 -- adenda N° 1, 2, 3...
    fecha       DATE,
    descripcion TEXT,
    archivo_url VARCHAR(500),                 -- si vas a adjuntar el PDF de la adenda

    CONSTRAINT fk_adenda_convenio
        FOREIGN KEY (convenio_id) REFERENCES convenios(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_convenio_numero (convenio_id, numero)
) ENGINE=InnoDB;

-- =========================================================
-- EJEMPLO DE INSERCIÓN con los datos que compartiste
-- =========================================================

INSERT INTO usuarios (id, nombre) VALUES (41, 'Responsable Produce #41')
    ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO convenios (
    institucion, nombre_convenio, objeto,
    fecha_emision, inicio_vigencia, vencimiento,
    tipo_renovacion, plazo_renovacion_anios, status,
    meta, financiamiento, plan_actividades, plazo_reportes, para_resolucion,
    responsable_produce_id, responsable_contraparte,
    avances, observaciones
) VALUES (
    'Voluptatum error mai', 'Elit labore volupta', 'Enim adipisicing con',
    '2026-09-25', '2026-09-26', '2027-09-26',
    'automatico', 1, 'VIGENTE',
    'Nihil officiis labor', 'Magna iusto dolore s', 'Hic pariatur Praese', 'Porro enim eaque ali', 'Eos anim temporibus ',
    41, 'Voluptatum ad aut no',
    'Reprehenderit est no', 'Est asperiores maior'
);

SET @convenio_id = LAST_INSERT_ID();

INSERT INTO contactos (convenio_id, rol, nombre, correo, celular) VALUES
    (@convenio_id, 'rep_produce',       'Unde ut ea iure est ',  'rivet@mailinator.com',     '999999995'),
    (@convenio_id, 'rep_contraparte',   'Velit quae non Nam e',  'dubil@mailinator.com',     '999399995'),
    (@convenio_id, 'coord_produce',     'Veniam consectetur',    'bygywezos@mailinator.com', '349999995'),
    (@convenio_id, 'coord_contraparte', 'Dolor voluptas volup',  'joma@mailinator.com',      '944999995');

INSERT INTO compromisos (convenio_id, tipo, orden, descripcion) VALUES
    (@convenio_id, 'produce', 1, 'Lorem ipsum dolor sit amet consectetur adipiscing elit...'),
    (@convenio_id, 'produce', 2, 'Lorem ipsum dolor sit amet consectetur adipiscing elit...'),
    (@convenio_id, 'produce', 3, 'Lorem ipsum dolor sit amet consectetur adipiscing elit...'),
    (@convenio_id, 'produce', 4, 'Lorem ipsum dolor sit amet consectetur adipiscing elit...'),
    (@convenio_id, 'contraparte', 1, 'Lorem ipsum dolor sit amet consectetur adipiscing elit...'),
    (@convenio_id, 'contraparte', 2, 'Lorem ipsum dolor sit amet consectetur adipiscing elit...'),
    (@convenio_id, 'contraparte', 3, 'Lorem ipsum dolor sit amet consectetur adipiscing elit...'),
    (@convenio_id, 'contraparte', 4, 'Lorem ipsum dolor sit amet consectetur adipiscing elit...'),
    (@convenio_id, 'partes', 1, 'Lorem ipsum dolor sit amet consectetur adipiscing elit...'),
    (@convenio_id, 'partes', 2, 'Lorem ipsum dolor sit amet consectetur adipiscing elit...');

-- =========================================================
-- CONSULTAS ÚTILES
-- =========================================================

-- Convenio completo con días transcurridos calculados al vuelo
-- SELECT c.*, DATEDIFF(CURDATE(), c.inicio_vigencia) AS dias_transcurridos
-- FROM convenios c WHERE c.id = 1;

-- Todos los contactos de un convenio
-- SELECT rol, nombre, correo, celular FROM contactos WHERE convenio_id = 1;

-- Compromisos agrupados por tipo, en orden
-- SELECT tipo, orden, descripcion FROM compromisos WHERE convenio_id = 1 ORDER BY tipo, orden;