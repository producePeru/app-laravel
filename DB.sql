ALTER TABLE actividades_pnte
ADD COLUMN eliminar TINYINT(1) NULL DEFAULT NULL
AFTER tipo_gestion;