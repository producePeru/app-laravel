<?php

use App\Http\Controllers\ComprasMiPeru\ComprasPeruController;
use Illuminate\Support\Facades\Route;


Route::controller(ComprasPeruController::class)->group(function () {

    Route::post('store', 'store');

    Route::put('update-data-mype', 'updateDataMype');
});




// purchases-my-peru
