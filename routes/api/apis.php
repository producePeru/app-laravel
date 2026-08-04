<?php

use App\Http\Controllers\Event\PublicEventsController;
use Illuminate\Support\Facades\Route;

Route::controller(PublicEventsController::class)->group(function () {

    Route::post('consult-company-ruc/{ruc}', 'rucConsultCompany');

    Route::post('consult-businessman-dni/{dni}', 'dniConsultBusinessman');
});

// api
