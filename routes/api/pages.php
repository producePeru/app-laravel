<?php

use App\Http\Controllers\Audit\AuditController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Page\PageController;

Route::controller(PageController::class)->group(function () {

    Route::get('list-all', 'allPages');

    Route::get('list-ugo', 'allPagesTypeUgo');

    Route::get('views-to-user/{id}', 'pageToUser');

    Route::get('views-to-user-sidebar', 'viewsToUserSidebar');

    Route::get('permissions', 'permissions');

    Route::put('user-assign-view', 'userAssignView');
});


Route::controller(AuditController::class)->group(function () {

    Route::get('audit-logs', 'index');
});

// page