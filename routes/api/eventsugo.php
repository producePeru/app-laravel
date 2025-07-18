<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Attendance\EventsUgoController;

Route::controller(EventsUgoController::class)->group(function () {
    Route::get('list-attendace-by-asesor', 'listAttendanceByAsesor');
});
