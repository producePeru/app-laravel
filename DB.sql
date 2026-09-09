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
CREATE TABLE empresarios_emprendimiento
    (
        id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        empresario_id        BIGINT UNSIGNED NOT NULL                  ,
        actividad_id         BIGINT UNSIGNED NOT NULL                  ,
        redes_sociales       JSON NULL                                 ,
        pertenece_gremio     TINYINT NOT NULL DEFAULT 0                ,
        nombre_gremio        TEXT NULL                                 ,
        cap_prod_mensual     VARCHAR(255) NULL                         ,
        porc_prod_planta     VARCHAR(255) NULL                         ,
        porc_prod_maquila    VARCHAR(255) NULL                         ,
        tiene_puntos_venta   TINYINT NOT NULL DEFAULT 0                ,
        num_puntos_ventas    VARCHAR(10) NULL                          ,
        desc_negocio         TEXT NULL                                 ,
        pos                  TINYINT NOT NULL DEFAULT 0                ,
        yape_plim            TINYINT NOT NULL DEFAULT 0                ,
        tiene_tiendas        TINYINT NOT NULL DEFAULT 0                ,
        nombre_tienda        TEXT NULL                                 ,
        tiene_delivery       TINYINT NOT NULL DEFAULT 0                ,
        factura_electronica  TINYINT NOT NULL DEFAULT 0                ,
        participado_produce  TINYINT NOT NULL DEFAULT 0                ,
        nombre_servicio      TEXT NULL                                 ,
        participado_feria    TINYINT NOT NULL DEFAULT 0                ,
        nombre_feria         TEXT NULL                                 ,
        formalizado_produce  TINYINT NOT NULL DEFAULT 0                ,
        indecopi             TINYINT NOT NULL DEFAULT 0                ,
        logros_empresa       TEXT NULL                                 ,
        terminos_condiciones TINYINT NOT NULL DEFAULT 0                ,
        created_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP  ,
        updated_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON
        UPDATE
            CURRENT_TIMESTAMP
            ,
            CONSTRAINT fk_empresarios_emprendimiento_empresario FOREIGN KEY (empresario_id) REFERENCES empresarios(id) ON
        DELETE
            CASCADE
            ,
            CONSTRAINT fk_empresarios_emprendimiento_actividad FOREIGN KEY (actividad_id) REFERENCES actividades_pnte(id) ON
        DELETE
            CASCADE );
*********************************************************************************************************
SELECT
    *
FROM
    `ferias`
WHERE
    estado = 1
ORDER BY
    `ferias`.`idFeria` DESC 
*********************************************************************************************************










SELECT
    i.*
    ,
    r.*
    ,
    d.*
FROM
    inscripcion_feria AS i
INNER JOIN
    representante_feria AS r
ON
    i.idInscripcion = r.idInscrito
INNER JOIN
    diagnostico_feria AS d
ON
    i.idInscripcion = d.idInscrito
WHERE
    i.idFeria = 100
AND i.estado  = 1;








SELECT
    i.*,
    r.*
FROM
    inscripcion_feria AS i
INNER JOIN
    representante_feria AS r
ON
    i.idInscripcion = r.idInscrito
WHERE
    i.idFeria = 71
AND i.estado = 1;