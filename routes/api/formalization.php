<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Formalization\Formalization10Controller;
use App\Http\Controllers\Formalization\Formalization20Controller;

Route::controller(Formalization10Controller::class)->group(function () {

    Route::post('create-ruc-10', 'storeRuc10');
});


Route::controller(Formalization20Controller::class)->group(function () {

    Route::post('create-ruc20', 'storeRuc20');
});
