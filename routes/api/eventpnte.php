<?php

use App\Http\Controllers\Pnte\ActividadPnteController;
use Illuminate\Support\Facades\Route;


Route::controller(ActividadPnteController::class)->group(function () {

    Route::POST('store', 'store');

    Route::PUT('update/{id}', 'update');

    Route::GET('index', 'index');
});


// event-pnte
