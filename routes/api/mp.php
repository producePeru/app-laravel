<?php

use App\Http\Controllers\MujerProduce\FormularioPublicoController;
use App\Http\Controllers\MujerProduce\MujerProduceController;
use Illuminate\Support\Facades\Route;


Route::controller(MujerProduceController::class)->group(function () {

    Route::post('register-capacitador', 'registerCapacitador');

    Route::get('all-capacitadores', 'allCapacitadores');

    Route::post('store-event', 'mpStoreEvent');

    Route::get('index-events', 'mpIndexEvents');

    Route::put('update-event/{id}', 'mpUpdateEvent');

    Route::get('attendance-event/{slug}', 'mpAttendance');

    Route::post('create-question-diagnostic', 'createQuestionDiagnostic');

    Route::get('get-question-diagnostic', 'getQuestionDiagnostic');

    Route::put('update-question-diagnostic/{id}', 'updateQuestionDiagnostic');

    Route::put('update-status/{id}', 'updateStatus');

    Route::get('participant-diagnostic', 'mpIndexParticipantDiagnostic');

    Route::put('update-order', 'updateOrder');

    Route::put('toggle-attendance', 'toggleAttendance');

});


Route::controller(FormularioPublicoController::class)->group(function () {

    Route::post('ruc-number/{ruc}', 'checkRucNumber');

    Route::post('dni-number/{dni}', 'checkDniNumber');

    Route::post('register-participant', 'registerParticipant');

    Route::post('register-attendance', 'registerAttendance');

    Route::get('info-event-public/{slug}', 'infoEventPublic');

    Route::get('diagnostic-questions', 'diagnosticQuestions');

    Route::get('question-diagnostic', 'getQuestionDiagnostic');

    Route::post('register-consulting', 'registerConsulting');     // inicia diagnostico

    Route::post('register-diagnostic-response', 'registerDiagnosticResponse');

    Route::post('sala-link-meet', 'salaLinkMeet');

    Route::post('attendance-participant', 'attendanceParticipant');

});

// mp
