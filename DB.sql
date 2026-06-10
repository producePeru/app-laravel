-- CREATE TABLE pp_capacitadores (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     nombres_apellidos VARCHAR(255) NOT NULL,
--     dni VARCHAR(20) NOT NULL UNIQUE,
--     correo VARCHAR(255) NOT NULL,
--     created_at TIMESTAMP NULL DEFAULT NULL,
--     updated_at TIMESTAMP NULL DEFAULT NULL
-- );




-- ALTER TABLE actividades_pnte
-- ADD COLUMN componente_id BIGINT UNSIGNED NULL AFTER link,
-- ADD COLUMN trainer_id BIGINT UNSIGNED NULL AFTER componente_id;


-- ALTER TABLE actividades_pnte
-- ADD CONSTRAINT fk_actividades_pnte_trainer
-- FOREIGN KEY (trainer_id)
-- REFERENCES pp_capacitadores(id);


ALTER TABLE empresarios 
ADD COLUMN tipo_empresa_id TINYINT NULL AFTER edad,
ADD COLUMN f_inicio_act DATE NULL AFTER tipo_empresa_id,
ADD COLUMN venta_anual TINYINT NULL AFTER f_inicio_act,
ADD COLUMN medio_entero TINYINT NULL AFTER venta_anual;
