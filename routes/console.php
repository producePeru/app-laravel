<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// CREATE TABLE drive_users (
//     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
//     drive_id BIGINT UNSIGNED,
//     user_ids JSON,
//     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//     deleted_at TIMESTAMP NULL,
//     CONSTRAINT fk_drive_users_drive_id FOREIGN KEY (drive_id) REFERENCES drives(id)
// );

// ALTER TABLE profiles
// ADD COLUMN city_id INT UNSIGNED DEFAULT NULL AFTER user_id,
// ADD COLUMN province_id INT UNSIGNED DEFAULT NULL AFTER city_id,
// ADD COLUMN district_id INT UNSIGNED DEFAULT NULL AFTER province_id,
// ADD COLUMN address VARCHAR(100) DEFAULT NULL AFTER district_id;

// ALTER TABLE advisories
// ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;

// ALTER TABLE formalizations10
// ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;

// *** ALTER
// ALTER TABLE advisories
// ADD COLUMN economicsector_id BIGINT UNSIGNED AFTER id,
// ADD COLUMN comercialactivity_id BIGINT UNSIGNED AFTER economicsector_id;

// ALTER TABLE advisories
// ADD CONSTRAINT fk_advisories_economicsector_id FOREIGN KEY (economicsector_id) REFERENCES economicsectors(id) ON DELETE CASCADE;

// ALTER TABLE advisories
// ADD CONSTRAINT fk_advisories_comercialactivity_id FOREIGN KEY (comercialactivity_id) REFERENCES comercialactivities(id) ON DELETE CASCADE;

// ALTER TABLE people
// ADD COLUMN hasSoon CHAR(3) DEFAULT NULL AFTER sick;

// CREATE TABLE typecapital (
//     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
//     name VARCHAR(40) NOT NULL,
//     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
// );

// ALTER TABLE formalizations20
// ADD COLUMN typecapital_id BIGINT UNSIGNED DEFAULT NULL AFTER userupdated_id;
// ALTER TABLE formalizations20
// ADD CONSTRAINT fk_formalizations20_typecapital_id
// FOREIGN KEY (typecapital_id) REFERENCES typecapital(id);

// ALTER TABLE formalizations20
// ADD COLUMN isbic CHAR(2) DEFAULT NULL AFTER typecapital_id;

// ALTER TABLE formalizations20
// ADD COLUMN montocapital VARCHAR(10) DEFAULT NULL AFTER isbic;

// USER_

// ALTER TABLE agreements
// ADD COLUMN created_id BIGINT UNSIGNED AFTER observations;

// ALTER TABLE agreements
// ADD CONSTRAINT fk_agreements_created_id FOREIGN KEY (created_id) REFERENCES users(id) ON DELETE CASCADE;

// /**
//  ME EQUIVOQUE DE LLAVE FORANEA

// SELECT CONSTRAINT_NAME
// FROM information_schema.KEY_COLUMN_USAGE
// WHERE TABLE_NAME = 'people'
//   AND COLUMN_NAME = 'country_id';

// ALTER TABLE people
// DROP FOREIGN KEY fk_people_country_id;

//*** Finalmente cambiar nombre y eliminar */
//  **/

// ALTER TABLE people
// ADD COLUMN country_id BIGINT UNSIGNED AFTER country;

// ALTER TABLE people
// ADD CONSTRAINT fk_people_country_id FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE;

// CREATE TABLE eventcategories (
//     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
//     name VARCHAR(100) NOT NULL,
//     color VARCHAR(20) NOT NULL,
//     status ENUM('1', '0') NOT NULL DEFAULT '0',
//     user_id BIGINT UNSIGNED,
//     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//     deleted_at TIMESTAMP NULL,
//     CONSTRAINT fk_eventcategories_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
// );

// CREATE TABLE events (
//     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
//     nameEvent VARCHAR(100) NOT NULL,
//     start DATETIME NOT NULL,
//     end DATETIME NOT NULL,
//     description VARCHAR(255) NULL,
//     linkVideo VARCHAR(255) NULL,
//     category_id BIGINT UNSIGNED,
//     allDay TINYINT(1) NULL default 0,
//     repetir ENUM('week', 'month', 'year') NULL,
//     color VARCHAR(50) NULL,
//     user_id BIGINT UNSIGNED,
//     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//     deleted_at TIMESTAMP NULL,
//     CONSTRAINT fk_eventcategories_category_id FOREIGN KEY (category_id) REFERENCES eventcategories(id),
//     CONSTRAINT fk_events_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
// );

// ALTER TABLE agreements
// ADD COLUMN external TINYINT(1) NOT NULL DEFAULT 0 AFTER endDate;

// NUEVOOO old

// CREATE TABLE agreement_commitments (
//     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
//     accion VARCHAR(255) NULL,
//     date DATETIME NULL,
//     modality VARCHAR(1) NULL,
//     address VARCHAR(100) NULL,
//     participants INT NULL,
//     file1_path VARCHAR(255) DEFAULT NULL,
//     file1_name VARCHAR(255) DEFAULT NULL,
//     file2_path VARCHAR(255) DEFAULT NULL,
//     file2_name VARCHAR(255) DEFAULT NULL,
//     file3_path VARCHAR(255) DEFAULT NULL,
//     file3_name VARCHAR(255) DEFAULT NULL,
//     details VARCHAR(255) DEFAULT NULL,
//     agreement_id BIGINT UNSIGNED,
//     user_id BIGINT UNSIGNED,
//     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

//     -- Definición de claves foráneas
//     CONSTRAINT fk_agreement_commitments_user_id FOREIGN KEY (user_id)
//         REFERENCES users(id) ON DELETE SET NULL,

//     CONSTRAINT fk_agreement_commitments_agreement_id FOREIGN KEY (agreement_id)
//         REFERENCES agreements(id) ON DELETE CASCADE ON UPDATE CASCADE
// );


// CREATE TABLE commitments (
//     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
//     title VARCHAR(100) NOT NULL,
//     type VARCHAR(10) NOT NULL,
//     description VARCHAR(255) NULL,
//     meta INT NULL,
//     agreement_id BIGINT UNSIGNED NULL,
//     user_id BIGINT UNSIGNED NULL,
//     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//     CONSTRAINT fk_commitments_user_id FOREIGN KEY (user_id)
//         REFERENCES users(id) ON DELETE SET NULL, -- Set NULL si el usuario es eliminado
//     CONSTRAINT fk_commitments_agreement_id FOREIGN KEY (agreement_id)
//         REFERENCES agreements(id) ON DELETE SET NULL ON UPDATE CASCADE -- Set NULL si el acuerdo es eliminado
// );


// ALTER TABLE agreement_commitments
// ADD COLUMN commitment_id BIGINT UNSIGNED AFTER details;

// ALTER TABLE agreement_commitments
// ADD CONSTRAINT fk_agreement_commitments_commitment_id FOREIGN KEY (commitment_id) REFERENCES commitments(id) ON DELETE CASCADE;
