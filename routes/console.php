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



// no
// ALTER TABLE formalizations20
// ADD COLUMN typecapital_id BIGINT UNSIGNED DEFAULT NULL AFTER userupdated_id;
// ALTER TABLE formalizations20
// ADD CONSTRAINT fk_formalizations20_typecapital_id
// FOREIGN KEY (typecapital_id) REFERENCES typecapital(id);



// ALTER TABLE formalizations20
// ADD COLUMN isbic CHAR(2) DEFAULT NULL AFTER typecapital_id;




// ALTER TABLE formalizations20
// ADD COLUMN montocapital VARCHAR(10) DEFAULT NULL AFTER isbic;
