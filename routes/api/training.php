<?php

use App\Http\Controllers\Training\TrainingController;
use App\Http\Controllers\Training\TrainingDimensionController;
use App\Http\Controllers\Training\TrainingMetaController;
use App\Http\Controllers\Training\TrainingSpecialistController;
use Illuminate\Support\Facades\Route;

Route::controller(TrainingSpecialistController::class)->group(function () {

    Route::get('list-specialist', 'index');

    Route::post('register-specialist', 'store');

    Route::put('update-specialist/{id}', 'update');

    Route::delete('delete-specialist/{id}', 'destroy');
});


Route::controller(TrainingDimensionController::class)->group(function () {

    Route::get('list-dimension', 'index');

    Route::post('register-dimension', 'store');

    Route::put('update-dimension/{id}', 'update');

    Route::delete('delete-dimension/{id}', 'destroy');
});


Route::controller(TrainingMetaController::class)->group(function () {

    Route::get('list-meta', 'index');

    Route::post('register-meta', 'store');

    Route::put('update-meta/{id}', 'update');

    Route::delete('delete-meta/{id}', 'destroy');
});


Route::controller(TrainingController::class)->group(function () {

    Route::get('list-trainings', 'index');

    Route::post('register-training', 'store');

    Route::put('update-training/{id}', 'update');

    Route::put('update-training-status/{id}', 'updateStatus');

    // Route::delete('delete-meta/{id}', 'destroy');

});

// training
