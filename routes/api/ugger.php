<?php

use App\Http\Controllers\Word\WordActividadesUgoController;
use Illuminate\Support\Facades\Route;


Route::controller(WordActividadesUgoController::class)->group(function () {

    Route::post('word-ugo-scheduled-activities', 'wordUgoScheduledActivities');
});


// ugger
