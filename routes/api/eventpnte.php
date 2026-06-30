<?php

use App\Http\Controllers\Pnte\ActividadPnteController;
use App\Http\Controllers\Pnte\ActividadPublicPnteController;
use Illuminate\Support\Facades\Route;

Route::controller(ActividadPnteController::class)->group(function () {

    Route::POST('store', 'store');

    Route::POST('store-pp093', 'pp093Store');

    Route::PUT('update/{id}', 'update');

    Route::GET('index', 'index');

    Route::PUT('reprogramar/{id}', 'reprogramar');

    Route::PUT('cancelar/{id}', 'cancelar');

    Route::GET('inscritos/{slug}', 'inscritosPorSlug');

    Route::GET('inscritos-pp093/{slug}', 'inscritosPP093PorSlug');

    Route::PUT('update-values-select', 'updateValuesSelect');

    Route::PUT('aprobar-evento/{id}', 'aprobarEvento');

    Route::DELETE('delete-event/{id}', 'deleteEvent'); // eliminar actividades UGGER

    // para las migraciones
    Route::POST('generar-fechas-attendance', 'generarFechasAttendance');

    Route::POST('clasificar-actividades-exacto', 'setNombreActividad');

    Route::PUT('actualizar-total-participantes', 'actualizarTotalParticipantes');

    Route::PUT('store-update-descripcion', 'storeOrUpdateDescripcion');

    Route::GET('get-descripcion-slug/{slug}', 'getDescripcionBySlug');

    Route::PUT('update-asistencia-fecha', 'updateAsistenciaFecha');

    Route::get('/evento/acceso', 'registrarAccesoToEmail');

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

    Route::POST('registro-cooperativas', 'storeEmpresarioCooperativa');
});

// event-pnte-public
