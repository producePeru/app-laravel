<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StaticController;
use App\Http\Controllers\WorkshopController;
use App\Http\Controllers\TestinController;
use App\Http\Controllers\TestoutController;
use App\Http\Controllers\MypeController;
use App\Http\Controllers\WorkshopDetailsController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\AgreementsController;



Route::post('login',                            [AuthController::class,             'login']);

//RutaDigital Test
Route::get('countries',                         [StaticController::class,           'getDataCountries']);
Route::get('departaments',                      [StaticController::class,           'getDataDepartaments']);
Route::get('province/{idDepartament}',          [StaticController::class,           'getDataProvinces']);
Route::get('district/{idProvince}',             [StaticController::class,           'getDataDistricts']);
Route::get('get-workshop-slug/{workshopSlug}',  [WorkshopController::class,         'getBySlug']);

// Route::get('invitation/{slug}',         [WorkshopController::class,         'invitation']);

Route::get('testin-questions/{workshopId}',     [TestinController::class,           'getQuestions']);
Route::get('testout-questions/{workshopId}',    [TestoutController::class,          'getQuestions']);
Route::get('data-mype/{ruc}',                   [MypeController::class,             'dataMypeRuc']);
Route::get('api-data-mype/{ruc}',               [MypeController::class,             'getDataFromExternalApi']);
Route::post('register-mype',                    [MypeController::class,             'registerMype']);
Route::post('sending-test-answers/{wsId}',      [WorkshopDetailsController::class,  'insertOrUpdateWorkshopDetails']);

Route::get('invitations/{workshopId}',          [InvitationController::class,       'invitationContent']);





// Route::post('register-user',  [AuthController::class,  'registerUser']);


Route::group(['prefix' => 'public', 'namespace' => 'App\Http\Controllers'], function() {
    
    // invitacion*******************************************************************************
    Route::get('invitation/{slug}',                 ['uses' => 'WorkshopController@invitation']);
    Route::post('accepted-invitation',              ['uses' => 'InvitationController@acceptedInvitation']);
    Route::get('person/{type}/{num}',               ['uses' => 'PeopleController@dniSearch']);                  //api
    Route::get('company/{ruc}',                     ['uses' => 'CompanyController@rucSearch']);                 //api
    Route::put('add-point/{workshopId}/{type}',     ['uses' => 'WorkshopDetailsController@addPointToWorkshop']); 
    // invitacion*******************************************************************************
});





