<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Event\PublicEventsController;

Route::controller(PublicEventsController::class)->group(function () {
    Route::post('participant-registration-sed', 'participantRegistrationSed');
});
