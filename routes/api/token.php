<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\TokenController;

Route::controller(TokenController::class)->group(function () {
    Route::get('list', 'index');
    Route::post('create', 'store');
    Route::put('update-status/{id}', 'updateStatus');
});
