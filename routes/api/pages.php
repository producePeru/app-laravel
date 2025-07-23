<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Page\PageController;

Route::controller(PageController::class)->group(function () {
    Route::get('views-to-user/{id}', 'pageToUser');


    Route::get('views-to-user-sidebar', 'viewsToUserSidebar');
});
