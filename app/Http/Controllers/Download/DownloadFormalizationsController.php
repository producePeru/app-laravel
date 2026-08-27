<?php

namespace App\Http\Controllers\Download;

use App\Exports\AsesoriasCooperativasExport;
use App\Exports\AsesoriasExport;
use App\Exports\FormalizationRUC10Export;
use App\Exports\FormalizationRUC20Export;
use App\Http\Controllers\Controller;
use App\Jobs\ExportAdvisoriesJob;
use App\Models\Advisory;
use App\Models\Formalization10;
use App\Models\Formalization20;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                'asesor' => $request->input('asesor'),
                'name' => $request->input('name'),
                'dateStart' => $request->input('dateStart'),
                'dateEnd' => $request->input('dateEnd'),
                'year' => $request->input('year'),
                'typeCdes' => $request->input('typeCdes'),
            ];

            $user = Auth::user();

            $query = Advisory::query();

            if ($user->rol == 1) {

                $query->withAdvisoryRangeDate($filters);
            } elseif ($user->rol == 2) {

                $query->withAdvisoryRangeDate($filters)->where('user_id', $user->id);
            } else {

                return response()->json([
                    'message' => 'No tienes permiso para acceder a esta sección',
                    'status' => 403,
                ]);
            }

            ini_set('memory_limit', '2G');

            set_time_limit(300);

            $advisories = [];

            $globalIndex = 1;

            $query->chunk(1000, function ($rows) use (&$advisories, &$globalIndex) {
                foreach ($rows as $advisory) {
                    $advisories[] = [
                        // 'index' => ($user->rol == 1) ? $advisory->id : $globalIndex++,
                        'index' => $globalIndex++,
                        'date' => $advisory->created_at->format('d/m/Y'),
                        'asesor' => strtoupper($advisory->user->name.' '.$advisory->user->lastname.' '.$advisory->user->middlename),

                        'asesor_cde_city' => $advisory->sede->city ? $advisory->sede->city : $advisory->sede->region->name,
                        'asesor_cde_province' => $advisory->sede->province ? $advisory->sede->province : $advisory->sede->provincia->name,
                        'asesor_cde_district' => $advisory->sede->district ? $advisory->sede->district : $advisory->sede->distrito->name,
                        'asesor_cde' => isset($advisory->sede->name) ? strtoupper($advisory->sede->name) : null,

                        'emp_document_type' => $advisory->people->typedocument->avr ?? null,
                        'emp_document_number' => $advisory->people->documentnumber ?? null,
                        'emp_country' => isset($advisory->people->pais->name) ? strtoupper($advisory->people->pais->name) : 'PERU',
                        'emp_birth' => $advisory->people->birthday ? \Carbon\Carbon::parse($advisory->people->birthday)->format('d/m/Y') : null,
                        'emp_lastname' => strtoupper($advisory->people->lastname),
                        'emp_middlename' => strtoupper($advisory->people->middlename),
                        'emp_name' => strtoupper($advisory->people->name),
                        'emp_gender' => $advisory->people->gender->name == 'FEMENINO' ? 'F' : 'M',

                        'emp_discapabilities' => match (trim(strtolower($advisory->people->sick ?? ''))) {
                            'yes' => 'SI',
                            'no' => 'NO',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        'emp_cuidadora' => $advisory->people->persona_cuidadora ? strtoupper($advisory->people->persona_cuidadora) : null,

                        'emp_soons' => match (trim(strtolower($advisory->people->hasSoon ?? ''))) {
                            'si' => 'SI',
                            'no' => 'NO',
                            'na', '' => 'PREFIERO NO ESPECIFICAR',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        'emp_phone' => $advisory->people->phone,

                        'emp_etnia' => $advisory->people->etnia->name ?? null,
                        'lengua_originaria' => $advisory->people->lengua?->id == 7 ? $advisory->people->lengua_otro : $advisory->people->lengua->name ?? null,

                        'emp_email' => isset($advisory->people->email) ? strtolower($advisory->people->email) : '-',
                        'supervisor' => 'MILIAN MELENDEZ ALEJANDRIA',
                        'city' => $advisory->city->name ?? null,
                        'province' => $advisory->province->name ?? null,
                        'district' => $advisory->district->name ?? null,
                        'ruc' => $advisory->ruc ?? null,
                        'economic_service' => $advisory->economicsector->name ?? null,
                        'activity_comercial' => $advisory->comercialactivity->name ?? null,
                        'component' => $advisory->component->name ?? null,
                        'theme' => isset($advisory->theme->name) ? strtoupper($advisory->theme->name) : null,
                        'observations' => $advisory->observations ? 'Z'.$advisory->observations : '-',
                        'modality' => $advisory->modality->name ?? null,
                    ];
                }
            });

            return Excel::download(new AsesoriasExport($advisories), 'asesorias.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrio un error al generar el reporte.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function exportFormalizationsRuc10(\Illuminate\Http\Request $request)
    {
        try {

            $permission = getPermission('asesorias-formalizaciones-reportes');

            $filters = [
                'asesor' => $request->input('asesor'),
                'name' => $request->input('name'),
                'dateStart' => $request->input('dateStart'),
                'dateEnd' => $request->input('dateEnd'),
                'year' => $request->input('year'),
                'typeCdes' => $request->input('typeCdes'),
            ];

            $user = Auth::user();

            $query = Formalization10::query();

            if ($user->rol == 1) {

                $query->withFormalizationRangeDate($filters);
            } elseif ($user->rol == 2) {

                $query->withFormalizationRangeDate($filters)->where('user_id', $user->id);
            } else {

                return response()->json([
                    'message' => 'No tienes permiso para acceder a esta sección',
                    'status' => 403,
                ]);
            }

            ini_set('memory_limit', '2G');

            set_time_limit(300);

            $fs10 = [];

            $globalIndex = 1;

            $query->chunk(1000, function ($rows) use (&$fs10, &$globalIndex) {
                foreach ($rows as $f10) {
                    $fs10[] = [
                        'index' => $globalIndex++,
                        'date' => $f10->created_at->format('d/m/Y'),
                        'asesor' => strtoupper($f10->user->name.' '.$f10->user->lastname.' '.$f10->user->middlename),

                        'asesor_cde_city' => $f10->sede->city ? $f10->sede->city : $f10->sede->region->name,
                        'asesor_cde_province' => $f10->sede->province ? $f10->sede->province : $f10->sede->provincia->name,
                        'asesor_cde_district' => $f10->sede->district ? $f10->sede->district : $f10->sede->distrito->name,

                        'asesor_cde' => strtoupper($f10->sede->name) ?? null,

                        'emp_document_type' => $f10->people->typedocument->avr ?? null,
                        'emp_document_number' => $f10->people->documentnumber ?? null,
                        'emp_country' => isset($f10->people->pais->name) ? strtoupper($f10->people->pais->name) : 'PERU',
                        'emp_birth' => $f10->people->birthday ? \Carbon\Carbon::parse($f10->people->birthday)->format('d/m/Y') : null,
                        // 'emp_age'               => $f10->people->birthday ? \Carbon\Carbon::parse($f10->people->birthday)->age : null,
                        'emp_lastname' => $f10->people->lastname,
                        'emp_middlename' => $f10->people->middlename,
                        'emp_name' => $f10->people->name,
                        'emp_gender' => $f10->people->gender->name == 'FEMENINO' ? 'F' : 'M',

                        // 'emp_discapabilities'   => trim(strtolower($f10->people->sick)) === 'yes' ? 'SI' : 'NO',
                        // 'emp_soons'             => $f10->people->hasSoon ?? null,

                        'emp_discapabilities' => match (trim(strtolower($f10->people->sick ?? ''))) {
                            'yes' => 'SI',
                            'no' => 'NO',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        'emp_cuidadora' => $f10->people->persona_cuidadora ? strtoupper($f10->people->persona_cuidadora) : null,

                        'emp_soons' => match (trim(strtolower($f10->people->hasSoon ?? ''))) {
                            'si' => 'SI',
                            'no' => 'NO',
                            'na', '' => 'PREFIERO NO ESPECIFICAR',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        'emp_phone' => $f10->people->phone,
                        'emp_etnia' => $f10->people->etnia->name ?? null,
                        'lengua_originaria' => $f10->people->lengua?->id == 7 ? $f10->people->lengua_otro : $f10->people->lengua->name ?? null,

                        'emp_email' => $f10->people->email ? strtolower($f10->people->email) : '-',
                        'type_formalization' => 'PPNN 10',
                        'supervisor' => 'MILIAN MELENDEZ ALEJANDRIA',
                        'city' => $f10->city->name ?? null,
                        'province' => $f10->province->name ?? null,
                        'district' => $f10->district->name ?? null,
                        'address' => $f10->address ?? null,
                        'ruc' => $f10->ruc ?? null,
                        'econimic_sector' => $f10->economicsector->name ?? null,
                        'activity_comercial' => $f10->comercialactivity->name ?? null,
                        'detail_tramit' => $f10->detailprocedure->name ?? null,
                        'modality' => $f10->modality->name ?? null,
                    ];
                }
            });

            return Excel::download(new FormalizationRUC10Export($fs10), 'formalizaciones10-pnte.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrio un error al generar el reporte.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function exportFormalizationsRuc20(Request $request)
    {
        try {
            $permission = getPermission('asesorias-formalizaciones-reportes');

            $filters = [
                'asesor' => $request->input('asesor'),
                'name' => $request->input('name'),
                'dateStart' => $request->input('dateStart'),
                'dateEnd' => $request->input('dateEnd'),
                'year' => $request->input('year'),
                'typeCdes' => $request->input('typeCdes'),
            ];

            $user = Auth::user();

            $query = Formalization20::query();

            if ($user->rol == 1) {

                $query->withFormalizationRangeDate($filters);
            } elseif ($user->rol == 2) {

                $query->withFormalizationRangeDate($filters)->where('user_id', $user->id);
            } else {

                return response()->json([
                    'message' => 'No tienes permiso para acceder a esta sección',
                    'status' => 403,
                ]);
            }

            ini_set('memory_limit', '2G');

            set_time_limit(300);

            $fs20 = [];
            $globalIndex = 1;

            $query->chunk(1000, function ($rows) use (&$fs20, &$globalIndex) {
                foreach ($rows as $f20) {
                    $fs20[] = [
                        'index' => $globalIndex++,
                        'date' => $f20->created_at->format('d/m/Y'),
                        'asesor' => strtoupper($f20->user->name.' '.$f20->user->lastname.' '.$f20->user->middlename),

                        'asesor_cde_city' => $f20->sede->city ? $f20->sede->city : $f20->sede->region->name,
                        'asesor_cde_province' => $f20->sede->province ? $f20->sede->province : $f20->sede->provincia->name,
                        'asesor_cde_district' => $f20->sede->district ? $f20->sede->district : $f20->sede->distrito->name,

                        'asesor_cde' => strtoupper($f20->sede->name) ?? null,

                        'emp_document_type' => $f20->people->typedocument->avr ?? null,
                        'emp_document_number' => $f20->people->documentnumber ?? null,
                        'emp_country' => isset($f20->people->pais->name) ? strtoupper($f20->people->pais->name) : 'PERU',
                        'emp_birth' => $f20->people->birthday ? \Carbon\Carbon::parse($f20->people->birthday)->format('d/m/Y') : null,
                        // 'emp_age'               => $f20->people->birthday ? \Carbon\Carbon::parse($f20->people->birthday)->age : null,
                        'emp_lastname' => $f20->people->lastname,
                        'emp_middlename' => $f20->people->middlename,
                        'emp_name' => $f20->people->name,
                        'emp_gender' => $f20->people->gender->name == 'FEMENINO' ? 'F' : 'M',

                        // 'emp_discapabilities'   => trim(strtolower($f20->people->sick)) === 'yes' ? 'SI' : 'NO',
                        // 'emp_soons'             => $f20->people->hasSoon ?? null,

                        'emp_discapabilities' => match (trim(strtolower($f20->people->sick ?? ''))) {
                            'yes' => 'SI',
                            'no' => 'NO',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        'emp_cuidadora' => $f20->people->persona_cuidadora ? strtoupper($f20->people->persona_cuidadora) : null,

                        'emp_soons' => match (trim(strtolower($f20->people->hasSoon ?? ''))) {
                            'si' => 'SI',
                            'no' => 'NO',
                            'na', '' => 'PREFIERO NO ESPECIFICAR',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        'emp_phone' => $f20->people->phone,
                        'emp_etnia' => $f20->people->etnia->name ?? null,
                        'lengua_originaria' => $f20->people->lengua?->id == 7 ? $f20->people->lengua_otro : $f20->people->lengua->name ?? null,

                        'emp_email' => $f20->people->email ? strtolower($f20->people->email) : '-',

                        'type_formalization' => 'PPJJ 20',

                        'supervisor' => 'MILIAN MELENDEZ ALEJANDRIA',

                        'city' => $f20->city->name ?? null,
                        'province' => $f20->province->name ?? null,
                        'district' => $f20->district->name ?? null,
                        'address' => $f20->address ?? null,
                        'ruc' => $f20->ruc ?? null,
                        'econimic_sector' => $f20->economicsector->name ?? null,
                        'activity_comercial' => $f20->comercialactivity->name ?? null,

                        'date_reception' => $f20->dateReception ? \Carbon\Carbon::parse($f20->dateReception)->format('d/m/Y') : null,
                        'date_tramite' => $f20->dateTramite ? \Carbon\Carbon::parse($f20->dateTramite)->format('d/m/Y') : null,
                        'name_mype' => strtoupper($f20->nameMype),
                        'type_regimen' => $f20->regime->name,
                        'bic' => $f20->isbic,
                        'num_solicitud' => $f20->numbernotary,

                        'notaria' => isset($f20->notary->name) ? strtoupper($f20->notary->name) : null,
                        'type_aporte' => optional($f20->typecapital)->name,
                        'monto_capital' => $f20->montocapital,
                        'modality' => $f20->modality->name ?? null,
                    ];
                }
            });

            return Excel::download(new FormalizationRUC20Export($fs20), 'f20-pnte.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurri\u00f3 un error al generar el reporte.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function exportAsesoriesQueued(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        Log::info('Solicitud de reporte recibida', [
            'email' => $request->email,
            'year' => $request->year,
        ]);

        try {
            ExportAdvisoriesJob::dispatch($request->email, $request->year);

            Log::info('Job despachado exitosamente');

            return response()->json([
                'success' => true,
                'message' => 'Reporte en proceso. Se enviará al correo: '.$request->email,
                'data' => [
                    'email' => $request->email,
                    'year' => $request->year,
                    'queued_at' => now()->toDateTimeString(),
                ],
            ], 202);
        } catch (\Exception $e) {
            Log::error('Error al despachar job: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al programar el reporte',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Cooperativas

    public function exportAsesoriesCooperativas(\Illuminate\Http\Request $request)
    {
        try {

            $filters = [
                'asesor' => $request->input('asesor'),
                'name' => $request->input('name'),
                'dateStart' => $request->input('dateStart'),
                'dateEnd' => $request->input('dateEnd'),
                'year' => $request->input('year'),
                'typeCdes' => $request->input('typeCdes'),
            ];

            $user = Auth::user();

            $query = Advisory::query();

            // ✅ SOLO COOPERATIVAS
            $query->whereHas('cooperativa');

            if ($user->rol == 1) {

                $query->withAdvisoryCooperativas($filters);
            } elseif ($user->rol == 2) {

                $query->withAdvisoryCooperativas($filters)
                    ->where('user_id', $user->id);
            } else {

                return response()->json([
                    'message' => 'No tienes permiso para acceder a esta sección',
                    'status' => 403,
                ]);
            }

            ini_set('memory_limit', '2G');

            set_time_limit(300);

            $advisories = [];

            $globalIndex = 1;

            // ✅ MAPA DE CARGOS
            $cargosCooperativa = [
                1 => 'DIRIGENTE',
                2 => 'DELEGADO',
                3 => 'SOCIO O PERSONAL ADMINISTRATIVO',
            ];

            $query->chunk(1000, function ($rows) use (
                &$advisories,
                &$globalIndex,
                $cargosCooperativa
            ) {

                foreach ($rows as $advisory) {

                    $advisories[] = [

                        'index' => $globalIndex++,

                        'date' => $advisory->created_at
                            ->format('d/m/Y'),

                        'asesor' => strtoupper(
                            $advisory->user->name.' '.
                                $advisory->user->lastname.' '.
                                $advisory->user->middlename
                        ),

                        'asesor_cde_city' => $advisory->sede->city
                            ? $advisory->sede->city
                            : $advisory->sede->region->name,

                        'asesor_cde_province' => $advisory->sede->province
                            ? $advisory->sede->province
                            : $advisory->sede->provincia->name,

                        'asesor_cde_district' => $advisory->sede->district
                            ? $advisory->sede->district
                            : $advisory->sede->distrito->name,

                        'asesor_cde' => isset($advisory->sede->name)
                            ? strtoupper($advisory->sede->name)
                            : null,

                        'emp_document_type' => $advisory->people
                            ->typedocument->avr ?? null,

                        'emp_document_number' => $advisory->people
                            ->documentnumber ?? null,

                        'emp_country' => isset(
                            $advisory->people->pais->name
                        )
                            ? strtoupper($advisory->people->pais->name)
                            : 'PERU',

                        'emp_birth' => $advisory->people->birthday
                            ? \Carbon\Carbon::parse(
                                $advisory->people->birthday
                            )->format('d/m/Y')
                            : null,

                        'emp_lastname' => strtoupper(
                            $advisory->people->lastname
                        ),

                        'emp_middlename' => strtoupper(
                            $advisory->people->middlename
                        ),

                        'emp_name' => strtoupper(
                            $advisory->people->name
                        ),

                        'emp_gender' => $advisory->people->gender->name == 'FEMENINO'
                            ? 'F'
                            : 'M',

                        'emp_discapabilities' => match (trim(
                            strtolower(
                                $advisory->people->sick ?? ''
                            )
                        )) {
                            'yes' => 'SI',
                            'no' => 'NO',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        'emp_soons' => match (trim(
                            strtolower(
                                $advisory->people->hasSoon ?? ''
                            )
                        )) {
                            'si' => 'SI',
                            'no' => 'NO',
                            'na', '' => 'PREFIERO NO ESPECIFICAR',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        'emp_phone' => $advisory->people->phone,

                        'emp_email' => isset(
                            $advisory->people->email
                        )
                            ? strtolower($advisory->people->email)
                            : '-',

                        'supervisor' => 'MILIAN MELENDEZ ALEJANDRIA',

                        'city' => $advisory->city->name ?? null,

                        'province' => $advisory->province->name ?? null,

                        'district' => $advisory->district->name ?? null,

                        'ruc' => $advisory->ruc ?? null,

                        'economic_service' => $advisory
                            ->economicsector->name ?? null,

                        'activity_comercial' => $advisory
                            ->comercialactivity->name ?? null,

                        'component' => $advisory
                            ->component->name ?? null,

                        'theme' => isset(
                            $advisory->theme->name
                        )
                            ? strtoupper($advisory->theme->name)
                            : null,

                        'observations' => $advisory->observations
                            ? 'Z'.$advisory->observations
                            : '-',

                        'modality' => $advisory->modality->name ?? null,

                        // ✅ DATOS COOPERATIVA
                        'cooperativa_ruc' => $advisory
                            ->cooperativa?->ruc ?? null,

                        'cooperativa_nombre' => $advisory
                            ->cooperativa?->nombre ?? null,

                        'cooperativa_cargo' => $cargosCooperativa[$advisory->cooperativa?->cargo] ?? null,
                    ];
                }
            });

            return Excel::download(
                new AsesoriasCooperativasExport($advisories),
                'asesorias_cooperativas.xlsx'
            );
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Ocurrio un error al generar el reporte.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // scv
    public function exportAsesoriasCsv(Request $request)
    {
        $permission = getPermission('asesorias-formalizaciones-reportes');

        $filters = [
            'asesor' => $request->input('asesor'),
            'name' => $request->input('name'),
            'dateStart' => $request->input('dateStart'),
            'dateEnd' => $request->input('dateEnd'),
            'year' => $request->input('year'),
            'typeCdes' => $request->input('typeCdes'),
        ];

        $user = Auth::user();

        $query = Advisory::query();

        if ($user->rol == 1) {
            $query->withAdvisoryRangeDate($filters);
        } elseif ($user->rol == 2) {
            $query->withAdvisoryRangeDate($filters)->where('user_id', $user->id);
        } else {
            return response()->json([
                'message' => 'No tienes permiso para acceder a esta sección',
                'status' => 403,
            ], 403);
        }

        $query->orderBy('advisories.id', 'desc');

        $query->with([
            'user', 'sede', 'sede.region', 'sede.provincia', 'sede.distrito',
            'people', 'people.typedocument', 'people.pais', 'people.gender',
            'people.etnia', 'people.lengua',
            'city', 'province', 'district',
            'economicsector', 'comercialactivity', 'component', 'theme', 'modality',
        ]);

        DB::connection()->disableQueryLog();

        ini_set('memory_limit', '512M');
        set_time_limit(0);

        $headings = [
            'No', 'Fecha de Registro', 'Asesor (a) - Nombre Completo',
            'Región del CDE del Asesor', 'Provincia del CDE del Asesor', 'Distrito del CDE del Asesor', 'Cde del Asesor',
            'Tipo de Documento de Identidad', 'Número de Documento de Identidad', 'Nombre del país de origen',
            'Fecha de Nacimiento', 'Apellido Paterno del Solicitante (socio o Gte General)',
            'Apellido Materno del Solicitante (socio o Gte General)', 'Nombres del Solicitante (socio o Gte General)',
            'Genero', 'Tiene alguna Discapacidad ? (SI / NO)', 'Nombre y apellido de la Persona cuidadora',
            '¿Tiene hijos?  (SI / NO)', 'Telefono', '¿Con qué cultura o etnia te identificas?', 'Lengua Originaria',
            'Correo electrónico o "NO TIENE"', 'SUPERVISOR',
            'Región del negocio', 'Provincia del Negocio', 'Distrito del Negocio', 'N_RUC',
            'Sector Económico', 'Actividad Comercial Inicial', 'Componente', 'Tema',
            'Nro de Reserva / Observacion', 'Modalidad',
        ];

        $fileName = 'asesorias_'.now()->format('Ymd_His').'.csv';

        $total = (clone $query)->count('advisories.id');

        return new StreamedResponse(function () use ($query, $headings, $total) {

            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            $delimiter = ',';

            fputcsv($handle, $headings, $delimiter);

            $globalIndex = 1;
            $processed = 0;

            // ⚡ chunk() en vez de chunkById(): respeta el orderBy('created_at desc', 'id desc')
            // Con offset, no id-cursor. A esta escala (46k filas) el rendimiento es perfectamente aceptable.
            $query->chunk(1000, function ($rows) use ($handle, &$globalIndex, &$processed, $delimiter) {

                foreach ($rows as $advisory) {

                    $row = [
                        $globalIndex++,
                        optional($advisory->created_at)->format('d/m/Y'),
                        strtoupper(trim(($advisory->user->name ?? '').' '.($advisory->user->lastname ?? '').' '.($advisory->user->middlename ?? ''))),
                        $advisory->sede->city ?: ($advisory->sede->region->name ?? null),
                        $advisory->sede->province ?: ($advisory->sede->provincia->name ?? null),
                        $advisory->sede->district ?: ($advisory->sede->distrito->name ?? null),
                        isset($advisory->sede->name) ? strtoupper($advisory->sede->name) : null,
                        $advisory->people->typedocument->avr ?? null,
                        $advisory->people->documentnumber ?? null,
                        isset($advisory->people->pais->name) ? strtoupper($advisory->people->pais->name) : 'PERU',
                        $advisory->people->birthday ? \Carbon\Carbon::parse($advisory->people->birthday)->format('d/m/Y') : null,
                        strtoupper($advisory->people->lastname ?? ''),
                        strtoupper($advisory->people->middlename ?? ''),
                        strtoupper($advisory->people->name ?? ''),
                        ($advisory->people->gender->name ?? null) == 'FEMENINO' ? 'F' : 'M',
                        match (trim(strtolower($advisory->people->sick ?? ''))) {
                            'yes' => 'SI',
                            'no' => 'NO',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },
                        $advisory->people->persona_cuidadora ? strtoupper($advisory->people->persona_cuidadora) : null,
                        match (trim(strtolower($advisory->people->hasSoon ?? ''))) {
                            'si' => 'SI',
                            'no' => 'NO',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },
                        $advisory->people->phone ?? null,
                        $advisory->people->etnia->name ?? null,
                        $advisory->people->lengua?->id == 7
                            ? $advisory->people->lengua_otro
                            : ($advisory->people->lengua->name ?? null),
                        isset($advisory->people->email) ? strtolower($advisory->people->email) : '-',
                        'MILIAN MELENDEZ ALEJANDRIA',
                        $advisory->city->name ?? null,
                        $advisory->province->name ?? null,
                        $advisory->district->name ?? null,
                        $advisory->ruc ?? null,
                        $advisory->economicsector->name ?? null,
                        $advisory->comercialactivity->name ?? null,
                        $advisory->component->name ?? null,
                        isset($advisory->theme->name) ? strtoupper($advisory->theme->name) : null,
                        $advisory->observations ? 'Z'.$advisory->observations : '-',
                        $advisory->modality->name ?? null,
                    ];

                    fputcsv($handle, $row, $delimiter);
                    $processed++;
                }

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            });

            fputcsv($handle, ["TOTAL EXPORTADO: {$processed} de {$total}"], $delimiter);

            fclose($handle);

        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function exportFormalizationsRuc10Csv(Request $request)
    {
        $permission = getPermission('asesorias-formalizaciones-reportes');

        $filters = [
            'asesor' => $request->input('asesor'),
            'name' => $request->input('name'),
            'dateStart' => $request->input('dateStart'),
            'dateEnd' => $request->input('dateEnd'),
            'year' => $request->input('year'),
            'typeCdes' => $request->input('typeCdes'),
        ];

        $user = Auth::user();

        $query = Formalization10::query();

        if ($user->rol == 1) {
            $query->withFormalizationRangeDate($filters);
        } elseif ($user->rol == 2) {
            $query->withFormalizationRangeDate($filters)->where('user_id', $user->id);
        } else {
            return response()->json([
                'message' => 'No tienes permiso para acceder a esta sección',
                'status' => 403,
            ], 403);
        }

        // 🔒 Desempate para que el OFFSET del chunk() sea determinístico entre vueltas.
        // El scope ya ordena por created_at desc; esto solo agrega 'id' como tiebreaker.
        $query->orderBy('formalizations10.id', 'desc'); // ✅ nombre real de la tabla

        $query->with([
            'user', 'sede', 'sede.region', 'sede.provincia', 'sede.distrito',
            'people', 'people.typedocument', 'people.pais', 'people.gender',
            'people.etnia', 'people.lengua',
            'city', 'province', 'district',
            'economicsector', 'comercialactivity', 'detailprocedure', 'modality',
        ]);

        DB::connection()->disableQueryLog();

        ini_set('memory_limit', '512M');
        set_time_limit(0);

        $headings = [
            'No', 'Fecha de Registro', 'Asesor (a) - Nombre Completo',
            'Región del CDE del Asesor', 'Provincia del CDE del Asesor', 'Distrito del CDE del Asesor', 'Cde del Asesor',
            'Tipo de Documento de Identidad', 'Número de Documento de Identidad', 'Nombre del país de origen',
            'Fecha de Nacimiento', 'Apellido Paterno', 'Apellido Materno', 'Nombres',
            'Genero', 'Tiene alguna Discapacidad ? (SI / NO)', 'Nombre y apellido de la Persona cuidadora',
            '¿Tiene hijos?  (SI / NO)', 'Telefono', '¿Con qué cultura o etnia te identificas?', 'Lengua Originaria',
            'Correo electrónico o "NO TIENE"', 'Tipo de Formalización', 'SUPERVISOR',
            'Región del negocio', 'Provincia del Negocio', 'Distrito del Negocio', 'Dirección', 'N_RUC',
            'Sector Económico', 'Actividad Comercial', 'Detalle del Trámite', 'Modalidad',
        ];

        $fileName = 'formalizaciones10-pnte_'.now()->format('Ymd_His').'.csv';

        $total = (clone $query)->count('formalizations10.id'); // ✅ nombre real de la tabla

        return new StreamedResponse(function () use ($query, $headings, $total) {

            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            $delimiter = ',';

            fputcsv($handle, $headings, $delimiter);

            $globalIndex = 1;
            $processed = 0;

            $query->chunk(1000, function ($rows) use ($handle, &$globalIndex, &$processed, $delimiter) {

                foreach ($rows as $f10) {

                    $row = [
                        $globalIndex++,
                        optional($f10->created_at)->format('d/m/Y'),
                        strtoupper(trim(($f10->user->name ?? '').' '.($f10->user->lastname ?? '').' '.($f10->user->middlename ?? ''))),

                        $f10->sede->city ?: ($f10->sede->region->name ?? null),
                        $f10->sede->province ?: ($f10->sede->provincia->name ?? null),
                        $f10->sede->district ?: ($f10->sede->distrito->name ?? null),
                        isset($f10->sede->name) ? strtoupper($f10->sede->name) : null,

                        $f10->people->typedocument->avr ?? null,
                        $f10->people->documentnumber ?? null,
                        isset($f10->people->pais->name) ? strtoupper($f10->people->pais->name) : 'PERU',
                        $f10->people->birthday ? \Carbon\Carbon::parse($f10->people->birthday)->format('d/m/Y') : null,
                        $f10->people->lastname ?? null,
                        $f10->people->middlename ?? null,
                        $f10->people->name ?? null,
                        ($f10->people->gender->name ?? null) == 'FEMENINO' ? 'F' : 'M',

                        match (trim(strtolower($f10->people->sick ?? ''))) {
                            'yes' => 'SI',
                            'no' => 'NO',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        $f10->people->persona_cuidadora ? strtoupper($f10->people->persona_cuidadora) : null,

                        match (trim(strtolower($f10->people->hasSoon ?? ''))) {
                            'si' => 'SI',
                            'no' => 'NO',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        $f10->people->phone ?? null,
                        $f10->people->etnia->name ?? null,
                        $f10->people->lengua?->id == 7
                            ? $f10->people->lengua_otro
                            : ($f10->people->lengua->name ?? null),

                        isset($f10->people->email) ? strtolower($f10->people->email) : '-',
                        'PPNN 10',
                        'MILIAN MELENDEZ ALEJANDRIA',

                        $f10->city->name ?? null,
                        $f10->province->name ?? null,
                        $f10->district->name ?? null,
                        $f10->address ?? null,
                        $f10->ruc ?? null,
                        $f10->economicsector->name ?? null,
                        $f10->comercialactivity->name ?? null,
                        $f10->detailprocedure->name ?? null,
                        $f10->modality->name ?? null,
                    ];

                    fputcsv($handle, $row, $delimiter);
                    $processed++;
                }

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            });

            fputcsv($handle, ["TOTAL EXPORTADO: {$processed} de {$total}"], $delimiter);

            fclose($handle);

        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function exportFormalizationsRuc20Csv(Request $request)
    {
        $permission = getPermission('asesorias-formalizaciones-reportes');

        $filters = [
            'asesor' => $request->input('asesor'),
            'name' => $request->input('name'),
            'dateStart' => $request->input('dateStart'),
            'dateEnd' => $request->input('dateEnd'),
            'year' => $request->input('year'),
            'typeCdes' => $request->input('typeCdes'),
        ];

        $user = Auth::user();

        $query = Formalization20::query();

        if ($user->rol == 1) {
            $query->withFormalizationRangeDate($filters);
        } elseif ($user->rol == 2) {
            $query->withFormalizationRangeDate($filters)->where('user_id', $user->id);
        } else {
            return response()->json([
                'message' => 'No tienes permiso para acceder a esta sección',
                'status' => 403,
            ], 403);
        }

        // 🔒 Desempate para que el OFFSET del chunk() sea determinístico entre vueltas.
        $query->orderBy('formalizations20.id', 'desc'); // ✅ nombre real de la tabla

        $query->with([
            'user', 'sede', 'sede.region', 'sede.provincia', 'sede.distrito',
            'people', 'people.typedocument', 'people.pais', 'people.gender',
            'people.etnia', 'people.lengua',
            'city', 'province', 'district',
            'economicsector', 'comercialactivity', 'regime', 'notary',
            'typecapital', 'modality',
        ]);

        DB::connection()->disableQueryLog();

        ini_set('memory_limit', '512M');
        set_time_limit(0);

        $headings = [
            'No', 'Fecha de Registro', 'Asesor (a) - Nombre Completo',
            'Región del CDE del Asesor', 'Provincia del CDE del Asesor', 'Distrito del CDE del Asesor', 'Cde del Asesor',
            'Tipo de Documento de Identidad', 'Número de Documento de Identidad', 'Nombre del país de origen',
            'Fecha de Nacimiento', 'Apellido Paterno', 'Apellido Materno', 'Nombres',
            'Genero', 'Tiene alguna Discapacidad ? (SI / NO)', 'Nombre y apellido de la Persona cuidadora',
            '¿Tiene hijos?  (SI / NO)', 'Telefono', '¿Con qué cultura o etnia te identificas?', 'Lengua Originaria',
            'Correo electrónico o "NO TIENE"', 'Tipo de Formalización', 'SUPERVISOR',
            'Región del negocio', 'Provincia del Negocio', 'Distrito del Negocio', 'Dirección', 'N_RUC',
            'Sector Económico', 'Actividad Comercial',
            'Fecha de Recepción', 'Fecha de Trámite', 'Nombre MYPE', 'Tipo de Régimen', 'BIC', 'Nro de Solicitud',
            'Notaría', 'Tipo de Aporte', 'Monto de Capital', 'Modalidad',
        ];

        $fileName = 'formalizaciones20-pnte_'.now()->format('Ymd_His').'.csv';

        $total = (clone $query)->count('formalizations20.id'); // ✅ nombre real de la tabla

        return new StreamedResponse(function () use ($query, $headings, $total) {

            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            $delimiter = ',';

            fputcsv($handle, $headings, $delimiter);

            $globalIndex = 1;
            $processed = 0;

            $query->chunk(1000, function ($rows) use ($handle, &$globalIndex, &$processed, $delimiter) {

                foreach ($rows as $f20) {

                    $row = [
                        $globalIndex++,
                        optional($f20->created_at)->format('d/m/Y'),
                        strtoupper(trim(($f20->user->name ?? '').' '.($f20->user->lastname ?? '').' '.($f20->user->middlename ?? ''))),

                        $f20->sede->city ?: ($f20->sede->region->name ?? null),
                        $f20->sede->province ?: ($f20->sede->provincia->name ?? null),
                        $f20->sede->district ?: ($f20->sede->distrito->name ?? null),
                        isset($f20->sede->name) ? strtoupper($f20->sede->name) : null,

                        $f20->people->typedocument->avr ?? null,
                        $f20->people->documentnumber ?? null,
                        isset($f20->people->pais->name) ? strtoupper($f20->people->pais->name) : 'PERU',
                        $f20->people->birthday ? \Carbon\Carbon::parse($f20->people->birthday)->format('d/m/Y') : null,
                        $f20->people->lastname ?? null,
                        $f20->people->middlename ?? null,
                        $f20->people->name ?? null,
                        ($f20->people->gender->name ?? null) == 'FEMENINO' ? 'F' : 'M',

                        match (trim(strtolower($f20->people->sick ?? ''))) {
                            'yes' => 'SI',
                            'no' => 'NO',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        $f20->people->persona_cuidadora ? strtoupper($f20->people->persona_cuidadora) : null,

                        match (trim(strtolower($f20->people->hasSoon ?? ''))) {
                            'si' => 'SI',
                            'no' => 'NO',
                            default => 'PREFIERO NO ESPECIFICAR',
                        },

                        $f20->people->phone ?? null,
                        $f20->people->etnia->name ?? null,
                        $f20->people->lengua?->id == 7
                            ? $f20->people->lengua_otro
                            : ($f20->people->lengua->name ?? null),

                        isset($f20->people->email) ? strtolower($f20->people->email) : '-',
                        'PPJJ 20',
                        'MILIAN MELENDEZ ALEJANDRIA',

                        $f20->city->name ?? null,
                        $f20->province->name ?? null,
                        $f20->district->name ?? null,
                        $f20->address ?? null,
                        $f20->ruc ?? null,
                        $f20->economicsector->name ?? null,
                        $f20->comercialactivity->name ?? null,

                        $f20->dateReception ? \Carbon\Carbon::parse($f20->dateReception)->format('d/m/Y') : null,
                        $f20->dateTramite ? \Carbon\Carbon::parse($f20->dateTramite)->format('d/m/Y') : null,
                        isset($f20->nameMype) ? strtoupper($f20->nameMype) : null,
                        $f20->regime->name ?? null,
                        $f20->isbic ?? null,
                        $f20->numbernotary ?? null,

                        isset($f20->notary->name) ? strtoupper($f20->notary->name) : null,
                        $f20->typecapital->name ?? null,
                        $f20->montocapital ?? null,
                        $f20->modality->name ?? null,
                    ];

                    fputcsv($handle, $row, $delimiter);
                    $processed++;
                }

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            });

            fputcsv($handle, ["TOTAL EXPORTADO: {$processed} de {$total}"], $delimiter);

            fclose($handle);

        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
