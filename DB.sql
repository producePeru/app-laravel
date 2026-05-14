

-- ALTER TABLE mp_eventos
-- ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER aliado,
-- ADD CONSTRAINT fk_mp_eventos_user
-- FOREIGN KEY (user_id) REFERENCES users(id)
-- ON DELETE SET NULL
-- ON UPDATE CASCADE;


-- ALTER TABLE attendancelist
-- ADD COLUMN visible TINYINT(1) DEFAULT 0 AFTER team,
-- ADD COLUMN resultados TEXT AFTER visible,
-- ADD COLUMN cancelado TEXT AFTER resultados,
-- ADD COLUMN reprogramado TEXT AFTER cancelado,
-- ADD COLUMN unidad VARCHAR(10) DEFAULT 'UGO' AFTER reprogramado;


-- ALTER TABLE mp_eventos
-- ADD COLUMN visible TINYINT(1) DEFAULT 0 AFTER user_id,
-- ADD COLUMN resultados TEXT AFTER visible,
-- ADD COLUMN cancelado TEXT AFTER resultados,
-- ADD COLUMN reprogramado TEXT AFTER cancelado,
-- ADD COLUMN unidad VARCHAR(10) DEFAULT 'MP' AFTER reprogramado;


-- ALTER TABLE fairs
-- ADD COLUMN visible TINYINT(1) DEFAULT 0 AFTER user_id,
-- ADD COLUMN resultados TEXT AFTER visible,
-- ADD COLUMN cancelado TEXT AFTER resultados,
-- ADD COLUMN reprogramado TEXT AFTER cancelado,
-- ADD COLUMN unidad VARCHAR(10) AFTER reprogramado;

-- -- en la base de datos cambiar fecha por date tipo date

-- tabla fairs dates JSON
-- mp_eventos dates JSON






CREATE TABLE tipo_actividad (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    unidad TINYINT UNSIGNED NOT NULL COMMENT '1,2,3,4,5',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
);

INSERT INTO tipo_actividad (name, unidad) VALUES
('CAPACITACIÓN', 1),
('CICLO DE CAPACITACIONES', 1),
('DESPEGA TU EMPRESA Y PRODUCE', 1),
('DIFUSIÓN', 1);


CREATE TABLE nombre_actividad (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo_actividad_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (tipo_actividad_id) REFERENCES tipo_actividad(id) ON DELETE CASCADE
);

INSERT INTO nombre_actividad (tipo_actividad_id, name) VALUES
(1, 'POTENCIA TU EMPRESA - FORMALIZACIÓN'),
(1, 'POTENCIA TU EMPRESA - GESTIÓN EMPRESARIAL'),
(1, 'POTENCIA TU EMPRESA - DIGITALIZACIÓN'),
(1, 'POTENCIA TU EMPRESA - DESAROLLO PRODUCTIVO'),
(1, 'POTENCIA TU EMPRESA - ACCESO AL FINANCIAMIENTO'),
(1, 'COMPRAS - SECTOR ECONÓMICO PRIORIZADO'),
(2, 'FORTALECE TU MERCADO'),
(3, 'CAMPAÑA DESPEGA TU EMPRESA'),
(4, 'DIFUSIÓN DE LOS SERVICIOS DEL PNTE');




