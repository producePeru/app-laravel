
-- ALTER TABLE empresarios
-- ADD COLUMN academicdegree_id BIGINT UNSIGNED NULL AFTER cargo_empresa_id,
-- ADD CONSTRAINT fk_empresarios_academicdegree
-- FOREIGN KEY (academicdegree_id)
-- REFERENCES academicdegree(id);


-- ALTER TABLE empresarios
-- ADD COLUMN role_company_id BIGINT UNSIGNED NULL AFTER academicdegree_id,
-- ADD CONSTRAINT fk_empresarios_role_company
-- FOREIGN KEY (role_company_id)
-- REFERENCES role_company(id);



-- CREATE TABLE pnte_test (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
--     test_entrada JSON NULL,
--     test_salida JSON NULL,
--     caso_practico TEXT NULL,
--     slug VARCHAR(255) NOT NULL UNIQUE,

--     created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
--         ON UPDATE CURRENT_TIMESTAMP
-- );


-- ALTER TABLE empresario_actividad
-- ADD COLUMN test_entrada JSON NULL
-- AFTER c_constancia;


-- ALTER TABLE empresario_actividad
-- ADD COLUMN test_salida JSON NULL AFTER test_entrada,
-- ADD COLUMN caso_practico LONGTEXT NULL AFTER test_salida,
-- ADD COLUMN ratings JSON NULL AFTER caso_practico,
-- ADD COLUMN sugerencias LONGTEXT NULL AFTER ratings;


-- ALTER TABLE empresario_actividad
-- ADD COLUMN fecha_te DATETIME NULL AFTER sugerencias,
-- ADD COLUMN fecha_ts DATETIME NULL AFTER fecha_te;
