<?php

namespace App\Http\Controllers\Download;

use App\Exports\AttendanceListSlugExport;
// use App\Exports\AttendanceListExport;
use App\Http\Controllers\Controller;
use App\Models\ActividadPnte;
use App\Models\Attendance;
use App\Models\AttendanceList;
use App\Models\City;
use App\Models\District;
use App\Models\EmpresarioActividad;
use App\Models\MPAttendance;
use App\Models\People;
use App\Models\Province;
use App\Models\Question;
use App\Models\SedQuestion;
use App\Models\sedQuestionAnswer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

Carbon::setLocale('es');

class DownloadAttendanceController extends Controller
{
    public function exportAttendance(Request $request)
    {
        try {

            ini_set('memory_limit', '2G');

            set_time_limit(300);

            $user = Auth::user();

            $actividades = ActividadPnte::with([
                'tipoActividad:id,name',
                'nombreActividad:id,name',
                'regionRel:id,name',
                'provinciaRel:id,name',
                'distritoRel:id,name',
                'representante:id,name,lastname,middlename',
                'modalidad:id,name',
                'registradoPor:id,name,lastname,middlename',
            ])
                ->select([
                    'id',
                    'unidad',
                    'mes',
                    'fechas',
                    'cantidad_dias',
                    'tipo_actividad_id',
                    'nombre_actividad_id',
                    'tema',
                    'region',
                    'provincia',
                    'distrito',
                    'lugar',
                    'entidad_organizadora',
                    'entidad_aliada',
                    'representante_id',
                    'requiere_pasaje',
                    'monto_gasto',
                    'mypes_beneficiadas',
                    'modalidad_id',
                    'total_participantes',
                    'total_asesorias',
                    'total_formalizaciones',
                    'slug',
                    'cancelado',
                    'reprogramado',
                    'registrado_por_id',
                    'created_at',
                ])

                ->addSelect([
                    'inscritos' => EmpresarioActividad::selectRaw('COUNT(*)')
                        ->whereColumn('empresario_actividad.slug', 'actividades_pnte.slug'),
                ])

                // ✅ UNIDAD DINÁMICA
                ->when(
                    $request->filled('unidad'),
                    function ($q) use ($request) {

                        $q->where('unidad', $request->input('unidad'));
                    },
                    function ($q) {

                        // ✅ SI NO ENVÍA UNIDAD → TRAER TODOS
                        $q;
                    }
                )

                // ✅ FILTRO POR ROL
                ->when($user->rol == 2, function ($q) use ($user) {

                    $q->where('representante_id', $user->id);
                })

                // ✅ FILTRO YEAR
                ->when($request->filled('year'), function ($q) use ($request) {

                    $q->where(
                        'fechas',
                        'LIKE',
                        "%{$request->input('year')}%"
                    );
                })

                // ✅ FILTRO RANGE DATE
                ->when($request->filled('rangeDate'), function ($q) use ($request) {

                    [$from, $to] = $request->input('rangeDate');

                    $current = Carbon::parse($from);

                    $end = Carbon::parse($to);

                    $q->where(function ($query) use ($current, $end) {

                        while ($current->lte($end)) {

                            $query->orWhere(
                                'fechas',
                                'LIKE',
                                "%{$current->format('Y-m-d')}%"
                            );

                            $current->addDay();
                        }
                    });
                })

                // ✅ FILTRO CITY
                ->when($request->filled('city'), function ($q) use ($request) {

                    $q->where(
                        'region',
                        $request->input('city')
                    );
                })

                // ✅ FILTRO TIPO ACTIVIDAD
                ->when($request->filled('tipo_actividad_id'), function ($q) use ($request) {

                    $q->where(
                        'tipo_actividad_id',
                        $request->input('tipo_actividad_id')
                    );
                })

                // ✅ FILTRO PNTE
                ->when($request->filled('pnte'), function ($q) use ($request) {

                    $q->where(
                        'tipo_actividad_id',
                        $request->input('pnte')
                    );
                })

                // ✅ FILTRO ASESOR
                ->when(
                    $request->filled('asesor') &&
                        $user->rol == 1,
                    function ($q) use ($request) {

                        $q->where(
                            'representante_id',
                            $request->input('asesor')
                        );
                    }
                )

                ->get()

                ->sortByDesc(function ($actividad) {

                    $fechas = is_array($actividad->fechas)
                        ? $actividad->fechas
                        : json_decode($actividad->fechas, true);

                    return collect($fechas)->max();
                })

                ->values();

            // 📄 PLANTILLA EXCEL
            $templatePath = storage_path(
                'app/plantillas/attendance_template.xlsx'
            );

            $spreadsheet = IOFactory::load($templatePath);

            $sheet = $spreadsheet->getActiveSheet();

            $startRow = 2;

            foreach ($actividades as $index => $item) {

                $fechas = is_array($item->fechas)
                    ? $item->fechas
                    : json_decode($item->fechas, true);

                $fechaMin = collect($fechas)->min();

                $fechaMax = collect($fechas)->max();

                // ✅ ESTADO
                $today = Carbon::today();

                if ($item->inscritos > 0) {

                    $estado = '4. FINALIZADOS';
                } elseif (Carbon::parse($fechaMax)->lt($today)) {

                    $estado = '3. PENDIENTE DE RESULTADOS';
                } elseif (Carbon::parse($item->created_at)->isToday()) {

                    $estado = '1. PROGRAMACION DIARIA';
                } else {

                    $estado = '2. PROGRAMACION CONSOLIDADA';
                }

                // ✅ ESTADO ACTIVIDAD
                $estadoActividad = $item->cancelado
                    ? 'CANCELADO'
                    : ($item->reprogramado
                        ? 'REPROGRAMADO'
                        : 'EN CURSO');

                // ✅ REPRESENTANTE
                $representante = $item->representante
                    ? strtoupper(
                        $item->representante->lastname . ' ' .
                            $item->representante->middlename . ', ' .
                            $item->representante->name
                    )
                    : null;

                // ✅ REGISTRADO POR
                $registradoPor = $item->registradoPor
                    ? strtoupper(
                        $item->registradoPor->name . ' ' .
                            $item->registradoPor->lastname . ' ' .
                            $item->registradoPor->middlename
                    )
                    : null;

                // ✅ COLUMNAS EXCEL
                $row = [

                    $index + 1,

                    // B UNIDAD
                    $item->unidad == 1 ? 'UGO' : 'UGSE',

                    strtoupper(
                        Carbon::parse($fechaMin)
                            ->translatedFormat('F')
                    ),

                    Carbon::parse($fechaMin)
                        ->format('d/m/Y'),

                    Carbon::parse($fechaMax)
                        ->format('d/m/Y'),

                    $item->cantidad_dias,

                    $item->tipoActividad->name ?? '-',

                    $item->nombreActividad->name ?? '-',

                    strtoupper($item->tema ?? '-'),

                    $item->regionRel->name ?? null,

                    $item->provinciaRel->name ?? null,

                    $item->distritoRel->name ?? null,

                    $item->lugar ?? null,

                    strtoupper(
                        $item->entidad_organizadora ?? '-'
                    ),

                    strtoupper(
                        $item->entidad_aliada ?? '-'
                    ),

                    $representante,

                    $item->requiere_pasaje
                        ? 'SÍ'
                        : 'NO',

                    $item->monto_gasto ?? 0,

                    $item->mypes_beneficiadas ?? 0,

                    $item->modalidad->name ?? null,

                    $item->inscritos > 0
                        ? 'CON LISTA'
                        : 'SIN LISTA',

                    $item->inscritos ?? 0,

                    $item->total_asesorias ?? 0,

                    $item->total_formalizaciones ?? 0,

                    $estado,

                    Carbon::parse($item->created_at)
                        ->format('d/m/Y'),

                    'https://programa.soporte-pnte.com/admin/actividades-ugo/eventos-inscritos/' . $item->slug,

                    'https://inscripcion.soporte-pnte.com/actividades-ugo/' . $item->slug,

                    $registradoPor,

                    $estadoActividad,
                ];

                $col = 'A';

                foreach ($row as $value) {

                    $sheet->setCellValue(
                        $col . ($startRow + $index),
                        $value
                    );

                    $col++;
                }
            }

            return new StreamedResponse(
                function () use ($spreadsheet) {

                    $writer = new Xlsx($spreadsheet);

                    $writer->save('php://output');
                },
                200,
                [
                    'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                    'Content-Disposition' =>
                    'attachment; filename="actividades_pnte_' .
                        now()->format('Ymd_His') .
                        '.xlsx"',
                ]
            );
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Ocurrió un error al generar el reporte',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function exportInscritosPorSlug($slug)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        // ─────────────────────────────────────────────
        // EVENTO
        // ─────────────────────────────────────────────
        $actividad = ActividadPnte::with([
            'tipoActividad:id,name',
            'nombreActividad:id,name',
            'regionRel:id,name',
            'provinciaRel:id,name',
            'distritoRel:id,name',
            'representante:id,name,lastname,middlename',
        ])
            ->where('slug', $slug)
            ->first();

        if (! $actividad) {
            return response()->json([
                'status' => 404,
                'message' => 'Actividad no encontrada',
            ], 404);
        }

        // ─────────────────────────────────────────────
        // QUERY INSCRITOS
        // ─────────────────────────────────────────────
        $query = EmpresarioActividad::with([
            'empresario' => function ($q) {
                $q->select([
                    'id',
                    'ruc',
                    'razon_social',
                    'nombre_comercial',
                    'sector_economico_id',
                    'rubro_id',
                    'actividad_comercial_nombre',
                    'region_id',
                    'provincia_id',
                    'distrito_id',
                    'direccion',
                    'pais_id',
                    'tipo_documento_id',
                    'numero_dni',
                    'apellido_paterno',
                    'apellido_materno',
                    'nombres',
                    'genero_id',
                    'discapacidad',
                    'celular',
                    'correo_electronico',
                ]);
            },

            'empresario.tipoDocumento:id,avr',
            'empresario.pais:id,name',
            'empresario.genero:id,avr',
            'empresario.region:id,name',
            'empresario.provincia:id,name',
            'empresario.distrito:id,name',

            // 🔥 ESTOS FALTABAN
            'empresario.sectorEconomico:id,name',
            'empresario.rubro:id,name',
        ])
            ->where('slug', $slug)
            ->orderByDesc('created_at');

        // ─────────────────────────────────────────────
        // TEMPLATE
        // ─────────────────────────────────────────────
        $templatePath = storage_path('app/plantillas/ugo_eventos_lista_registrados_template.xlsx');

        if (! file_exists($templatePath)) {
            return response()->json([
                'status' => 404,
                'message' => 'Plantilla no encontrada',
            ], 404);
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // ─────────────────────────────────────────────
        // START
        // ─────────────────────────────────────────────
        $row = 3;
        $index = 1;

        $query->chunk(1000, function ($items) use (
            &$row,
            &$index,
            $sheet,
            $actividad
        ) {

            foreach ($items as $item) {

                $e = $item->empresario;

                $col = 'D';

                // NRO
                $sheet->setCellValue("{$col}{$row}", $index++);
                $col++;

                // FECHAS
                $sheet->setCellValue(
                    "{$col}{$row}",
                    collect($actividad->fechas ?? [])
                        ->map(fn($f) => Carbon::parse($f)->format('d/m/Y'))
                        ->implode(' - ')
                );
                $col++;

                // TIPO ACTIVIDAD
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $actividad->tipoActividad?->name
                );
                $col++;

                // NOMBRE ACTIVIDAD
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $actividad->nombreActividad?->name
                );
                $col++;

                // TEMA
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($actividad->tema ?? '', 'UTF-8')
                );
                $col++;

                // REGION
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $actividad->regionRel?->name
                );
                $col++;

                // PROVINCIA
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $actividad->provinciaRel?->name
                );
                $col++;

                // DISTRITO
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $actividad->distritoRel?->name
                );
                $col++;

