<?php

use App\Http\Controllers\Google\GoogleOAuthController;
use App\Http\Controllers\GoogleDriveController;
use Illuminate\Support\Facades\Route;


Route::controller(GoogleOAuthController::class)->group(function () {

    Route::get('auth', 'redirectToGoogle');

    Route::get('callback', 'handleGoogleCallback');
});

// google
