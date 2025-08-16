<?php

use App\Http\Controllers\People\PersonController;
use Illuminate\Support\Facades\Route;

Route::controller(PersonController::class)->group(function () {

    Route::post('new-register',  'registerNewBusinessman');

    Route::get('list', 'index');
});
