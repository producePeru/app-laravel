<?php

use App\Http\Controllers\Pnte\PpCapacitadorController;
use Illuminate\Support\Facades\Route;

Route::controller(PpCapacitadorController::class)->group(function () {

    Route::POST('new-trainer', 'store');

    Route::GET('list-trainer', 'index');

    Route::PUT('update-trainer/{id}', 'update');
});

// pp093 