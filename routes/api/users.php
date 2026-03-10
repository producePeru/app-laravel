<?php

use App\Http\Controllers\User\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\UserController;

Route::controller(UserController::class)->group(function () {

    Route::get('pnte', 'usersPnte');

    Route::get('ugo', 'usersUgo');

    Route::post('register-user-pnte', 'registerNewUser');

    Route::put('update-user-pnte/{idUser}', 'updateUserPnte');

    Route::delete('delete-user-pnte/{idUser}', 'deleteUserPnte');

    Route::put('update-cde', 'updateCde');

    Route::put('updated-personal-info/{id}/{dni}', 'updatedPersonalInfo');

    Route::get('get-personal-info', 'getPersonalInfo');
});

Route::controller(AuthController::class)->group(function () {

    Route::post('password-reset', 'passwordReset');
});



// users
