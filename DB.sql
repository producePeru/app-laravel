
 CREATE TABLE pnteSoft (
//-     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
//-     name VARCHAR(255) NOT NULL,
//-     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//-     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
//- );

//- ALTER TABLE attendancelist
//- ADD COLUMN eventsoffice_id BIGINT UNSIGNED NULL AFTER id,
//- ADD CONSTRAINT fk_eventsoffice_id FOREIGN KEY (eventsoffice_id) REFERENCES eventsoffice(id) ON DELETE SET NULL;


CREATE TABLE workshop (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workshopName VARCHAR(250) NOT NULL,
    date DATE NOT NULL,
    hour VARCHAR(20) NOT NULL,
    link VARCHAR(255) NOT NULL,
    description TEXT,
    expositor VARCHAR(100),
    status_inv TINYINT(1) NOT NULL DEFAULT 0,
    status_te TINYINT(1) NOT NULL DEFAULT 0,
    status_ts TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);


php artisan make:controller Workshop/WorkshopController --resource




//- ALTER TABLE events
//- ADD COLUMN city_id BIGINT UNSIGNED NULL AFTER title,
//- ADD CONSTRAINT fk_city_id FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL;


//- ALTER TABLE events
//- ADD COLUMN rescheduled TEXT AFTER resultado;


//- ALTER TABLE events
//- ADD COLUMN canceled TEXT AFTER rescheduled;



//- CREATE TABLE cdesType  (
//-     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
//-     name VARCHAR(255) NOT NULL,
//-     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//-     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
//- );

//- insert into cdesType (name) values
//- ('CDE'),
//- ('CDEAI AGENTE INSTITUCIONES'),
//- ('CDEAN AGENTE NOTARÍAS');

//- ALTER TABLE cdes
//- ADD COLUMN cdetype_id BIGINT UNSIGNED NULL AFTER notary_id,
//- ADD CONSTRAINT fk_cdetype_id FOREIGN KEY (cdetype_id) REFERENCES cdesType(id) ON DELETE SET NULL;



//- ALTER TABLE events
//- ADD COLUMN province_id BIGINT UNSIGNED NULL AFTER city_id,
//- ADD COLUMN district_id BIGINT UNSIGNED NULL AFTER province_id,
//- ADD CONSTRAINT fk_events_province FOREIGN KEY (province_id) REFERENCES provinces(id),
//- ADD CONSTRAINT fk_events_district FOREIGN KEY (district_id) REFERENCES districts(id);



//- ALTER TABLE cdes
//- ADD COLUMN city_id BIGINT UNSIGNED NULL AFTER cdetype_id,
//- ADD COLUMN province_id BIGINT UNSIGNED NULL AFTER city_id,
//- ADD COLUMN district_id BIGINT UNSIGNED NULL AFTER province_id,
//- ADD CONSTRAINT fk_cdes_city FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL,
//- ADD CONSTRAINT fk_cdes_province FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE SET NULL,
//- ADD CONSTRAINT fk_cdes_district FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL;



//- ALTER TABLE `profiles`
//- ADD COLUMN `rol_id` BIGINT UNSIGNED NULL AFTER `notary_id`,
//- ADD CONSTRAINT `fk_profiles_roles`
//- FOREIGN KEY (`rol_id`) REFERENCES `roles`(`id`);




MGEA: K0jLf3IX_7nIPMLt9RF3VQ




SELECT
    CONCAT(
      REGEXP_REPLACE(JSON_UNQUOTE(JSON_EXTRACT(gastos, '$[0].gasto')), '<[^>]+>', ''),
      ' ',
      REGEXP_REPLACE(JSON_UNQUOTE(JSON_EXTRACT(gastos, '$[0].condicion')), '<[^>]+>', '')
    ) AS gastos_limpios
FROM
    notaries;



    SELECT
    CONCAT(
      REGEXP_REPLACE(JSON_UNQUOTE(JSON_EXTRACT(gastos, '$[0].gasto')), '<[^>]+>', ''),
      ' ',
      REGEXP_REPLACE(JSON_UNQUOTE(JSON_EXTRACT(gastos, '$[0].condicion')), '<[^>]+>', '')
    ) AS gastos_limpios
FROM
    notaries;






    UPDATE notaries
SET biometrico = REGEXP_REPLACE(biometrico, '<[^>]+>', '');



UPDATE notaries
SET sociointerveniente = REGEXP_REPLACE(sociointerveniente, '<[^>]+>', '');



ALTER TABLE notaries
ADD COLUMN tarifa1 VARCHAR(100) NULL AFTER gastos,
ADD COLUMN tarifa2 VARCHAR(100) NULL AFTER tarifa1,
ADD COLUMN tarifa3 VARCHAR(100) NULL AFTER tarifa2,
ADD COLUMN tarifa4 VARCHAR(100) NULL AFTER tarifa3;



UPDATE notaries
SET status = 1;




CREATE TABLE typecompanies  (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO typecompanies (name)
VALUES
  ('EIRL'),
  ('SAC'),
  ('SRL'),
  ('Persona Natural con Negocio');



ALTER TABLE mypes
ADD COLUMN typecompany_id BIGINT UNSIGNED AFTER economicsector_id,
ADD CONSTRAINT fk_typecompany
  FOREIGN KEY (typecompany_id)
  REFERENCES typecompanies(id)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;




//
CREATE TABLE `eventspp03` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nameEvent` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(100) UNIQUE NOT NULL,
    `city_id` BIGINT UNSIGNED NOT NULL,
    `place` VARCHAR(255) NOT NULL,
    `modality_id` BIGINT UNSIGNED NOT NULL,
    `dateStart` DATE NOT NULL,
    `dateEnd` DATE DEFAULT NULL,
    `hours` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (`city_id`) REFERENCES `cities`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`modality_id`) REFERENCES `modalities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE annualSales  (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO annualSales (name)
VALUES
  ('100'),
  ('200'),
  ('300'),
  ('400');

























//ok
CREATE TABLE images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    url TEXT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size BIGINT UNSIGNED NOT NULL,
    from_origin VARCHAR(50) NOT NULL,
    id_origin BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);



//- ALTER TABLE fairs
//- ADD COLUMN image_id BIGINT UNSIGNED AFTER msgSendEmail,
//- ADD CONSTRAINT fk_fairs_image_id FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE SET NULL;



//- $urlBase = config('app.custom_url_base');

//- 'logo' => $item->mype->logo_path ? $urlBase . $item->mype->logo_path : null,






CREATE TABLE eventsugopostulate (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_mype BIGINT UNSIGNED NULL,
    id_businessman BIGINT UNSIGNED NULL,
    id_form BIGINT UNSIGNED NULL,

    comercialName VARCHAR(100) NOT NULL,
    sick VARCHAR(5) NOT NULL,
    phone VARCHAR(9) NOT NULL,
    email VARCHAR(100) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_eventsugo_mype FOREIGN KEY (id_mype) REFERENCES mypes(id) ON DELETE SET NULL,
    CONSTRAINT fk_eventsugo_businessman FOREIGN KEY (id_businessman) REFERENCES people(id) ON DELETE SET NULL,
    CONSTRAINT fk_eventsugo_form FOREIGN KEY (id_form) REFERENCES attendancelist(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



//- AZ#j12Jav38E                    outlook                                     QZL8Q-93QUV-BXYF7-DH6U3-ZPKU7







-- //- ALTER TABLE fairpostulate
-- //- ADD COLUMN invitado_id BIGINT UNSIGNED NULL AFTER person_id,
-- //- ADD CONSTRAINT fk_invitado_id FOREIGN KEY (invitado_id) REFERENCES people(id) ON DELETE SET NULL;


-- //- ALTER TABLE fairpostulate
-- //- ADD COLUMN positionUser1 VARCHAR(100) NULL AFTER propagandamedia_id,
-- //- ADD COLUMN positionUser2 VARCHAR(100) NULL AFTER positionUser1;




********************* aqui se hace un hito




-- //- ALTER TABLE attendancelist_users
-- //-   ADD COLUMN category_id BIGINT UNSIGNED NULL AFTER comercialactivity_id,
-- //-   ADD COLUMN city_id BIGINT UNSIGNED NULL AFTER category_id;


-- ALTER TABLE attendancelist_users
--   ADD COLUMN howKnowEvent_id BIGINT UNSIGNED NULL AFTER city_id,
--   ADD COLUMN slug VARCHAR(255) NULL AFTER howKnowEvent_id,



-- ALTER TABLE attendancelist_users
-- MODIFY COLUMN sick ENUM('si', 'no') DEFAULT NULL;













-- CREATE TABLE ugse_postulantes (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

--     ruc VARCHAR(11) NOT NULL,
--     comercialName VARCHAR(200) NOT NULL,
--     socialReason VARCHAR(200) NOT NULL,

--     economicsector_id INT UNSIGNED NOT NULL,
--     comercialactivity_id INT UNSIGNED NOT NULL,
--     category_id INT UNSIGNED NOT NULL,
--     city_id INT UNSIGNED NOT NULL,

--     typedocument_id INT UNSIGNED NOT NULL,
--     documentnumber VARCHAR(12) NOT NULL,
--     lastname VARCHAR(100) NOT NULL,
--     middlename VARCHAR(100),
--     name VARCHAR(100) NOT NULL,
--     gender_id INT UNSIGNED NOT NULL,
--     sick VARCHAR(5),
--     phone VARCHAR(9),
--     email VARCHAR(100),
--     birthday DATE,
--     positionCompany VARCHAR(100),

--     bringsGuest TINYINT(1) DEFAULT 0,
--     howKnowEvent_id INT UNSIGNED NOT NULL,
--     event_id INT UNSIGNED NOT NULL,

--     instagram VARCHAR(200),
--     facebook VARCHAR(200),
--     web VARCHAR(200),

--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     deleted_at TIMESTAMP NULL DEFAULT NULL

-- );

-- ALTER TABLE ugse_postulantes
-- ADD COLUMN typeAsistente TINYINT(1) NOT NULL COMMENT '1 = Representante, 2 = Invitado' AFTER city_id;



CREATE TABLE emails (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    count INT,
    image VARCHAR(255),
    description TEXT,
    emailAccount VARCHAR(255),
    status CHAR(1) DEFAULT '0',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);






-- CREATE TABLE pages (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(255) NOT NULL UNIQUE,
--     slug VARCHAR(255) NOT NULL UNIQUE,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
-- );

CREATE TABLE page_user (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    page_id BIGINT UNSIGNED NOT NULL,

    can_view_all TINYINT(1) DEFAULT 0,
    can_create TINYINT(1) DEFAULT 0,
    can_update TINYINT(1) DEFAULT 0,
    can_delete TINYINT(1) DEFAULT 0,
    can_download TINYINT(1) DEFAULT 0,

    can_finish TINYINT(1) DEFAULT 0,
    can_import TINYINT(1) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_page_user_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_page_user_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);


INSERT INTO pages (name) VALUES ('Usuarios lista');















--  CREATE TABLE typeTaxpayers  (
--      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--      name VARCHAR(50) NOT NULL,
--      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
--  );

--  INSERT INTO typeTaxpayers (name)
--  VALUES
--    ('E.I.R.L - EMPRESA INDIVIDUAL DE RESP. LTDA'),
--    ('S.A - SOCIEDAD ANÓNIMA'),
--    ('S.A.A - SOCIEDAD ANÓNIMA ABIERTA'),
--    ('S.A.C - SOCIEDAD ANÓNIMA CERRADA'),
--    ('S.R.Ltda. - SOCIEDAD COMERCIAL DE RESPONSABILIDAD LIMITADA'),
--    ('PERSONA NATURAL CON NEGOCIO');









-- CREATE TABLE questions_answers (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     question TEXT NOT NULL,
--     answer TEXT NOT NULL,
--     user_id BIGINT UNSIGNED NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     CONSTRAINT fk_questions_answers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
-- );



-- CREATE TABLE activities (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(100) NOT NULL,
--     rubro_id BIGINT UNSIGNED NOT NULL,
--     created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
-- );


--  CREATE TABLE sedQuestions (
--    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--    question_1 VARCHAR(100) NULL,
--    question_2 VARCHAR(100) NULL,
--    question_3 VARCHAR(100) NULL,
--    question_4 VARCHAR(100) NULL,
--    question_5 VARCHAR(100) NULL,
--    documentnumber VARCHAR(12) NULL,

--    event_id BIGINT UNSIGNED NOT NULL,
--    FOREIGN KEY (event_id) REFERENCES fairs(id) ON DELETE CASCADE,

--    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--    deleted_at TIMESTAMP NULL DEFAULT NULL
--  );



--  ALTER TABLE images
--   ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at,
--   ADD INDEX idx_images_deleted_at (deleted_at);


-- CREATE TABLE reasons (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     table_name VARCHAR(50) NOT NULL,     -- Renombrado a table_name porque "table" es palabra reservada en MySQL
--     row_id INT NOT NULL,
--     description TEXT,
--     action ENUM('d','c','u','imp','dow') NOT NULL, -- acciones permitidas
--     user_id BIGINT UNSIGNED NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

--     CONSTRAINT fk_reasons_user FOREIGN KEY (user_id) REFERENCES users(id)
-- );




-- INSERT INTO pages (name, slug, office) VALUES
-- ('Registrar Asesoría Formalización', 'registrar-asesoria-formalizacion', 'UGO'),
-- ('Reportes Asesoría Formalización', 'reportes-asesoria-formalizacion', 'UGO'),
-- ('Empresarios UGO', 'empresarios-ugo', 'UGO'),
-- ('Historial Asesorías Formalizaciones', 'historial-asesorias-formalizaciones', 'UGO'),
-- ('Correcciones Asesorías Formalizaciones', 'correcciones-asesorias-formalizaciones', 'UGO'),
-- ('Eventos UGO', 'eventos-ugo', 'UGO'),
-- ('Asesor Registro Participantes', 'asesor-registro-participantes', 'UGO'),
-- ('Ruta Digital UGO', 'ruta-digital-ugo', 'UGO'),
-- ('Convenios UGO Seguimiento Gráfico', 'convenios-ugo-seguimiento-grafico', 'UGO'),
-- ('Convenios UGO', 'convenios-ugo', 'UGO'),
-- ('Planes de Acción UGO', 'planes-accion-ugo', 'UGO'),
-- ('Notarías', 'notarias', 'UGO');


-- INSERT INTO pages (name, slug, office) VALUES
-- ('USUARIOS PNTE', 'usuarios-pnte', 'ADMIN'),
-- ('USUARIOS UGO', 'usuarios-ugo', 'UGO');


-- CREATE TABLE notifications (
--     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     count INT DEFAULT 0,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
-- );



-- CREATE TABLE trainingSpecialists (
--     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(100) NOT NULL,
--     ocupation VARCHAR(100) NOT NULL,
--     color VARCHAR(7) NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
-- );

-- CREATE TABLE trainingDimensions (
--     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(100) NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
-- );


-- CREATE TABLE trainingMetas (
--     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     month DATE NOT NULL,
--     capacitaciones INT UNSIGNED NOT NULL,
--     participantes INT UNSIGNED NOT NULL,
--     empresas INT UNSIGNED NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
-- );


-- CREATE TABLE trainings (
--     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

--     meta_id INT UNSIGNED NOT NULL,
--     especialista_id INT UNSIGNED NOT NULL,
--     dimension_id INT UNSIGNED NOT NULL,

--     fecha DATE NOT NULL,
--     horaInicio TIME NOT NULL,
--     horaFin TIME NOT NULL,

--     modalidad TINYINT NOT NULL COMMENT '1=Presencial, 2=Virtual, 3=Mixto',
--     tema VARCHAR(255) NOT NULL,
--     lugar VARCHAR(255),
--     participantes INT UNSIGNED DEFAULT 0,
--     empresas INT UNSIGNED DEFAULT 0,
--     estado TINYINT NOT NULL COMMENT '1=Programada, 2=En curso, 3=Completado, 4=Cancelado',
--     coordinador VARCHAR(150),
--     observaciones TEXT,

--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

--     CONSTRAINT fk_trainings_meta
--         FOREIGN KEY (meta_id) REFERENCES trainingMetas(id),
--     CONSTRAINT fk_trainings_especialista
--         FOREIGN KEY (especialista_id) REFERENCES trainingSpecialists(id),
--     CONSTRAINT fk_trainings_dimension
--         FOREIGN KEY (dimension_id) REFERENCES trainingDimensions(id)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




-- CREATE TABLE cyberwowParticipants (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     event_id BIGINT UNSIGNED NOT NULL, -- referencia a fairs.id

--     ruc VARCHAR(20) NOT NULL,
--     razonSocial VARCHAR(255) NOT NULL,
--     nombreComercial VARCHAR(255),

--     city_id BIGINT UNSIGNED NOT NULL,
--     province_id BIGINT UNSIGNED NOT NULL,
--     district_id BIGINT UNSIGNED NOT NULL,
--     direccion VARCHAR(255),

--     economicsector_id BIGINT UNSIGNED NOT NULL,
--     comercialactivity_id BIGINT UNSIGNED NOT NULL,
--     rubro_id BIGINT UNSIGNED NOT NULL,
--     descripcion TEXT,

--     socials JSON,

--     typedocument_id BIGINT UNSIGNED NOT NULL,
--     documentnumber VARCHAR(20) NOT NULL,
--     lastname VARCHAR(100),
--     middlename VARCHAR(100),
--     name VARCHAR(100),
--     gender_id BIGINT UNSIGNED NOT NULL,

--     sick VARCHAR(4) DEFAULT 'no',
--     phone VARCHAR(10),
--     email VARCHAR(150),
--     birthday DATE,
--     age INT CHECK (age BETWEEN 18 AND 100),
--     country_id BIGINT UNSIGNED NOT NULL,

--     question_1 VARCHAR(5),
--     question_2 VARCHAR(5),
--     question_3 VARCHAR(5),
--     question_4 VARCHAR(5),
--     question_5 VARCHAR(5),
--     question_6 VARCHAR(5),
--     question_7 VARCHAR(5),

--     howKnowEvent_id BIGINT UNSIGNED NOT NULL,
--     autorization TINYINT(1) NOT NULL DEFAULT 0,

--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     deleted_at TIMESTAMP NULL,

--     -- Relaciones (foreign keys)
--     CONSTRAINT fk_event FOREIGN KEY (event_id) REFERENCES fairs(id),
--     CONSTRAINT fk_city FOREIGN KEY (city_id) REFERENCES cities(id),
--     CONSTRAINT fk_province FOREIGN KEY (province_id) REFERENCES provinces(id),
--     CONSTRAINT fk_district FOREIGN KEY (district_id) REFERENCES districts(id),
--     CONSTRAINT fk_economicsector FOREIGN KEY (economicsector_id) REFERENCES economicsectors(id),
--     CONSTRAINT fk_comercialactivity FOREIGN KEY (comercialactivity_id) REFERENCES activities(id),
--     CONSTRAINT fk_typedocument FOREIGN KEY (typedocument_id) REFERENCES typedocuments(id),
--     CONSTRAINT fk_gender FOREIGN KEY (gender_id) REFERENCES genders(id),
--     CONSTRAINT fk_country FOREIGN KEY (country_id) REFERENCES countries(id),
--     CONSTRAINT fk_howknow FOREIGN KEY (howKnowEvent_id) REFERENCES propagandamedia(id)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- CREATE TABLE cyberwowleader (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     user_id BIGINT UNSIGNED NOT NULL,
--     wow_id BIGINT UNSIGNED NOT NULL,
--     status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0 = inactivo, 1 = activo',
--     created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     CONSTRAINT fk_cyberwowleader_user FOREIGN KEY (user_id) REFERENCES users(id),
--     CONSTRAINT fk_cyberwowleader_fair FOREIGN KEY (wow_id) REFERENCES fairs(id)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ALTER TABLE cyberwowparticipants
-- ADD COLUMN user_id BIGINT UNSIGNED AFTER autorization,
-- ADD CONSTRAINT fk_cyberwowparticipants_user FOREIGN KEY (user_id) REFERENCES users(id);


-- ALTER TABLE cyberwowparticipants
-- ADD COLUMN paso1 TINYINT(1) UNSIGNED NULL DEFAULT NULL AFTER user_id,
-- ADD COLUMN paso2 TINYINT(1) UNSIGNED NULL DEFAULT NULL AFTER paso1,
-- ADD COLUMN paso3 TINYINT(1) UNSIGNED NULL DEFAULT NULL AFTER paso2;





-- CREATE TABLE cyberwowbrand (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

--     -- campos propios
--     isService VARCHAR(2) NOT NULL,            -- 's' o 'n'
--     description VARCHAR(1000) NULL,           -- hasta 1000 caracteres
--     url VARCHAR(800) NOT NULL,                -- enlace a la red social

--     -- relaciones
--     logo256_id BIGINT UNSIGNED NOT NULL,
--     logo160_id BIGINT UNSIGNED NOT NULL,
--     wow_id BIGINT UNSIGNED NOT NULL,
--     user_id BIGINT UNSIGNED NOT NULL,
--     company_id BIGINT UNSIGNED NOT NULL,      -- relación con cyberwowparticipants

--     -- timestamps
--     created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     deleted_at TIMESTAMP NULL DEFAULT NULL,

--     -- foreign keys
--     CONSTRAINT fk_cyberwowbrand_logo256 FOREIGN KEY (logo256_id) REFERENCES images(id),
--     CONSTRAINT fk_cyberwowbrand_logo160 FOREIGN KEY (logo160_id) REFERENCES images(id),
--     CONSTRAINT fk_cyberwowbrand_wow FOREIGN KEY (wow_id) REFERENCES fairs(id),
--     CONSTRAINT fk_cyberwowbrand_user FOREIGN KEY (user_id) REFERENCES users(id),
--     CONSTRAINT fk_cyberwowbrand_company FOREIGN KEY (company_id) REFERENCES cyberwowparticipants(id)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- correos
CREATE TABLE email_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,     -- Nombre de la plantilla
    content MEDIUMTEXT NOT NULL,    -- Contenido HTML
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;






-- CREATE TABLE cyberwowoffers (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

--     -- Relaciones principales
--     wow_id BIGINT UNSIGNED NOT NULL,             -- id de la tabla fairs
--     company_id BIGINT UNSIGNED NOT NULL,         -- id de la tabla cyberwowparticipants

--     imgFull BIGINT UNSIGNED NULL,                -- id de la tabla images (imagen desktop)
--     img BIGINT UNSIGNED NULL,                    -- id de la tabla images (imagen phone)

--     -- Datos de la oferta
--     title VARCHAR(255) NOT NULL,
--     link VARCHAR(255) DEFAULT NULL,
--     category VARCHAR(120) DEFAULT NULL,
--     tipo VARCHAR(80) DEFAULT NULL,
--     beneficio VARCHAR(255) DEFAULT NULL,

--     moneda VARCHAR(10) NULL,
--     precioAnterior DECIMAL(10,2) DEFAULT 0.00,
--     precioOferta DECIMAL(10,2) DEFAULT 0.00,
--     descripcion TEXT DEFAULT NULL,

--     -- Día de la oferta (1, 2 o 3)
--     dia TINYINT UNSIGNED NOT NULL CHECK (dia IN (1, 2, 3)),

--     -- Fechas de auditoría
--     created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

--     -- Claves foráneas
--     CONSTRAINT fk_cyberwow_offers_fairs FOREIGN KEY (wow_id)
--         REFERENCES fairs(id) ON DELETE CASCADE,

--     CONSTRAINT fk_cyberwow_offers_company FOREIGN KEY (company_id)
--         REFERENCES cyberwowparticipants(id) ON DELETE CASCADE,

--     CONSTRAINT fk_cyberwow_offers_imgFull FOREIGN KEY (imgFull)
--         REFERENCES images(id) ON DELETE SET NULL,

--     CONSTRAINT fk_cyberwow_offers_img FOREIGN KEY (img)
--         REFERENCES images(id) ON DELETE SET NULL,

--     -- Limitar máximo 3 ofertas por empresa/evento (una por día)
--     CONSTRAINT unique_offer_per_day UNIQUE (wow_id, company_id, dia)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ALTER TABLE `cyberwowleader`
-- ADD COLUMN `supervisor` VARCHAR(3) NULL AFTER `wow_id`;


-- ALTER TABLE cyberwowbrand
-- ADD COLUMN red TINYINT NULL CHECK (red IN (1,2,3,4,5)) AFTER description;  ....



CREATE TABLE `videos_pnte` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,

  `google_file_id` VARCHAR(255) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(255) NULL,
  `file_size` VARCHAR(255) NULL,
  `web_view_link` VARCHAR(500) NULL,
  `web_content_link` VARCHAR(500) NULL,

  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,

  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,

  PRIMARY KEY (`id`),

  CONSTRAINT `videos_pnte_user_fk`
    FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`)
    ON DELETE CASCADE
);










ALTER TABLE tokens
ADD COLUMN user_id BIGINT UNSIGNED AFTER token,
ADD CONSTRAINT tokens_user_id_foreign
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL;

















-- ============================================
-- 1. TABLA: mp_componentes
-- ============================================
-- CREATE TABLE mp_componentes (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(255) NOT NULL,
--     created_at TIMESTAMP NULL,
--     updated_at TIMESTAMP NULL
-- );

-- ============================================
-- 2. TABLA: mp_capacitadores
-- ============================================
-- CREATE TABLE mp_capacitadores (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(255) NOT NULL,
--     dni VARCHAR(20),
--     created_at TIMESTAMP NULL,
--     updated_at TIMESTAMP NULL
-- );

-- ============================================
-- 3. TABLA: mp_eventos
-- ============================================
-- CREATE TABLE mp_eventos (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

--     title VARCHAR(255) NOT NULL,

--     component TINYINT UNSIGNED NOT NULL,
--     CHECK (component IN (1,2,3,4,5)),

--     capacitador_id BIGINT UNSIGNED NOT NULL,
--     city_id BIGINT UNSIGNED NOT NULL,
--     modality_id BIGINT UNSIGNED NOT NULL,

--     place VARCHAR(255),
--     date DATE,
--     hours VARCHAR(100),

--     startDate DATE,
--     endDate DATE,

--     created_at TIMESTAMP NULL,
--     updated_at TIMESTAMP NULL,
--     deleted_at TIMESTAMP NULL,

--     FOREIGN KEY (capacitador_id) REFERENCES mp_capacitadores(id),
--     FOREIGN KEY (city_id) REFERENCES cities(id),
--     FOREIGN KEY (modality_id) REFERENCES modalities(id)
-- );


-- ALTER TABLE mp_eventos 
-- ADD slug VARCHAR(255) UNIQUE AFTER title;

-- ALTER TABLE mp_eventos
-- ADD COLUMN training_time SMALLINT AFTER hours;

-- ALTER TABLE mp_eventos
-- ADD COLUMN province_id BIGINT UNSIGNED NULL AFTER city_id;

-- ALTER TABLE mp_eventos
-- ADD CONSTRAINT fk_mp_eventos_province_id
-- FOREIGN KEY (province_id) REFERENCES provinces(id);


-- ALTER TABLE mp_eventos
-- ADD COLUMN district_id BIGINT UNSIGNED NULL AFTER province_id;

-- ALTER TABLE mp_eventos
-- ADD CONSTRAINT fk_mp_eventos_district_id
-- FOREIGN KEY (district_id) REFERENCES districts(id);





-- ============================================
-- 4. TABLA: mp_empresas
-- ============================================

-- CREATE TABLE civilstatus (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(100) NOT NULL,
--     created_at TIMESTAMP NULL,
--     updated_at TIMESTAMP NULL
-- );

-- INSERT INTO civilstatus (name) VALUES
-- ('CASADO(A)'),
-- ('CONVIVIENTE'),
-- ('DIVORCIADO(A)'),
-- ('SEPARADO(A)'),
-- ('SOLTERO(A)'),
-- ('VIUDO(A)');

-- CREATE TABLE academicdegree (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(255) NOT NULL,
--     created_at TIMESTAMP NULL,
--     updated_at TIMESTAMP NULL
-- );


-- INSERT INTO academicdegree (name) VALUES
-- ('EDUCACIÓN PRIMARIA'),
-- ('EDUCACIÓN SECUNDARIA'),
-- ('TÉCNICO'),
-- ('BACHILLER'),
-- ('TITULADO'),
-- ('MAESTRÍA'),
-- ('DOCTORADO'),
-- ('POSTDOCTORADO');



-- CREATE TABLE role_company (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(255) NOT NULL,
--     created_at TIMESTAMP NULL,
--     updated_at TIMESTAMP NULL
-- );

-- INSERT INTO role_company (name) VALUES
-- ('PROPIETARIO(A)'),
-- ('GERENTE GENERAL'),
-- ('GERENTE DE OPERACIONES'),
-- ('GERENTE ADMINISTRATIVO'),
-- ('GERENTE DE FINANZAS'),
-- ('GERENTE DE RECURSOS HUMANOS'),
-- ('JEFE DE VENTAS'),
-- ('JEFE DE MARKETING'),
-- ('SUPERVISOR(A)'),
-- ('LÍDER DE EQUIPO'),
-- ('COORDINADOR(A)'),
-- ('ASISTENTE'),
-- ('ANALISTA'),
-- ('CONSULTOR(A)'),
-- ('PRACTICANTE');


-- CREATE TABLE mp_participantes (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     ruc VARCHAR(11) NOT NULL,
--     social_reason VARCHAR(255) NOT NULL,
--     economic_sector_id BIGINT UNSIGNED NOT NULL,
--     rubro_id BIGINT UNSIGNED NOT NULL,
--     comercial_activity_id BIGINT UNSIGNED NOT NULL,
--     city_id BIGINT UNSIGNED NOT NULL,
--     province_id BIGINT UNSIGNED NOT NULL,
--     district_id BIGINT UNSIGNED NOT NULL,
--     t_doc_id BIGINT UNSIGNED NOT NULL,
--     doc_number VARCHAR(12) NOT NULL,

--     country_id BIGINT UNSIGNED NOT NULL,

--     date_of_birth DATE,

--     names VARCHAR(100) NOT NULL,
--     last_name VARCHAR(100) NOT NULL,
--     middle_name VARCHAR(100),

--     civil_status_id BIGINT UNSIGNED NOT NULL,

--     num_soons VARCHAR(3),   

--     gender_id BIGINT UNSIGNED NOT NULL,

--     sick VARCHAR(10) NULL,  

--     academicdegree_id BIGINT UNSIGNED NOT NULL,

--     phone VARCHAR(9),
--     email VARCHAR(200),

--     role_company_id BIGINT UNSIGNED NOT NULL,

--     created_at TIMESTAMP NULL,
--     updated_at TIMESTAMP NULL,

 
--     FOREIGN KEY (economic_sector_id) REFERENCES economicsectors(id),
--     -- FOREIGN KEY (rubro_id) REFERENCES categories(id),
--     FOREIGN KEY (comercial_activity_id) REFERENCES activities(id),

--     FOREIGN KEY (city_id) REFERENCES cities(id),
--     FOREIGN KEY (province_id) REFERENCES provinces(id),
--     FOREIGN KEY (district_id) REFERENCES districts(id),

--     FOREIGN KEY (t_doc_id) REFERENCES typedocuments(id),
--     FOREIGN KEY (country_id) REFERENCES countries(id),

--     FOREIGN KEY (civil_status_id) REFERENCES civilstatus(id),

--     FOREIGN KEY (gender_id) REFERENCES genders(id),

--     FOREIGN KEY (academicdegree_id) REFERENCES academicdegree(id),

--     FOREIGN KEY (role_company_id) REFERENCES role_company(id)
-- );


-- ALTER TABLE mp_participantes
-- ADD COLUMN obs_ruc TINYINT(1) NULL AFTER role_company_id,
-- ADD COLUMN obs_dni TINYINT(1) NULL AFTER obs_ruc;



-- ============================================
-- 6. TABLA: mp_asistencias
-- ============================================
-- CREATE TABLE mp_asistencias (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

--     event_id BIGINT UNSIGNED NOT NULL,
--     participant_id BIGINT UNSIGNED NOT NULL,

--     attendance TINYINT(1) NULL DEFAULT NULL, -- permite 0, 1 o NULL

--     created_at TIMESTAMP NULL,
--     updated_at TIMESTAMP NULL,

--     FOREIGN KEY (event_id) REFERENCES mp_eventos(id),
--     FOREIGN KEY (participant_id) REFERENCES mp_participantes(id)
-- );




-- CREATE TABLE mp_diag_preguntas (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     label VARCHAR(250) NOT NULL,
--     type VARCHAR(5) NOT NULL,
--     model VARCHAR(100) NOT NULL,
--     required TINYINT(1) DEFAULT 0,
--     status TINYINT(1) DEFAULT 1,
--     created_at TIMESTAMP NULL,
--     updated_at TIMESTAMP NULL
-- );


-- CREATE TABLE mp_diag_preguntas_opciones (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(250) NOT NULL,
--     diag_pregunta_id BIGINT UNSIGNED NOT NULL,
--     created_at TIMESTAMP NULL,
--     updated_at TIMESTAMP NULL,

--     FOREIGN KEY (diag_pregunta_id) REFERENCES mp_diag_preguntas(id)
-- );



-- CREATE TABLE mp_diag_respuestas (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

--     participant_id BIGINT UNSIGNED NOT NULL,
--     question_id BIGINT UNSIGNED NOT NULL,
--     answer_option_id BIGINT UNSIGNED NULL,
--     answer_text VARCHAR(250) NULL,

--     created_at TIMESTAMP NULL DEFAULT NULL,
--     updated_at TIMESTAMP NULL DEFAULT NULL,

--     CONSTRAINT fk_diag_respuesta_participant
--         FOREIGN KEY (participant_id)
--         REFERENCES mp_participantes(id)
--         ON DELETE CASCADE,

--     CONSTRAINT fk_diag_respuesta_question
--         FOREIGN KEY (question_id)
--         REFERENCES mp_diag_preguntas(id)
--         ON DELETE CASCADE,

--     CONSTRAINT fk_diag_respuesta_option
--         FOREIGN KEY (answer_option_id)
--         REFERENCES mp_diag_preguntas_opciones(id)
--         ON DELETE SET NULL
-- ) ENGINE=InnoDB;



-- INSERT INTO pages (name, slug, office) VALUES
-- ('Capacitaciones MP', 'capacitaciones-mp', 'MP'),
-- ('Diagnosticos MP', 'diagnosticos-mp', 'MP'),
-- ('Capacitadores MP', 'capacitadores-mp', 'MP'),
-- ('Preguntas diagnostico MP', 'preguntas-diagnostico-mp', 'MP');







-- ============================================
-- 7. TABLA: mp_diagnosticos
-- ============================================
-- CREATE TABLE mp_diagnosticos (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     id_mp_empresas BIGINT UNSIGNED,
--     id_mp_empresario BIGINT UNSIGNED,
--     created_at TIMESTAMP NULL,
--     updated_at TIMESTAMP NULL,

--     FOREIGN KEY (id_mp_empresas)
--         REFERENCES mp_empresas(id)
--         ON DELETE SET NULL,

--     FOREIGN KEY (id_mp_empresario)
--         REFERENCES mp_empresarios(id)
--         ON DELETE SET NULL
-- );

