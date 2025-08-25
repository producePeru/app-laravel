<?php

use App\Http\Controllers\People\PersonController;
use Illuminate\Support\Facades\Route;

Route::controller(PersonController::class)->group(function () {

  Route::get('get-businessman-data/{numberDocument}', 'getBusinessmanData');

  Route::post('register-update-businessman', 'registerOrUpdateBusinessman');
});


// 