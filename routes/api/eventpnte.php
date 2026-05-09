<?php

use App\Http\Controllers\Pnte\ActividadPnteController;
use Illuminate\Support\Facades\Route;

Route::controller(ActividadPnteController::class)->group(function () {

    Route::POST('store', 'store');

    Route::PUT('update/{id}', 'update');

    Route::GET('index', 'index');

    // para las migraciones
    Route::POST('generar-fechas-attendance', 'generarFechasAttendance');

    Route::POST('clasificar-actividades-exacto', 'setNombreActividad');

});

// event-pnte
