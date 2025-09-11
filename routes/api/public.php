<?php

use App\Http\Controllers\Attendance\AttendanceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Event\PublicEventsController;
use App\Http\Controllers\Fair\FairController;
use App\Http\Controllers\Training\TrainingController;

Route::controller(PublicEventsController::class)->group(function () {

    Route::post('consult-company-ruc/{ruc}', 'rucConsultCompany');

    Route::post('consult-businessman-dni/{dni}', 'dniConsultBusinessman');

    Route::post('questions-answers-formalization', 'formalizationsQuestionsAndAnswers');
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

    Route::get('breakdown-by-month/{year}', 'breakdownByMonth');

    Route::get('annual-summary/{year}', 'annualSummary');


    Route::get('meeting-monthly-goals/{year}/{month}', 'meetingMonthlyGoals');
});
