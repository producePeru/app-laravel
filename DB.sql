-- ALTER TABLE actividades_pnte
-- ADD COLUMN eliminar TINYINT(1) NULL DEFAULT NULL
-- AFTER tipo_gestion;


-- ALTER TABLE empresarios
-- ADD COLUMN nombre_mercado VARCHAR(200) NULL
-- AFTER medio_entero;


ALTER TABLE actividades_pnte
ADD COLUMN prendido TINYINT(1) NOT NULL DEFAULT 0
AFTER eliminar;