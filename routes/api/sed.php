<?php

use App\Http\Controllers\Sed\SedController;
use App\Http\Controllers\Sed\SedPublicController;
use Illuminate\Support\Facades\Route;


Route::controller(SedController::class)->group(function () {

    Route::post('store-sed-survey', 'storeSedSurvey');

    Route::get('get-sed-survey-admin/{slug}', 'getSedSurvey');

    Route::put('update-sed-survey/{id}', 'updateSedQuestion');

    Route::delete('delete-sed-survey/{id}', 'deleteSedQuestion');
});

Route::controller(SedPublicController::class)->group(function () {

    Route::get('get-sed-survey/{slug}', 'getSedSurvey');
});


// sed
