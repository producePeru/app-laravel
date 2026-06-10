<?php

use App\Http\Controllers\Disclaimer\DisclaimerController;
use App\Http\Controllers\Event\PublicEventsController;
use App\Http\Controllers\EventsPNTE\CoffeEventController;
use App\Http\Controllers\EventsPNTE\InfoController;
use App\Http\Controllers\Foro\ForoController;
use App\Http\Controllers\Page\TareaController;
use App\Http\Controllers\Pnte\CursosPP093Controller;
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

Route::controller(DisclaimerController::class)->group(function () {

    Route::post('save-disclaimer-cw', 'saveDisclaimer');
});

Route::controller(TareaController::class)->group(function () {

    Route::get('tareas', 'index');
    Route::post('tareas', 'store');

    Route::put('tareas/{tarea}', 'update');
    Route::delete('tareas/{tarea}', 'destroy');

    Route::patch('tareas/reordenar', 'reordenar');
    Route::patch('tareas/{tarea}/completar', 'completar');
    Route::patch('tareas/{tarea}/restaurar', 'restaurar');
});

Route::controller(CursosPP093Controller::class)->group(function () {

    Route::get('courses-pp093', 'coursesPP093');
});

// events-pnte 🌎