CREATE TABLE actividades (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    unidad TINYINT UNSIGNED NOT NULL,
    mes TINYINT UNSIGNED NOT NULL,
    fechas JSON NOT NULL,
    cantidad_dias TINYINT UNSIGNED NOT NULL,

    tipo_actividad_id BIGINT UNSIGNED NOT NULL,
    nombre_actividad_id BIGINT UNSIGNED NOT NULL,
    tema VARCHAR(255) NULL,

    region BIGINT UNSIGNED NOT NULL,
    provincia BIGINT UNSIGNED NOT NULL,
    distrito BIGINT UNSIGNED NOT NULL,
    lugar VARCHAR(255) NULL,

    entidad_organizadora VARCHAR(255) NULL,
    entidad_aliada VARCHAR(255) NULL,
    representante_id BIGINT UNSIGNED NULL,

    requiere_pasaje TINYINT(1) NOT NULL DEFAULT 0,
    monto_gasto VARCHAR(255) NULL,

    mypes_beneficiadas INT UNSIGNED NULL,
    modalidad_id BIGINT UNSIGNED NULL,
    total_participantes INT UNSIGNED NULL,
    total_asesorias INT UNSIGNED NULL,
    total_formalizaciones INT UNSIGNED NULL,

    slug VARCHAR(255) NOT NULL,
    cancelado VARCHAR(255) NULL,
    cancelado_por_id BIGINT UNSIGNED NULL,
    reprogramado VARCHAR(255) NULL,
    reprogramado_por_id BIGINT UNSIGNED NULL,
    registrado_por_id BIGINT UNSIGNED NULL,
    actualizado_por_id BIGINT UNSIGNED NULL,

    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    -- UNIQUE
    UNIQUE KEY actividades_slug_unique (slug),

    -- FOREIGN KEYS (estilo Laravel: nombreTabla_campo_foreign)
    CONSTRAINT actividades_tipo_actividad_id_foreign
        FOREIGN KEY (tipo_actividad_id) REFERENCES tipo_actividad(id) ON DELETE RESTRICT,

    CONSTRAINT actividades_nombre_actividad_id_foreign
        FOREIGN KEY (nombre_actividad_id) REFERENCES nombre_actividad(id) ON DELETE RESTRICT,

    CONSTRAINT actividades_region_foreign
        FOREIGN KEY (region) REFERENCES cities(id) ON DELETE RESTRICT,

    CONSTRAINT actividades_provincia_foreign
        FOREIGN KEY (provincia) REFERENCES provinces(id) ON DELETE RESTRICT,

    CONSTRAINT actividades_distrito_foreign
        FOREIGN KEY (distrito) REFERENCES districts(id) ON DELETE RESTRICT,

    CONSTRAINT actividades_representante_id_foreign
        FOREIGN KEY (representante_id) REFERENCES users(id) ON DELETE SET NULL,

    CONSTRAINT actividades_modalidad_id_foreign
        FOREIGN KEY (modalidad_id) REFERENCES modalities(id) ON DELETE SET NULL,

    CONSTRAINT actividades_cancelado_por_id_foreign
        FOREIGN KEY (cancelado_por_id) REFERENCES users(id) ON DELETE SET NULL,

    CONSTRAINT actividades_reprogramado_por_id_foreign
        FOREIGN KEY (reprogramado_por_id) REFERENCES users(id) ON DELETE SET NULL,

    CONSTRAINT actividades_registrado_por_id_foreign
        FOREIGN KEY (registrado_por_id) REFERENCES users(id) ON DELETE SET NULL,

    CONSTRAINT actividades_actualizado_por_id_foreign
        FOREIGN KEY (actualizado_por_id) REFERENCES users(id) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;













-- PARA LOS PARTICIPANTES

CREATE TABLE sectores_economicos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE rubros (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE actividades_comerciales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    rubro_id BIGINT UNSIGNED NOT NULL,

    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT fk_actividades_rubro
        FOREIGN KEY (rubro_id)
        REFERENCES rubros(id)
        ON DELETE CASCADE
);

CREATE TABLE cargo_empresa (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,

    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE participantes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Empresa
    ruc CHAR(11) NOT NULL,
    razon_social VARCHAR(255) NOT NULL,
    nombre_comercial VARCHAR(255) NULL,

    sector_economico_id BIGINT UNSIGNED NULL,
    rubro_id BIGINT UNSIGNED NULL,
    actividad_comercial_id BIGINT UNSIGNED NULL,

    region_id BIGINT UNSIGNED NULL,
    provincia_id BIGINT UNSIGNED NULL,
    distrito_id BIGINT UNSIGNED NULL,

    direccion VARCHAR(255) NULL,

    -- Persona
    tipo_documento_id BIGINT UNSIGNED NULL,
    numero_dni VARCHAR(12) NULL,

    apellido_paterno VARCHAR(150) NULL,
    apellido_materno VARCHAR(150) NULL,
    nombres VARCHAR(150) NULL,

    genero_id BIGINT UNSIGNED NULL,
    discapacidad TINYINT(1) DEFAULT 0,

    celular CHAR(9) NULL,
    correo_electronico VARCHAR(255) NULL,

    cargo_empresa_id BIGINT UNSIGNED NULL,

    fecha_nacimiento DATE NULL,
    edad TINYINT UNSIGNED NULL,

    -- Timestamps
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,

    -- 🔗 FOREIGN KEYS
    CONSTRAINT fk_empresa_sector
        FOREIGN KEY (sector_economico_id)
        REFERENCES sectores_economicos(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_empresa_rubro
        FOREIGN KEY (rubro_id)
        REFERENCES rubros(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_empresa_actividad
        FOREIGN KEY (actividad_comercial_id)
        REFERENCES actividades_comerciales(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_empresa_region
        FOREIGN KEY (region_id)
        REFERENCES cities(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_empresa_provincia
        FOREIGN KEY (provincia_id)
        REFERENCES provinces(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_empresa_distrito
        FOREIGN KEY (distrito_id)
        REFERENCES districts(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_empresa_tipo_doc
        FOREIGN KEY (tipo_documento_id)
        REFERENCES typedocuments(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_empresa_genero
        FOREIGN KEY (genero_id)
        REFERENCES genders(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_empresa_cargo
        FOREIGN KEY (cargo_empresa_id)
        REFERENCES cargo_empresa(id)
        ON DELETE SET NULL
);






CREATE TABLE advisories_cooperativa (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    advisory_id BIGINT UNSIGNED NOT NULL,

    ruc CHAR(11) NULL,

    nombre VARCHAR(255) NULL,

    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT fk_advisories_cooperativa_advisory
        FOREIGN KEY (advisory_id)
        REFERENCES advisories(id)
        ON DELETE CASCADE
);