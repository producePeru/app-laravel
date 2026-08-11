ALTER TABLE actividades_pnte
ADD COLUMN tipo_mercado TINYINT(1) NULL DEFAULT NULL AFTER google_meet_uri,
ADD COLUMN tipo_gestion TINYINT(1) NULL DEFAULT NULL AFTER tipo_mercado;



attendance_template.xlsx

CREATE TABLE tiendas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    ruc VARCHAR(20) NOT NULL UNIQUE,
    envio_id BIGINT UNSIGNED NULL,
    celular VARCHAR(20) NULL,
    correo VARCHAR(255) NULL,
    image_id BIGINT UNSIGNED NULL,
    socials JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT tiendas_image_id_foreign
        FOREIGN KEY (image_id)
        REFERENCES images(id)
        ON DELETE SET NULL
);