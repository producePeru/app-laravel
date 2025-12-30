<?php

use App\Http\Controllers\Api\ReasonController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\TokenController;

Route::controller(TokenController::class)->group(function () {

    Route::get('list', 'index');

    Route::post('create', 'store');

    Route::put('update-status/{id}', 'updateStatus');
});


Route::controller(ReasonController::class)->group(function () {

    Route::get('shows-all-reasons', 'index');

    Route::post('indicate-reason-action', 'indicateReasonAction');

    Route::get('how-many-alerts', 'howManyAlerts');
});


// Route::controller(ReasonController::class)->group(function () {

//     Route::get('how-many-alerts', 'howManyAlerts');
// });

// token
