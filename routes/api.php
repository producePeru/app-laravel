<?php

use App\Http\Controllers\Advisory\AdvisoryController;
use App\Http\Controllers\Agreement\AgreementController;
use App\Http\Controllers\Agreement\CommitmentsController;
use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\User\TokenController;
// use App\Http\Controllers\Mype\MypeController;
use App\Http\Controllers\Automatic\CertificadoPDFController;
use App\Http\Controllers\Automatic\EmailSendController;
use App\Http\Controllers\Automatic\SendMailAyacuchoController;
use App\Http\Controllers\Dgtdif\SurveysController;
use App\Http\Controllers\Download\DownloadActionsPlanController;
use App\Http\Controllers\Download\DownloadAttendanceController;
use App\Http\Controllers\Download\DownloadDigitalRouterController;
use App\Http\Controllers\Download\DownloadFairParticipantsController;
use App\Http\Controllers\Download\DownloadFormalizationsController;
use App\Http\Controllers\Download\DownloadOthersController;
use App\Http\Controllers\Drive\DriveController;
use App\Http\Controllers\Event\EventsController;
use App\Http\Controllers\Formalization\ChartController;
use App\Http\Controllers\Formalization\Formalization10Controller;
use App\Http\Controllers\Formalization\Formalization20Controller;
use App\Http\Controllers\Formalization\FormalizationDigitalController;
use App\Http\Controllers\Formalization\HistorialController;
use App\Http\Controllers\Formalization\NotaryController;
use App\Http\Controllers\Formalization\PlanActionsController;
use App\Http\Controllers\Mype\MypeController;
use App\Http\Controllers\People\PersonController;
use App\Http\Controllers\Selects\CreateController;
use App\Http\Controllers\Selects\SelectController;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\SupervisorController;
use App\Http\Controllers\Fair\FairController;
use App\Http\Controllers\Formalization\ReportController;
use App\Http\Controllers\Google\GoogleCalendarController;
use App\Http\Controllers\Notary\QRNotaryController;
// use App\Http\Controllers\User\TokenController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\PDF\PDFConveniosGeneralController;
use App\Http\Controllers\Room\RoomController;
use App\Http\Controllers\RutaDigital\RutaDigitalController;
use App\Http\Controllers\Workshop\WorkshopController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

//testing
Route::post('create', [UserController::class, 'store']);

Route::group(['prefix' => 'public', 'namespace' => 'App\Http\Controllers'], function () {
    Route::get('dni/{num}', [AuthController::class, 'dniDataUser']);
    Route::post('formalization-digital', [FormalizationDigitalController::class, 'formalizationDigital']);
    Route::post('formalization-digital/exist-number', [FormalizationDigitalController::class, 'getStatusByDocumentNumber']);

    Route::get('location-cdes', [FormalizationDigitalController::class, 'gpsCdes']);
    Route::get('formalization/select-cde/{dni}/{id}', [FormalizationDigitalController::class, 'selectCde']);

    Route::get('notaries', [NotaryController::class, 'indexNotary']);
    Route::get('notaries-filters', [NotaryController::class, 'indexNotaryById']);

    Route::get('apk', [DownloadOthersController::class, 'descargarAPKar']);


    // FERIAS EMPRESARIALES
    Route::get('data/{slug}',           [FairController::class, 'show']);                       // TRAE LA FERIA POR SLUG
    Route::get('search-api-ruc/{ruc}',  [MypeController::class, 'apiRUC']);                     // BUSCA DATOS A PARTIR DEL RUC
    Route::post('first-or-new',         [MypeController::class, 'registerMype']);               // PASO 1 CREA O EDITA UNA MYPE
    Route::get('search-api-dni/{dni}',  [PersonController::class, 'apiDNI']);                   // BUSCA DATOS A PARTIR DEL DNI ***
    Route::post('create-up',            [PersonController::class, 'createUpdate']);             // PASO 2 EDITA O CREA UN USUARIO PERSON
    Route::post('mype/{ruc}',           [FairController::class, 'updateFieldsMypeFair']);       // PASO 3 actualiza los campos faltantes de la mype
    Route::post('postulate',            [FairController::class, 'postulateFair']);              // POSTULAR EN FERIA

    Route::post('survey',               [SurveysController::class, 'store']);              // ENCUESTAS 3° PISO
    Route::get('surveys',               [SurveysController::class, 'index']);              // ENCUESTAS 3° PISO

    Route::get('data-attendance/{slug}',           [AttendanceController::class, 'show']);                       // TRAE LA LAS ASISTENCIAS POR SLUG
    Route::post('attendance-present',        [AttendanceController::class, 'userPresent']);                       // TRAE LA LAS ASISTENCIAS POR SLUG


    // // EVENTOS SR CARLOS
    // Route::get('dots/{month}',                  [EventsController::class, 'getEventsDots']);
    // Route::get('events-day/{day}',              [EventsController::class, 'getEventsByDate']);

    Route::post('valorization-notary',  [QRNotaryController::class, 'store']);              // VALORIZACION DE NOTARIOS

});

