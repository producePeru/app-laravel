<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Image\ImageController;

Route::controller(ImageController::class)->group(function () {

  Route::post('upload-image', 'upload');

  Route::put('origin-image/{id}', 'setOriginImage');

  Route::get('download/{folder}/{idImage}', 'download');

  Route::post('upload-logo-cyber-wow', 'uploadLogoCyberWow');
});


// image