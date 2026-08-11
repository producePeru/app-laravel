<?php

use App\Http\Controllers\Pnte\TiendaController;
use Illuminate\Support\Facades\Route;

Route::controller(TiendaController::class)->group(function () {

    Route::GET('list-tiendas', 'index');

    Route::POST('new-tienda', 'store');

    Route::PUT('update-tienda/{id}', 'update');

    Route::DELETE('delete-tienda/{id}', 'destroy');

});

// pnte

Route::controller(TiendaController::class)->group(function () {

    Route::GET('tiendas', 'publicIndex');

    Route::GET('show-tienda/{id}', 'show');
});

// pnte-public
