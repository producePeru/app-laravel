<?php

use App\Http\Controllers\Pnte\ActividadPnteController;
use App\Http\Controllers\Pnte\ActividadPublicPnteController;
use Illuminate\Support\Facades\Route;

Route::controller(ActividadPnteController::class)->group(function () {

    Route::POST('store', 'store');

    Route::PUT('update/{id}', 'update');

    Route::GET('index', 'index');

    Route::PUT('reprogramar/{id}', 'reprogramar');

    Route::PUT('cancelar/{id}', 'cancelar');

    Route::GET('inscritos/{slug}', 'inscritosPorSlug');

    Route::PUT('update-values-select', 'updateValuesSelect');

    Route::PUT('aprobar-evento/{id}', 'aprobarEvento');

    // para las migraciones
    Route::POST('generar-fechas-attendance', 'generarFechasAttendance');

    Route::POST('clasificar-actividades-exacto', 'setNombreActividad');

    Route::PUT('actualizar-total-participantes', 'actualizarTotalParticipantes');

    Route::PUT('store-update-descripcion', 'storeOrUpdateDescripcion');

    Route::GET('get-descripcion-slug/{slug}', 'getDescripcionBySlug');

    Route::PUT('update-asistencia-fecha', 'updateAsistenciaFecha');

    Route::GET('attendance-summary-slug/{slug}', 'attendanceSummaryBySlug');
});

// event-pnte

Route::controller(ActividadPublicPnteController::class)->group(function () {

    Route::GET('actividad-detalle/{slug}', 'show');

    Route::GET('empresa/{ruc}', 'getByRuc');

    Route::GET('empresario/{dni}', 'getByDni');

    Route::POST('registrar', 'storeEmpresario');

    Route::GET('dots', 'getEventsDots');

    Route::GET('events-day', 'getEventsByDate');
});

// event-pnte-public
