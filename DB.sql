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
-- =========================================================

CREATE TABLE convenios_contactos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    convenio_id BIGINT UNSIGNED NOT NULL,

    tipo ENUM(
        'repProduce',
        'repContraparte',
        'coordProduce',
        'coordContraparte'
    ) NOT NULL,

    nombre VARCHAR(255) NOT NULL,
    correo VARCHAR(255) NULL,
    celular VARCHAR(20) NULL,

    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT fk_convenios_contactos_convenio
        FOREIGN KEY (convenio_id)
        REFERENCES agreements(id)
        ON DELETE CASCADE,

    INDEX idx_convenios_contactos_convenio (convenio_id)
);


-- =========================================================
-- 2. COMPROMISOS DEL CONVENIO
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

    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT fk_convenios_compromisos_convenio
        FOREIGN KEY (convenio_id)
        REFERENCES agreements(id)
        ON DELETE CASCADE,

    INDEX idx_convenios_compromisos_convenio (convenio_id)
);


-- =========================================================
-- 3. ADENDAS DEL CONVENIO
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
        REFERENCES agreements(id)
        ON DELETE CASCADE,

    UNIQUE KEY uk_convenios_adendas_numero (
        convenio_id,
        numero
    ),

    INDEX idx_convenios_adendas_convenio (convenio_id)
);


-- =========================================================
-- 4. INFORMACIÓN DE GESTIÓN DEL CONVENIO
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

    avances TEXT NULL,
    observaciones TEXT NULL,

    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT fk_convenios_gestion_convenio
        FOREIGN KEY (convenio_id)
        REFERENCES agreements(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_convenios_gestion_responsable_produce
        FOREIGN KEY (responsable_produce)
        REFERENCES users(id)
        ON DELETE SET NULL,

    UNIQUE KEY uk_convenios_gestion_convenio (convenio_id)
);