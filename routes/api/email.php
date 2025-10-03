<?php

use App\Http\Controllers\Email\EmailTemplateController;
use Illuminate\Support\Facades\Route;


Route::controller(EmailTemplateController::class)->group(function () {

    Route::post('create-email-templates', 'store');

    Route::get('list-email-templates', 'index');

    Route::get('show-list-email-templates', 'showSelect');
});

// api
