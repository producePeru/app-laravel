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

            $filters = [
                'name'      => $request->input('name'),
                'dateStart' => $request->input('dateStart'),
                'dateEnd'   => $request->input('dateEnd'),
                'year'      => $request->input('year'),
                'orderby'   => $request->input('orderby'),
                'asesor'    => $request->input('asesor')
            ];


            // $userRole = getUserRole();
            // $roleIds  = $userRole['role_id'];
            // $userId   = $userRole['user_id'];

            $query = Attendance::query();

            $query->withItems($filters);

            ini_set('memory_limit', '2G');
            set_time_limit(300);

            $items = [];
            $globalIndex = 1;

            $query->chunk(1000, function ($rows) use (&$items, &$globalIndex) {
                foreach ($rows as $item) {
                    $items[] = [
                        'index'             => $globalIndex++,
                        'title' => $item->title,
                        'attendance_list_count' => $item->attendanceList?->count() ?? 0,
                        'startDate' => Carbon::parse($item->startDate)->format('d/m/Y'),
                        'endDate' => Carbon::parse($item->endDate)->format('d/m/Y'),
                        // 'modality' => $item->modality == 'v' ? 'VIRTUAL' : 'PRESENCIAL',
                        'city' => $item->region->name ?? null,
                        'province' => $item->provincia->name ??  null,
                        'district' => $item->distrito->name ?? null,
                        'address' => $item->address ??  null,
                        'asesor' => $item['asesor'],
                        'asesor' => $item->asesor
                            ? strtoupper($item->asesor->name . ' ' . $item->asesor->lastname . ' ' . $item->asesor->middlename)
                            : null,
                        'profile_creater' => $item->profile
                            ? strtoupper($item->profile->name . ' ' . $item->profile->lastname . ' ' . $item->profile->middlename)
                            : null,
                        'description' => $item->description ?? null,
                        'created_at' => Carbon::parse($item->created_at)->format('d/m/Y'),

                        'link_participantes' => ' https://programa.soporte-pnte.com/admin/ugo/eventos-inscritos/' . $item->slug,

                        'link_registro_participantes' => $item->eventsoffice_id == 3 ?
                            'https://inscripcion.soporte-pnte.com/fortalece-tu-mercado/' . $item->slug :
                            'https://programa.soporte-pnte.com/asistencias/' . $item->slug,


                    ];
                }
            });

            return Excel::download(new AttendanceExport($items), 'eventos-pnte.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrio un error al generar el reporte.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function exportRegistrantsUgoEvents($slug)
    {

        $attendance = Attendance::where('slug', $slug)->first();

        if (!$attendance) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $asesor = User::find($attendance->asesorId);
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
                'nameActividad'         => $attendance->title,

                'asesor'                => strtoupper($asesor->name . ' ' . $asesor->lastname . ' ' . $asesor->middlename),
                'dateActividad'         => Carbon::parse($attendance->startDate)->format('d/m/Y') . ' - ' . Carbon::parse($attendance->endDate)->format('d/m/Y'),

                'region'                => $region->name,
                'provincia'             => $province->name,
                'distrito'              => $district->name,

                'place'                 => $attendance->address ?? '-',
                'lastname'              => strtoupper($item->lastname . ' ' . $item->middlename),
                'name'                  => strtoupper($item->name),
                'typedocument'          => $item->typedocument->name,
                'documentnumber'        => $item->documentnumber,
                'email'                 => $item->email ?? '-',
                'phone'                 => $item->phone ?? '-',
                'gender'                => $item->gender->avr,
                'sick'                  => strtoupper($item->sick) ?? '-',
                'ruc'                   => $item->ruc ?? '-',
                'economicsector'        => $item->economicsector ? $item->economicsector->name : '-',
                'comercialActivity'     => strtoupper($item->comercialActivity ?? '-'),
            ];
        });

        // return Excel::download(new AttendanceListSlugExport($result), 'attendance.xlsx');

        $templatePath = storage_path('app/plantillas/ugo_eventos_lista_registrados_template.xlsx');
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
