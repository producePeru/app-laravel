<?php

namespace App\Http\Controllers\Download;

use App\Exports\AttendanceExport;
use App\Exports\AttendanceListSlugExport;
// use App\Exports\AttendanceListExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceList;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DownloadAttendanceController extends Controller
{

    public function exportAttendance(Request $request)
    {

        try {

            $filters = $request->query();

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
                        'city' => $item->region->name,
                        'province' => $item->provincia->name,
                        'district' => $item->distrito->name,
                        'address' => $item->address,
                        'asesor' => $item['asesor'],
                        'asesor' => $item->asesor
                            ? strtoupper($item->asesor->name . ' ' . $item->asesor->lastname . ' ' . $item->asesor->middlename)
                            : null,
                        'profile_creater' => $item->profile
                            ? strtoupper($item->profile->name . ' ' . $item->profile->lastname . ' ' . $item->profile->middlename)
                            : null,
                        'description' => $item->description ?? null,
                        'created_at' => Carbon::parse($item->created_at)->format('d-m-Y'),
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


    public function exportAttendanceInscriptos($slug)
    {

        $attendance = Attendance::where('slug', $slug)->first();

        if (!$attendance) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $query = AttendanceList::with([
            'typedocument:id,name',
            'gender:id,name,avr',
            'economicsector:id,name',
            // 'comercialactivity:id,name',
            'list'
        ])->where('attendancelist_id', $attendance->id)
        ->orderBy('created_at', 'desc');

        $data = $query->get();

        $result = $data->map(function ($item, $index) {
            return [
                'index' => $index + 1,
                'created_at' => Carbon::parse($item->created_at)->format('d-m-Y H:i'),
                'lastname' => $item->lastname . ' ' . $item->middlename,
                'name' => $item->name,
                'typedocument' => $item->typedocument->name,
                'documentnumber' => $item->documentnumber,
                'email' => $item->email,
                'phone' => $item->phone,
                'gender' => $item->gender->avr,
                'sick' => $item->sick,
                'ruc' => $item->ruc ? $item->ruc : '-',
                'economicsector' => $item->economicsector ? $item->economicsector->name : '-',
                'comercialActivity' => $item->comercialActivity
            ];
        });

        // return $result;

        return Excel::download(new AttendanceListSlugExport($result), 'attendance.xlsx');
    }
}