<?php

use App\Http\Controllers\Sed\SedController;
use App\Http\Controllers\Sed\SedPublicController;
use Illuminate\Support\Facades\Route;


Route::controller(SedController::class)->group(function () {

    Route::post('store-sed-survey', 'storeSedSurvey');

    Route::get('get-sed-survey-admin/{slug}', 'getSedSurvey');

    Route::put('update-sed-survey/{id}', 'updateSedQuestion');

    Route::delete('delete-sed-survey/{id}', 'deleteSedQuestion');

    Route::post('store-question', 'storeSedQuestion');
});

Route::controller(SedPublicController::class)->group(function () {

    Route::get('get-sed-survey/{slug}', 'getSedSurvey');

    Route::get('is-register-this-user', 'isRegisterThisUser');

    Route::get('ruc-company/{ruc}', 'rucCompany');

    Route::get('dni-busimess-man/{dni}', 'dniBusimessMan');

    Route::post('sed-register-mype', 'sedRegisterMype');

    Route::post('qr-send-email-invitation', 'qrSendEmailInvitation');

    Route::post('save-survey', 'saveSurvey');

    Route::post('participant-consultation', 'participantConsultation');

    Route::put('register-attendance', 'registerAttendance');

    Route::put('mark-attendance', 'markAttendance');
});


// sed
