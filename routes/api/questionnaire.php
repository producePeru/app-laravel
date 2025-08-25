<?php

use App\Http\Controllers\User\SupervisorController;
use Illuminate\Support\Facades\Route;


Route::controller(SupervisorController::class)->group(function () {

    Route::get('user-has-completed-formalization-form', 'userHasCompletedFormalizationForm');

    Route::get('list-questions-answers-formalizations', 'listQuestionsAnswersFormalizations');
});


// questionnaire