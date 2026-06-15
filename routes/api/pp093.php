<?php

use App\Http\Controllers\Pnte\PpCapacitadorController;
use App\Http\Controllers\Pnte\ActividadPublicPnteController;
use App\Http\Controllers\PP03\CapacitacionesPP093Controller;
use Illuminate\Support\Facades\Route;

Route::controller(PpCapacitadorController::class)->group(function () {

    Route::POST('new-trainer', 'store');

    Route::GET('list-trainer', 'index');

    Route::PUT('update-trainer/{id}', 'update');
});

// pp093 


Route::controller(PpCapacitadorController::class)->group(function () {

    Route::POST('is-register', 'isRegisterPlataforma');

    Route::POST('send-email-trainings', 'enviarCorreoPP093');
});


Route::controller(ActividadPublicPnteController::class)->group(function () {

    Route::POST('register-mype', 'registerBusinessManPP093');

    Route::POST('check-course', 'checkToCoursePp093');
});

Route::controller(CapacitacionesPP093Controller::class)->group(function () {

    Route::GET('my-courses-business/{id_usuario}', 'getCoursesByBusinessManPp093');

    Route::GET('get-public-data-ruc-dni/{ruc}/{dni}', 'getPublicDataByRucAndDni');
});

// pp093-public
