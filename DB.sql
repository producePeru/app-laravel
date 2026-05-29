INSERT INTO actividades_pnte (
    unidad,
    mes,
    fechas,
    tipo_actividad_id,
    nombre_actividad_id,
    tema,
    region,
    provincia,
    distrito,
    lugar,
    entidad_organizadora,
    entidad_aliada,
    representante_id,
    requiere_pasaje,
    monto_gasto,
    mypes_beneficiadas,
    modalidad_id,
    total_asesorias,
    total_formalizaciones,
    slug,
    cancelado,
    reprogramado,
    resultados
)
SELECT
    1 AS unidad,
    NULL AS mes,
    dates AS fechas,
    tipo_actividad_id,
    nombre_actividad_id,
    theme AS tema,
    city_id AS region,
    province_id AS provincia,
    district_id AS distrito,
    address AS lugar,
    entidad AS entidad_organizadora,
    entidad_aliada,
    asesorId AS representante_id,
    pasaje AS requiere_pasaje,
    monto AS monto_gasto,
    beneficiarios AS mypes_beneficiadas,
    CASE
        WHEN modality = 'P' THEN 1
        WHEN modality = 'V' THEN 2
        ELSE NULL
    END AS modalidad_id,
    totalAsesorias AS total_asesorias,
    totalFormalizaciones AS total_formalizaciones,
    slug,
    cancelado,
    reprogramado,
    resultados
FROM attendancelist;
























INSERT INTO empresarios (
    ruc,
    razon_social,
    nombre_comercial,
    sector_economico_id,
    rubro_id,
    actividad_comercial_id,
    pais_id,
    region_id,
    provincia_id,
    distrito_id,
    direccion,
    tipo_documento_id,
    numero_dni,
    apellido_paterno,
    apellido_materno,
    nombres,
    genero_id,
    discapacidad,
    celular,
    correo_electronico,
    cargo_empresa_id,
    fecha_nacimiento,
    edad,
    como_entero
)
SELECT
    NULLIF(TRIM(p.ruc), ''),
    NULLIF(TRIM(p.socialReason), ''),
    NULLIF(TRIM(p.comercialName), ''),
    CASE WHEN EXISTS (SELECT 1 FROM economicsectors WHERE id = p.economicsector_id)    THEN p.economicsector_id    ELSE NULL END,
    CASE WHEN EXISTS (SELECT 1 FROM categories      WHERE id = p.category_id)           THEN p.category_id          ELSE NULL END,
    CASE WHEN EXISTS (SELECT 1 FROM activities      WHERE id = p.comercialactivity_id)  THEN p.comercialactivity_id ELSE NULL END,
    CASE WHEN EXISTS (SELECT 1 FROM countries       WHERE id = p.country_id)            THEN p.country_id           ELSE NULL END,
    CASE WHEN EXISTS (SELECT 1 FROM cities          WHERE id = p.city_id)               THEN p.city_id              ELSE NULL END,
    CASE WHEN EXISTS (SELECT 1 FROM provinces       WHERE id = p.province_id)           THEN p.province_id          ELSE NULL END,
    CASE WHEN EXISTS (SELECT 1 FROM districts       WHERE id = p.district_id)           THEN p.district_id          ELSE NULL END,
    NULL,
    COALESCE(NULLIF(p.typedocument_id, 0), 1),
    NULLIF(TRIM(p.documentnumber), ''),
    NULLIF(TRIM(p.lastname), ''),
    NULLIF(TRIM(p.middlename), ''),
    NULLIF(TRIM(p.name), ''),
    CASE WHEN EXISTS (SELECT 1 FROM genders         WHERE id = p.gender_id)             THEN p.gender_id            ELSE NULL END,
    CASE WHEN p.sick = 1 THEN 1 ELSE 0 END,
    NULLIF(TRIM(p.phone), ''),
    NULLIF(TRIM(p.email), ''),
    NULL,
    NULL,
    NULL,
    CASE WHEN EXISTS (SELECT 1 FROM propagandamedia WHERE id = p.howKnowEvent_id)       THEN p.howKnowEvent_id      ELSE NULL END

FROM (
    SELECT p.*,
           ROW_NUMBER() OVER (
               PARTITION BY TRIM(p.documentnumber)
               ORDER BY p.id ASC
           ) AS rn
    FROM ugo_postulantes p
    WHERE LENGTH(TRIM(COALESCE(p.documentnumber, ''))) >= 8
) p

