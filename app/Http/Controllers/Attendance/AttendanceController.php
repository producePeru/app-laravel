<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceList;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use mysqli;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $year = $request->input('year');
        $orderBy = $request->input('order_by'); // 'desc' por defecto si no se envía

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

        $data = $query->paginate(50);

        // Transformar los datos
        $data->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'attendance_list_count' => $item->attendance_list_count,
                'slug' => $item->slug,
                'title' => $item->title,
                'startDate' => Carbon::parse($item->startDate)->format('d-m-Y'),
                'endDate' => Carbon::parse($item->endDate)->format('d-m-Y'),
                'startDate2' => $item->startDate,
                'endDate2' => $item->endDate,
                'modality' => $item->modality,
                'city' => $item->region->name,
                'province' => $item->provincia->name,
                'district' => $item->distrito->name,
                'city_id' => $item->region->id,
                'address' => $item->address ?? null,
                'province_id' => $item->provincia->id,
                'district_id' => $item->distrito->id,
                'profile' => strtoupper($item->profile->name . ' ' . $item->profile->lastname . ' ' . $item->profile->middlename),
                'people_id' => $item->asesor->id ?? null,
                'asesor' => $item->asesor
                    ? strtoupper($item->asesor->name . ' ' . $item->asesor->lastname . ' ' . $item->asesor->middlename)
                    : null,
                'description' => $item->description ?? null,
                'created_at' => Carbon::parse($item->created_at)->format('d-m-Y')
            ];
        });

        return response()->json(['data' => $data]);
    }



    public function allWithoutPagination(Request $request)
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

        // Transformar los datos
        $transformedData = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'attendance_list_count' => $item->attendance_list_count,
                'slug' => $item->slug,
                'title' => $item->title,
                'startDate' => Carbon::parse($item->startDate)->format('d-m-Y'),
                'endDate' => Carbon::parse($item->endDate)->format('d-m-Y'),
                'startDate2' => $item->startDate,
                'endDate2' => $item->endDate,
                'modality' => $item->modality,
                'city' => $item->region->name,
                'province' => $item->provincia->name,
                'district' => $item->distrito->name,
                'city_id' => $item->region->id,
                'address' => $item->address ?? null,
                'province_id' => $item->provincia->id,
                'district_id' => $item->distrito->id,
                'profile' => strtoupper($item->profile->name . ' ' . $item->profile->lastname . ' ' . $item->profile->middlename),
                'people_id' => $item->asesor->id ?? null,
                'asesor' => $item->asesor
                    ? strtoupper($item->asesor->name . ' ' . $item->asesor->lastname . ' ' . $item->asesor->middlename)
                    : null,
                'description' => $item->description ?? null,
                'created_at' => Carbon::parse($item->created_at)->format('d-m-Y')
            ];
        });

        return response()->json(['data' => $transformedData]);
    }


    public function create(Request $request)
    {
        try {

            $user_role = getUserRole();
            $role_array = $user_role['role_id'];

            if (
                in_array(5, $role_array) ||
                in_array(1, $role_array)
            ) {

                $user_role = getUserRole();
                $user_id = $user_role['user_id'];

                $data = $request->all();

                $slug = Str::slug($data['title']);

                if (Attendance::where('slug', $slug)->exists()) {
                    $slug .= '-' . now()->format('His');
                }

                $data['slug'] = $slug;
                $data['user_id'] = $user_id;

                Attendance::create($data);

                return response()->json(['message' => 'Evento creado con éxito', 'status' => 200]);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error de registro', 'status' => 500, 'error' => $e], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $user_role = getUserRole();
            $role_array = $user_role['role_id'];

            if (
                in_array(5, $role_array) ||
                in_array(1, $role_array)
            ) {

                $registro = Attendance::findOrFail($id);
                $data = $request->all();

                if (isset($data['title'])) {
                    $slug = Str::slug($data['title']);

                    if (Attendance::where('slug', $slug)->exists()) {
                        $slug .= '-' . now()->format('His');
                    }

                    $data['slug'] = $slug;
                }

                $registro->update($data);

                return response()->json(['message' => 'Registro actualizado con éxito', 'status' => 200]);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error de registro', 'status' => 500, 'error' => $e], 500);
        }
    }

    public function delete($id)
    {
        $user_role = getUserRole();
        $role_array = $user_role['role_id'];

        if (in_array(5, $role_array) || in_array(1, $role_array)) {
            $registro = Attendance::findOrFail($id);
            $registro->delete();
            return response()->json(['message' => 'Registro eliminado con éxito', 'status' => 200]);
        } else {
            return response()->json(['message' => 'Sin acceso', 'status' => 403]);
        }
    }


    public function show($slug)
    {
        $today = Carbon::now();

        $attendance = Attendance::where('slug', $slug)->first();

        if ($attendance) {

            if ($today->gt(Carbon::parse($attendance->endDate)->endOfDay())) {
                return response()->json(['message' => 'Esta lista ya no esta vigente.', 'status' => 500]);
            }

            return response()->json(['data' => [
                'slug' => $attendance->slug,
                'title' => $attendance->title,
                // 'subTitle' => $attendance->subTitle,
                // 'description' => $attendance->description,
                // 'modality' => $attendance->modality
            ], 'status' => 200]);
        }

        return response()->json(['message' => 'Lista no encontrada.', 'status' => 400]);
    }

    public function userPresent(Request $request)
    {
        $attendance = Attendance::where('slug', $request->slug)->first();

        if ($attendance) {
            $attendancelist_id = $attendance->id;

            $existingUser = AttendanceList::where('attendancelist_id', $attendancelist_id)
                ->where('documentnumber', $request->documentnumber)
                ->first();

            if ($existingUser) {
                return response()->json([
                    'message' => 'El documento ya está registrado en esta lista de asistencia.',
                    'status' => 400
                ]);
            }

            $user = new AttendanceList();
            $user->typedocument_id = $request->typedocument_id;
            $user->documentnumber = $request->documentnumber;
            $user->name = $request->name;
            $user->lastname = $request->lastname;
            $user->middlename = $request->middlename;
            $user->gender_id = $request->gender_id;
            $user->sick = $request->sick;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->ruc = $request->ruc;
            $user->socialReason = $request->socialReason;
            $user->economicsector_id = $request->economicsector_id;
            $user->comercialactivity = $request->comercialactivity;
            $user->attendancelist_id = $attendancelist_id;
            $user->save();

            return response()->json([
                'message' => 'Registrado exitosamente',
                'status' => 200,
                'data' => $user
            ]);
        } else {
            return response()->json([
                'message' => 'No se encontró la lista de asistencia proporcionado',
                'status' => 404
            ]);
        }
    }

    public function attendaceApplicants(Request $request, $slug)
    {
        $search = $request->input('search');

        $attendance = Attendance::where('slug', $slug)->first();

        if (!$attendance) {
            return response()->json(['message' => 'Fair not found'], 404);
        }

        $query = AttendanceList::with([
            'typedocument:id,name',
            'gender:id,name',
            'economicsector:id,name',
            // 'comercialactivity:id,name',
            'list'
        ])
            ->where('attendancelist_id', $attendance->id)
            ->searchApplicants($search)
            ->orderBy('created_at', 'desc');

        $data = $query->paginate(50);

        $data->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'created_at' => Carbon::parse($item->created_at)->format('d-m-Y H:i'),
                'lastname' => $item->lastname . ' ' . $item->middlename,
                'name' => $item->name,
                'typedocument' => $item->typedocument->name,
                'documentnumber' => $item->documentnumber,
                'email' => $item->email,
                'phone' => $item->phone,
                'gender' => $item->gender->name,
                'sick' => $item->sick,
                'ruc' => $item->ruc ? $item->ruc : '-',
                'economicsector' => $item->economicsector ? $item->economicsector->name : '-',
                'comercialActivity' => $item->comercialActivity,
                // 'comercialactivity' => $item->comercialactivity->name
            ];
        });

        return response()->json(['data' => $data, 'nameEvent' => $attendance->title]);
    }
}
