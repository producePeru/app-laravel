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





