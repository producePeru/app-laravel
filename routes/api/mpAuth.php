<?php

use App\Http\Controllers\MujerProduce\FormularioPublicoController;
use App\Http\Controllers\MujerProduce\MujerProduceController;
use Illuminate\Support\Facades\Route;


Route::controller(MujerProduceController::class)->group(function () {

    Route::post('create-personalized-advice', 'createPersonalizedAdvice');

    Route::get('mp-index-advice', 'mpIndexAdvice');

    Route::put('update-personalized-advice/{id}', 'updatePersonalizedAdvice');

    Route::delete('remove-attend/{id}', 'removeAttend');

    Route::put('update-attend/{id}', 'updateAttend');

    Route::delete('delete-attend/{id}', 'deleteAttend');
});


// mp
