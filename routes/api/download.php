<?php

use App\Http\Controllers\Download\ActividadesUgoController;
use App\Http\Controllers\Download\CyberWowCarpetaLiderController;
use App\Http\Controllers\Download\CyberWowParticipantesController;
use App\Http\Controllers\Download\DownloadAttendanceController;
use App\Http\Controllers\Download\DownloadComprasPeruController;
use App\Http\Controllers\Download\DownloadCyberWowMarcaController;
use App\Http\Controllers\Download\DownloadExportDiagnosticMP;
use App\Http\Controllers\Download\DownloadFormalizationsController;
use App\Http\Controllers\Download\DownloadLiderAsesorController;
use App\Http\Controllers\Download\DownloadMPParticipantesController;
use App\Http\Controllers\Download\DownloadTemplateUgoActividadesController;
use App\Http\Controllers\Download\QuestionnarieController;
use App\Http\Controllers\Download\SedAsistentesController;
use Illuminate\Support\Facades\Route;

Route::controller(DownloadFormalizationsController::class)->group(function () {

    Route::post('asesories', 'exportAsesories');

    Route::post('formalizations-ruc10', 'exportFormalizationsRuc10');

    Route::post('formalizations-ruc20', 'exportFormalizationsRuc20');
});

Route::controller(SedAsistentesController::class)->group(function () {

    Route::post('sed-asistentes/{slug}', 'exportList');
});

Route::controller(CyberWowParticipantesController::class)->group(function () {

    Route::post('cyber-wow-participants/{slug}', 'exportList');
});


Route::controller(QuestionnarieController::class)->group(function () {

    Route::post('questions-answers-advisors-formalizations', 'questionsAnswersAdvisorsFormalizations');
});


Route::controller(DownloadAttendanceController::class)->group(function () {

    Route::get('attendance-mercado/{slug}', 'exportFortaleceTuMercado');

    Route::get('attendance/{slug}', 'exportRegistrantsUgoEvents');
});


Route::controller(ActividadesUgoController::class)->group(function () {

    Route::post('all-activities-the-year', 'allActivitiesTheYear');
});


Route::controller(CyberWowCarpetaLiderController::class)->group(function () {

    Route::get('cyber-wow-folder-leader', 'downloadUserFolders');

    Route::get('cyber-wow-folder-all-brands/{slug}', 'cyberWowFolderAllBrands');
});

Route::controller(DownloadTemplateUgoActividadesController::class)->group(function () {

    Route::get('download-ugo-activities', 'downloadUgoActividades');
});


Route::controller(DownloadLiderAsesorController::class)->group(function () {

    Route::post('resumen-by-users/{slug}', 'resumenPorUsuarios');
});

Route::controller(DownloadCyberWowMarcaController::class)->group(function () {

    Route::post('export-cyber-wow-marca/{slug}', 'exportCyberWowMarca');

    Route::post('export-cyber-wow-products/{slug}', 'exportCyberWowOfertas');

    Route::post('export-cyber-wow-brands-all/{slug}', 'exportCyberWowBrandsAll');
});

Route::controller(DownloadMPParticipantesController::class)->group(function () {

    Route::post('mp-attendance-export/{slug}', 'mpAttendanceExport');
});

Route::controller(DownloadExportDiagnosticMP::class)->group(function () {

    Route::post('mp-export-participants-excel', 'exportParticipantsExcel');
});

Route::controller(DownloadComprasPeruController::class)->group(function () {

    Route::post('cp-registros/export', 'exportExcel');
});

// download
