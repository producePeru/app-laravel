<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Event\PublicEventsController;

Route::controller(PublicEventsController::class)->group(function () {

    Route::post('consult-company-ruc/{ruc}', 'rucConsultCompany');

    Route::post('consult-businessman-dni/{dni}', 'dniConsultBusinessman');
});

// api
