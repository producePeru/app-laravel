<?php

use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\Download\DownloadImageCyberWowTemplateController;
use App\Http\Controllers\Event\CyberWowController;
use App\Http\Controllers\Event\CyberwowParticipantController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Event\PublicEventsController;
use App\Http\Controllers\Fair\FairController;
use App\Http\Controllers\Training\TrainingController;

Route::controller(PublicEventsController::class)->group(function () {

    Route::post('consult-company-ruc/{ruc}', 'rucConsultCompany');

    Route::post('consult-businessman-dni/{dni}', 'dniConsultBusinessman');

    Route::post('questions-answers-formalization', 'formalizationsQuestionsAndAnswers');

    Route::get('exist-event/{slug}/{typeId}', 'existEvent');          // existe formulario de wow
});


Route::controller(FairController::class)->group(function () {

    Route::get('event-sed-details/{slug}', 'sedDetailsEvent');

    Route::get('message-form-completed/{slug}', 'messageFormCompleted');
});


Route::controller(AttendanceController::class)->group(function () {

    Route::post('attendance-present', 'userPresent');
});



// DASHBOARD - SERGIO - training
Route::controller(TrainingController::class)->group(function () {

    Route::get('meeting-monthly-goals/{year}/{month}', 'meetingMonthlyGoals');

    Route::get('annual-summary/{year}', 'annualSummary');

    Route::get('breakdown-by-month/{year}', 'breakdownByMonth');

    Route::get('calendar-events', 'calendarEvents');

    Route::get('top-especialistas', 'topEspecialistas');

    Route::get('estadisticas-dimensiones', 'estadisticasDimensiones');

    Route::get('estadisticas-modalidad', 'estadisticasModalidad');

    Route::get('estadisticas-trainings', 'estadisticasTrainings');
});



Route::controller(CyberwowParticipantController::class)->group(function () {

    Route::post('register-cyber-wow', 'store');
});


Route::controller(DownloadImageCyberWowTemplateController::class)->group(function () {

    Route::POST('merge-with-frame/{idOffert}', 'generateOfferTemplate');
});


Route::controller(CyberWowController::class)->group(function () {

    Route::GET('cyber-pnte/{idWow}', 'offertsCyberWow');

    Route::GET('categories-cyber-wow/{idWow}', 'categoriesCyberWow');
});

// public