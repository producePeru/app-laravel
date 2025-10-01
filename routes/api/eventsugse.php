<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Fair\FairController;
use App\Http\Controllers\Event\UgsePostulanteController;

Route::controller(FairController::class)->group(function () {

    Route::get('sed', 'sedList');

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

    Route::get('cyberwow-count-my-progress/{slug}', 'cyberwowCountMyProgress');
});

// events-ugse