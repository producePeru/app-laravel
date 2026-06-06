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







CREATE TABLE tareas (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo              VARCHAR(255) NOT NULL,
    unidad              ENUM('UGSE','UGO','UGGER','COOPERATIVAS','DE','COMUNICACIONES') NOT NULL,
    detalle             TEXT,
    completada          TINYINT(1)   NOT NULL DEFAULT 0,
    orden               INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


















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








-- RESETEA EL CONTADOR DE LA TABLA A 1       *******************************************
SET @nuevo_id = 0;

UPDATE TABLE_NAME
SET id = (@nuevo_id := @nuevo_id + 1)
ORDER BY id;

************************************************************************ SETEAMOS EL SED
INSERT INTO actividades_pnte (
    unidad,
    mes,
    fechas,
    cantidad_dias,
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
    total_participantes,
    total_asesorias,
    total_formalizaciones,
    slug,
    cancelado,
    cancelado_por_id,
    reprogramado,
    reprogramado_por_id,
    resultados,
    activo,
    registrado_por_id,
    actualizado_por_id,
    horario,
    descripcion,
    nombre_asesor,
    link,
    created_at,
    updated_at
)
SELECT
    2 AS unidad,
    1 AS mes,
    dates AS fechas,
    1 AS cantidad_dias,
    7 AS tipo_actividad_id,
    16 AS nombre_actividad_id,
    title AS tema,
    city_id AS region,
    province_id AS provincia,
    district_id AS distrito,
    place AS lugar,
    'PNTE' AS entidad_organizadora,
    NULL AS entidad_aliada,
    126 AS representante_id,
    0 AS requiere_pasaje,
    NULL AS monto_gasto,
    metaMypes AS mypes_beneficiadas,
    modality_id,
    NULL AS total_participantes,
    NULL AS total_asesorias,
    NULL AS total_formalizaciones,
    slug,
    cancelado,
    NULL AS cancelado_por_id,
    reprogramado,
    NULL AS reprogramado_por_id,
    resultados,
    1 AS activo,
    126 AS registrado_por_id,
    NULL AS actualizado_por_id,
    hours AS horario,
    NULL AS descripcion,
    NULL AS nombre_asesor,
    NULL AS link,
    NOW() AS created_at,
    NOW() AS updated_at
FROM fairs
WHERE fairtype_id = 1;



-- ACTUALIZAR COLUMNA mes SED *************************************
UPDATE actividades_pnte
SET mes = MONTH(
    JSON_UNQUOTE(
        JSON_EXTRACT(fechas, '$[0]')
    )
)
WHERE tipo_actividad_id = 7;


-- CONSULTAR LOS DISTINTOS ************************************
SELECT DISTINCT fairtype_id
FROM fairs
ORDER BY fairtype_id;