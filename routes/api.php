<?php

use App\Http\Controllers\People\PersonController;
use App\Http\Controllers\User\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Advisory\AdvisoryController;
use App\Http\Controllers\Selects\SelectController;
use App\Http\Controllers\Selects\CreateController;
use App\Http\Controllers\Formalization\Formalization10Controller;
use App\Http\Controllers\Formalization\Formalization20Controller;
use App\Http\Controllers\Formalization\PlanActionsController;
use App\Http\Controllers\Formalization\NotaryController;
use App\Http\Controllers\Formalization\HistorialController;
use App\Http\Controllers\Download\DownloadFormalizationsController;
use App\Http\Controllers\Download\DownloadActionsPlanController;
use App\Http\Controllers\User\SupervisorController;
use App\Http\Controllers\Drive\DriveController;
use App\Http\Controllers\Formalization\FormalizationDigitalController;
use App\Http\Controllers\Formalization\ChartController;
use App\Http\Controllers\Download\DownloadOthersController;
use App\Http\Controllers\Agreement\AgreementController;
use App\Http\Controllers\User\TokenController;
use App\Http\Controllers\Mype\MypeController;
use App\Http\Controllers\Automatic\CertificadoPDFController;


Route::post('login', [AuthController::class, 'login']);

//testing
Route::post('create', [UserController::class, 'store']);

Route::group(['prefix' => 'public', 'namespace' => 'App\Http\Controllers'], function() {
  Route::get('dni/{num}', [AuthController::class, 'dniDataUser']);
  Route::post('formalization-digital', [FormalizationDigitalController::class, 'formalizationDigital']);
  Route::post('formalization-digital/exist-number', [FormalizationDigitalController::class, 'getStatusByDocumentNumber']);

  Route::get('location-cdes', [FormalizationDigitalController::class, 'gpsCdes']);
  Route::get('formalization/select-cde/{dni}/{id}', [FormalizationDigitalController::class, 'selectCde']);

  Route::get('notaries', [NotaryController::class, 'indexNotary']);
  Route::get('notaries-filters', [NotaryController::class, 'indexNotaryById']);

  Route::get('apk', [DownloadOthersController::class, 'descargarAPKar']);

});



Route::group(['prefix' => 'user', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
  Route::get('list', [UserController::class, 'index']);
  Route::post('create', [UserController::class, 'store']);
  Route::delete('delete/{id}', [UserController::class, 'destroy']);
  Route::put('update/{id}', [UserController::class, 'update']);

  Route::get('api/{type}/{num}', [AuthController::class, 'dniDataUser']);
  Route::get('only-dni/{num}', [AuthController::class, 'dniDataUser2']);

  Route::post('logout', [AuthController::class, 'logout']);
  Route::post('password-reset', [AuthController::class, 'passwordReset']);
  Route::put('views/{id}', [UserController::class, 'asignViewsUser']);
  Route::get('views/{id}', [UserController::class, 'showViewsUser']);

  Route::get('list-asesories', [UserController::class, 'allAsesores']);
  Route::get('my-profile', [UserController::class, 'showMyProfile']);


  // REGISTRAR UN ASESOR EXTERNO NOTARIO
  Route::post('register-user',      [UserController::class, 'registerUsers']);
  Route::post('register-profile',   [UserController::class, 'registerProfiles']);
  Route::post('register-roles',     [UserController::class, 'registerRoles']);
  Route::post('register-views',     [UserController::class, 'registerViewsSeven']);

});


// DRIVE - KARINA
Route::group(['prefix' => 'drive', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::get('list-files', [DriveController::class, 'index']);
    Route::get('download/{any}', [DriveController::class, 'downloadFile'])->where('any', '.*');
    Route::post('up-files', [DriveController::class, 'store']);
    Route::delete('delete-file/{id}', [DriveController::class, 'deleteFile']);

    Route::get('files', [DriveController::class, 'allFiles']);
    Route::post('create-file', [DriveController::class, 'createFile']);
    Route::put('update-file/{id}', [DriveController::class, 'updateFile']);
    Route::get('data-files/{id}', [DriveController::class, 'dataByIdFile']);

    Route::put('visible-all/{id}', [DriveController::class, 'visibleByAll']);

    Route::get('users-selected/{idDrive}', [DriveController::class, 'usersSelectedDrive']);
    Route::get('users', [DriveController::class, 'usersOnlyDrivers']); //lista de usuarios
    Route::put('visible-users', [DriveController::class, 'storeOrUpdateDriveUsers']);
});

Route::group(['prefix' => 'person', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
  Route::get('list',                [PersonController::class, 'index']);
  Route::get('found/{type}/{dni}',  [PersonController::class, 'dniFoundUser']);
  Route::post('create',             [PersonController::class, 'store']);
  Route::delete('delete/{id}',      [PersonController::class, 'destroy']);
  Route::put('update/{id}',         [PersonController::class, 'update']);
  Route::get('data/{dni}',          [PersonController::class, 'findUserById']);            //busca people x id

});