// EVENTOS SR CARLOS   'middleware' => ['restrict.ip']
Route::group(['prefix' => 'pnte', 'namespace' => 'App\Http\Controllers'], function () {
    Route::get('dots',                      [EventsController::class, 'getEventsDots']);
    Route::get('events-day',                [EventsController::class, 'getEventsByDate']);
});

Route::group(['prefix' => 'user', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list',              [UserController::class, 'index']);
    Route::post('create',           [UserController::class, 'store']);
    Route::delete('delete/{id}',    [UserController::class, 'destroy']);
    Route::put('update/{id}',       [UserController::class, 'update']);

    Route::get('api/{type}/{num}',  [AuthController::class, 'dniDataUser']);
    Route::get('only-dni/{num}',    [AuthController::class, 'dniDataUser2']);

    Route::post('logout',           [AuthController::class, 'logout']);
    Route::post('password-reset',   [AuthController::class, 'passwordReset']);
    Route::put('views/{id}',        [UserController::class, 'asignViewsUser']);
    Route::get('views/{id}',        [UserController::class, 'showViewsUser']);

    Route::get('list-asesories',    [UserController::class, 'allAsesores']);
    Route::get('my-profile',        [UserController::class, 'showMyProfile']);

    // REGISTRAR UN ASESOR EXTERNO NOTARIO
    Route::post('register-user',    [UserController::class, 'registerUsers']);
    Route::post('register-profile', [UserController::class, 'registerProfiles']);
    Route::post('register-roles',   [UserController::class, 'registerRoles']);
    Route::post('register-views',   [UserController::class, 'registerViewsSeven']);

    Route::post('new-user-views',   [UserController::class, 'newUser']);     // REGISTRO PARA CADA USUARIO
    Route::post('update-password',  [AuthController::class, 'updatePassword']);     // RESETEO DE PASSWORD

});

// DRIVE - KARINA
Route::group(['prefix' => 'drive', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list-files', [DriveController::class, 'index']);
    Route::get('download/{any}', [DriveController::class, 'downloadFile'])->where('any', '.*');
    Route::post('up-files', [DriveController::class, 'store']);
    Route::delete('delete-file/{id}', [DriveController::class, 'deleteFile']);

    Route::get('files', [DriveController::class, 'allFiles']);
    Route::post('create-file', [DriveController::class, 'createFile']);
    Route::put('update-file/{id}', [DriveController::class, 'updateFile']);
    Route::get('data-files/{id}', [DriveController::class, 'dataByIdFile']);

    Route::put('visible-all/{id}', [DriveController::class, 'visibleByAll']);

    Route::get('users-selected/{idDrive}', [DriveController::class, 'usersSelectedDrive']);
    Route::get('users', [DriveController::class, 'usersOnlyDrivers']); //lista de usuarios
    Route::put('visible-users', [DriveController::class, 'storeOrUpdateDriveUsers']);
});

