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
        $user_role = getUserRole();
        $role_array = $user_role['role_id'];

        $data = collect($request->all());

        if (in_array(1, $role_array) || in_array(5, $role_array)) {

        } elseif (in_array(2, $role_array) || in_array(7, $role_array)) {

            $data = $data->where('asesor_dni', $user_role['user_id']);

        } else {
            return response()->json(['error' => 'Unauthorized', 'status' => 409]);
        }

        $result = $data->map(function ($item, $index) {

            return [
                'index' => $index + 1,
                'title' => $item['title'],
                'attendance_list_count' => $item['attendance_list_count'],
                'startDate' => $item['startDate'],
                'endDate' => $item['endDate'],
                'modality' => $item['modality'] == 'v' ? 'VIRTUAL' : 'PRESENCIAL',
                'city' => $item['city'],
                'province' => $item['province'],
                'district' => $item['district'],
                'asesor' => $item['asesor'],
                'profile_creater' => $item['profile'],
                'description' => $item['description'],
                'created_at' => $item['created_at']

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
