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



Route::post('login', [AuthController::class, 'login']);

//testing
Route::post('create', [UserController::class, 'store']);

Route::group(['prefix' => 'user', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
  Route::get('list', [UserController::class, 'index']);
  Route::post('create', [UserController::class, 'store']);
  Route::delete('delete/{id}', [UserController::class, 'destroy']);
  Route::put('update/{id}', [UserController::class, 'update']);

  Route::get('dni-data/{num}', [AuthController::class, 'dniDataUser']);
  Route::post('logout', [AuthController::class, 'logout']);

  Route::get('list-asesories', [UserController::class, 'allAsesores']);

});

Route::group(['prefix' => 'person', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
  Route::get('list/{rol}/{dni}', [PersonController::class, 'index']);
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
});

Route::group(['prefix' => 'historial', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::get('advisories/{rol}/{dni}',        [HistorialController::class, 'historialAdvisories']);
    Route::get('formalizations-10/{rol}/{dni}', [HistorialController::class, 'historialFormalizations10']);
    Route::get('formalizations-20/{rol}/{dni}', [HistorialController::class, 'historialFormalizations20']);
});

Route::group(['prefix' => 'download', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::get('asesories', [DownloadFormalizationsController::class, 'exportAsesories']);
});

Route::group(['prefix' => 'notary', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::get('list', [NotaryController::class, 'indexNotary']);
    Route::get('list/{id}', [NotaryController::class, 'indexNotaryById']);
    Route::post('create', [NotaryController::class, 'storeNotary']);
    Route::delete('delete/{id}', [NotaryController::class, 'deleteNotary']);
    Route::patch('update/{id}', [NotaryController::class, 'updateNotary']);
});
Route::group(['prefix' => 'notary', 'namespace' => 'App\Http\Controllers'], function() {
    Route::get('list', [NotaryController::class, 'indexNotary']);
});

// Route::group(['prefix' => 'mype', 'namespace' => 'App\Http\Controllers'], function() {
//     Route::post('mype', [MypeController::class, 'store']);

// });

Route::group(['prefix' => 'create', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function() {
    Route::post('comercial-activities', [CreateController::class, 'postComercialActivities']);
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
});


Route::group(['prefix' => 'v1', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function(){

});





// {
//     "email": "test3@test.com",
//     "password": "12345678",
//     "name": "Pedro",
//     "lastname": "Mendoza",
//     "middlename": "Gonzales",
//     "birthday": "2000-12-12",
//     "sick": 1,
//     "phone": "987654321",
//     "gender_id": 1,
//     "cde_id": 1,
//     "office_id": 1,
//     "role_id": 1
// }


// INSERT INTO views (user_id, views, created_at, updated_at) VALUES (2, '["home","person"]', NOW(), NOW());


// People
// {
//   "documentnumber": "1212",
//   "lastname": "Libido",
//   "middlename": "Gonzales",
//   "name": "Pamela",
//   "phone": "987654001",
//   "email": "pame@hahah.com",
//   "birthday": "1990-12-12",
//   "sick": 2,
//   "facebook": null,
//   "linkedin": null,
//   "instagram": "https://www.youtube.com/watch?v=3JkKdgs6IS8&list=PL36D5522F03F1E241&index=3&ab_channel=DiegoHalc%C3%B3n",
//   "tiktok": null,
//   "city_id": 1,
//   "province_id": 1,
//   "district_id": 2,
//   "typedocument_id": 1,
//   "gender_id": 1,
//   "people_id": 2,
//   "from_id": 1
// }


//asesoria
// {
//   "observations": null,
//   "user_id": 2,
//   "people_id": 2,
//   "component_id": 1,
//   "theme_id": 1,
//   "modality_id": 1
// }


// formalizacion 10
// {
//     "detailprocedure_id": 1,
//     "modality_id": 1,
//     "economicsector_id": 1,
//     "comercialactivity_id": 1,
//     "city_id": 1,
//     "province_id": 1,
//     "district_id": 2,
//     "people_id": 6,
//     "user_id": 2
// }








// paso1
    // "task": 1,
    // "codesunarp": "PERU899",
    // "economicsector_id": 1,
    // "comercialactivity_id": 1,
    // "regime_id": 1,
    // "address": "Calle los jardeincesz 1234",
    // "city_id": 1,
    // "province_id": 2,
    // "district_id": 25,
    // "modality_id": 2,
    // "user_id": 2,


    // paso2
    // "task": 2,
    // "user_id": 2,
    // "people_id": 25,
    // "name": "Hermanos RIVERA SAC",
    // "numbernotary": "R23",
    // "notary_id": 1,
    // "userupdated_id": 2

    // paso3 /code
    // "task": 3,
    // "mype_id": 3,
    // "ruc": "2099393939"
