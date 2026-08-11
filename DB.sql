-- ALTER TABLE actividades_pnte
-- ADD COLUMN tipo_mercado TINYINT(1) NULL DEFAULT NULL AFTER trainer_id,
-- ADD COLUMN tipo_gestion TINYINT(1) NULL DEFAULT NULL AFTER tipo_mercado;



-- attendance_template.xlsx

-- CREATE TABLE tiendas (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     nombre VARCHAR(255) NOT NULL,
--     descripcion TEXT NULL,
--     ruc VARCHAR(20) NOT NULL UNIQUE,
--     envio_id BIGINT UNSIGNED NULL,
--     celular VARCHAR(20) NULL,
--     correo VARCHAR(255) NULL,
--     image_id BIGINT UNSIGNED NULL,
--     socials JSON NULL,
--     created_at TIMESTAMP NULL,
--     updated_at TIMESTAMP NULL,
--     deleted_at TIMESTAMP NULL DEFAULT NULL,

--     CONSTRAINT tiendas_image_id_foreign
--         FOREIGN KEY (image_id)
--         REFERENCES images(id)
--         ON DELETE SET NULL
-- );



-- CREATE TABLE tiendas_contactos (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     nombre VARCHAR(255) NOT NULL,
--     celular VARCHAR(20) NULL,
--     correo VARCHAR(255) NULL,
--     productos TEXT NULL,
--     id_empresa BIGINT UNSIGNED NOT NULL,
--     created_at TIMESTAMP NULL DEFAULT NULL,
--     updated_at TIMESTAMP NULL DEFAULT NULL,

--     CONSTRAINT fk_tiendas_contactos_empresa
--         FOREIGN KEY (id_empresa)
--         REFERENCES tiendas(id)
--         ON DELETE CASCADE
--         ON UPDATE CASCADE
-- );