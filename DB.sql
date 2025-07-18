


CREATE TABLE restrict_ips (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(20) NOT NULL,
    access VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

php artisan make:model RestrictIp
php artisan make:controller Restrict/RestrictIpController --api



//- CREATE TABLE eventsoffice (
//-     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
//-     name VARCHAR(50) NOT NULL,
//-     office VARCHAR(10) NOT NULL,
//-     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//-     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//-     deleted_at TIMESTAMP NULL
//- );



//- ALTER TABLE events
//- ADD COLUMN pnte_id BIGINT UNSIGNED NULL AFTER user_id,
//- ADD CONSTRAINT fk_pnte_id FOREIGN KEY (pnte_id) REFERENCES eventsoffice(id) ON DELETE SET NULL ON UPDATE CASCADE;


//- CREATE TABLE events (
//-     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

//-     id_pnte BIGINT UNSIGNED NOT NULL,
//-     title VARCHAR(255) NOT NULL,
//-     organiza VARCHAR(150) NULL,
//-     numMypes VARCHAR(10) NULL,
//-     date DATE NOT NULL,
//-     start TIME NULL,
//-     end TIME NULL,
//-     description VARCHAR(255) NULL,
//-     nameUser VARCHAR(100) NULL,
//-     link VARCHAR(150) NULL,

//-     user_id BIGINT UNSIGNED NOT NULL,
//-     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//-     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//-     deleted_at TIMESTAMP NULL,

//-     -- Claves foráneas
//-     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
//-     FOREIGN KEY (id_pnte) REFERENCES eventsoffice(id) ON DELETE CASCADE
//- );






//- CREATE TABLE pnteSoft (
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


//- CREATE TABLE rooms (
//-   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
//-   sala VARCHAR(100) NOT NULL,
//-   inicio DATETIME NOT NULL,
//-   fin DATETIME NOT NULL,
//-   descripcion TEXT,
//-   unidad VARCHAR(50),

//-   created_by BIGINT UNSIGNED NOT NULL,
//-   updated_by BIGINT UNSIGNED NULL,
//-   FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
//-   FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,

//-   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//-   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//-   deleted_at TIMESTAMP NULL DEFAULT NULL
//- );



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

-- CREATE TABLE page_user (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     user_id BIGINT UNSIGNED NOT NULL,
--     page_id BIGINT UNSIGNED NOT NULL,

--     can_view_all TINYINT(1) DEFAULT 1,
--     can_create TINYINT(1) DEFAULT 0,
--     can_update TINYINT(1) DEFAULT 0,
--     can_delete TINYINT(1) DEFAULT 0,
--     can_download TINYINT(1) DEFAULT 0,

--     can_finish TINYINT(1) DEFAULT 0,
--     can_import TINYINT(1) DEFAULT 0,

--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

--     CONSTRAINT fk_page_user_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
--     CONSTRAINT fk_page_user_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
-- );


INSERT INTO pages (name) VALUES ('Usuarios lista');















 CREATE TABLE typeTaxpayers  (
     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     name VARCHAR(50) NOT NULL,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 );

 INSERT INTO typeTaxpayers (name)
 VALUES
   ('E.I.R.L - EMPRESA INDIVIDUAL DE RESP. LTDA'),
   ('S.A - SOCIEDAD ANÓNIMA'),
   ('S.A.A - SOCIEDAD ANÓNIMA ABIERTA'),
   ('S.A.C - SOCIEDAD ANÓNIMA CERRADA'),
   ('S.R.Ltda. - SOCIEDAD COMERCIAL DE RESPONSABILIDAD LIMITADA'),
   ('PERSONA NATURAL CON NEGOCIO');



CREATE TABLE empresas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ruc VARCHAR(11) NOT NULL UNIQUE,
  razonSocial VARCHAR(255) NOT NULL,
  sectorEconomico_id BIGINT UNSIGNED,
  rubro_id BIGINT UNSIGNED,
  actividadComercial_id BIGINT UNSIGNED,
  region_id BIGINT UNSIGNED,
  provincia_id BIGINT UNSIGNED,
  distrito_id BIGINT UNSIGNED,
  direccion VARCHAR(255) NULL,
  estado VARCHAR(255) NULL,
  condicion VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE empresarios (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  typedocument_id BIGINT UNSIGNED,
  dni VARCHAR(11) NOT NULL UNIQUE,
  name VARCHAR(100),
  lastname VARCHAR(100),
  middlename VARCHAR(100),
  gender_id BIGINT UNSIGNED,
  birthday DATE,
  phone VARCHAR(9),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


INSERT INTO empresas (
  ruc,
  razonSocial,
  sectorEconomico_id,
  rubro_id,
  actividadComercial_id,
  region_id,
  provincia_id,
  distrito_id,
  direccion
)
SELECT
  p.ruc,
  p.socialReason,
  p.economicsector_id,
  p.category_id,
  p.comercialactivity_id,
  p.city_id,
  p.province_id,
  p.district_id,
  p.address
FROM
  ugse_postulantes p
WHERE
  p.ruc IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM empresas e
    WHERE e.ruc = p.ruc
  );


