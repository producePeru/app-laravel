<?php

use App\Http\Controllers\Convenio\ConvenioArchivoController;
use App\Http\Controllers\Convenio\ConvenioController;
use App\Http\Controllers\Convenio\ConvenioSeguimientoController;
use Illuminate\Support\Facades\Route;

Route::controller(ConvenioController::class)->group(function () {

    Route::post('store', 'store');

    Route::get('', 'index');

    Route::get('{id}', 'show');

    Route::put('{id}', 'update');

});

Route::controller(ConvenioSeguimientoController::class)->group(function () {

    Route::get('seguimiento/{convenioId}', 'seguimiento');

    Route::put('compromiso/{id}', 'actualizarCompromiso');

    Route::post('compromiso/{id}/medios', 'subirMedios');

    Route::delete('medios/{id}', 'eliminarMedio');

});

Route::controller(ConvenioArchivoController::class)->group(function () {

    Route::get('{convenioId}/archivos', 'index');

    Route::post('{convenioId}/archivos', 'store');

    Route::delete('archivos/{id}', 'destroy');

});

// convenio
