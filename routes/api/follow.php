<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Historial\FollowUpController;


Route::controller(FollowUpController::class)->group(function () {

    Route::get('registration-day/{dni}', 'registrationDay');

    Route::get('show-all-consultancies/{idPeople}', 'showAllConsultancies');

    Route::get('show-all-f10/{idPeople}', 'showAllF10');

    Route::get('show-all-f20/{idPeople}', 'showAllF20');
});


// follow
