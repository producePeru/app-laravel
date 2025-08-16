<?php

use App\Http\Controllers\Download\DownloadFormalizationsController;
use Illuminate\Support\Facades\Route;

Route::controller(DownloadFormalizationsController::class)->group(function () {

    Route::post('asesories', 'exportAsesories');

    Route::post('formalizations-ruc10', 'exportFormalizationsRuc10');

    Route::post('formalizations-ruc20', 'exportFormalizationsRuc20');
});