Route::group(['prefix' => 'person', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list',                  [PersonController::class, 'index']);
    Route::get('found/{type}/{dni}',    [PersonController::class, 'dniFoundUser']);
    Route::post('create',               [PersonController::class, 'store']);
    Route::delete('delete/{id}',        [PersonController::class, 'destroy']);
    Route::put('update/{id}',           [PersonController::class, 'update']);
    Route::get('data/{dni}',            [PersonController::class, 'findUserById']);            //busca people x id
});

Route::group(['prefix' => 'advisory', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list', [AdvisoryController::class, 'index']);
    Route::post('create', [AdvisoryController::class, 'store']);
    Route::delete('delete/{id}', [AdvisoryController::class, 'destroy']);
    Route::get('find/{id}', [AdvisoryController::class, 'getDataAdvisoryById']);
    Route::put('update/{id}', [AdvisoryController::class, 'update']);

    Route::put('updates-1020/{id}', [HistorialController::class, 'updateAdvisoryToFormalizations']);           // se actualizan los de ruc 10 y ruc 20 a las asesorias

});

// FORMALIZACIÓN*
Route::group(['prefix' => 'formalization', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list-ruc-10', [Formalization10Controller::class, 'indexRuc10']);
    Route::get('list-ruc-20', [Formalization20Controller::class, 'indexRuc20']);
    Route::get('list-ruc-20/{idPerson}', [Formalization20Controller::class, 'allFormalizationsRuc20ByPersonId']);
    Route::post('create-ruc-10', [Formalization10Controller::class, 'storeRuc10']);

    Route::post('ruc20-step1', [Formalization20Controller::class, 'ruc20Step1']);
    Route::post('ruc20-step2/{codesunarp}', [Formalization20Controller::class, 'ruc20Step2']);
    Route::post('ruc20-step3/{codesunarp}', [Formalization20Controller::class, 'ruc20Step3']);
    Route::post('create-ruc20', [Formalization20Controller::class, 'ruc20All']);

    Route::delete('delete-ruc-10/{id}', [Formalization10Controller::class, 'destroy']);         //ACTUALIZACIONES 10
    Route::get('find-ruc-10/{id}', [Formalization10Controller::class, 'getDataF10ById']);
    Route::put('update-ruc-10/{id}', [Formalization10Controller::class, 'update']);

    Route::get('find-ruc-20/{id}', [Formalization20Controller::class, 'getDataF20ById']);
    Route::put('update-ruc-20/{id}', [Formalization20Controller::class, 'update']);
    Route::delete('delete-ruc-20/{id}', [Formalization20Controller::class, 'destroy']);

    // Formalizacion Digital
    Route::get('digital-list', [FormalizationDigitalController::class, 'index']);
    Route::delete('delete/{id}', [FormalizationDigitalController::class, 'deleteRegister']);
    Route::put('digital/update-attended', [FormalizationDigitalController::class, 'updateAttendedStatus']);

    // Chart
    Route::get('chart', [ChartController::class, 'index']);
    // Route::get('by-advisors', [ChartController::class, 'countAdvisoriesByAdvisors']);

    // NUEVA FUNCION DE SETEO para MYPEs
    Route::get('plan-actions', [PlanActionsController::class, 'planActions']);

    Route::put('set-ruc-advisories', [PlanActionsController::class, 'rucAdvisoriesSet']);
    Route::put('set-ruc-ruc-10', [PlanActionsController::class, 'rucFormalizationR10Set']);
    Route::put('set-ruc-ruc-20', [PlanActionsController::class, 'rucFormalizationR20Set']);
});

