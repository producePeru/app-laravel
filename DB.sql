-- =========================================================
-- CONVENIOS UGSE · Esquema MySQL de referencia
-- Tablas: convenios, contactos, compromisos, avances por corte,
-- evidencias, adendas, gestión y archivos.
-- =========================================================

CREATE TABLE convenios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    institucion VARCHAR(255) NULL,
    nombre_convenio VARCHAR(255) NULL,
    objeto TEXT NULL,

    fecha_emision DATE NULL,
    inicio_vigencia DATE NULL,

    tipo_renovacion ENUM(
        'manual',
        'automatico'
    ) NULL,

    plazo_renovacion_anios INT UNSIGNED NULL,
    vencimiento DATE NULL,

    estado VARCHAR(50) NULL,

    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
);



-- =========================================================
-- 1. CONTACTOS DEL CONVENIO
-- Representantes y coordinadores (contraparte admite 2: ...1 y ...2)
-- =========================================================

CREATE TABLE convenios_contactos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    convenio_id BIGINT UNSIGNED NOT NULL,

    tipo ENUM(
        'repProduce',
        'repContraparte',
        'repContraparte2',
        'coordProduce',
        'coordContraparte',
        'coordContraparte2'
    ) NOT NULL,

    nombre VARCHAR(255) NOT NULL,
    correo VARCHAR(255) NULL,
    celular VARCHAR(20) NULL,

    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT fk_convenios_contactos_convenio
        FOREIGN KEY (convenio_id)
        REFERENCES convenios(id)
        ON DELETE CASCADE,

    INDEX idx_convenios_contactos_convenio (convenio_id)
);



-- =========================================================
-- 2. COMPROMISOS DEL CONVENIO
-- realizado / actividad quedan como foto del corte vigente
-- (el detalle por corte vive en convenios_compromiso_avances)
-- =========================================================

CREATE TABLE convenios_compromisos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    convenio_id BIGINT UNSIGNED NOT NULL,

    tipo ENUM(
        'produce',
        'contraparte',
        'partes'
    ) NOT NULL,

    compromiso TEXT NOT NULL,
    orden INT UNSIGNED DEFAULT 0,
    realizado VARCHAR(2) NULL COMMENT 'SI/NO según columna SE REALIZÓ de la plantilla',
    actividad TEXT NULL,

    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT fk_convenios_compromisos_convenio
        FOREIGN KEY (convenio_id)
        REFERENCES convenios(id)
        ON DELETE CASCADE,

    INDEX idx_convenios_compromisos_convenio (convenio_id)
);



-- =========================================================
-- 2B. AVANCES POR CORTE SEMESTRAL (cortes al 30/06 y 31/12)
-- Cada corte empieza vacío; user_id = quién llenó la actividad
-- =========================================================

CREATE TABLE convenios_compromiso_avances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    compromiso_id BIGINT UNSIGNED NOT NULL,

    corte INT UNSIGNED NOT NULL DEFAULT 1,
    desde DATE NULL,
    hasta DATE NULL,

    realizado VARCHAR(2) NULL,
    actividad TEXT NULL,
    user_id BIGINT UNSIGNED NULL,

    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT fk_avances_compromiso
        FOREIGN KEY (compromiso_id)
        REFERENCES convenios_compromisos(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_avances_usuario
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE SET NULL,

    UNIQUE KEY uk_avance_compromiso_corte (compromiso_id, corte),
    INDEX idx_avances_corte (corte)
);



-- =========================================================
-- 2C. MEDIOS DE VERIFICACIÓN (archivos por avance/corte)
-- avance_id NULL = histórico del corte 1; user_id = quién subió
-- =========================================================

CREATE TABLE convenios_compromisos_evidencias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    compromiso_id BIGINT UNSIGNED NOT NULL,
    avance_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,

    nombre_original VARCHAR(255) NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    mime VARCHAR(255) NULL,
    tamano BIGINT UNSIGNED NOT NULL DEFAULT 0,

    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT fk_evidencias_compromiso
        FOREIGN KEY (compromiso_id)
        REFERENCES convenios_compromisos(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_evidencias_avance
        FOREIGN KEY (avance_id)
        REFERENCES convenios_compromiso_avances(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_evidencias_usuario
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE SET NULL,

    INDEX idx_evidencias_compromiso (compromiso_id),
    INDEX idx_evidencias_avance (avance_id)
);



-- =========================================================
-- 3. ADENDAS DEL CONVENIO (extienden la fecha de fin y los cortes)
-- =========================================================

CREATE TABLE convenios_adendas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    convenio_id BIGINT UNSIGNED NOT NULL,

    numero INT UNSIGNED NOT NULL,
    anios INT UNSIGNED NOT NULL,

    desde DATE NOT NULL,
    hasta DATE NOT NULL,

    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT fk_convenios_adendas_convenio
        FOREIGN KEY (convenio_id)
        REFERENCES convenios(id)
        ON DELETE CASCADE,

    UNIQUE KEY uk_convenios_adendas_numero (
        convenio_id,
        numero
    ),

    INDEX idx_convenios_adendas_convenio (convenio_id)
);



-- =========================================================
-- 4. INFORMACIÓN DE GESTIÓN DEL CONVENIO
-- Responsable de contraparte: nombre + cargo + correo + celular
-- =========================================================

CREATE TABLE convenios_gestion (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    convenio_id BIGINT UNSIGNED NOT NULL,

    meta TEXT NULL,
    financiamiento TEXT NULL,
    plan_actividades TEXT NULL,
    plan_validado BOOLEAN NOT NULL DEFAULT FALSE,
    plazo_reportes TEXT NULL,
    para_resolucion TEXT NULL,

    responsable_produce BIGINT UNSIGNED NULL,
    responsable_contraparte VARCHAR(255) NULL,
    responsable_contraparte_cargo VARCHAR(255) NULL,
    responsable_contraparte_correo VARCHAR(255) NULL,
    responsable_contraparte_celular VARCHAR(20) NULL,

    avances TEXT NULL,
    observaciones TEXT NULL,

    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT fk_convenios_gestion_convenio
        FOREIGN KEY (convenio_id)
        REFERENCES convenios(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_convenios_gestion_responsable_produce
        FOREIGN KEY (responsable_produce)
        REFERENCES users(id)
        ON DELETE SET NULL,

    UNIQUE KEY uk_convenios_gestion_convenio (convenio_id)
);



-- =========================================================
-- 5. DOCUMENTOS DEL CONVENIO (convenios_archivos)
-- =========================================================

CREATE TABLE convenios_archivos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    convenio_id BIGINT UNSIGNED NOT NULL,

    nombre_original VARCHAR(255) NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    mime VARCHAR(255) NULL,
    tamano BIGINT UNSIGNED NOT NULL DEFAULT 0,

    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT fk_convenios_archivos_convenio
        FOREIGN KEY (convenio_id)
        REFERENCES convenios(id)
        ON DELETE CASCADE,

    INDEX idx_convenios_archivos_convenio (convenio_id)
);
