<?php

namespace App\Http\Controllers\Download;

use App\Exports\AttendanceExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceList;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DownloadAttendanceController extends Controller
{
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
                'lastname' => $item->lastname,
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