Route::group(['prefix' => 'advisory', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
  Route::get('list', [AdvisoryController::class, 'index']);
  Route::post('create', [AdvisoryController::class, 'store']);
  Route::delete('delete/{id}', [AdvisoryController::class, 'destroy']);
  Route::get('find/{id}', [AdvisoryController::class, 'getDataAdvisoryById']);
  Route::put('update/{id}', [AdvisoryController::class, 'update']);
});

// FORMALIZACIÓN*
Route::group(['prefix' => 'formalization', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::get('list-ruc-10', [Formalization10Controller::class, 'indexRuc10']);
    Route::get('list-ruc-20', [Formalization20Controller::class, 'indexRuc20']);
    Route::get('list-ruc-20/{idPerson}', [Formalization20Controller::class, 'allFormalizationsRuc20ByPersonId']);
    Route::post('create-ruc-10', [Formalization10Controller::class, 'storeRuc10']);

    Route::post('ruc20-step1', [Formalization20Controller::class, 'ruc20Step1']);
    Route::post('ruc20-step2/{codesunarp}', [Formalization20Controller::class, 'ruc20Step2']);
    Route::post('ruc20-step3/{codesunarp}', [Formalization20Controller::class, 'ruc20Step3']);
    Route::post('create-ruc20', [Formalization20Controller::class, 'ruc20All']);

    Route::delete('delete-ruc-10/{id}', [Formalization10Controller::class, 'destroy']);         //ACTUALIZACIONES 10
    Route::get('find-ruc-10/{id}', [Formalization10Controller::class, 'getDataF10ById']);
    Route::put('update-ruc-10/{id}', [Formalization10Controller::class, 'update']);

    Route::get('find-ruc-20/{id}', [Formalization20Controller::class, 'getDataF20ById']);
    Route::put('update-ruc-20/{id}', [Formalization20Controller::class, 'update']);
    Route::delete('delete-ruc-20/{id}', [Formalization20Controller::class, 'destroy']);

    // Formalizacion Digital
    Route::get('digital-list', [FormalizationDigitalController::class, 'index']);
    Route::delete('delete/{id}', [FormalizationDigitalController::class, 'deleteRegister']);
    Route::put('digital/update-attended', [FormalizationDigitalController::class, 'updateAttendedStatus']);

    // Chart
    Route::get('chart', [ChartController::class, 'index']);
    // Route::get('by-advisors', [ChartController::class, 'countAdvisoriesByAdvisors']);


    // NUEVA FUNCION DE SETEO para MYPEs
    Route::get('plan-actions', [PlanActionsController::class, 'planActions']);

    Route::put('set-ruc-advisories',   [PlanActionsController::class, 'rucAdvisoriesSet']);
    Route::put('set-ruc-ruc-10',       [PlanActionsController::class, 'rucFormalizationR10Set']);
    Route::put('set-ruc-ruc-20',       [PlanActionsController::class, 'rucFormalizationR20Set']);
});

Route::group(['prefix' => 'historial', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::get('advisories',        [HistorialController::class, 'historialAdvisories']);
    Route::get('formalizations-10', [HistorialController::class, 'historialFormalizations10']);
    Route::get('formalizations-20', [HistorialController::class, 'historialFormalizations20']);

    //filters
    Route::get('advisories/filters', [HistorialController::class, 'filterHistorialAdvisoriesByDates']);
    Route::get('formalizations-10/filters', [HistorialController::class, 'filterHistorialFormalizations10ByDates']);
    Route::get('formalizations-20/filters', [HistorialController::class, 'filterHistorialFormalizations20ByDates']);

    //registros-historial
    Route::get('registers/{idPeople}', [HistorialController::class, 'getByPeopleIdRegisters']);

});

Route::group(['prefix' => 'download', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::get('asesories',             [DownloadFormalizationsController::class, 'exportAsesories']);
    Route::get('formalizations-ruc10',  [DownloadFormalizationsController::class, 'exportFormalizationsRuc10']);
    Route::get('formalizations-ruc20',  [DownloadFormalizationsController::class, 'exportFormalizationsRuc20']);

    Route::get('actions-plans',         [DownloadActionsPlanController::class, 'exportActionPlans']);

});

Route::group(['prefix' => 'token', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::get('list', [TokenController::class, 'index']);
    Route::post('create', [TokenController::class, 'store']);
    Route::put('update-status/{id}', [TokenController::class, 'updateStatus']);
});

Route::group(['prefix' => 'notary', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::get('list', [NotaryController::class, 'indexNotary']);
    Route::get('list/{id}', [NotaryController::class, 'indexNotaryById']);
    Route::post('create', [NotaryController::class, 'storeNotary']);
    Route::delete('delete/{id}', [NotaryController::class, 'deleteNotary']);
    Route::patch('update/{id}', [NotaryController::class, 'updateNotary']);
});

Route::group(['prefix' => 'notary', 'namespace' => 'App\Http\Controllers'], function() {

});

// Route::group(['prefix' => 'mype', 'namespace' => 'App\Http\Controllers'], function() {
//     Route::post('mype', [MypeController::class, 'store']);