Route::group(['prefix' => 'historial', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('advisories', [HistorialController::class, 'historialAdvisories']);
    Route::get('formalizations-10', [HistorialController::class, 'historialFormalizations10']);
    Route::get('formalizations-20', [HistorialController::class, 'historialFormalizations20']);

    //filters
    Route::get('advisories/filters',                [HistorialController::class, 'filterHistorialAdvisoriesByDates']);                  //1
    Route::get('formalizations-10/filters',         [HistorialController::class, 'filterHistorialFormalizations10ByDates']);
    Route::get('formalizations-20/filters',         [HistorialController::class, 'filterHistorialFormalizations20ByDates']);

    //registros-historial
    Route::get('registers/{idPeople}', [HistorialController::class, 'getByPeopleIdRegisters']);



    // datatables
    Route::get('advisories-filters',                [HistorialController::class, 'indexDataTableAdvisories']);
});

Route::group(['prefix' => 'download', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::post('asesories',                    [DownloadFormalizationsController::class, 'exportAsesories']);
    Route::post('formalizations-ruc10',         [DownloadFormalizationsController::class, 'exportFormalizationsRuc10']);
    Route::post('formalizations-ruc20',         [DownloadFormalizationsController::class, 'exportFormalizationsRuc20']);
    Route::post('actions-plans',                [DownloadActionsPlanController::class, 'exportActionPlans']);
    Route::get('fair-participants/{slug}',      [DownloadFairParticipantsController::class, 'exportFairParticipants']);
    Route::post('digital-routes',               [DownloadDigitalRouterController::class, 'exportDigitalRouter']);
    Route::post('attendance-ugo',               [DownloadAttendanceController::class, 'exportDigitalRouter']);
    Route::get('attendance/{slug}',             [DownloadAttendanceController::class, 'exportAttendance']);
    Route::post('votations-notaries',           [DownloadAttendanceController::class, 'exportDigitalRouter']);
});

Route::group(['prefix' => 'token', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list', [TokenController::class, 'index']);
    Route::post('create', [TokenController::class, 'store']);
    Route::put('update-status/{id}', [TokenController::class, 'updateStatus']);
});

Route::group(['prefix' => 'notary', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list',                      [NotaryController::class, 'indexNotary']);
    Route::get('list/{id}',                 [NotaryController::class, 'indexNotaryById']);
    Route::post('create',                   [NotaryController::class, 'storeNotary']);
    Route::delete('delete/{id}',            [NotaryController::class, 'deleteNotary']);
    Route::patch('update/{id}',             [NotaryController::class, 'updateNotary']);
});

Route::group(['prefix' => 'notary', 'namespace' => 'App\Http\Controllers'], function () {});

// Route::group(['prefix' => 'mype', 'namespace' => 'App\Http\Controllers'], function() {
//     Route::post('mype', [MypeController::class, 'store']);

// });

Route::group(['prefix' => 'create', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::post('comercial-activities',         [CreateController::class, 'postComercialActivities']);
    Route::post('office',                       [CreateController::class, 'createOffice']);
    Route::post('economic-sector',              [CreateController::class, 'createEconomicSector']);
    Route::post('component',                    [CreateController::class, 'createNewComponent']);
    Route::post('theme',                        [CreateController::class, 'createNewTheme']);
    // Route::post('economic-sector', [CreateController::class, 'createNewEconomicSector']);
    Route::post('cde-notary',                   [CreateController::class, 'createCdeNotary']);            //crea automaticamente la cde del asesor externo notario
    Route::post('cde',                          [CreateController::class, 'createCde']);
});

Route::group(['prefix' => 'supervisores', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list', [SupervisorController::class, 'index']);
});

