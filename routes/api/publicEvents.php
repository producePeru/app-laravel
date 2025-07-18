<?php

use App\Http\Controllers\EventsPNTE\InfoController;
use Illuminate\Support\Facades\Route;

Route::controller(InfoController::class)->group(function () {
    Route::post('company', 'createEmpresa');
    Route::post('businessman', 'createEmpresario');
});
