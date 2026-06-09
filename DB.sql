CREATE TABLE pp_capacitadores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombres_apellidos VARCHAR(255) NOT NULL,
    dni VARCHAR(20) NOT NULL UNIQUE,
    correo VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
);




ALTER TABLE actividades_pnte
ADD COLUMN componente_id BIGINT UNSIGNED NULL AFTER link,
ADD COLUMN trainer_id BIGINT UNSIGNED NULL AFTER componente_id;


ALTER TABLE actividades_pnte
ADD CONSTRAINT fk_actividades_pnte_trainer
FOREIGN KEY (trainer_id)
REFERENCES pp_capacitadores(id);