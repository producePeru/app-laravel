<?php

use App\Http\Controllers\People\PersonController;
use Illuminate\Support\Facades\Route;

Route::controller(PersonController::class)->group(function () {

    Route::get('list', 'index');

    Route::get('get-businessman-data/{numberDocument}', 'getBusinessmanData');

    Route::post('register-new-businessman', 'registerOrUpdateBusinessman');

    Route::put('update-data-businessman/{id}', 'updateDataBusinessman');
});


// businessman
