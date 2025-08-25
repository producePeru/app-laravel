<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Fair\FairController;
use App\Http\Controllers\Event\UgsePostulanteController;

Route::controller(FairController::class)->group(function () {

    Route::get('sed', 'sedList');
});

Route::controller(UgsePostulanteController::class)->group(function () {

    Route::get('users-registered-list/{slug}', 'usersRegisteredList');
});

// events-ugse