                // LUGAR
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($actividad->lugar ?? '', 'UTF-8')
                );
                $col++;

                // REPRESENTANTE
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper(
                        trim(
                            ($actividad->representante?->name ?? '') . ' ' .
                                ($actividad->representante?->lastname ?? '') . ' ' .
                                ($actividad->representante?->middlename ?? '')
                        ),
                        'UTF-8'
                    )
                );
                $col++;

                // ─────────────────────────────────────────────
                // TIPO DOCUMENTO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->tipoDocumento?->avr
                );
                $col++;

                // ─────────────────────────────────────────────
                // NRO DOCUMENTO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->numero_dni
                );
                $col++;

                // ─────────────────────────────────────────────
                // PAIS
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->pais?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // APELLIDOS
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper(
                        trim(
                            ($e->apellido_paterno ?? '') . ' ' .
                                ($e->apellido_materno ?? '')
                        ),
                        'UTF-8'
                    )
                );
                $col++;

                // ─────────────────────────────────────────────
                // NOMBRES
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->nombres ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // GENERO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->genero?->avr
                );
                $col++;

                // ─────────────────────────────────────────────
                // DISCAPACIDAD
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->discapacidad ? 'SI' : 'NO'
                );
                $col++;

                // ─────────────────────────────────────────────
                // RUC
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->ruc
                );
                $col++;

                // ─────────────────────────────────────────────
                // REGION
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->region?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // PROVINCIA
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->provincia?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // DISTRITO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->distrito?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // SECTOR ECONOMICO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->sectorEconomico?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // RUBRO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->rubro?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // CELULAR
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->celular
                );
                $col++;

                // ─────────────────────────────────────────────
                // CORREO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->correo_electronico
                );

                $row++;
            }
        });

        // ─────────────────────────────────────────────
        // DOWNLOAD
        // ─────────────────────────────────────────────
        return new StreamedResponse(function () use ($spreadsheet) {

            $writer = new Xlsx($spreadsheet);

            // 🔥 mejora rendimiento
            $writer->setPreCalculateFormulas(false);

            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="lista-inscritos-ugo.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportFortaleceTuMercado($slug)
    {

        $attendance = Attendance::where('slug', $slug)->first();

        if (! $attendance) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $asesor = People::find($attendance->people_id);
        $region = City::find($attendance->city_id);
        $province = Province::find($attendance->province_id);
        $district = District::find($attendance->district_id);

        $query = AttendanceList::with([
            'typedocument:id,name',
            'gender:id,name,avr',
            'economicsector:id,name',
            // 'comercialactivity:id,name',
            'list',
        ])->where('attendancelist_id', $attendance->id)
            ->orderBy('created_at', 'desc');

        $data = $query->get();

        $result = $data->map(function ($item, $index) use ($attendance, $region, $province, $district) {
            return [
                'index' => $index + 1,
                'fechaCapacitacion' => Carbon::parse($item->startDate)->format('d/m/Y'),
                'name' => mb_strtoupper($item->name, 'UTF-8'),
                'lastName' => mb_strtoupper($item->lastname, 'UTF-8'),
                'middleName' => mb_strtoupper($item->middlename, 'UTF-8'),
                'documentType' => $item->typedocument->name,
                'numberDocument' => $item->documentnumber,
                'gender' => mb_strtoupper($item->gender->avr, 'UTF-8'),
                'email' => $item->email ? $item->email : '-',
                'phone' => $item->phone ?? '-',
                'ruc' => $item->ruc ?? '-',
                'region' => $region->name,
                'provincia' => $province->name,
                'distrito' => $district->name,
                'comercialActivity' => $item->comercialActivity ?? '-',            // rubro *
                'tema' => $item->list->title,                                      // Tema de la capacitación *
                'place' => $attendance->address ?? '-',
                'mercadoPertenece' => $item->mercado ?? '-',
            ];
        });

        // return Excel::download(new AttendanceListSlugExport($result), 'attendance.xlsx');

        $templatePath = storage_path('app/plantillas/fortalece_tu_mercado_template.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $startRow = 2;
        foreach ($result as $i => $resultRow) {
            $col = 'A';
            foreach ($resultRow as $value) {
                $sheet->setCellValue("{$col}" . ($startRow + $i), $value);
                $col++;
            }
        }

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="lista-registrados.xlsx"',
        ]);
    }

    // descargamos de acuerdo al tipo de evento que queremos 1,2,3,4,5
    public function exportAttendanceByComponentsId($eventsoffice_id)
    {
        // Buscar todos los Attendance que pertenecen al eventsoffice_id
        $attendances = Attendance::where('eventsoffice_id', $eventsoffice_id)
            ->orderBy('startDate', 'desc') // o 'created_at', según lo que prefieras
            ->get();

        if ($attendances->isEmpty()) {
            return response()->json(['message' => 'No se encontraron actividades para este eventsoffice.'], 404);
        }

        $data = collect();

        foreach ($attendances as $attendance) {
            $asesor = People::find($attendance->people_id);
            $region = City::find($attendance->city_id);
            $province = Province::find($attendance->province_id);
            $district = District::find($attendance->district_id);

            $lists = AttendanceList::with([
                'typedocument:id,name',
                'gender:id,name,avr',
                'economicsector:id,name',
                'list',
            ])
                ->where('attendancelist_id', $attendance->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $transformed = $lists->map(function ($item, $index) use ($attendance, $asesor, $region, $province, $district, $data) {
                return [
                    'index' => $data->count() + $index + 1,
                    'nameActividad' => $attendance->title,
                    'dateActividad' => Carbon::parse($attendance->startDate)->format('d/m/Y') . ' - ' . Carbon::parse($attendance->endDate)->format('d/m/Y'),
                    'asesor' => $asesor ? "{$asesor->name} {$asesor->lastname} {$asesor->middlename}" : null,
                    'region' => $region->name ?? '-',
                    'provincia' => $province->name ?? '-',
                    'distrito' => $district->name ?? '-',
                    'place' => $attendance->address ?? null,
                    'lastname' => $item->lastname . ' ' . $item->middlename,
                    'name' => $item->name,
                    'typedocument' => $item->typedocument->name ?? '-',
                    'documentnumber' => $item->documentnumber,
                    'email' => $item->email,
                    'phone' => $item->phone,
                    'gender' => $item->gender->avr ?? '-',
                    'sick' => $item->sick,
                    'ruc' => $item->ruc ?? '-',
                    'economicsector' => $item->economicsector->name ?? '-',
                    'comercialActivity' => $item->comercialActivity ?? '-',
                ];
            });

            $data = $data->concat($transformed);
        }

        // Cargar plantilla
        $templatePath = storage_path('app/plantillas/ugo_eventos_lista_registrados_template.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Llenar Excel
        $startRow = 2;
        foreach ($data as $i => $row) {
            $col = 'A';
            foreach ($row as $value) {
                $sheet->setCellValue("{$col}" . ($startRow + $i), $value);
                $col++;
            }
        }

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="lista-registrados.xlsx"',
        ]);
    }

    // DESCARGAR TODAS LAS ACTIVIDADES CON LOS INSCRITOS
    public function exportInscritos(Request $request)
    {
        try {
            ini_set('memory_limit', '2G');
            set_time_limit(600);

            $user = Auth::user();

            // ✅ Solo rol 1 puede descargar
            if ($user->rol != 1) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No tienes permisos para descargar este reporte.',
                ], 403);
            }

            // ✅ Helper para limpiar saltos de línea y tabs
            $clean = fn($value) => is_string($value)
                ? str_replace(["\r\n", "\r", "\n", "\t"], ' ', trim($value))
                : $value;

            $actividades = ActividadPnte::with([
                'tipoActividad:id,name',
                'nombreActividad:id,name',
                'regionRel:id,name',
                'provinciaRel:id,name',
                'distritoRel:id,name',
                'representante:id,name,lastname,middlename',
                'modalidad:id,name',
                'registradoPor:id,name,lastname,middlename',
            ])
                ->select([
                    'id',
                    'unidad',
                    'slug',
                    'fechas',
                    'cantidad_dias',
                    'tema',
                    'lugar',
                    'tipo_actividad_id',
                    'nombre_actividad_id',
                    'modalidad_id',
                    'region',
                    'provincia',
                    'distrito',
                    'representante_id',
                    'entidad_organizadora',
                    'entidad_aliada',
                    'requiere_pasaje',
                    'monto_gasto',
                    'mypes_beneficiadas',
                    'total_participantes',
                    'total_asesorias',
                    'total_formalizaciones',
                    'cancelado',
                    'reprogramado',
                    'registrado_por_id',
                    'created_at',
                ])
                ->addSelect([
                    'inscritos' => EmpresarioActividad::selectRaw('COUNT(*)')
                        ->whereColumn('empresario_actividad.slug', 'actividades_pnte.slug'),
                ])

                // ✅ FILTRO UNIDAD
                ->when(
                    $request->filled('unidad'),
                    fn($q) => $q->where('unidad', $request->input('unidad'))
                )

                ->when(
                    $request->filled('year'),
                    fn($q) => $q->where('fechas', 'LIKE', "%{$request->input('year')}%")
                )

                ->when($request->filled('rangeDate'), function ($q) use ($request) {

                    [$from, $to] = $request->input('rangeDate');

                    $current = Carbon::parse($from);
                    $end = Carbon::parse($to);

                    $q->where(function ($query) use ($current, $end) {

                        while ($current->lte($end)) {

                            $query->orWhere(
                                'fechas',
                                'LIKE',
                                "%{$current->format('Y-m-d')}%"
                            );

                            $current->addDay();
                        }
                    });
                })

                ->when(
                    $request->filled('city'),
                    fn($q) => $q->where('region', $request->input('city'))
                )

                ->when(
                    $request->filled('tipo_actividad_id'),
                    fn($q) => $q->where(
                        'tipo_actividad_id',
                        $request->input('tipo_actividad_id')
                    )
                )

                ->when(
                    $request->filled('asesor'),
                    fn($q) => $q->where(
                        'representante_id',
                        $request->input('asesor')
                    )
                )

                ->get()

                ->sortByDesc(function ($a) {

                    $fechas = is_array($a->fechas)
                        ? $a->fechas
                        : json_decode($a->fechas, true);

                    return collect($fechas)->max();
                })

                ->values();

            $slugs = $actividades->pluck('slug')->toArray();

            $inscritosPorSlug = EmpresarioActividad::with([
                'empresario:id,ruc,razon_social,apellido_paterno,apellido_materno,nombres,numero_dni,celular,correo_electronico,genero_id,discapacidad,sector_economico_id,rubro_id,region_id,provincia_id,distrito_id,pais_id,tipo_documento_id',
                'empresario.genero:id,name',
                'empresario.tipoDocumento:id,name',
                'empresario.pais:id,name',
                'empresario.region:id,name',
                'empresario.provincia:id,name',
                'empresario.distrito:id,name',
                'empresario.sectorEconomico:id,name',
                'empresario.rubro:id,name',
            ])
                ->whereIn('slug', $slugs)
                ->orderBy('slug')
                ->orderBy('id')
                ->get()
                ->groupBy('slug');

            // ✅ CSV en memoria con BOM UTF-8
            $handle = fopen('php://temp', 'r+');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // ✅ Cabecera
            fputcsv($handle, [
                'Nro.',
                'UNIDAD',
                'MES   (autollenado)',
                'FECHA DE INICIO',
                'FECHA DE FIN',
                'CANTIDAD DE DIAS DE LA ACTIVIDAD   (autollenado)',
                'TIPO DE ACTIVIDAD',
                'NOMBRE DE ACTIVIDAD',
                'TEMA',
                'REGION DE LA ACTIVIDAD',
                'PROVINCIA DE LA ACTIVIDAD',
                'DISTRITO DE LA ACTIVIDAD',
                'LUGAR DE LA ACTIVIDAD',
                'NOMBRE DE ENTIDAD ORGANIZADORA',
                'NOMBRE DE ENTIDAD O INSTITUCIÓN ALIADA / PARTICIPANTE',
                'REPRESENTANTE DE PRODUCE QUE PARTICIPA (APELLIDOS Y NOMBRES)',
                '¿REQUERIRA PASAJES?  (SÍ / NO)',
                'COLOCAR SOLO EL MONTO DE GASTOS EN PASAJES EN SOLES IDA + VUELTA (BUS Y/O AVION)',
                'MYPE Y/O EMPRENDEDORES BENEFICIADOS ESPERADOS',
                'MODALIDAD   (VIRTUAL / PRESENCIAL)',
                'ESTADO DE LISTA',
                'TOTAL DE INSCRITOS',
                'TOTAL ASESORIAS (UGO)',
                'TOTAL FORMALIZACIONES (UGO)',
                'ESTADO',
                'FECHA CREADA LA ACTIVIDAD',
                'LINK DE INSCRITOS A LA ACTIVIDAD',
                'LINK DE FORMULARIO DE REGISTRO',
                'REGISTRADO POR',
                'ESTADO DE LA ACTIVIDAD EN CURSO / CANCELADO / REPROGRAMADO',
                'TIPO DE DOCUMENTO (DNI, CE, CPP, PS)',
                'NRO. DE DOCUMENTO  (NO DEJAR EN BLANCO NI COLOCAR GUION)',
                'PAIS DE PROCEDENCIA',
                'APELLIDOS',
                'NOMBRES',
                'GENERO  F / M',
                'DISCAPACIDAD   SI / NO',
                'RUC',
                'REGIÓN_MYPE',
                'PROVINCIA_MYPE',
                'DISTRITO_MYPE',
                'SECTOR (COMERCIO, SERVICIOS O INDUSTRIA)',
                'RUBRO / RUBRO',
                'CELULAR',
                'CORREO',
                '¿SE BRINDÓ ASESORÍA AL USUARIO DURANTE LA ACTIVIDAD?  SI / NO',
                '¿SE FORMALIZO AL USUARIO DURANTE LA ACTIVIDAD?  SI / NO',
            ], ',');

            $globalIndex = 1;

            $today = Carbon::today();

            foreach ($actividades as $actividad) {

                $fechas = is_array($actividad->fechas)
                    ? $actividad->fechas
                    : json_decode($actividad->fechas, true);

                $fechaMin = collect($fechas)->min();
                $fechaMax = collect($fechas)->max();

                // ── ESTADO ───────────────────────────────────────────
                if ($actividad->inscritos > 0) {

                    $estado = '4. FINALIZADOS';
                } elseif (Carbon::parse($fechaMax)->lt($today)) {

                    $estado = '3. PENDIENTE DE RESULTADOS';
                } elseif (Carbon::parse($actividad->created_at)->isToday()) {

                    $estado = '1. PROGRAMACION DIARIA';
                } else {

                    $estado = '2. PROGRAMACION CONSOLIDADA';
                }

                $estadoActividad = $actividad->cancelado
                    ? 'CANCELADO'
                    : ($actividad->reprogramado ? 'REPROGRAMADO' : 'EN CURSO');

                $representante = $actividad->representante
                    ? strtoupper(
                        $actividad->representante->lastname . ' ' .
                            $actividad->representante->middlename . ', ' .
                            $actividad->representante->name
                    )
                    : null;

                $registradoPor = $actividad->registradoPor
                    ? strtoupper(
                        $actividad->registradoPor->name . ' ' .
                            $actividad->registradoPor->lastname . ' ' .
                            $actividad->registradoPor->middlename
                    )
                    : null;

                // ✅ TEXTO UNIDAD
                $unidadTexto = match ((int)$actividad->unidad) {
                    1 => 'UGO',
                    2 => 'UPP',
                    3 => 'UGSE',
                    default => 'SIN UNIDAD',
                };

                // ── COLUMNAS FIJAS DE LA ACTIVIDAD ───────────────────
                $colsActividad = [
                    $globalIndex,
                    $unidadTexto,
                    $clean(strtoupper(Carbon::parse($fechaMin)->translatedFormat('F'))),
                    Carbon::parse($fechaMin)->format('d/m/Y'),
                    Carbon::parse($fechaMax)->format('d/m/Y'),
                    $actividad->cantidad_dias,
                    $clean($actividad->tipoActividad->name ?? '-'),
                    $clean($actividad->nombreActividad->name ?? '-'),
                    $clean(strtoupper($actividad->tema ?? '-')),
                    $clean($actividad->regionRel->name ?? null),
                    $clean($actividad->provinciaRel->name ?? null),
                    $clean($actividad->distritoRel->name ?? null),
                    $clean($actividad->lugar ?? null),
                    $clean(strtoupper($actividad->entidad_organizadora ?? '-')),
                    $clean(strtoupper($actividad->entidad_aliada ?? '-')),
                    $clean($representante),
                    $actividad->requiere_pasaje ? 'SÍ' : 'NO',
                    $actividad->monto_gasto ?? 0,
                    $actividad->mypes_beneficiadas ?? 0,
                    $clean($actividad->modalidad->name ?? null),
                    $actividad->inscritos > 0 ? 'CON LISTA' : 'SIN LISTA',
                    $actividad->inscritos ?? 0,
                    $actividad->total_asesorias ?? 0,
                    $actividad->total_formalizaciones ?? 0,
                    $clean($estado),
                    Carbon::parse($actividad->created_at)->format('d/m/Y'),
                    'https://programa.soporte-pnte.com/admin/actividades-ugo/eventos-inscritos/' . $actividad->slug,
                    'https://inscripcion.soporte-pnte.com/actividades-ugo/' . $actividad->slug,
                    $clean($registradoPor),
                    $clean($estadoActividad),
                ];

                $inscritos = $inscritosPorSlug->get(
                    $actividad->slug,
                    collect()
                );

                if ($inscritos->isEmpty()) {

                    fputcsv(
                        $handle,
                        array_merge($colsActividad, array_fill(0, 17, null)),
                        ','
                    );
                } else {

                    foreach ($inscritos as $registro) {

                        $emp = $registro->empresario;

                        $apellidos = $emp
                            ? strtoupper(
                                trim(
                                    $emp->apellido_paterno . ' ' .
                                        $emp->apellido_materno
                                )
                            )
                            : null;

                        $colsParticipante = [
                            $clean($emp?->tipoDocumento?->name ?? null),
                            $clean($registro->numero_dni ?? null),
                            $clean($emp?->pais?->name ?? null),
                            $clean($apellidos),
                            $clean(strtoupper($emp?->nombres ?? '')),
                            $clean(strtoupper($emp?->genero?->name ?? '')),
                            $emp?->discapacidad ? 'SI' : 'NO',
                            $clean($emp?->ruc ?? null),
                            $clean($emp?->region?->name ?? null),
                            $clean($emp?->provincia?->name ?? null),
                            $clean($emp?->distrito?->name ?? null),
                            $clean($emp?->sectorEconomico?->name ?? null),
                            $clean($emp?->rubro?->name ?? null),
                            $clean($emp?->celular ?? null),
                            $clean($emp?->correo_electronico ?? null),
                            $registro->personal_asesoria ? 'SI' : 'NO',
                            $registro->personal_formalizacion ? 'SI' : 'NO',
                        ];

                        fputcsv(
                            $handle,
                            array_merge($colsActividad, $colsParticipante),
                            ','
                        );
                    }
                }

                $globalIndex++;
            }

            rewind($handle);

            $csvContent = stream_get_contents($handle);

            fclose($handle);

            $filename = 'inscritos_ugo_' . now()->format('Ymd_His') . '.csv';

            return response($csvContent, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($csvContent),
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Error al generar el reporte.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // todos los inscritos de mujer produce

    public function exportInscritosMujerProduce(Request $request)
    {
        $year      = $request->input('year');
        $startDate = $request->input('startDate');
        $endDate   = $request->input('endDate');

        $filename = 'inscritos_mujer_produce_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'              => 'text/csv',
            'Content-Disposition'       => "attachment; filename=\"{$filename}\"",
            'Cache-Control'             => 'no-store, no-cache',
            'X-Accel-Buffering'         => 'no',
        ];

        $callback = function () use ($year, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');

            // BOM para Excel (UTF-8)
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Cabeceras del CSV
            fputcsv($handle, [
                // Evento
                '#',
                'Título del Evento',
                'Fecha del Evento',
                'Modalidad',
                'Ciudad del Evento',
                'Capacitador',

                // Participante
                'RUC',
                'RAZÓN SOCIAL',
                'SECTOR ECONÓMICO',
                'RUBRO',
                'ACTIVIDAD COMERCIAL',
                'REGIÓN',
                'PROVINCIA',
                'DISTRITO',

                'TIPO DOCUMENTO',
                'N°. DOCUMENTO',
                'APELLIDO PATERNO',
                'APELLIDO MATERNO',
                'NOMBRES',
                'PAÍS NACIMIENTO',
                'FECHA DE NACIMIENTO',
                'GÉNERO',
                '¿TIENE UNA DISCAPACIDAD?',
                'GRADO ACADÉMICO',
                'ESTADO CIVIL',
                'CANTIDAD HIJOS',
                'NÚMERO DE CELULAR',
                'CORREO ELECTRÓNICO',
                'ROL EN EMPRESA',

                // Asistencia
                'Asistencia',
            ], ',');

            // Query base sobre MPAttendance (tabla pivote)
            $query = MPAttendance::query()
                ->join('mp_eventos as e', 'e.id', '=', 'mp_asistencias.event_id')
                ->join('mp_participantes as p', 'p.id', '=', 'mp_asistencias.participant_id')
                // Joins opcionales para lookups
                ->leftJoin('modalities as mod',       'mod.id',  '=', 'e.modality_id')
                ->leftJoin('cities as ce',            'ce.id',   '=', 'e.city_id')
                ->leftJoin('mp_capacitadores as cap', 'cap.id',  '=', 'e.capacitador_id')
                ->leftJoin('typedocuments as td',    'td.id',   '=', 'p.t_doc_id')
                ->leftJoin('genders as g',            'g.id',    '=', 'p.gender_id')
                ->leftJoin('activities as ac',       'ac.id',    '=', 'p.comercial_activity_id')
                ->leftJoin('countries as co',       'co.id',     '=', 'p.country_id')
                ->leftJoin('cities as cp',            'cp.id',   '=', 'p.city_id')
                ->leftJoin('provinces as prov',       'prov.id', '=', 'p.province_id')
                ->leftJoin('districts as dist',       'dist.id', '=', 'p.district_id')
                ->leftJoin('economicsectors as es',  'es.id',   '=', 'p.economic_sector_id')
                ->leftJoin('categories as cat',       'cat.id',  '=', 'p.rubro_id')
                ->leftJoin('academicdegree as ad',  'ad.id',   '=', 'p.academicdegree_id')
                ->leftJoin('civilstatus as cs',    'cs.id',   '=', 'p.civil_status_id')
                ->leftJoin('role_company as rc',    'rc.id',   '=', 'p.role_company_id')
                ->select([
                    // Evento
                    'e.id          as event_id',
                    'e.title       as event_title',
                    'e.date        as event_date',
                    'mod.name      as modality',
                    'ce.name       as event_city',
                    'cap.name      as capacitador',
                    // Participante
                    'p.id          as participant_id',
                    'p.ruc',
                    'ac.name       as activities',
                    'co.name       as countries',
                    'p.social_reason',
                    'p.names',
                    'p.last_name',
                    'p.middle_name',
                    'p.sick',
                    'p.num_soons',
                    'td.name       as type_document',
                    'p.doc_number',
                    'g.name        as gender',
                    'p.date_of_birth',
                    'p.phone',
                    'p.email',
                    'cp.name       as participant_city',
                    'prov.name     as province',
                    'dist.name     as district',
                    'es.name       as economic_sector',
                    'cat.name      as rubro',
                    'ad.name       as academic_degree',
                    'cs.name       as civil_status',
                    'rc.name       as role_company',
                    // Asistencia
                    'mp_asistencias.attendance',
                ]);

            // Filtros de fecha sobre el evento
            if ($startDate && $endDate) {
                $query->whereBetween('e.date', [$startDate, $endDate]);
            }

            if ($year) {
                $query->whereYear('e.date', $year);
            }

            // Soft-delete: excluir eventos eliminados
            $query->whereNull('e.deleted_at');

            $index = 1;
            $lastEventId = null;

            // Chunk para no reventar la memoria
            $query->orderBy('e.date', 'desc')->orderBy('e.id')->orderBy('p.id')
                ->chunk(500, function ($rows) use ($handle, &$index, &$lastEventId) {
                    foreach ($rows as $row) {

                        // Solo incrementa cuando cambia el evento
                        if ($lastEventId !== null && $lastEventId !== $row->event_id) {
                            $index++;
                        }
                        $lastEventId = $row->event_id;

                        fputcsv($handle, [
                            $index,
                            $row->event_title,
                            $row->event_date,
                            $row->modality,
                            $row->event_city,
                            $row->capacitador,

                            $row->ruc,
                            $row->social_reason,
                            $row->economic_sector,
                            $row->rubro,
                            $row->activities,
                            $row->participant_city,
                            $row->province,
                            $row->district,

                            $row->type_document,
                            $row->doc_number,
                            $row->last_name,
                            $row->middle_name,
                            $row->names,
                            $row->countries,
                            $row->date_of_birth,
                            $row->gender,
                            $row->sick,
                            $row->academic_degree,
                            $row->civil_status,
                            $row->num_soons,
                            $row->phone,
                            $row->email,
                            $row->role_company,
                            $row->attendance == 1 ? '✔️' : '-',
                        ], ',');
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }




    // SED 2026 ********************************************************

    public function exportInscritosPorSlugSed($slug)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        // ─────────────────────────────────────────────
        // EVENTO / ACTIVIDAD
        // ─────────────────────────────────────────────
        $actividad = ActividadPnte::with([
            'tipoActividad:id,name',
            'nombreActividad:id,name',
            'regionRel:id,name',
            'provinciaRel:id,name',
            'distritoRel:id,name',
            'representante:id,name,lastname,middlename',
        ])
            ->where('slug', $slug)
            ->first();

        if (!$actividad) {
            return response()->json([
                'status'  => 404,
                'message' => 'Actividad no encontrada',
            ], 404);
        }

        // ─────────────────────────────────────────────
        // MAPEO ESTÁTICO DE PREGUNTAS FIJAS (question_1 a question_5)
        // ─────────────────────────────────────────────
        $fixedQuestionsMap = [
            'question_1' => [
                'label' => '¿Cómo planificas el crecimiento de tu negocio usando tecnología?',
                'options' => [
                    'sin_interes'          => 'A. No tengo interés en la tecnología; mi negocio depende solo de mi presencia física y el boca a boca.',
                    'redes_sociales'       => 'B. Uso Facebook o WhatsApp porque otros lo hacen, pero no tengo un plan ni metas de ventas digitales.',
                    'estrategia_digital'   => 'C. Tengo una estrategia digital clara y uso datos de mis ventas pasadas para decidir qué comprar o vender.',
                    'plan_transformacion'  => 'D. Tengo un Plan de Transformación Digital escrito y mi modelo de negocio se adapta rápidamente a los cambios del mercado tecnológico.',
                ]
            ],
            'question_2' => [
                'label' => '¿Cómo se involucra tu equipo o personal en el uso de herramientas digitales?',
                'options' => [
                    'sin_interes'       => 'A. Solo yo tomo las decisiones y no usamos herramientas digitales para coordinar el trabajo.',
                    'redes_sociales'    => 'B. Mis empleados usan sus WhatsApp personales para atender clientes, pero no han recibido capacitación en herramientas de gestión.',
                    'capacitacion'      => 'C. Capacito a mi equipo en el uso de herramientas digitales y todos usamos un sistema común para registrar pedidos y tareas.',
                    'lideres_digitales' => 'D. Contamos con líderes digitales en el equipo, todos tienen altas competencias digitales y tomamos decisiones basadas en reportes de datos en tiempo real.',
                ]
            ],
            'question_3' => [
                'label' => '¿Con qué herramientas tecnológicas y seguridad cuenta tu negocio para operar?',
                'options' => [
                    'celular'                  => 'A. Solo tengo un celular básico para llamadas y no confío en los pagos digitales ni en internet.',
                    'internet_basico'          => 'B. Tengo internet básico y uso computadoras personales para tareas simples (Word/Excel básico) sin protocolos de seguridad.',
                    'internet_alta_velocidad'  => 'C. Tengo internet de alta velocidad, uso software con licencia y protejo mi información con contraseñas y respaldos frecuentes.',
                    'nube'                     => 'D. Uso servicios en la nube (Cloud), mi infraestructura está integrada y tengo sistemas de ciberseguridad para proteger los datos de mis clientes.',
                ]
            ],
            'question_4' => [
                'label' => '¿Cómo llevas el control de tus inventarios, producción y contabilidad?',
                'options' => [
                    'anotado'   => 'A. Todo lo anoto en cuadernos o lo tengo en la memoria; a veces pierdo el control de lo que falta.',
                    'excel'     => 'B. Registro mis ventas en Excel al final del día, pero mi inventario y contabilidad los llevo por separado o en físico.',
                    'software'  => 'C. Uso un software o App específica para controlar mi stock, mis ventas y emitir comprobantes electrónicos de forma automática.',
                    'integrado' => 'D. Mi sistema está totalmente integrado: me avisa automáticamente cuando queda poco stock y genera reportes contables y de producción sin errores.',
                ]
            ],
            'question_5' => [
                'label' => '¿Cómo te encuentran los clientes nuevos?',
                'options' => [
                    'local'     => 'A. Solo me encuentran si pasan por mi local; no guardo datos de contacto de quienes me compran.',
                    'excel'     => 'B. Respondo consultas por Facebook o WhatsApp, pero no tengo un catálogo digital ni analizo si los clientes están satisfechos.',
                    'software'  => 'C. Tengo presencia en Google Maps, uso catálogos digitales y acepto múltiples pagos (Yape, Plin, POS). Mido la satisfacción de mis clientes.',
                    'integrado' => 'D. Tengo una tienda online o CRM donde el cliente compra directamente y utilizo sus datos para enviarles ofertas personalizadas.',
                ]
            ]
        ];

        // ─────────────────────────────────────────────
        // PRE-CARGAR SedQuestions indexadas por documentnumber
        // ─────────────────────────────────────────────
        $sedQuestions = SedQuestion::with('propagandaMedia:id,name')
            ->where('slug', $slug)
            ->get()
            ->keyBy('documentnumber');

        // ─────────────────────────────────────────────
        // PRE-CARGAR SedQuestionAnswers agrupadas por DNI
        // ─────────────────────────────────────────────
        $sedAnswers = sedQuestionAnswer::where('slug_sed', $slug)
            ->orderBy('dni')
            ->get()
            ->groupBy('dni');

        // ─────────────────────────────────────────────
        // CARGAR Questions con opciones
        // ─────────────────────────────────────────────
        $questions = Question::with(['options' => function ($q) {
            $q->orderBy('position');
        }])
            ->orderBy('position')
            ->get()
            ->keyBy('model');

        // ─────────────────────────────────────────────
        // COLUMNAS DINÁMICAS
        // ─────────────────────────────────────────────
        $dynamicColumns = sedQuestionAnswer::where('slug_sed', $slug)
            ->distinct()
            ->pluck('question')
            ->sortBy(function ($modelIdentifier) use ($questions) {
                $normalized = preg_replace('/^questions_/', 'question_', $modelIdentifier);
                return $questions->get($normalized)?->position ?? 9999;
            })
            ->values();

        // ─────────────────────────────────────────────
        // QUERY INSCRITOS
        // ─────────────────────────────────────────────
        $query = EmpresarioActividad::with([
            'empresario' => function ($q) {
                $q->select([
                    'id',
                    'ruc',
                    'razon_social',
                    'nombre_comercial',
                    'sector_economico_id',
                    'rubro_id',
                    'actividad_comercial_id',
                    'region_id',
                    'provincia_id',
                    'distrito_id',
                    'direccion',
                    'pais_id',
                    'tipo_documento_id',
                    'numero_dni',
                    'apellido_paterno',
                    'apellido_materno',
                    'nombres',
                    'genero_id',
                    'discapacidad',
                    'celular',
                    'correo_electronico',
                    'cargo_empresa_id',
                    'fecha_nacimiento',
                    'edad',
                ]);
            },
            'empresario.tipoDocumento:id,avr',
            'empresario.pais:id,name',
            'empresario.genero:id,avr',
            'empresario.region:id,name',
            'empresario.provincia:id,name',
            'empresario.distrito:id,name',
            'empresario.sectorEconomico:id,name',
            'empresario.rubro:id,name',
            'empresario.cargoEmpresa:id,name',
            'empresario.actividadComercial:id,name'
        ])
            ->where('slug', $slug)
            ->orderByDesc('created_at');

        // ─────────────────────────────────────────────
        // TEMPLATE EXCEL
        // ─────────────────────────────────────────────
        $templatePath = storage_path('app/plantillas/sed_lista_registrados_template.xlsx');

        if (!file_exists($templatePath)) {
            return response()->json([
                'status'  => 404,
                'message' => 'Plantilla no encontrada',
            ], 404);
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet       = $spreadsheet->getActiveSheet();

        // ─────────────────────────────────────────────
        // HEADERS EN FILA 2 (Fijos y Dinámicos)
        // ─────────────────────────────────────────────
        // Definición de las celdas asignadas a las 5 preguntas fijas de SedQuestion
        $sheet->setCellValue('AL2', $fixedQuestionsMap['question_1']['label']);
        $sheet->setCellValue('AM2', $fixedQuestionsMap['question_2']['label']);
        $sheet->setCellValue('AN2', $fixedQuestionsMap['question_3']['label']);
        $sheet->setCellValue('AO2', $fixedQuestionsMap['question_4']['label']);
        $sheet->setCellValue('AP2', $fixedQuestionsMap['question_5']['label']);

        // Habilitar ajuste de texto automático para las cabeceras fijas
        foreach (['AL2', 'AM2', 'AN2', 'AO2', 'AP2'] as $cell) {
            $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
        }

        // Configuración de las columnas dinámicas (comienzan a partir de AN)
        $firstDynamicCol = 'AN';
        $headerCol = $firstDynamicCol;
        foreach ($dynamicColumns as $modelIdentifier) {
            $normalizedModel = preg_replace('/^questions_/', 'question_', $modelIdentifier);
            $question        = $questions->get($normalizedModel);
            $headerLabel     = $question?->label ?? $modelIdentifier;
            $sheet->setCellValue("{$headerCol}2", $headerLabel);
            $headerCol++;
        }

        // ─────────────────────────────────────────────
        // CONTROL DE FILAS Y CHUNK PROCESAMIENTO
        // ─────────────────────────────────────────────
        $row   = 3;
        $index = 1;

        $query->chunk(1000, function ($items) use (
            &$row,
            &$index,
            $sheet,
            $actividad,
            $sedQuestions,
            $sedAnswers,
            $dynamicColumns,
            $questions,
            $firstDynamicCol,
            $fixedQuestionsMap
        ) {
            foreach ($items as $item) {
                $e  = $item->empresario;
                $sq = $sedQuestions->get($e->numero_dni);
                $col = 'D';

                // NRO
                $sheet->setCellValue("{$col}{$row}", $index++);
                $col++;

                // FECHAS
                $sheet->setCellValue(
                    "{$col}{$row}",
                    collect($actividad->fechas ?? [])
                        ->map(fn($f) => Carbon::parse($f)->format('d/m/Y'))
                        ->implode(' - ')
                );
                $col++;

                // TIPO ACTIVIDAD
                $sheet->setCellValue("{$col}{$row}", $actividad->tipoActividad?->name);
                $col++;

                // NOMBRE ACTIVIDAD
                $sheet->setCellValue("{$col}{$row}", $actividad->nombreActividad?->name);
                $col++;

                // TEMA
                $sheet->setCellValue("{$col}{$row}", mb_strtoupper($actividad->tema ?? '', 'UTF-8'));
                $col++;

                // REGION (actividad)
                $sheet->setCellValue("{$col}{$row}", $actividad->regionRel?->name);
                $col++;

                // PROVINCIA (actividad)
                $sheet->setCellValue("{$col}{$row}", $actividad->provinciaRel?->name);
                $col++;

                // DISTRITO (actividad)
                $sheet->setCellValue("{$col}{$row}", $actividad->distritoRel?->name);
                $col++;

                // LUGAR
                $sheet->setCellValue("{$col}{$row}", mb_strtoupper($actividad->lugar ?? '', 'UTF-8'));
                $col++;

                // REPRESENTANTE
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper(
                        trim(
                            ($actividad->representante?->name      ?? '') . ' ' .
                                ($actividad->representante?->lastname  ?? '') . ' ' .
                                ($actividad->representante?->middlename ?? '')
                        ),
                        'UTF-8'
                    )
                );
                $col++;

                // RUC
                $sheet->setCellValue("{$col}{$row}", $e->ruc);
                $col++;

                // Razon social 
                $sheet->setCellValue("{$col}{$row}", $e->razon_social);
                $col++;

                // nombre comercial
                $sheet->setCellValue("{$col}{$row}", mb_strtoupper($e->nombre_comercial?->name ?? '', 'UTF-8'));
                $col++;

                // SECTOR ECONOMICO
                $sheet->setCellValue("{$col}{$row}", mb_strtoupper($e->sectorEconomico?->name ?? '', 'UTF-8'));
                $col++;

                // RUBRO
                $sheet->setCellValue("{$col}{$row}", mb_strtoupper($e->rubro?->name ?? '', 'UTF-8'));
                $col++;

                // actividad comercial
                $sheet->setCellValue("{$col}{$row}", mb_strtoupper($e->actividadComercial?->name ?? '', 'UTF-8'));
                $col++;

                // REGION (empresario)
                $sheet->setCellValue("{$col}{$row}", mb_strtoupper($e->region?->name ?? '', 'UTF-8'));
                $col++;

                // PROVINCIA (empresario)
                $sheet->setCellValue("{$col}{$row}", mb_strtoupper($e->provincia?->name ?? '', 'UTF-8'));
                $col++;

                // DISTRITO (empresario)
                $sheet->setCellValue("{$col}{$row}", mb_strtoupper($e->distrito?->name ?? '', 'UTF-8'));
                $col++;

                // dirección
                $sheet->setCellValue("{$col}{$row}", mb_strtoupper($e->direccion ?? '', 'UTF-8'));
                $col++;

                // *****************

                // TIPO DOCUMENTO
                $sheet->setCellValue("{$col}{$row}", $e->tipoDocumento?->avr);
                $col++;

                // NRO DOCUMENTO
                $sheet->setCellValue("{$col}{$row}", $e->numero_dni);
                $col++;

                // APELLIDO paterno
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper(trim(($e->apellido_paterno ?? '')), 'UTF-8')
                );
                $col++;

                // APELLIDO materno
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper(trim(($e->apellido_materno ?? '')), 'UTF-8')
                );
                $col++;

                // NOMBRES
                $sheet->setCellValue("{$col}{$row}", mb_strtoupper($e->nombres ?? '', 'UTF-8'));
                $col++;

                // GENERO
                $sheet->setCellValue("{$col}{$row}", $e->genero?->avr);
                $col++;

                // DISCAPACIDAD
                $sheet->setCellValue("{$col}{$row}", $e->discapacidad ? 'SI' : 'NO');
                $col++;

                // CELULAR
                $sheet->setCellValue("{$col}{$row}", $e->celular);
                $col++;

                // CORREO
                $sheet->setCellValue("{$col}{$row}", $e->correo_electronico);
                $col++;

                // CARGO EN LA EMPRESA
                $sheet->setCellValue("{$col}{$row}", mb_strtoupper($e->cargoEmpresa?->name ?? '', 'UTF-8'));
                $col++;

                // FECHA NACIMIENTO
                $sheet->setCellValue("{$col}{$row}", $e->fecha_nacimiento ? Carbon::parse($e->fecha_nacimiento)->format('d/m/Y') : '');
                $col++;

                // EDAD
                $sheet->setCellValue("{$col}{$row}", $e->edad ?? '');
                $col++;

                // // PAIS
                // $sheet->setCellValue("{$col}{$row}", mb_strtoupper($e->pais?->name ?? '', 'UTF-8'));
                // $col++;

                // CÓMO SE ENTERÓ DEL EVENTO
                $sheet->setCellValue("{$col}{$row}", mb_strtoupper($sq?->propagandaMedia?->name ?? '', 'UTF-8'));
                $col++;

                // HORA REGISTRO
                $sheet->setCellValue("{$col}{$row}", $item->created_at ? Carbon::parse($item->created_at)->format('d/m/Y H:i:s') : '');
                $col++;

                // FECHA ASISTENCIA
                $sheet->setCellValue("{$col}{$row}", $item->fecha_asistencia ?: 'x');
                $col++;

                // ── CONTENIDO PREGUNTAS FIJAS (Traducción de Keys a Labels) ──
                $q1Val = $sq?->question_1;
                $sheet->setCellValue("{$col}{$row}", $fixedQuestionsMap['question_1']['options'][$q1Val] ?? $q1Val ?? '');
                $col++;

                $q2Val = $sq?->question_2;
                $sheet->setCellValue("{$col}{$row}", $fixedQuestionsMap['question_2']['options'][$q2Val] ?? $q2Val ?? '');
                $col++;

                $q3Val = $sq?->question_3;
                $sheet->setCellValue("{$col}{$row}", $fixedQuestionsMap['question_3']['options'][$q3Val] ?? $q3Val ?? '');
                $col++;

                $q4Val = $sq?->question_4;
                $sheet->setCellValue("{$col}{$row}", $fixedQuestionsMap['question_4']['options'][$q4Val] ?? $q4Val ?? '');
                $col++;

                $q5Val = $sq?->question_5;
                $sheet->setCellValue("{$col}{$row}", $fixedQuestionsMap['question_5']['options'][$q5Val] ?? $q5Val ?? '');
                $col++;

                // Aplicar ajuste de línea (Wrap Text) a las celdas del bloque fijo
                foreach (['AL', 'AM', 'AN', 'AO', 'AP'] as $c) {
                    $sheet->getStyle("{$c}{$row}")->getAlignment()->setWrapText(true);
                }

                // ─────────────────────────────────────────────
                // CONTENIDO PREGUNTAS DINÁMICAS
                // ─────────────────────────────────────────────
                $answersIndexed = $sedAnswers
                    ->get($e->numero_dni, collect())
                    ->keyBy('question');

                $dynCol = $firstDynamicCol;
                foreach ($dynamicColumns as $modelIdentifier) {
                    $normalizedModel = preg_replace('/^questions_/', 'question_', $modelIdentifier);

                    $question = $questions->get($normalizedModel);
                    $answer   = $answersIndexed->get($modelIdentifier);
                    $rawValue = $answer?->answer ?? null;

                    if ($rawValue === null || $rawValue === '') {
                        $cellValue = '';
                    } elseif ($question && in_array($question->type, ['radio', 'checkbox-multiple', 'select'])) {
                        $decoded        = json_decode($rawValue, true);
                        $selectedValues = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                            ? array_map('strval', $decoded)
                            : [strval($rawValue)];

                        $optionMap = $question->options
                            ->mapWithKeys(fn($opt) => [strval($opt->value) => $opt->label]);

                        $lines = [];
                        foreach ($selectedValues as $val) {
                            $label   = $optionMap->get(strval($val));
                            $lines[] = '- ' . ($label ?? $val);
                        }

                        $cellValue = implode("\n", $lines);
                    } else {
                        $cellValue = $rawValue;
                    }

                    $sheet->setCellValue("{$dynCol}{$row}", $cellValue);

                    if (str_contains($cellValue, "\n")) {
                        $sheet->getStyle("{$dynCol}{$row}")
                            ->getAlignment()
                            ->setWrapText(true);
                    }

                    $dynCol++;
                }

                $row++;
            }
        });

        // ─────────────────────────────────────────────
        // DESCARGA STREAMED RESPONSE
        // ─────────────────────────────────────────────
        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="lista-inscritos-ugo.xlsx"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }



    // PP093 ***********************************************************

    public function exportInscritosPorSlugPp093(Request $request, $slug)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        // ─────────────────────────────────────────────
        // EVENTO
        // ─────────────────────────────────────────────
        $actividad = ActividadPnte::with([
            'tipoActividad:id,name',
            'nombreActividad:id,name',
            'regionRel:id,name',
            'provinciaRel:id,name',
            'distritoRel:id,name',
            'representante:id,name,lastname,middlename',
        ])
            ->where('slug', $slug)
            ->first();

        if (! $actividad) {
            return response()->json([
                'status' => 404,
                'message' => 'Actividad no encontrada',
            ], 404);
        }

        // ─────────────────────────────────────────────
        // QUERY INSCRITOS
        // ─────────────────────────────────────────────
        $query = EmpresarioActividad::with([
            'empresario' => function ($q) {
                $q->select([
                    'id',
                    'ruc',
                    'razon_social',
                    'nombre_comercial',
                    'sector_economico_id',
                    'rubro_id',
                    'actividad_comercial_nombre',
                    'region_id',
                    'provincia_id',
                    'distrito_id',
                    'direccion',
                    'pais_id',
                    'tipo_documento_id',
                    'numero_dni',
                    'apellido_paterno',
                    'apellido_materno',
                    'nombres',
                    'genero_id',
                    'discapacidad',
                    'celular',
                    'correo_electronico',
                ]);
            },

            'empresario.tipoDocumento:id,avr',
            'empresario.pais:id,name',
            'empresario.genero:id,avr',
            'empresario.region:id,name',
            'empresario.provincia:id,name',
            'empresario.distrito:id,name',

            // 🔥 ESTOS FALTABAN
            'empresario.sectorEconomico:id,name',
            'empresario.rubro:id,name',
        ])
            ->where('slug', $slug)

            // ✅ FILTRO POR FECHA SELECCIONADA (CORREGIDO)
            ->when($request->filled('dateEvent'), function ($q) use ($request) {
                $q->where('fecha_seleccionada', $request->input('dateEvent'));
            })

            ->orderByDesc('created_at');

        // ─────────────────────────────────────────────
        // TEMPLATE
        // ─────────────────────────────────────────────
        $templatePath = storage_path('app/plantillas/ugo_eventos_lista_pp093.xlsx');

        if (! file_exists($templatePath)) {
            return response()->json([
                'status' => 404,
                'message' => 'Plantilla no encontrada',
            ], 404);
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // ─────────────────────────────────────────────
        // START
        // ─────────────────────────────────────────────
        $row = 3;
        $index = 1;

        $query->chunk(1000, function ($items) use (
            &$row,
            &$index,
            $sheet,
            $actividad
        ) {

            foreach ($items as $item) {

                $e = $item->empresario;

                $col = 'D';

                // NRO
                $sheet->setCellValue("{$col}{$row}", $index++);
                $col++;

                // FECHAS
                $sheet->setCellValue(
                    "{$col}{$row}",
                    collect($actividad->fechas ?? [])
                        ->map(fn($f) => Carbon::parse($f)->format('d/m/Y'))
                        ->implode(' - ')
                );
                $col++;

                // TIPO ACTIVIDAD
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $actividad->tipoActividad?->name
                );
                $col++;

                // NOMBRE ACTIVIDAD
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $actividad->nombreActividad?->name
                );
                $col++;

                // TEMA
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($actividad->tema ?? '', 'UTF-8')
                );
                $col++;

                // REGION
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $actividad->regionRel?->name
                );
                $col++;

                // PROVINCIA
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $actividad->provinciaRel?->name
                );
                $col++;

                // DISTRITO
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $actividad->distritoRel?->name
                );
                $col++;

                // LUGAR
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($actividad->lugar ?? '', 'UTF-8')
                );
                $col++;

                // REPRESENTANTE
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper(
                        trim(
                            ($actividad->representante?->name ?? '') . ' ' .
                                ($actividad->representante?->lastname ?? '') . ' ' .
                                ($actividad->representante?->middlename ?? '')
                        ),
                        'UTF-8'
                    )
                );
                $col++;

                // ─────────────────────────────────────────────
                // TIPO DOCUMENTO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->tipoDocumento?->avr
                );
                $col++;

                // ─────────────────────────────────────────────
                // NRO DOCUMENTO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->numero_dni
                );
                $col++;

                // ─────────────────────────────────────────────
                // PAIS
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->pais?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // APELLIDOS
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper(
                        trim(
                            ($e->apellido_paterno ?? '') . ' ' .
                                ($e->apellido_materno ?? '')
                        ),
                        'UTF-8'
                    )
                );
                $col++;

                // ─────────────────────────────────────────────
                // NOMBRES
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->nombres ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // GENERO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->genero?->avr
                );
                $col++;

                // ─────────────────────────────────────────────
                // DISCAPACIDAD
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->discapacidad ? 'SI' : 'NO'
                );
                $col++;

                // ─────────────────────────────────────────────
                // RUC
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->ruc
                );
                $col++;

                // ─────────────────────────────────────────────
                // REGION
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->region?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // PROVINCIA
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->provincia?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // DISTRITO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->distrito?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // SECTOR ECONOMICO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->sectorEconomico?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // RUBRO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->rubro?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // CELULAR
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->celular
                );
                $col++;

                // ─────────────────────────────────────────────
                // CORREO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->correo_electronico
                );

                $row++;
            }
        });

        // ─────────────────────────────────────────────
        // DOWNLOAD
        // ─────────────────────────────────────────────
        return new StreamedResponse(function () use ($spreadsheet) {

            $writer = new Xlsx($spreadsheet);

            // 🔥 mejora rendimiento
            $writer->setPreCalculateFormulas(false);

            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="lista-inscritos-ugo.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }


    // UGSC

    public function exportInscritosPorSlugUgsc(Request $request, $slug)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        // ─────────────────────────────────────────────
        // EVENTO
        // ─────────────────────────────────────────────
        $actividad = ActividadPnte::with([
            'tipoActividad:id,name',
            'nombreActividad:id,name',
            'regionRel:id,name',
            'provinciaRel:id,name',
            'distritoRel:id,name',
            'representante:id,name,lastname,middlename',
        ])
            ->where('slug', $slug)
            ->first();

        if (! $actividad) {
            return response()->json([
                'status' => 404,
                'message' => 'Actividad no encontrada',
            ], 404);
        }

        // ─────────────────────────────────────────────
        // QUERY INSCRITOS
        // ─────────────────────────────────────────────
        $query = EmpresarioActividad::with([
            'empresario' => function ($q) {
                $q->select([
                    'id',
                    'ruc',
                    'razon_social',
                    'nombre_comercial',
                    'sector_economico_id',
                    'rubro_id',
                    'actividad_comercial_nombre',
                    'region_id',
                    'provincia_id',
                    'distrito_id',
                    'direccion',
                    'pais_id',
                    'tipo_documento_id',
                    'numero_dni',
                    'apellido_paterno',
                    'apellido_materno',
                    'nombres',
                    'genero_id',
                    'discapacidad',
                    'celular',
                    'correo_electronico',

                    // NUEVOS
                    'coop_ruc',
                    'coop_razon_social',
                    'coop_rol',
                ]);
            },

            'empresario.tipoDocumento:id,avr',
            'empresario.pais:id,name',
            'empresario.genero:id,avr',
            'empresario.region:id,name',
            'empresario.provincia:id,name',
            'empresario.distrito:id,name',

            // 🔥 ESTOS FALTABAN
            'empresario.sectorEconomico:id,name',
            'empresario.rubro:id,name',
        ])
            ->where('slug', $slug)

            // ✅ FILTRO POR FECHA SELECCIONADA (CORREGIDO)
            ->when($request->filled('dateEvent'), function ($q) use ($request) {
                $q->where('fecha_seleccionada', $request->input('dateEvent'));
            })

            ->orderByDesc('created_at');

        // ─────────────────────────────────────────────
        // TEMPLATE
        // ─────────────────────────────────────────────
        $templatePath = storage_path('app/plantillas/plantilla_ugsc_download.xlsx');

        if (! file_exists($templatePath)) {
            return response()->json([
                'status' => 404,
                'message' => 'Plantilla no encontrada',
            ], 404);
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // ─────────────────────────────────────────────
        // START
        // ─────────────────────────────────────────────
        $row = 3;
        $index = 1;

        $roles = [
            'dir' => 'DIRIGENTE',
            'soc' => 'SOCIO',
            'mie' => 'MIEMBRO',
        ];

        $query->chunk(1000, function ($items) use (
            &$row,
            &$index,
            $sheet,
            $actividad,
            $roles
        ) {

            foreach ($items as $item) {

                $e = $item->empresario;

                $col = 'D';

                // NRO
                $sheet->setCellValue("{$col}{$row}", $index++);
                $col++;

                // FECHAS
                $sheet->setCellValue(
                    "{$col}{$row}",
                    collect($actividad->fechas ?? [])
                        ->map(fn($f) => Carbon::parse($f)->format('d/m/Y'))
                        ->implode(' - ')
                );
                $col++;

                // TIPO ACTIVIDAD
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $actividad->tipoActividad?->name
                );
                $col++;

                // NOMBRE ACTIVIDAD
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $actividad->nombreActividad?->name
                );
                $col++;

                // TEMA
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($actividad->tema ?? '', 'UTF-8')
                );
                $col++;

                // REGION
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $actividad->regionRel?->name
                );
                $col++;

                // PROVINCIA
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $actividad->provinciaRel?->name
                );
                $col++;

                // DISTRITO
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $actividad->distritoRel?->name
                );
                $col++;

                // LUGAR
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($actividad->lugar ?? '', 'UTF-8')
                );
                $col++;

                // REPRESENTANTE
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper(
                        trim(
                            ($actividad->representante?->name ?? '') . ' ' .
                                ($actividad->representante?->lastname ?? '') . ' ' .
                                ($actividad->representante?->middlename ?? '')
                        ),
                        'UTF-8'
                    )
                );
                $col++;

                // ─────────────────────────────────────────────
                // TIPO DOCUMENTO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->tipoDocumento?->avr
                );
                $col++;

                // ─────────────────────────────────────────────
                // NRO DOCUMENTO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->numero_dni
                );
                $col++;

                // ─────────────────────────────────────────────
                // PAIS
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->pais?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // APELLIDOS
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper(
                        trim(
                            ($e->apellido_paterno ?? '') . ' ' .
                                ($e->apellido_materno ?? '')
                        ),
                        'UTF-8'
                    )
                );
                $col++;

                // ─────────────────────────────────────────────
                // NOMBRES
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->nombres ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // GENERO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->genero?->avr
                );
                $col++;

                // ─────────────────────────────────────────────
                // DISCAPACIDAD
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->discapacidad ? 'SI' : 'NO'
                );
                $col++;

                // ─────────────────────────────────────────────
                // RUC
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->ruc
                );
                $col++;

                // ─────────────────────────────────────────────
                // REGION
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->region?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // PROVINCIA
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->provincia?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // DISTRITO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->distrito?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // SECTOR ECONOMICO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->sectorEconomico?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // RUBRO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->rubro?->name ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // CELULAR
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->celular
                );
                $col++;

                // ─────────────────────────────────────────────
                // CORREO
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->correo_electronico
                );
                $col++;

                // ─────────────────────────────────────────────
                // COOP RUC (AC)
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $e->coop_ruc
                );
                $col++;

                // ─────────────────────────────────────────────
                // COOP RAZON SOCIAL (AD)
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    mb_strtoupper($e->coop_razon_social ?? '', 'UTF-8')
                );
                $col++;

                // ─────────────────────────────────────────────
                // COOP ROL (AE)
                // ─────────────────────────────────────────────
                $sheet->setCellValue(
                    "{$col}{$row}",
                    $roles[$e->coop_rol] ?? ''
                );


                $row++;
            }
        });

        // ─────────────────────────────────────────────
        // DOWNLOAD
        // ─────────────────────────────────────────────
        return new StreamedResponse(function () use ($spreadsheet) {

            $writer = new Xlsx($spreadsheet);

            // 🔥 mejora rendimiento
            $writer->setPreCalculateFormulas(false);

            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="lista-inscritos-ugo.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