// CONVENIOS
Route::group(['prefix' => 'agreement', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list/{entity}',                 [AgreementController::class, 'index']);
    Route::get('list/{id}',                     [AgreementController::class, 'allActionsById']);
    Route::get('list-files/{id}',               [AgreementController::class, 'listAllFilesById']);
    Route::delete('delete-acction/{id}',        [AgreementController::class, 'deleteActionById']);
    Route::delete('delete/{id}',                [AgreementController::class, 'deleteAgreement']);
    Route::delete('delete/file/{id}',           [AgreementController::class, 'deleteFileById']);
    Route::post('create',                       [AgreementController::class, 'store']);
    Route::post('create-ugse',                  [AgreementController::class, 'storeUgse']);
    Route::post('create-acction',               [AgreementController::class, 'storeAction']);
    Route::post('file',                         [AgreementController::class, 'upFileAgreement']);
    Route::post('file-download/{id}',           [AgreementController::class, 'download']);
    Route::get('download',                      [AgreementController::class, 'exportAgreement']);
    Route::put('update/{id}',                   [AgreementController::class, 'updateActionById']);
    Route::put('update-values/{id}',            [AgreementController::class, 'updateValuesAgreement']);

    // compromisos
    Route::post('commitments',                  [AgreementController::class, 'createCompromission']);
    Route::get('commitments/{id}',              [AgreementController::class, 'listCompromission']);
    // Route::get('commitment/download/{any}',     [AgreementController::class, 'downloadCompromission']);

    Route::get('commitment-download/{any}',     [AgreementController::class, 'downloadCompromission'])->where('any', '.*');
    Route::get('general/{id}',                  [AgreementController::class, 'resumenGeneral']);
    Route::delete('commitment-delete/{id}',     [AgreementController::class, 'deleteCommitment']);

    // COMPROMISOS HANNA
    Route::post('create-commitment',            [AgreementController::class, 'createConvenioMetas']);
    Route::get('all-commitments/{id}',          [AgreementController::class, 'allCommitments']);
    Route::put('update-commitment/{id}',        [AgreementController::class, 'updateCommitment']);

    // charts
    Route::get('chart/{name}',                         [AgreementController::class, 'chatAgreement']);
});
// Route::group(['prefix' => 'agreement', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
//     Route::get('list', [AgreementController::class, 'index']);
//     Route::get('list/{id}', [AgreementController::class, 'allActionsById']);
//     Route::get('list-files/{id}', [AgreementController::class, 'listAllFilesById']);

// COMPROMISOS
Route::group(['prefix' => 'commitments', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list/{id}/{type}',              [CommitmentsController::class, 'index']);
    Route::post('create',                       [CommitmentsController::class, 'store']);
    Route::put('fulfilled/{id}',                [CommitmentsController::class, 'updateFulfilled']);
});


Route::group(['prefix' => 'select', 'namespace' => 'App\Http\Controllers'], function () {
    Route::get('countries', [SelectController::class, 'getCountries']);
    Route::get('cities', [SelectController::class, 'getCities']);
    Route::get('provinces/{idCity}', [SelectController::class, 'getProvinces']);
    Route::get('districts/{idProv}', [SelectController::class, 'getDistricts']);
    Route::get('offices', [SelectController::class, 'getOffices']);
    Route::get('cdes', [SelectController::class, 'getCdes']);
    Route::get('genders', [SelectController::class, 'getGenders']);
    Route::get('modalities', [SelectController::class, 'getModalities']);
    Route::get('type-documents', [SelectController::class, 'getTypeDocuments']);
    Route::get('components', [SelectController::class, 'getComponents']);
    Route::get('component-theme/{id}', [SelectController::class, 'getComponentTheme']);
    Route::get('roles', [SelectController::class, 'getRoles']);
    Route::get('detail-procedures', [SelectController::class, 'getProcedures']);
    Route::get('economic-sectors', [SelectController::class, 'getEconomicSectors']);
    Route::get('comercial-activities', [SelectController::class, 'getComercialActivities']);
    Route::get('regimes', [SelectController::class, 'getRegimes']);
    Route::get('notaries', [SelectController::class, 'getNotaries']);
    Route::get('supervisores', [SelectController::class, 'getSupervisores']);
    Route::get('folders', [SelectController::class, 'getFolders']);
    Route::get('asesores', [SelectController::class, 'getAsesores']);
    Route::get('operational-status', [SelectController::class, 'getOperationalStatus']);
    Route::get('agreement-status', [SelectController::class, 'getAgreementStatus']);
    Route::get('type-capital', [SelectController::class, 'getTypeCapital']);
    Route::get('pnte-offices', [SelectController::class, 'getOfficesPnte']);

    Route::get('asesores-report', [SelectController::class, 'getAsesoresReporte']);
});

