<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Page\PageController;

Route::controller(PageController::class)->group(function () {
    Route::post('new-page', 'store');
    Route::get('permissions', 'index');
});
