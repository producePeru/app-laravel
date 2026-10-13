-- CREATE TABLE `cyberwow_disclainer` (
--     `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     `descripcion` TEXT NULL,
--     `id_fair` BIGINT UNSIGNED NOT NULL,
--     `user_id` BIGINT UNSIGNED NOT NULL,
--     `created_at` TIMESTAMP NULL DEFAULT NULL,
--     `updated_at` TIMESTAMP NULL DEFAULT NULL,

--     CONSTRAINT `fk_cyberwow_disclainer_fair`
--         FOREIGN KEY (`id_fair`) REFERENCES `fairs`(`id`)
--         ON DELETE CASCADE,

--     CONSTRAINT `fk_cyberwow_disclainer_user`
--         FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
--         ON DELETE CASCADE
-- );

-- Responsables 2 y 3 de la contraparte (máx. 3 por convenio)
ALTER TABLE `convenios_gestion`
ADD COLUMN `responsable_contraparte2` VARCHAR(255) NULL AFTER `responsable_contraparte_celular`,
ADD COLUMN `responsable_contraparte_cargo2` VARCHAR(255) NULL AFTER `responsable_contraparte2`,
ADD COLUMN `responsable_contraparte_correo2` VARCHAR(255) NULL AFTER `responsable_contraparte_cargo2`,
ADD COLUMN `responsable_contraparte_celular2` VARCHAR(20) NULL AFTER `responsable_contraparte_correo2`,
ADD COLUMN `responsable_contraparte3` VARCHAR(255) NULL AFTER `responsable_contraparte_celular2`,
ADD COLUMN `responsable_contraparte_cargo3` VARCHAR(255) NULL AFTER `responsable_contraparte3`,
ADD COLUMN `responsable_contraparte_correo3` VARCHAR(255) NULL AFTER `responsable_contraparte_cargo3`,
ADD COLUMN `responsable_contraparte_celular3` VARCHAR(20) NULL AFTER `responsable_contraparte_correo3`;