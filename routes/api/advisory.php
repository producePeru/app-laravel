<?php

use App\Http\Controllers\Advisory\AdvisoryController;
use Illuminate\Support\Facades\Route;

Route::controller(AdvisoryController::class)->group(function () {

    Route::post('create', 'store');

    Route::put('update-values-advisory/{id}', 'updateValuesAdvisory');
});


// advisory
