<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Advisory\AdvisoryController;
use App\Http\Controllers\Formalization\AdvisoryExportController;
use App\Http\Controllers\Formalization\Formalization10Controller;
use App\Http\Controllers\Formalization\Formalization20Controller;
use App\Http\Controllers\Formalization\FormalizationRuc10ExportController;
use App\Http\Controllers\Formalization\FormalizationRuc20ExportController;

// Route::controller(AdvisoryController::class)->group(function () {

//     Route::put('update-values-advisory/{id}', 'updateValuesAdvisory');
// });


Route::controller(Formalization10Controller::class)->group(function () {

    Route::post('create-ruc-10', 'storeRuc10');

    Route::delete('delete-ruc-10/{id}', 'destroy');

    Route::put('update-values-ruc-10/{id}', 'updateValueRuc10');
});


Route::controller(Formalization20Controller::class)->group(function () {

    Route::post('create-ruc20', 'storeRuc20');

    Route::put('update-values-ruc-20/{id}', 'updateValueRuc20');
});


Route::controller(AdvisoryExportController::class)->group(function () {

    Route::post('export-asesories', 'exportAsesories');
});

Route::controller(FormalizationRuc10ExportController::class)->group(function () {

    Route::post('export-formalizations-ppnn', 'exportRuc10');
});


Route::controller(FormalizationRuc20ExportController::class)->group(function () {

    Route::post('export-formalizations-ppjj', 'exportRuc20');
});


// formalization
