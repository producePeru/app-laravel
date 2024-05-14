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


// **** FALTAN
// ALTER TABLE advisories
// ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;


// ALTER TABLE profiles
// ADD COLUMN city_id INT UNSIGNED DEFAULT NULL AFTER user_id,
// ADD COLUMN province_id INT UNSIGNED DEFAULT NULL AFTER city_id,
// ADD COLUMN district_id INT UNSIGNED DEFAULT NULL AFTER province_id,
// ADD COLUMN address VARCHAR(100) DEFAULT NULL AFTER district_id;