// Route::group(['prefix' => 'automatic', 'namespace' => 'App\Http\Controllers'], function() {
//     Route::put('mype-info', [MypeController::class, 'setInfoMype']);
// });

Route::group(['prefix' => 'mype', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list', [MypeController::class, 'index']);
    Route::put('update/{ruc}', [MypeController::class, 'getApiInfo']);
    Route::get('get-by-ruc/{ruc}', [MypeController::class, 'getDataByRuc']);
    Route::put('update-by-ruc/{id}', [MypeController::class, 'updateDataByRuc']);
    Route::post('create', [MypeController::class, 'store']);

    Route::get('search-api-ruc/{ruc}',  [MypeController::class, 'apiRUC']);
});

Route::group(['prefix' => 'plans-action', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list',                      [PlanActionsController::class, 'index']);
    Route::get('list-all',                  [PlanActionsController::class, 'allWithoutPagination']);
    Route::get('components/{ruc}',          [PlanActionsController::class, 'listAllServicesAF']);
    Route::post('create',                   [PlanActionsController::class, 'store']);
    Route::put('edit-component',            [PlanActionsController::class, 'editComponent']);
    Route::put('edit-yes-no',               [PlanActionsController::class, 'updateField']);
    Route::post('update',                   [PlanActionsController::class, 'update']);
    Route::delete('delete/{id}',            [PlanActionsController::class, 'delete']);
    Route::put('status/{id}/{status}',      [PlanActionsController::class, 'changeStatus']);
    Route::put('details',                   [PlanActionsController::class, 'sendMessageDetails']);
});

Route::group(['prefix' => 'event', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::post('create-category', [EventsController::class, 'createCategory']);
    Route::get('list-categories', [EventsController::class, 'listCategories']);
    Route::put('status-categories/{id}', [EventsController::class, 'statusCategories']);

    // Route::post('create-event',             [EventsController::class, 'createEvent']);
    // Route::get('list',                    [EventsController::class, 'listAllEvents']);

    Route::post('create-event', [EventsController::class, 'createEvent']);
    // Route::get('list', [EventsController::class, 'index']);

    // eventos sr carlos
    Route::post('create',                       [EventsController::class, 'store']);
    Route::get('list',                          [EventsController::class, 'index']);
    Route::delete('delete/{id}',                [EventsController::class, 'deleteEventById']);
    Route::put('update/{id}',                   [EventsController::class, 'update']);
    // Route::put('observations/{id}',             [EventsController::class, 'observations']);

    // eventos sra dianita
    Route::get('rooms',                         [EventsController::class, 'listRooms']);
    Route::post('reserve-room',                 [EventsController::class, 'storeRoom']);
    Route::post('to-attendance/{id}',           [AttendanceController::class, 'createEventoToAttendance']);
    Route::post('delete/{id}',                  [EventsController::class, 'destroy']);
    Route::put('update-obs/{id}',               [EventsController::class, 'updateObservation']);
});

Route::group(['prefix' => 'automatic', 'namespace' => 'App\Http\Controllers'], function () {
    Route::post('send-certificates',    [CertificadoPDFController::class, 'sendEmailWithCertificates']);

    Route::post('/ayacucho',            [SendMailAyacuchoController::class, 'sendEmailsAyacucho']);
    Route::post('/invitations',         [SendMailAyacuchoController::class, 'sendEmailsAyacuchoArray']);
    Route::post('/send-emails',         [EmailSendController::class, 'sendEmails']);            // nuevo desde home-25
});

