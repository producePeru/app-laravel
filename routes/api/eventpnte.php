<?php

use App\Http\Controllers\Pnte\ActividadPnteController;
use App\Http\Controllers\Pnte\ActividadPublicPnteController;
use App\Http\Controllers\Pnte\PnteTestController;
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

Route::controller(PnteTestController::class)->group(function () {

    Route::PUT('pp093-save-test', 'saveTest');  // guardar las preguntas desde el Admin

    Route::GET('pp093-get-test-entrada/{slug}', 'getTestEntrada');

    Route::GET('pp093-get-test-salida/{slug}', 'getTestSalida');
});

// event-pnte 🔒

Route::controller(ActividadPublicPnteController::class)->group(function () {

    Route::GET('actividad-detalle/{slug}', 'show');

    Route::GET('empresa/{ruc}', 'getByRuc');

    Route::GET('empresario/{dni}', 'getByDni');

    Route::POST('registrar', 'storeEmpresario');

    Route::GET('dots', 'getEventsDots');

    Route::GET('events-day', 'getEventsByDate');

    Route::POST('registro-cooperativas', 'storeEmpresarioCooperativa');
});

Route::controller(PnteTestController::class)->group(function () {

    Route::GET('pp093-get-public-test/{slug}', 'getPublicTest');

    Route::GET('pp093-get-public-test-end/{slug}', 'getPublicTestEnd');

    Route::POST('pp093-validate-mype-user', 'validatePublicTest');

    Route::GET('pp093-get-event-info/{slug}', 'getEventInfo');

    Route::POST('pp093-save-public-test', 'savePublicTest');    // PARA LA PRUEBA DE ENTRADA Y SALIDA

    Route::POST('i-want-my-certificate', 'iWantMyCertificate');
});


// event-pnte-public 🌍
