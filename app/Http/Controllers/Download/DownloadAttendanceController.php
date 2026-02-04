<?php

namespace App\Http\Controllers\Download;

use App\Exports\AttendanceExport;
use App\Exports\AttendanceListSlugExport;
// use App\Exports\AttendanceListExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceList;
use App\Models\City;
use App\Models\District;
use App\Models\People;
use App\Models\Province;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DownloadAttendanceController extends Controller
{

    public function exportAttendance(Request $request)
    {

        try {

            ini_set('memory_limit', '2G');
            set_time_limit(300);

            $filters = [
                'name'      => $request->input('name'),
                'dateStart' => $request->input('dateStart'),
                'dateEnd'   => $request->input('dateEnd'),
                'year'      => $request->input('year'),
                'orderby'   => $request->input('orderby'),
                'asesor'    => $request->input('asesor'),
            ];

            $query = Attendance::query();
            $query->withItems($filters);

            // 📄 Cargar plantilla Excel
            $templatePath = storage_path('app/plantillas/attendance_template.xlsx');
            $spreadsheet  = IOFactory::load($templatePath);
            $sheet        = $spreadsheet->getActiveSheet();

            $startRow   = 2;
            $rowIndex   = 0;
            $globalIndex = 1;

            // 🚀 Procesar por bloques
            $query->chunk(500, function ($rows) use (
                &$sheet,
                &$rowIndex,
                &$globalIndex,
                $startRow
            ) {
                foreach ($rows as $item) {

                    $row = [
                        $globalIndex++,
                        'UGO',
                        strtoupper(Carbon::now()->translatedFormat('F')),
                        Carbon::parse($item->startDate)->format('d/m/Y'),
                        Carbon::parse($item->endDate)->format('d/m/Y'),
                        Carbon::parse($item->startDate)->diffInDays(Carbon::parse($item->endDate)) + 1,
                        $item->pnte->name ?? '-',
                        strtoupper($item->title) ?? '-',
                        strtoupper($item->theme ?? null) ?? '-',
                        $item->modality == 'v' ? 'VIRTUAL' : 'PRESENCIAL',
                        $item->region->name ?? null,
                        $item->provincia->name ?? null,
                        $item->distrito->name ?? null,
                        $item->address ?? null,
                        strtoupper($item->entidad ?? null) ?? '-',
                        strtoupper($item->entidad_aliada ?? null) ?? '-',
                        $item->asesor ? strtoupper($item->asesor->name . ' ' . $item->asesor->lastname . ' ' . $item->asesor->middlename) : null,
                        $item->pasaje == 'n' ? 'NO' : ($item->pasaje == 's' ? 'SI' : null),
                        $item->monto ?? null,
                        $item->beneficiarios ?? null,
                        Carbon::parse($item->created_at)->format('d/m/Y'),
                        // $item->attendanceList?->count() ?? 0,
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
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="attendance_template.xlsx"',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al generar el reporte',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public function exportRegistrantsUgoEvents($slug)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        // ===== Buscar evento =====
        $attendance = Attendance::with([
            'asesor:id,name,lastname,middlename',
            'region:id,name',
            'provincia:id,name',
            'distrito:id,name',
            'pnte:id,name'
        ])->where('slug', $slug)->first();

        if (!$attendance) {
            return response()->json(['message' => 'Not found'], 404);
        }

        // ===== Query participantes =====
        $query = AttendanceList::with([
            'typedocument:id,avr',
            'gender:id,avr',
            'economicsector:id,name',
            'country:id,name',
            'city:id,name',
            'province:id,name',
            'dictrict:id,name',
            'rubro:id,name'
        ])
            ->where('attendancelist_id', $attendance->id)
            ->orderBy('created_at', 'desc');

        // ===== Cargar plantilla =====
        $templatePath = storage_path('app/plantillas/ugo_eventos_lista_registrados_template.xlsx');

        if (!file_exists($templatePath)) {
            return response()->json(['message' => 'Template not found'], 404);
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // ===== Fila inicial D3 =====
        $row = 3;
        $index = 1;

        $query->chunk(1000, function ($items) use (&$row, &$index, $sheet, $attendance) {

            foreach ($items as $item) {

                $col = 'D';

                $sheet->setCellValue("{$col}{$row}", $index++);
                $col++;

                $sheet->setCellValue("{$col}{$row}", $attendance->startDate ? Carbon::parse($attendance->startDate)->format('d/m/Y') : null);
                $col++;

                $sheet->setCellValue("{$col}{$row}", $attendance->endDate ? Carbon::parse($attendance->endDate)->format('d/m/Y') : null);
                $col++;

                $sheet->setCellValue("{$col}{$row}", $attendance->pnte->name);
                $col++;

                $sheet->setCellValue("{$col}{$row}", $attendance->title);
                $col++;

                $sheet->setCellValue("{$col}{$row}", mb_strtoupper($attendance->theme));
                $col++;

                $sheet->setCellValue("{$col}{$row}", $attendance->region?->name ?? '');
                $col++;

                $sheet->setCellValue("{$col}{$row}", $attendance->provincia?->name ?? '');
                $col++;

                $sheet->setCellValue("{$col}{$row}", $attendance->distrito?->name ?? '');
                $col++;

                $sheet->setCellValue("{$col}{$row}", mb_strtoupper($attendance->address ?? '', 'UTF-8'));
                $col++;

                $sheet->setCellValue("{$col}{$row}", mb_strtoupper($attendance->asesor->lastname . ' ' . $attendance->asesor->middlename . ' ' . $attendance->asesor->name) ?? '');
                $col++;

                $sheet->setCellValue("{$col}{$row}", $item->typedocument?->avr ?? '');
                $col++;

                $sheet->setCellValue("{$col}{$row}", $item->documentnumber ?? null);
                $col++;

                $sheet->setCellValue("{$col}{$row}", mb_strtoupper($item->country->name ?? null));
                $col++;

                $sheet->setCellValue("{$col}{$row}", mb_strtoupper(trim("{$item->lastname} {$item->middlename}"), 'UTF-8'));
                $col++;

                $sheet->setCellValue("{$col}{$row}", mb_strtoupper(trim("{$item->name}"), 'UTF-8'));
                $col++;

                $sheet->setCellValue("{$col}{$row}", $item->gender?->avr);
                $col++;

                $sheet->setCellValue("{$col}{$row}", mb_strtoupper(trim("{$item->sick}"), 'UTF-8'));
                $col++;

                $sheet->setCellValue("{$col}{$row}", $item->ruc ?? '-');
                $col++;

                $sheet->setCellValue("{$col}{$row}", $item->city?->name ?? '');
                $col++;

                $sheet->setCellValue("{$col}{$row}", $item->province?->name ?? '');
                $col++;

                $sheet->setCellValue("{$col}{$row}", $item->dictrict?->name ?? '');
                $col++;

                $sheet->setCellValue("{$col}{$row}", $item->economicsector?->name);
                $col++;

                $sheet->setCellValue("{$col}{$row}", $item->rubro->name ?? '');
                $col++;

                $sheet->setCellValue("{$col}{$row}", $item->phone);
                $col++;

                $sheet->setCellValue("{$col}{$row}", $item->email);
                $col++;

                $sheet->setCellValue("{$col}{$row}", $item->is_asesoria);
                $col++;

                $sheet->setCellValue("{$col}{$row}", $item->was_formalizado);

                $row++;
            }
        });

        // ===== Descargar =====
        return new StreamedResponse(function () use ($spreadsheet) {

            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false); // mejora rendimiento
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="lista-registrados.xlsx"',
            'Cache-Control'       => 'max-age=0',
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
