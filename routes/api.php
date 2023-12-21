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



Route::post('login',                            [AuthController::class,             'login']);

//RutaDigital Test
Route::get('countries',                         [StaticController::class,           'getDataCountries']);
Route::get('departaments',                      [StaticController::class,           'getDataDepartaments']);
Route::get('province/{idDepartament}',          [StaticController::class,           'getDataProvinces']);
Route::get('district/{idProvince}',             [StaticController::class,           'getDataDistricts']);
Route::get('get-workshop-slug/{workshopSlug}',  [WorkshopController::class,         'getBySlug']);
Route::get('testin-questions/{workshopId}',     [TestinController::class,           'getQuestions']);
Route::get('testout-questions/{workshopId}',    [TestoutController::class,          'getQuestions']);
Route::get('data-mype/{ruc}',                   [MypeController::class,             'dataMypeRuc']);
Route::get('api-data-mype/{ruc}',               [MypeController::class,             'getDataFromExternalApi']);
Route::post('register-mype',                    [MypeController::class,             'registerMype']);
Route::post('sending-test-answers/{wsId}',      [WorkshopDetailsController::class,  'insertOrUpdateWorkshopDetails']);
Route::put('add-point/{workshopId}/{type}',     [WorkshopDetailsController::class,  'addPointToWorkshop']);
Route::get('invitations/{workshopId}',          [InvitationController::class,       'invitationContent']);



Route::post('register', [AuthController::class, 'register']);




Route::group(['prefix' => 'v1', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function(){
    

    Route::apiResource('exponents',                 ExponentController::class);
    Route::get('enableds-exponents',                ['uses' => 'ExponentController@allExponents']);
    Route::put('enabled-disabled/{exponentId}',     ['uses' => 'ExponentController@isEnabledDisabled']);

    Route::get('mype-registered',                   [ReportsController::class,  'TotalMype']);
    Route::get('mype-anual-progress',               [ReportsController::class,  'AnualProgress']);

    


    Route::apiResource('users', UserController::class);
    

    // MYPE
    Route::apiResource('mype', MypeController::class);
    
    Route::get('data-mype/{ruc}', ['uses' => 'MypeController@dataMypeRuc']);

    Route::get('api-data-mype/{ruc}', ['uses' => 'MypeController@getDataFromExternalApi']);

    //excel
    Route::post('import-excel', ['uses' => 'MypeController@uploadExcel']);
    Route::get('export-excel', ['uses' => 'MypeController@downloadExcel']);

    
    
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


    Route::post('accept-invitation/{workshopId}', ['uses' => 'WorkshopDetailsController@acceptInvitationWorkshopDetails']);             //mype acepta la invitacion



    



    Route::post('register', [AuthController::class, 'register']);
    


    Route::post('invoices/bulk', ['uses' => 'InvoiceController@bulkStore']);


    Route::post('logout', [AuthController::class, 'logout']);



    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('invoices', InvoiceController::class);
});