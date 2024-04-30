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
use App\Http\Controllers\Formalization\NotaryController;
use App\Http\Controllers\Formalization\HistorialController;
use App\Http\Controllers\Download\DownloadFormalizationsController;
use App\Http\Controllers\User\SupervisorController;
use App\Http\Controllers\Drive\DriveController;
use App\Http\Controllers\Formalization\FormalizationDigitalController;
use App\Http\Controllers\Formalization\ChartController;

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
  Route::get('notaries/{id}', [NotaryController::class, 'indexNotaryById']);
});



Route::group(['prefix' => 'user', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
  Route::get('list', [UserController::class, 'index']);
  Route::post('create', [UserController::class, 'store']);
  Route::delete('delete/{id}', [UserController::class, 'destroy']);
  Route::put('update/{id}', [UserController::class, 'update']);

  Route::get('dni-data/{num}', [AuthController::class, 'dniDataUser']);
  Route::post('logout', [AuthController::class, 'logout']);
  Route::post('password-reset', [AuthController::class, 'passwordReset']);

  Route::get('list-asesories', [UserController::class, 'allAsesores']);
  Route::get('my-profile', [UserController::class, 'showMyProfile']);
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

});

Route::group(['prefix' => 'person', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
  Route::get('list', [PersonController::class, 'index']);
  Route::get('found/{type}/{dni}', [PersonController::class, 'dniFoundUser']);
  Route::post('create', [PersonController::class, 'store']);
  Route::delete('delete/{id}', [PersonController::class, 'destroy']);
  Route::put('update/{id}', [PersonController::class, 'update']);
});


Route::group(['prefix' => 'advisory', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
  Route::get('list', [AdvisoryController::class, 'index']);
  Route::post('create', [AdvisoryController::class, 'store']);
  Route::delete('delete/{id}', [AdvisoryController::class, 'destroy']);
});

Route::group(['prefix' => 'formalization', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::get('list-ruc-10', [Formalization10Controller::class, 'indexRuc10']);
    Route::get('list-ruc-20', [Formalization20Controller::class, 'indexRuc20']);
    Route::get('list-ruc-20/{idPerson}', [Formalization20Controller::class, 'allFormalizationsRuc20ByPersonId']);
    Route::post('create-ruc-10', [Formalization10Controller::class, 'storeRuc10']);
    Route::post('ruc20-step1', [Formalization20Controller::class, 'ruc20Step1']);
    Route::post('ruc20-step2/{codesunarp}', [Formalization20Controller::class, 'ruc20Step2']);
    Route::post('ruc20-step3/{codesunarp}', [Formalization20Controller::class, 'ruc20Step3']);

    // Formalizacion Digital
    Route::get('digital-list', [FormalizationDigitalController::class, 'index']);
    Route::delete('delete/{id}', [FormalizationDigitalController::class, 'deleteRegister']);
    Route::put('digital/update-attended', [FormalizationDigitalController::class, 'updateAttendedStatus']);
    // Chart
    Route::get('chart', [ChartController::class, 'index']);
    Route::get('by-advisors', [ChartController::class, 'countAdvisoriesByAdvisors']);
});

Route::group(['prefix' => 'historial', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::get('advisories',        [HistorialController::class, 'historialAdvisories']);
    Route::get('formalizations-10', [HistorialController::class, 'historialFormalizations10']);
    Route::get('formalizations-20', [HistorialController::class, 'historialFormalizations20']);

    //filters
    Route::get('advisories/filters', [HistorialController::class, 'filterHistorialAdvisoriesByDates']);
    Route::get('formalizations-10/filters', [HistorialController::class, 'filterHistorialFormalizations10ByDates']);
    Route::get('formalizations-20/filters', [HistorialController::class, 'filterHistorialFormalizations20ByDates']);

});

Route::group(['prefix' => 'download', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::get('asesories', [DownloadFormalizationsController::class, 'exportAsesories']);
    Route::get('formalizations-ruc10', [DownloadFormalizationsController::class, 'exportFormalizationsRuc10']);
    Route::get('formalizations-ruc20', [DownloadFormalizationsController::class, 'exportFormalizationsRuc20']);
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

});

Route::group(['prefix' => 'supervisores', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::get('list', [SupervisorController::class, 'index']);

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

});


Route::group(['prefix' => 'v1', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function(){

});


// ALTER TABLE drives
// ADD COLUMN is_visible BOOLEAN DEFAULT FALSE AFTER file_id;

