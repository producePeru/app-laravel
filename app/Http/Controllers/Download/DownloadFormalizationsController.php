<?php

namespace App\Http\Controllers\Download;

use App\Exports\AsesoriasAllExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Formalization20;
use App\Models\Formalization10;
use App\Models\Advisory;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Exports\AsesoriasExport;
use App\Exports\FormalizationRUC10Export;
use App\Exports\FormalizationRUC20Export;
use App\Notifications\ReportReadyNotification;
use Illuminate\Support\Facades\Notification;
use App\Jobs\ExportAdvisoriesJob;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;


use Carbon\Carbon;

Carbon::setLocale('es');


class DownloadFormalizationsController extends Controller
{

    public function exportAsesories(\Illuminate\Http\Request $request)
    {
        try {
            $permission = getPermission('asesorias-formalizaciones-reportes');

            // if (!$permission['hasPermission']) {
            //     return response()->json([
            //         'message' => 'No tienes permiso para acceder a esta sección',
            //         'status' => 403
            //     ]);
            // }

            $filters = [
                'asesor'    => $request->input('asesor'),
                'name'      => $request->input('name'),
                'dateStart' => $request->input('dateStart'),
                'dateEnd'   => $request->input('dateEnd'),
                'year'      => $request->input('year'),
                'typeCdes'  => $request->input('typeCdes'),
            ];

            $user = Auth::user();

            $query = Advisory::query();

            if ($user->rol == 1) {

                $query->withAdvisoryRangeDate($filters);
            } else if ($user->rol == 2) {

                $query->withAdvisoryRangeDate($filters)->where('user_id', $user->id);
            } else {

                return response()->json([
                    'message' => 'No tienes permiso para acceder a esta sección',
                    'status' => 403
                ]);
            }

            ini_set('memory_limit', '2G');

            set_time_limit(300);

            $advisories = [];

            $globalIndex = 1;

            $query->chunk(1000, function ($rows) use (&$advisories, &$globalIndex, $user) {
                foreach ($rows as $advisory) {
                    $advisories[] = [
                        'index' => ($user->rol == 1) ? $advisory->id : $globalIndex++,
                        'date'                  => $advisory->created_at->format('d/m/Y'),
                        'asesor'                => strtoupper($advisory->user->name . ' ' . $advisory->user->lastname . ' ' . $advisory->user->middlename),

                        'asesor_cde_city'       => $advisory->sede->city ? $advisory->sede->city : $advisory->sede->region->name,
                        'asesor_cde_province'   => $advisory->sede->province ? $advisory->sede->province : $advisory->sede->provincia->name,
                        'asesor_cde_district'   => $advisory->sede->district ? $advisory->sede->district : $advisory->sede->distrito->name,
                        'asesor_cde'            => isset($advisory->sede->name) ? strtoupper($advisory->sede->name) : null,

                        'emp_document_type'     => $advisory->people->typedocument->avr ?? null,
                        'emp_document_number'   => $advisory->people->documentnumber ?? null,
                        'emp_country'           => isset($advisory->people->pais->name) ? strtoupper($advisory->people->pais->name) : 'PERU',
                        'emp_birth'             => $advisory->people->birthday ? \Carbon\Carbon::parse($advisory->people->birthday)->format('d/m/Y') : null,
                        'emp_lastname'          => strtoupper($advisory->people->lastname),
                        'emp_middlename'        => strtoupper($advisory->people->middlename),
                        'emp_name'              => strtoupper($advisory->people->name),
                        'emp_gender'            => $advisory->people->gender->name == 'FEMENINO' ? 'F' : 'M',

                        'emp_discapabilities' => match (trim(strtolower($advisory->people->sick ?? ''))) {
                            'yes' => 'SI',
                            'no'  => 'NO',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        'emp_soons' => match (trim(strtolower($advisory->people->hasSoon ?? ''))) {
                            'si' => 'SI',
                            'no' => 'NO',
                            'na', '' => 'PREFIERO NO ESPECIFICAR',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        'emp_phone'             => $advisory->people->phone,
                        'emp_email'             => isset($advisory->people->email) ? strtolower($advisory->people->email) : '-',
                        'supervisor'            => 'MILIAN MELENDEZ ALEJANDRIA',
                        'city'                  => $advisory->city->name ?? null,
                        'province'              => $advisory->province->name ?? null,
                        'district'              => $advisory->district->name ?? null,
                        'ruc'                   => $advisory->ruc ?? null,
                        'economic_service'      => $advisory->economicsector->name ?? null,
                        'activity_comercial'    => $advisory->comercialactivity->name ?? null,
                        'component'             => $advisory->component->name ?? null,
                        'theme'                 => isset($advisory->theme->name) ? strtoupper($advisory->theme->name) : null,
                        'observations'          => $advisory->observations ? 'Z' . $advisory->observations : '-',
                        'modality'              => $advisory->modality->name ?? null,
                    ];
                }
            });

            return Excel::download(new AsesoriasExport($advisories), 'asesorias.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrio un error al generar el reporte.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function exportFormalizationsRuc10(\Illuminate\Http\Request $request)
    {
        try {

            $permission = getPermission('asesorias-formalizaciones-reportes');

            // if (!$permission['hasPermission']) {
            //     return response()->json([
            //         'message' => 'No tienes permiso para acceder a esta sección',
            //         'status' => 403
            //     ]);
            // }

            $filters = [
                'asesor'    => $request->input('asesor'),
                'name'      => $request->input('name'),
                'dateStart' => $request->input('dateStart'),
                'dateEnd'   => $request->input('dateEnd'),
                'year'      => $request->input('year'),
                'typeCdes'  => $request->input('typeCdes'),
            ];


            $user = Auth::user();

            $query = Formalization10::query();


            if ($user->rol == 1) {

                $query->withFormalizationRangeDate($filters);
            } else if ($user->rol == 2) {

                $query->withFormalizationRangeDate($filters)->where('user_id', $user->id);
            } else {

                return response()->json([
                    'message' => 'No tienes permiso para acceder a esta sección',
                    'status' => 403
                ]);
            }

            ini_set('memory_limit', '2G');

            set_time_limit(300);

            $fs10 = [];

            $globalIndex = 1;

            $query->chunk(1000, function ($rows) use (&$fs10, &$globalIndex) {
                foreach ($rows as $f10) {
                    $fs10[] = [
                        'index'                 => $globalIndex++,
                        'date'                  => $f10->created_at->format('d/m/Y'),
                        'asesor'                => strtoupper($f10->user->name . ' ' . $f10->user->lastname . ' ' . $f10->user->middlename),

                        'asesor_cde_city'       => $f10->sede->city ? $f10->sede->city : $f10->sede->region->name,
                        'asesor_cde_province'   => $f10->sede->province ? $f10->sede->province : $f10->sede->provincia->name,
                        'asesor_cde_district'   => $f10->sede->district ? $f10->sede->district : $f10->sede->distrito->name,

                        'asesor_cde'            => strtoupper($f10->sede->name) ?? null,

                        'emp_document_type'     => $f10->people->typedocument->avr ?? null,
                        'emp_document_number'   => $f10->people->documentnumber ?? null,
                        'emp_country'           => isset($f10->people->pais->name) ? strtoupper($f10->people->pais->name) : 'PERU',
                        'emp_birth'             => $f10->people->birthday ? \Carbon\Carbon::parse($f10->people->birthday)->format('d/m/Y') : null,
                        // 'emp_age'               => $f10->people->birthday ? \Carbon\Carbon::parse($f10->people->birthday)->age : null,
                        'emp_lastname'          => $f10->people->lastname,
                        'emp_middlename'        => $f10->people->middlename,
                        'emp_name'              => $f10->people->name,
                        'emp_gender'            => $f10->people->gender->name == 'FEMENINO' ? 'F' : 'M',

                        // 'emp_discapabilities'   => trim(strtolower($f10->people->sick)) === 'yes' ? 'SI' : 'NO',
                        // 'emp_soons'             => $f10->people->hasSoon ?? null,

                        'emp_discapabilities' => match (trim(strtolower($f10->people->sick ?? ''))) {
                            'yes' => 'SI',
                            'no'  => 'NO',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        'emp_soons' => match (trim(strtolower($f10->people->hasSoon ?? ''))) {
                            'si' => 'SI',
                            'no' => 'NO',
                            'na', '' => 'PREFIERO NO ESPECIFICAR',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        'emp_phone'             => $f10->people->phone,
                        'emp_email'             => $f10->people->email ? strtolower($f10->people->email) : '-',

                        'type_formalization'    => 'PPNN 10',

                        'supervisor'            => 'MILIAN MELENDEZ ALEJANDRIA',

                        'city'                  => $f10->city->name ?? null,
                        'province'              => $f10->province->name ?? null,
                        'district'              => $f10->district->name ?? null,
                        'address'               => $f10->address ?? null,
                        'ruc'                   => $f10->ruc ?? null,

                        'econimic_sector'       => $f10->economicsector->name ?? null,
                        'activity_comercial'    => $f10->comercialactivity->name ?? null,
                        'detail_tramit'         => $f10->detailprocedure->name ?? null,
                        'modality'              => $f10->modality->name ?? null,
                    ];
                }
            });

            return Excel::download(new FormalizationRUC10Export($fs10), 'formalizaciones10-pnte.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrio un error al generar el reporte.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function exportFormalizationsRuc20(Request $request)
    {
        try {
            $permission = getPermission('asesorias-formalizaciones-reportes');

            // if (!$permission['hasPermission']) {
            //     return response()->json([
            //         'message' => 'No tienes permiso para acceder a esta sección',
            //         'status' => 403
            //     ]);
            // }

            $filters = [
                'asesor'    => $request->input('asesor'),
                'name'      => $request->input('name'),
                'dateStart' => $request->input('dateStart'),
                'dateEnd'   => $request->input('dateEnd'),
                'year'      => $request->input('year'),
                'typeCdes'  => $request->input('typeCdes'),
            ];

            $user = Auth::user();

            $query = Formalization20::query();

            if ($user->rol == 1) {

                $query->withFormalizationRangeDate($filters);
            } else if ($user->rol == 2) {

                $query->withFormalizationRangeDate($filters)->where('user_id', $user->id);
            } else {

                return response()->json([
                    'message' => 'No tienes permiso para acceder a esta sección',
                    'status' => 403
                ]);
            }

            ini_set('memory_limit', '2G');

            set_time_limit(300);

            $fs20 = [];
            $globalIndex = 1;

            $query->chunk(1000, function ($rows) use (&$fs20, &$globalIndex) {
                foreach ($rows as $f20) {
                    $fs20[] = [
                        'index'                 => $globalIndex++,
                        'date'                  => $f20->created_at->format('d/m/Y'),
                        'asesor'                => strtoupper($f20->user->name . ' ' . $f20->user->lastname . ' ' . $f20->user->middlename),

                        'asesor_cde_city'       => $f20->sede->city ? $f20->sede->city : $f20->sede->region->name,
                        'asesor_cde_province'   => $f20->sede->province ? $f20->sede->province : $f20->sede->provincia->name,
                        'asesor_cde_district'   => $f20->sede->district ? $f20->sede->district : $f20->sede->distrito->name,

                        'asesor_cde'            => strtoupper($f20->sede->name) ?? null,

                        'emp_document_type'     => $f20->people->typedocument->avr ?? null,
                        'emp_document_number'   => $f20->people->documentnumber ?? null,
                        'emp_country'           => isset($f20->people->pais->name) ? strtoupper($f20->people->pais->name) : 'PERU',
                        'emp_birth'             => $f20->people->birthday ? \Carbon\Carbon::parse($f20->people->birthday)->format('d/m/Y') : null,
                        // 'emp_age'               => $f20->people->birthday ? \Carbon\Carbon::parse($f20->people->birthday)->age : null,
                        'emp_lastname'          => $f20->people->lastname,
                        'emp_middlename'        => $f20->people->middlename,
                        'emp_name'              => $f20->people->name,
                        'emp_gender'            => $f20->people->gender->name == 'FEMENINO' ? 'F' : 'M',

                        // 'emp_discapabilities'   => trim(strtolower($f20->people->sick)) === 'yes' ? 'SI' : 'NO',
                        // 'emp_soons'             => $f20->people->hasSoon ?? null,

                        'emp_discapabilities' => match (trim(strtolower($f20->people->sick ?? ''))) {
                            'yes' => 'SI',
                            'no'  => 'NO',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        'emp_soons' => match (trim(strtolower($f20->people->hasSoon ?? ''))) {
                            'si' => 'SI',
                            'no' => 'NO',
                            'na', '' => 'PREFIERO NO ESPECIFICAR',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        'emp_phone'             => $f20->people->phone,
                        'emp_email'             => $f20->people->email ? strtolower($f20->people->email) : '-',

                        'type_formalization'    => 'PPJJ 20',

                        'supervisor'            => 'MILIAN MELENDEZ ALEJANDRIA',

                        'city'                  => $f20->city->name ?? null,
                        'province'              => $f20->province->name ?? null,
                        'district'              => $f20->district->name ?? null,
                        'address'               => $f20->address ?? null,
                        'ruc'                   => $f20->ruc ?? null,
                        'econimic_sector'       => $f20->economicsector->name ?? null,
                        'activity_comercial'    => $f20->comercialactivity->name ?? null,

                        'date_reception'        => $f20->dateReception ? \Carbon\Carbon::parse($f20->dateReception)->format('d/m/Y') : null,
                        'date_tramite'          => $f20->dateTramite ? \Carbon\Carbon::parse($f20->dateTramite)->format('d/m/Y') : null,
                        'name_mype'             => strtoupper($f20->nameMype),
                        'type_regimen'          => $f20->regime->name,
                        'bic'                   => $f20->isbic,
                        'num_solicitud'         => $f20->numbernotary,


                        'notaria'               => isset($f20->notary->name) ? strtoupper($f20->notary->name) : null,
                        'type_aporte'           => optional($f20->typecapital)->name,
                        'monto_capital'         => $f20->montocapital,
                        'modality'              => $f20->modality->name ?? null
                    ];
                }
            });

            return Excel::download(new FormalizationRUC20Export($fs20), 'f20-pnte.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurri\u00f3 un error al generar el reporte.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }



    public function exportAsesoriesQueued(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'year' => 'required|integer|min:2020|max:2030'
        ]);

        Log::info("Solicitud de reporte recibida", [
            'email' => $request->email,
            'year' => $request->year
        ]);

        try {
            ExportAdvisoriesJob::dispatch($request->email, $request->year);

            Log::info("Job despachado exitosamente");

            return response()->json([
                'success' => true,
                'message' => 'Reporte en proceso. Se enviará al correo: ' . $request->email,
                'data' => [
                    'email' => $request->email,
                    'year' => $request->year,
                    'queued_at' => now()->toDateTimeString()
                ]
            ], 202);
        } catch (\Exception $e) {
            Log::error('Error al despachar job: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al programar el reporte',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
