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
use App\Models\People;
use App\Models\Province;
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
}
