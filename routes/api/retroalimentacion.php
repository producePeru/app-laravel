<?php

use App\Http\Controllers\RetroAlimentacion\RetroAlimentacionController;
use Illuminate\Support\Facades\Route;

// Prefijo: retroalimentacion (ver routes/api.php) + middleware auth:sanctum
// El usuario creador / calificador se toma de auth()->id()

Route::controller(RetroAlimentacionController::class)->group(function () {
    // Temas de capacitación
    Route::get('temas', 'index');                          // listar + promedios
    Route::post('temas', 'store');                         // crear (user logueado = creador)
    Route::get('temas/{id}', 'show');                      // detalle + resumen + calificaciones
    Route::put('temas/{id}', 'update');                    // editar (solo creador/admin)
    Route::delete('temas/{id}', 'destroy');                // eliminar
    Route::put('temas/{id}/estado', 'toggleEstado');       // abrir/cerrar calificaciones
    Route::put('temas/{id}/compromiso', 'guardarCompromiso'); // compromiso de mejora del creador

    // Calificaciones de colegas
    Route::post('temas/{id}/calificar', 'calificar');      // crear o actualizar mi calificación
    Route::delete('calificaciones/{id}', 'destroyCalificacion');
});

// retroalimentacion
