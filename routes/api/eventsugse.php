<?php

use App\Http\Controllers\Event\CyberWowController;
use App\Http\Controllers\Event\CyberwowParticipantController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Fair\FairController;
use App\Http\Controllers\Event\UgsePostulanteController;
use App\Http\Controllers\Foro\ForoController;

Route::controller(FairController::class)->group(function () {

    Route::get('sed', 'sedList');

    Route::get('mujer-produce', 'mujerProduceList');

    Route::get('cyber-wow', 'cyberWowList');
});

Route::controller(UgsePostulanteController::class)->group(function () {

    Route::get('users-registered-list/{slug}', 'usersRegisteredList');


    // cyberwow

    Route::get('cyber-wow-list-assistants/{slug}', 'cyberWowListAssistants');

    Route::post('cyber-wow-register-event', 'cyberWowRegisterEvent');

    Route::delete('cyber-wow-delete-participant/{id}', 'cyberWowDeleteParticipant');

    Route::put('select-leader-for-this-event', 'selectLeaderForThisEvent');

    Route::put('a-company-to-leader', 'aCompanyToLeader');

    Route::get('cyberwow-company-count/{slug}', 'cyberwowCompanyCount');

    Route::get('companies-assigned-to-my-user/{slug}', 'companiesAssignedToMyUser');

    Route::post('cyberwow-step-1', 'cyberwowStep1');

    Route::post('cyberwow-step-2', 'cyberwowStep2');

    Route::post('cyberwow-step-3', 'cyberwowStep3');

    Route::get('cyberwow-count-my-progress/{slug}', 'cyberwowCountMyProgress');

    Route::get('cyberwow-count-principal-panel/{slug}', 'cyberwowCountPrincipalPanel');

    Route::get('cyberwow-leaders-report/{slug}', 'resumenPorUsuarios');
});


Route::controller(CyberwowParticipantController::class)->group(function () {

    Route::put('update-participant-data/{id}', 'updateParticipantData');
});


Route::controller(CyberWowController::class)->group(function () {

    Route::put('cyberwow-back-step-1/{idCompany}', 'cyberwowBackStep1');

    Route::put('cyberwow-back-step-2/{idCompany}', 'cyberwowBackStep2');

    Route::get('cyberwow-data-step-2/{idWow}/{idParticipante}', 'cyberwowDataStep2');

    Route::get('cyberwow-data-step-3/{slug}/{company_id}', 'cyberwowDataStep3');

    Route::put('update-leader-supervisor', 'updateLeadertoSupervisor');

    Route::POST('get-list-leaders-supervisor', 'getListLeadersToSupervisor');

    Route::POST('follow-up-leader-to-company', 'followUpLeaderToCompany');

    Route::POST('follow-up-leader-to-brand', 'followUpLeaderToBrand');

    Route::POST('follow-up-leader-to-products', 'followUpLeaderToProducts');

    Route::PUT('remove-leader-from-company/{idParticipante}', 'removeLeaderFromCompany');

    // Route::GET('merge-with-frame/{idParticipante}', 'mergeWithFrame');
});



// events-ugse


// Route::get('cyberwow-step-3/{slug}/{company_id}', [CyberWowController::class, 'getCyberwowStep3']);
