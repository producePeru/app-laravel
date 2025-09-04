<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Historial\FollowUpController;


Route::controller(FollowUpController::class)->group(function () {

    Route::get('registration-day/{dni}', 'registrationDay');
});


// follow
