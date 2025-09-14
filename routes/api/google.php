<?php

use App\Http\Controllers\Google\GoogleDriveServiceAccountController;
use App\Http\Controllers\Google\GoogleOAuthController;
use Illuminate\Support\Facades\Route;


Route::controller(GoogleOAuthController::class)->group(function () {

    Route::get('redirect', 'redirectToGoogle');

    Route::get('callback', 'handleGoogleCallback');
});


Route::controller(GoogleDriveServiceAccountController::class)->group(function () {

    Route::get('list-folders', 'listarSubcarpetas');

    Route::post('create-folder', 'crearCarpeta');



    Route::post('register-sheet-data', 'registrarEnSheet');
});

// google-api
