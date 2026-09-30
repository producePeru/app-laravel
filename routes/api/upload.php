<?php

use App\Http\Controllers\Upload\UploadController;
use Illuminate\Support\Facades\Route;

Route::controller(UploadController::class)->group(function () {

    Route::post('sed-correccion', 'sedCorreccion');

});

// uplaod
