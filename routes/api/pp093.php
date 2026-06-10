<?php

use App\Http\Controllers\Pnte\PpCapacitadorController;
use App\Http\Controllers\Pnte\ActividadPublicPnteController;
use Illuminate\Support\Facades\Route;

Route::controller(PpCapacitadorController::class)->group(function () {

    Route::POST('new-trainer', 'store');

    Route::GET('list-trainer', 'index');

    Route::PUT('update-trainer/{id}', 'update');
});

// pp093 


Route::controller(PpCapacitadorController::class)->group(function () {

    Route::GET('is-register/{ruc}/{dni}', 'isRegisterPlataforma');
});


Route::controller(ActividadPublicPnteController::class)->group(function () {
    
    Route::POST('register-mype', 'registerBusinessManPP093');

    Route::POST('check-course', 'checkToCoursePp093');

});

// pp093-public


    