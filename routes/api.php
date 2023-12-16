<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;




Route::post('login', [AuthController::class, 'login']);

// Route::post('register', [AuthController::class, 'register']);




Route::group(['prefix' => 'v1', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function(){
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('invoices', InvoiceController::class);


    //exponentes
    Route::apiResource('exponents', ExponentController::class);
    Route::get('all-exponents', ['uses' => 'ExponentController@allExponents']);

    Route::apiResource('users', UserController::class);
    

    // MYPE
    Route::apiResource('mype', MypeController::class);
    Route::get('data-mype/{ruc}', ['uses' => 'MypeController@dataMypeRuc']);
    Route::get('api-data-mype/{ruc}', ['uses' => 'MypeController@getDataFromExternalApi']);

    //excel
    Route::post('import-excel', ['uses' => 'MypeController@uploadExcel']);
    Route::get('export-excel', ['uses' => 'MypeController@downloadExcel']);

    
    
    Route::apiResource('workshops', WorkshopController::class);
    Route::get('get-workshop-slug/{workshopSlug}', ['uses' => 'WorkshopController@getBySlug']);

    // Route::apiResource('invitations', InvitationController::class);


    Route::apiResource('testin', TestinController::class);
    Route::post('create-test-in/{workshopId}', ['uses' => 'TestinController@createTestin']);
    
    Route::apiResource('testout', TestoutController::class);
    Route::post('create-test-out/{workshopId}', ['uses' => 'TestoutController@createTestout']);

    Route::apiResource('invitations', InvitationController::class);
    Route::post('create-invitation/{workshopId}', ['uses' => 'InvitationController@createInvitation']);


    // Route::apiResource('invitations', InvitationController::class);
    Route::get('workshop/details/{workshopId}', ['uses' => 'WorkshopDetailsController@workshopDetails']);
    Route::post('sending-test-answers/{workshopId}', ['uses' => 'WorkshopDetailsController@insertOrUpdateWorkshopDetails']);
    Route::put('workshop/details/average', ['uses' => 'WorkshopDetailsController@averageWorkshopDetails']);
    Route::get('workshop/bydate', ['uses' => 'WorkshopDetailsController@getWorkshopsGroupedByDate']);
    Route::get('test-all-questions/{workshopId}', ['uses' => 'WorkshopDetailsController@testAllQuestions']);
    Route::put('addPoint/{workshopId}/{type}', ['uses' => 'WorkshopDetailsController@addPointToWorkshop']);


    Route::post('accept-invitation/{workshopId}', ['uses' => 'WorkshopDetailsController@acceptInvitationWorkshopDetails']);             //mype acepta la invitacion



    Route::get('countries', ['uses' => 'StaticController@getDataCountries']);
    Route::get('departaments', ['uses' => 'StaticController@getDataDepartaments']);
    Route::get('province/{idDepartament}', ['uses' => 'StaticController@getDataProvinces']);
    Route::get('district/{idProvince}', ['uses' => 'StaticController@getDataDistricts']);



    Route::post('register', [AuthController::class, 'register']);
    


    Route::post('invoices/bulk', ['uses' => 'InvoiceController@bulkStore']);


    Route::post('logout', [AuthController::class, 'logout']);
});