<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;




Route::post('login', [AuthController::class, 'login']);



Route::group(['prefix' => 'v1', 'namespace' => 'App\Http\Controllers'], function(){
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('invoices', InvoiceController::class);


    //prod
    Route::apiResource('exponents', ExponentController::class);
    Route::get('all-exponents', ['uses' => 'ExponentController@allExponents']);

    Route::apiResource('users', UserController::class);
    Route::apiResource('mype', MypeController::class);
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
    Route::post('sending-test-answers/{workshopId}', ['uses' => 'WorkshopDetailsController@insertOrUpdateWorkshopDetails']);
    Route::put('workshop/details/average', ['uses' => 'WorkshopDetailsController@averageWorkshopDetails']);
    Route::get('workshop/bydate', ['uses' => 'WorkshopDetailsController@getWorkshopsGroupedByDate']);

    Route::post('accept-invitation/{workshopId}', ['uses' => 'WorkshopDetailsController@acceptInvitationWorkshopDetails']);







    
    Route::post('register', [AuthController::class, 'register']);



    Route::post('invoices/bulk', ['uses' => 'InvoiceController@bulkStore']);


    Route::post('logout', [AuthController::class, 'logout']);
});