Route::group(['prefix' => 'v1', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function(){
    

    Route::apiResource('exponents',                 ExponentController::class);
    Route::get('enableds-exponents',                ['uses' => 'ExponentController@allExponents']);
    Route::put('enabled-disabled/{exponentId}',     ['uses' => 'ExponentController@isEnabledDisabled']);

    Route::get('mype-registered',                   [ReportsController::class,      'TotalMype']);
    Route::get('mype-anual-progress',               [ReportsController::class,      'AnualProgress']);
    Route::get('mype-month-progress',               [ReportsController::class,      'MonthProgress']);

    Route::get('sedes',                             [StaticController::class,       'getDataSedes']);
    // Route::post('register',                         [AuthController::class,         'registerUser']);
    
    
    //convenios
    Route::post('agreements/new-agreement',         [AgreementsController::class,   'newAgreement']);
    Route::post('agreements/upload-pdf',            [AgreementsController::class,   'uploadPdf']);
    Route::post('agreements/commitments',           [AgreementsController::class,   'newCommitments']);
    Route::delete('delete/agreements/{id}',         [AgreementsController::class,   'deleteCommitments']);
    
    Route::get('agreements/commitments/{id}',       [AgreementsController::class,   'commitments']);

    Route::get('agreements/get-uploaded-files',     [AgreementsController::class,   'getUploadedFiles']);
    

    //MYPE
    Route::apiResource('mype',                      MypeController::class);
    Route::post('import-excel',                     ['uses' => 'MypeController@uploadExcel']);
    Route::get('export-excel',                      ['uses' => 'MypeController@downloadExcel']);
    Route::get('data-mype/{ruc}',                   ['uses' => 'MypeController@dataMypeRuc']);
    Route::get('api-data-mype/{ruc}',               ['uses' => 'MypeController@getDataFromExternalApi']);

    //patrimonios
    // Route::post('import-excel',                     []);



   
    

    // usuarios*******************************************************************************
    Route::get('users',                             ['uses' => 'UserController@index']);
    Route::get('user/{dni}',                        ['uses' => 'UserController@showUserWithViews']);
    Route::put('delete-user/{idAdmin}/{dni}',       ['uses' => 'UserController@deleteUserStatus']);
    Route::post('register-user',                    ['uses' => 'AuthController@registerUpdateUser']);
    Route::post('permission',                       ['uses' => 'PermissionController@asignedViews']);
    Route::get('permission/{idUser}',               ['uses' => 'PermissionController@showPermissions']);
    // usuarios*******************************************************************************
    
    // personas_master *******************************************************************************
    Route::get('person/{type}/{num}',               ['uses' => 'PeopleController@dniSearch']);          //api buscar dni RENIEC
    Route::post('new-person',                       ['uses' => 'PeopleController@personCreate']);
    Route::get('person/{idPost}',                   ['uses' => 'PeopleController@index']);
    Route::get('person-by-dni/{dni}',               ['uses' => 'PeopleController@show']);
    Route::put('person-dni/{dni}/{rol}',            ['uses' => 'PeopleController@deleteUser']); 
    // personas_master *******************************************************************************
    
    // notarias*******************************************************************************
    Route::get('notaries',                         ['uses' => 'NotaryController@index']);
    Route::post('notary',                          ['uses' => 'NotaryController@store']);
    Route::get('notary/{id}',                      ['uses' => 'NotaryController@show']);
    Route::put('notary/{id}',                      ['uses' => 'NotaryController@update']);
    Route::put('notary-delete/{id}',               ['uses' => 'NotaryController@deleteNotary']);
    // notarias*******************************************************************************

    // compañias_master*******************************************************************************
    Route::get('companies',                        ['uses' => 'CompanyController@index']);
    Route::post('company',                         ['uses' => 'CompanyController@companyCreateUpadate']);
    Route::put('company-delete/{id}',              ['uses' => 'CompanyController@deleteCompany']);
    
    
    Route::get('notary/{id}',                      ['uses' => 'NotaryController@show']);
    Route::put('notary/{id}',                      ['uses' => 'NotaryController@update']);
    // compañias_master*******************************************************************************





    
    
    Route::apiResource('workshops', WorkshopController::class);
    

    // Route::apiResource('invitations', InvitationController::class);


    Route::apiResource('testin', TestinController::class);
    Route::post('create-test-in/{workshopId}', ['uses' => 'TestinController@createTestin']);
    
    Route::apiResource('testout', TestoutController::class);
    Route::post('create-test-out/{workshopId}', ['uses' => 'TestoutController@createTestout']);

    Route::apiResource('invitations', InvitationController::class);
    Route::post('create-invitation/{workshopId}', ['uses' => 'InvitationController@createInvitation']);


    // Route::apiResource('invitations', InvitationController::class);
    Route::get('workshop/details/{workshopId}', ['uses' => 'WorkshopDetailsController@workshopDetails']);
    Route::put('workshop/details/average', ['uses' => 'WorkshopDetailsController@averageWorkshopDetails']);
    Route::get('workshop/bydate', ['uses' => 'WorkshopDetailsController@getWorkshopsGroupedByDate']);
    Route::get('test-all-questions/{workshopId}', ['uses' => 'WorkshopDetailsController@testAllQuestions']);


                 //mype acepta la invitacion



    



   
    


    // Route::post('invoices/bulk', ['uses' => 'InvoiceController@bulkStore']);


    Route::post('logout', [AuthController::class, 'logout']);



    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('invoices', InvoiceController::class);
});