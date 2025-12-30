<?php

use App\Http\Controllers\Event\PublicEventsController;
use App\Http\Controllers\EventsPNTE\CoffeEventController;
use App\Http\Controllers\EventsPNTE\InfoController;
use App\Http\Controllers\Foro\ForoController;
use Illuminate\Support\Facades\Route;

Route::controller(InfoController::class)->group(function () {

    Route::post('company', 'createEmpresa');

    Route::post('businessman', 'createEmpresario');
});


Route::controller(PublicEventsController::class)->group(function () {

    Route::post('participant-registration-sed', 'participantRegistrationSed');

    Route::post('is-this-user-registered', 'isThisUserRegistered');

    Route::post('is-this-user-registered-mercado', 'isThisUserRegisteredMercado');

    Route::post('finally-questions-extras-sed', 'finallyQuestionsExtrasSed');
});


Route::controller(CoffeEventController::class)->group(function () {

    Route::get('show-all-photos-from-coffee-event', 'showAllPhotosFromCoffeeEvent');

    Route::delete('remove-image-from-coffee-event/{idPhoto}', 'removeImageFromCoffeeEvent');
});


Route::controller(ForoController::class)->group(function () {

    Route::post('is-registered', 'isRegistered');
});



// events-pnte 🌎