WHERE p.rn = 1
  AND NOT EXISTS (
      SELECT 1 FROM empresarios e
      WHERE e.numero_dni = NULLIF(TRIM(p.documentnumber), '')
  );





sigo con duplicados pero hagamos que numero_dni debe de tener como minimo 8 digitos solo eso y que no exista duplicados si



  SELECT 
    numero_dni,
    COUNT(*) as total
FROM empresarios
GROUP BY numero_dni
HAVING COUNT(*) > 1
ORDER BY total DESC;


CREATE TABLE empresario_actividad (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actividad_id     BIGINT UNSIGNED NULL,
    slug             VARCHAR(255)    NULL,
    empresario_id    BIGINT UNSIGNED NULL,
    numero_dni       VARCHAR(12)     NULL,
    fecha_asistencia DATETIME        NULL,
    created_at       TIMESTAMP       NULL DEFAULT NULL,
    updated_at       TIMESTAMP       NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO empresario_actividad (
    actividad_id,
    slug,
    empresario_id,
    numero_dni,
    fecha_asistencia
)
SELECT
    p.attendancelist_id,
    NULLIF(TRIM(p.slug), ''),
    NULL,
    NULLIF(TRIM(p.documentnumber), ''),
    NULL

FROM ugo_postulantes p;


ALTER TABLE empresarios 
ADD COLUMN actividad_comercial_nombre VARCHAR(255) NULL 
AFTER actividad_comercial_id;


ALTER TABLE empresario_actividad
ADD COLUMN personal_asesoria TINYINT(1) DEFAULT NULL AFTER fecha_asistencia,
ADD COLUMN personal_formalizacion TINYINT(1) DEFAULT NULL AFTER personal_asesoria;





-- UPDATE empresario_actividad ea
-- INNER JOIN attendancelist al
--     ON ea.actividad_id = al.id
-- SET ea.slug = al.slug
-- WHERE ea.slug IS NULL
--   AND ea.actividad_id IS NOT NULL;











de la tabla ugo_postulantes pega esos datos en empresario_actividad

attendancelist_id  -> 'actividad_id',
slug -> 'slug' si no hay null,
'empresario_id' null,
documentnumber -> 'numero_dni',
'fecha_asistencia' null




la tabla empresario_actividad tiene los siguientes columnas (id, actividad_id, slug, empresario_id, numero_dni, personal_asesoria, personal_formalizacion)
la tabla ugo_postulantes tiene (id, slug , documentnumber, is_asesoria, was_formalizado, attendancelist_id )
seteamos en empresario_actividad lo siguiente 
actividad_id -> attendancelist_id 
slug -> slug 
empresario_id -> null
numero_dni -> documentnumber
personal_asesoria -> is_asesoria(s=1)
personal_formalizacion -> was_formalizado(s=1)



INSERT INTO empresario_actividad (
    actividad_id,
    slug,
    empresario_id,
    numero_dni,
    personal_asesoria,
    personal_formalizacion
)
SELECT
    u.attendancelist_id,
    u.slug,
    NULL,
    u.documentnumber,

    CASE 
        WHEN u.is_asesoria = 's' THEN 1
        ELSE NULL
    END,

    CASE 
        WHEN u.was_formalizado = 's' THEN 1
        ELSE NULL
    END

FROM ugo_postulantes u

WHERE NOT EXISTS (
    SELECT 1
    FROM empresario_actividad ea
    WHERE ea.actividad_id = u.attendancelist_id
      AND ea.numero_dni = u.documentnumber
);



ahora bien la tabla empresario_actividad tiene una columna llamada slug algunas estan en null pero debes de llenarlas por actividad_id 
que hace refrencia a la tabla attendancelist y setear en slug -> slug ya que esa tabla tiene slug

UPDATE empresario_actividad ea
INNER JOIN attendancelist a 
    ON ea.actividad_id = a.id
SET ea.slug = a.slug
WHERE ea.slug IS NULL;



UPDATE sedsurvey ss
INNER JOIN fairs f
    ON f.id = ss.sed_id
SET ss.actividad_pnte_slug = f.slug;


ALTER TABLE sed_questions_answers
ADD COLUMN slug_sed VARCHAR(100) NULL
AFTER sed_id;


