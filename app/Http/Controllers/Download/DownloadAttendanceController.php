<?php

namespace App\Http\Controllers\Download;

use App\Exports\AttendanceExport;
use App\Exports\AttendanceListExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceList;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DownloadAttendanceController extends Controller
{

    public function exportDigitalRouter(Request $request)
    {

        $search = $request->input('search');
        $year = $request->input('year');
        $orderBy = $request->input('order_by');

        $query = Attendance::with([
            'attendanceList',
            'region',
            'provincia',
            'distrito',
            'profile:id,user_id,name,lastname,middlename',
            'asesor'
        ])
            ->withCount('attendanceList')
            ->search($search)
            ->when($year, function ($q) use ($year) {
                $q->whereYear('created_at', $year);
            });

        if (in_array($orderBy, ['asc', 'desc'])) {
            $query->orderBy('attendance_list_count', $orderBy);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $data = $query->get();


        $result = $data->map(function ($item, $index) {

            return [
                'index' => $index + 1,
                'title' => $item->title,
                'attendance_list_count' => $item['attendance_list_count'],
                'startDate' => Carbon::parse($item->startDate)->format('d-m-Y'),
                'endDate' => Carbon::parse($item->endDate)->format('d-m-Y'),
                'modality' => $item->modality == 'v' ? 'VIRTUAL' : 'PRESENCIAL',

                'city' => $item->region->name,
                'province' => $item->provincia->name,
                'district' => $item->distrito->name,
                'asesor' => $item['asesor'],

                'asesor' => $item->asesor
                    ? strtoupper($item->asesor->name . ' ' . $item->asesor->lastname . ' ' . $item->asesor->middlename)
                    : null,

                'profile_creater' => $item['profile'],
                'description' => $item->description ?? null,

                'created_at' => Carbon::parse($item->created_at)->format('d-m-Y')

            ];
        });

        return Excel::download(new AttendanceListExport($result), 'attendances.xlsx');
    }
    public function exportAttendance($slug)
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

        return Excel::download(new AttendanceExport($result), 'attendance.xlsx');
    }
}
