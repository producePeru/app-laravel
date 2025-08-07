<?php

use App\Http\Controllers\Attendance\AttendanceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Attendance\EventsUgoController;


Route::controller(EventsUgoController::class)->group(function () {

    Route::get('list-attendace-by-asesor', 'listAttendanceByAsesor');
});


Route::controller(AttendanceController::class)->group(function () {

    Route::put('update-participant-data-ugo-event/{idParticipant}', 'updateParticipantDataUgoEvent');

    Route::delete('remove-participant-ugo-event/{idParticipant}', 'removeParticipantUgoEvent');
});


// events-ugo 🔒