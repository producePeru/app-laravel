-- ALTER TABLE actividades_pnte
-- ADD COLUMN eliminar TINYINT(1) NULL DEFAULT NULL
-- AFTER tipo_gestion;


-- ALTER TABLE empresarios
-- ADD COLUMN nombre_mercado VARCHAR(200) NULL
-- AFTER medio_entero;


-- ALTER TABLE actividades_pnte
-- ADD COLUMN prendido TINYINT(1) NOT NULL DEFAULT 0
-- AFTER eliminar;



-- ALTER TABLE empresario_actividad
-- ADD COLUMN asistire TINYINT NULL DEFAULT NULL
-- AFTER fecha_ts;


-- CREATE TABLE email_cancelados (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     email VARCHAR(255) NOT NULL,
--     motivo TEXT NULL,
--     created_at TIMESTAMP NULL DEFAULT NULL,
--     updated_at TIMESTAMP NULL DEFAULT NULL,
--     deleted_at TIMESTAMP NULL DEFAULT NULL
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
