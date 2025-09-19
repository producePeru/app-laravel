<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Fair\FairController;
use App\Http\Controllers\Event\UgsePostulanteController;

Route::controller(FairController::class)->group(function () {

    Route::get('sed', 'sedList');

    Route::get('cyber-wow', 'cyberWowList');
});

Route::controller(UgsePostulanteController::class)->group(function () {

    Route::get('users-registered-list/{slug}', 'usersRegisteredList');


    // cyberwow

    Route::get('cyber-wow-list-assistants/{slug}', 'cyberWowListAssistants');

    Route::post('cyber-wow-register-event', 'cyberWowRegisterEvent');
});

// events-ugse