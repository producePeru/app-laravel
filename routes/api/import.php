<?php

use App\Http\Controllers\Import\ActividadAsesorController;
use App\Http\Controllers\Import\ActividadesUgoImport;
use App\Http\Controllers\Import\ImportEventsUgoController;
use Illuminate\Support\Facades\Route;


Route::controller(ImportEventsUgoController::class)->group(function () {

  Route::get('events-ugo', 'importEventsUgo');
});

Route::controller(ActividadAsesorController::class)->group(function () {

  // Route::post('activities-asesor-ugo-preview/{slug}', 'previewExcel');

  Route::post('activities-asesor-ugo/{slug}', 'importExcel');
});


Route::controller(ActividadesUgoImport::class)->group(function () {

  Route::post('inscritos-evento-ugo/{slug}', 'importarInscritosEvento');
});



// import
