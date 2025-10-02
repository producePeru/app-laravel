<?php

use App\Http\Controllers\Download\ActividadesUgoController;
use App\Http\Controllers\Download\CyberWowParticipantesController;
use App\Http\Controllers\Download\DownloadAttendanceController;
use App\Http\Controllers\Download\DownloadFormalizationsController;
use App\Http\Controllers\Download\QuestionnarieController;
use App\Http\Controllers\Download\SedAsistentesController;
use Illuminate\Support\Facades\Route;

Route::controller(DownloadFormalizationsController::class)->group(function () {

    Route::post('asesories', 'exportAsesories');

    Route::post('formalizations-ruc10', 'exportFormalizationsRuc10');

    Route::post('formalizations-ruc20', 'exportFormalizationsRuc20');
});

Route::controller(SedAsistentesController::class)->group(function () {

    Route::post('sed-asistentes/{slug}', 'exportList');
});

Route::controller(CyberWowParticipantesController::class)->group(function () {

    Route::post('cyber-wow-participants/{slug}', 'exportList');
});


Route::controller(QuestionnarieController::class)->group(function () {

    Route::post('questions-answers-advisors-formalizations', 'questionsAnswersAdvisorsFormalizations');
});


Route::controller(DownloadAttendanceController::class)->group(function () {

    Route::get('attendance-mercado/{slug}', 'exportFortaleceTuMercado');

    Route::get('attendance/{slug}', 'exportRegistrantsUgoEvents');
});

Route::controller(ActividadesUgoController::class)->group(function () {

    Route::post('all-activities-the-year', 'allActivitiesTheYear');
});



// download
