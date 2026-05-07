<?php

namespace App\Http\Controllers\Download;

use App\Exports\AttendanceExport;
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
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Auth;

Carbon::setLocale('es');

class DownloadAttendanceController extends Controller
{
    public function exportAttendance(Request $request)
    {
        try {

            ini_set('memory_limit', '2G');
            set_time_limit(300);

            $filters = [
                'name'       => $request->input('name'),
                'asesor'     => $request->input('asesor'),
                'modalidad'  => $request->input('modalidad'),
                'year'       => $request->input('year'),
                'date'       => $request->input('date'),
                'rangeDate'  => $request->input('rangeDate') ?? [],
                'city'       => $request->input('city'),
                'province'   => $request->input('province'),
                'district'   => $request->input('district'),
                'status'     => $request->input('status'),
                'orderby'    => $request->input('orderby'),
            ];

            $user = Auth::user();

            // 🔒 si es asesor solo exporta sus eventos
            if ($user->rol == 2) {
                $filters['asesor'] = $user->id;
            }

            $query = Attendance::query();

            $query->withItems($filters)
                ->withCount('attendanceList'); // ⚡ evita consultas extra

            // 📄 Cargar plantilla Excel
            $templatePath = storage_path('app/plantillas/attendance_template.xlsx');
            $spreadsheet  = IOFactory::load($templatePath);
            $sheet        = $spreadsheet->getActiveSheet();

            $startRow    = 2;
            $rowIndex    = 0;
            $globalIndex = 1;

            // 🚀 Procesar por bloques
            $query->chunk(500, function ($rows) use (
                &$sheet,
                &$rowIndex,
                &$globalIndex,
                $startRow
            ) {

                foreach ($rows as $item) {

                    $estado = $item->getEstado();

                    $row = [

                        $globalIndex++,

                        'UGO',

                        strtoupper(Carbon::parse($item->startDate)->translatedFormat('F')),

                        Carbon::parse($item->startDate)->format('d/m/Y'),
                        Carbon::parse($item->endDate)->format('d/m/Y'),

                        Carbon::parse($item->startDate)
                            ->diffInDays(Carbon::parse($item->endDate)) + 1,

                        $item->pnte->name ?? '-',

                        strtoupper($item->title) ?? '-',

                        strtoupper($item->theme ?? '-') ?? '-',

                        $item->region->name ?? null,
                        $item->provincia->name ?? null,
                        $item->distrito->name ?? null,

                        $item->address ?? null,

                        strtoupper($item->entidad ?? '-') ?? '-',

                        strtoupper($item->entidad_aliada ?? '-') ?? '-',

                        $item->asesor
                            ? strtoupper(
                                $item->asesor->name . ' ' .
                                    $item->asesor->lastname . ' ' .
                                    $item->asesor->middlename
                            )
                            : null,

                        $item->pasaje == 'n'
                            ? 'NO'
                            : ($item->pasaje == 's' ? 'SI' : '-'),

                        $item->monto ?? 0,

                        $item->beneficiarios ?? null,

                        $item->modality == 'v'
                            ? 'VIRTUAL'
                            : 'PRESENCIAL',

                        $item->attendance_list_count > 0
                            ? 'CON LISTA'
                            : 'SIN LISTA',

                        $item->attendance_list_count ?? 0,

                        $item->total_asesorias ?? 0,

                        $item->total_formalizaciones ?? 0,

                        // null,
                        // null,
                        // null,
                        // null,
                        // null,
                        // null,
                        // null,

                        $estado,

                        Carbon::parse($item->created_at)->format('d/m/Y'),

                        'https://programa.soporte-pnte.com/admin/actividades-ugo/eventos-inscritos/' . $item->slug,

                        'https://inscripcion.soporte-pnte.com/actividades-ugo/' . $item->slug,

                        $item->registrador ? strtoupper($item->registrador->name . ' ' . $item->registrador->lastname . ' ' . $item->registrador->middlename) : null,

                    ];

                    $col = 'A';

                    foreach ($row as $value) {
                        $sheet->setCellValue($col . ($startRow + $rowIndex), $value);
                        $col++;
                    }

                    $rowIndex++;
                }
            });

            // 📥 Descargar archivo
            return new StreamedResponse(function () use ($spreadsheet) {

                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 200, [

                'Content-Type' =>
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                'Content-Disposition' =>
                'attachment; filename="attendance_template.xlsx"',

            ]);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Ocurrió un error al generar el reporte',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // Carbon::parse($item->startDate)->format('d/m/Y'),
    // Carbon::parse($item->endDate)->format('d/m/Y'),
    // Carbon::parse($item->startDate)
    //     ->diffInDays(Carbon::parse($item->endDate)) + 1,
    // $item->region->name ?? '-',
    // $item->provincia->name ?? '-',
    // $item->distrito->name ?? '-',
    // $item->address ?? '-',
    // $item->asesor
    //     ? strtoupper(
    //         $item->asesor->name . ' ' .
    //             $item->asesor->lastname . ' ' .
    //             $item->asesor->middlename
    //     )
    //     : '-',
    // $item->profile
    //     ? strtoupper(
    //         $item->profile->name . ' ' .
    //             $item->profile->lastname . ' ' .
    //             $item->profile->middlename
    //     )
    //     : '-',
    // $item->description ?? '-',
    // Carbon::parse($item->created_at)->format('d/m/Y'),
    // 'https://programa.soporte-pnte.com/admin/ugo/eventos-inscritos/' . $item->slug,
    // $item->eventsoffice_id == 3
    //     ? 'https://inscripcion.soporte-pnte.com/fortalece-tu-mercado/' . $item->slug
    //     : 'https://programa.soporte-pnte.com/asistencias/' . $item->slug,


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
            'representante:id,name,lastname,middlename'
        ])
            ->where('slug', $slug)
            ->first();

        if (!$actividad) {
            return response()->json([
                'status' => 404,
                'message' => 'Actividad no encontrada'
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
                    'correo_electronico'
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

        if (!file_exists($templatePath)) {
            return response()->json([
                'status' => 404,
                'message' => 'Plantilla no encontrada'
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

        if (!$attendance) {
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
            'list'
        ])->where('attendancelist_id', $attendance->id)
            ->orderBy('created_at', 'desc');

        $data = $query->get();

        $result = $data->map(function ($item, $index) use ($attendance, $asesor, $region, $province, $district) {
            return [
                'index' => $index + 1,
                'fechaCapacitacion'     => Carbon::parse($item->startDate)->format('d/m/Y'),
                'name'                  => mb_strtoupper($item->name, 'UTF-8'),
                'lastName'              => mb_strtoupper($item->lastname, 'UTF-8'),
                'middleName'            => mb_strtoupper($item->middlename, 'UTF-8'),
                'documentType'          => $item->typedocument->name,
                'numberDocument'        => $item->documentnumber,
                'gender'                => mb_strtoupper($item->gender->avr, 'UTF-8'),
                'email'                 => $item->email ? $item->email : '-',
                'phone'                 => $item->phone ?? '-',
                'ruc'                   => $item->ruc ?? '-',
                'region'                => $region->name,
                'provincia'             => $province->name,
                'distrito'              => $district->name,
                'comercialActivity'     => $item->comercialActivity ?? '-',            // rubro *
                'tema'                  => $item->list->title,                                      // Tema de la capacitación *
                'place'                 => $attendance->address ?? '-',
                'mercadoPertenece'      => $item->mercado ?? '-'
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
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
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
                'list'
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
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="lista-registrados.xlsx"',
        ]);
    }
}
