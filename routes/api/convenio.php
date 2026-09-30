<?php

use App\Http\Controllers\Convenio\ConvenioController;
use Illuminate\Support\Facades\Route;

Route::controller(ConvenioController::class)->group(function () {

    Route::post('store', 'store');

    Route::get('{id}', 'show');

    Route::put('{id}', 'update');

});

// convenio
