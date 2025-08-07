<?php

use App\Http\Controllers\Event\PublicEventsController;
use App\Http\Controllers\EventsPNTE\InfoController;
use Illuminate\Support\Facades\Route;

Route::controller(InfoController::class)->group(function () {

    Route::post('company', 'createEmpresa');

    Route::post('businessman', 'createEmpresario');
});


Route::controller(PublicEventsController::class)->group(function () {

    Route::post('participant-registration-sed', 'participantRegistrationSed');

    Route::post('is-this-user-registered', 'isThisUserRegistered');

    Route::post('is-this-user-registered-mercado', 'isThisUserRegisteredMercado');
});

// events-pnte 🌎