// });

Route::group(['prefix' => 'create', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::post('comercial-activities', [CreateController::class, 'postComercialActivities']);
    Route::post('office', [CreateController::class, 'createOffice']);
    Route::post('economic-sector', [CreateController::class, 'createEconomicSector']);
    Route::post('component', [CreateController::class, 'createNewComponent']);
    Route::post('theme', [CreateController::class, 'createNewTheme']);
    // Route::post('economic-sector', [CreateController::class, 'createNewEconomicSector']);
    Route::post('cde-notary', [CreateController::class, 'createCdeNotary']);            //crea automaticamente la cde del asesor externo notario

});

Route::group(['prefix' => 'supervisores', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::get('list', [SupervisorController::class, 'index']);

});

// CONVENIOS
Route::group(['prefix' => 'agreement', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::get('list', [AgreementController::class, 'index']);
    Route::get('list/{id}', [AgreementController::class, 'allActionsById']);
    Route::get('list-files/{id}', [AgreementController::class, 'listAllFilesById']);

    Route::delete('delete-acction/{id}', [AgreementController::class, 'deleteActionById']);
    Route::delete('delete/{id}', [AgreementController::class, 'deleteAgreement']);
    Route::delete('delete/file/{id}', [AgreementController::class, 'deleteFileById']);

    Route::post('create', [AgreementController::class, 'store']);
    Route::post('create-acction', [AgreementController::class, 'storeAction']);
    Route::post('file', [AgreementController::class, 'upFileAgreement']);
    Route::post('file-download/{id}', [AgreementController::class, 'download']);
    Route::post('download', [AgreementController::class, 'exportAgreement']);

    Route::put('update/{id}', [AgreementController::class, 'updateActionById']);
});

Route::group(['prefix' => 'select', 'namespace' => 'App\Http\Controllers'], function() {
    Route::get('cities', [SelectController::class, 'getCities']);
    Route::get('provinces/{idCity}', [SelectController::class, 'getProvinces']);
    Route::get('districts/{idProv}', [SelectController::class, 'getDistricts']);
    Route::get('offices', [SelectController::class, 'getOffices']);
    Route::get('cdes', [SelectController::class, 'getCdes']);
    Route::get('genders', [SelectController::class, 'getGenders']);
    Route::get('modalities', [SelectController::class, 'getModalities']);
    Route::get('type-documents', [SelectController::class, 'getTypeDocuments']);
    Route::get('components', [SelectController::class, 'getComponents']);
    Route::get('component-theme/{id}', [SelectController::class, 'getComponentTheme']);
    Route::get('roles', [SelectController::class, 'getRoles']);
    Route::get('detail-procedures', [SelectController::class, 'getProcedures']);
    Route::get('economic-sectors', [SelectController::class, 'getEconomicSectors']);
    Route::get('comercial-activities', [SelectController::class, 'getComercialActivities']);
    Route::get('regimes', [SelectController::class, 'getRegimes']);
    Route::get('notaries', [SelectController::class, 'getNotaries']);
    Route::get('supervisores', [SelectController::class, 'getSupervisores']);
    Route::get('folders', [SelectController::class, 'getFolders']);
    Route::get('asesores', [SelectController::class, 'getAsesores']);
    Route::get('operational-status', [SelectController::class, 'getOperationalStatus']);
    Route::get('agreement-status', [SelectController::class, 'getAgreementStatus']);
    Route::get('type-capital', [SelectController::class, 'getTypeCapital']);

});

// Route::group(['prefix' => 'automatic', 'namespace' => 'App\Http\Controllers'], function() {
//     Route::put('mype-info', [MypeController::class, 'setInfoMype']);
// });

Route::group(['prefix' => 'mype', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::get('list', [MypeController::class, 'index']);
    Route::put('update/{ruc}', [MypeController::class, 'getApiInfo']);
    Route::get('get-by-ruc/{ruc}', [MypeController::class, 'getDataByRuc']);
    Route::put('update-by-ruc/{id}', [MypeController::class, 'updateDataByRuc']);
    Route::post('create', [MypeController::class, 'store']);

});

Route::group(['prefix' => 'plans-action', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::get('list', [PlanActionsController::class, 'index']);
    Route::get('components/{ruc}', [PlanActionsController::class, 'listAllServicesAF']);
    Route::post('create', [PlanActionsController::class, 'store']);
    Route::put('edit-component', [PlanActionsController::class, 'editComponent']);
    Route::put('edit-yes-no', [PlanActionsController::class, 'updateField']);


});

Route::group(['prefix' => 'automatic', 'namespace' => 'App\Http\Controllers'], function() {
    Route::post('send-certificates', [CertificadoPDFController::class, 'sendEmailWithCertificates']);

});

Route::group(['prefix' => 'google', 'namespace' => 'App\Http\Controllers'], function() {
    Route::post('index-calendar', [CertificadoPDFController::class, 'calendar']);

});


Route::group(['prefix' => 'v1', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function(){

});