Route::group(['prefix' => 'pdf', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('agreements-general/{id}', [PDFConveniosGeneralController::class, 'pdfConvenio']);
});

Route::group(['prefix' => 'fair', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list',                          [FairController::class, 'index']);
    Route::post('create',                       [FairController::class, 'create']);
    Route::put('update/{id}',                   [FairController::class, 'update']);
    Route::get('applicants/{slug}',             [FairController::class, 'fairApplicants']);     // LISTA LOS PARTICIPANTES EN LA FERIA
    Route::put('status-participant/{id}',       [FairController::class, 'toggleStatus']);       // TOGGLE PARTICIPARA O NO
    Route::delete('delete-participant/{id}',    [FairController::class, 'destroyParticipant']);       // delete PARTICIPAnte
});

Route::group(['prefix' => 'attendance', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list',                          [AttendanceController::class, 'index']);
    Route::get('list-all',                      [AttendanceController::class, 'allWithoutPagination']);
    Route::post('create',                       [AttendanceController::class, 'create']);
    Route::put('update/{id}',                   [AttendanceController::class, 'update']);
    Route::delete('delete/{id}',                [AttendanceController::class, 'delete']);
    Route::get('applicants/{slug}',             [AttendanceController::class, 'attendaceApplicants']);     // LISTA LOS PARTICIPANTES EN LA FERIA
    Route::put('status-participant/{id}',       [AttendanceController::class, 'toggleStatus']);       // TOGGLE PARTICIPARA O NO
    Route::delete('delete-participant/{id}',    [AttendanceController::class, 'destroyParticipant']);       // delete PARTICIPAnte


    Route::get('list-vote',                     [QRNotaryController::class, 'index']);          // votación notarias...
    Route::get('list-vote-all',                 [QRNotaryController::class, 'allWithoutPagination']);

    Route::post('migrate-events',               [AttendanceController::class, 'migrateEvents']);        // migra los eventos de UGO al calendario sr Carlos
    Route::put('event-finally/{id}',            [AttendanceController::class, 'eventFinally']);        // migra los eventos de UGO al calendario sr Carlos

});


Route::group(['prefix' => 'room', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list',                          [RoomController::class, 'index']);
    Route::post('store',                        [RoomController::class, 'store']);
    Route::delete('delete/{id}',                        [RoomController::class, 'destroy']);
});

Route::group(['prefix' => 'ruta-digital', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('is-new/{type}/{number}',        [PersonController::class, 'isNewRecord']);
    Route::post('businessman',                  [RutaDigitalController::class, 'businessman']);         // si existe lo creas si no lo editas
    Route::post('mype',                         [RutaDigitalController::class, 'mype']);                // si existe lo creas si no lo editas
    Route::post('create',                       [RutaDigitalController::class, 'store']);
    Route::get('list',                          [RutaDigitalController::class, 'index']);
    Route::get('list-all',                      [RutaDigitalController::class, 'allWithoutPagination']);
    Route::put('status/{id}',                   [RutaDigitalController::class, 'status']);
});


Route::group(['prefix' => 'google', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::post('create-event',                     [GoogleCalendarController::class, 'createEvent']);
    Route::get('events-pnte/{type}',                       [GoogleCalendarController::class, 'listEvents']);
    Route::delete('delete-event-pnte/{id}/{type}',         [GoogleCalendarController::class, 'deleteEvent']);
});

Route::group(['prefix' => 'restrict', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::post('restrict-ips',                 [GoogleCalendarController::class, 'store']);
    Route::delete('restrict-ips/{id}',          [GoogleCalendarController::class, 'destroy']);
});

Route::group(['prefix' => 'v1', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {});




// REFORMA
Route::group(['prefix' => 'report', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::get('advisories',                 [ReportController::class, 'advisiories']);
});

Route::group(['prefix' => 'route-digital', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
    Route::post('store',                    [WorkshopController::class, 'store']);
    Route::get('list',                      [WorkshopController::class, 'index']);
});
