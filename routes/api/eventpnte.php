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
});

// event-pnte


Route::controller(ActividadPublicPnteController::class)->group(function () {

    Route::GET('actividad-detalle/{slug}', 'show');

    Route::GET('empresa/{ruc}', 'getByRuc');

    Route::GET('empresario/{dni}', 'getByDni');

    Route::POST('registrar', 'storeEmpresario');
});

// event-pnte-public
