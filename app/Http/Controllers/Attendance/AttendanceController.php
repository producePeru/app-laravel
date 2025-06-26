<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceList;
use App\Models\City;
use App\Models\District;
use App\Models\Event;
use App\Models\Province;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use mysqli;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'name'      => $request->input('name'),
            'dateStart' => $request->input('dateStart'),
            'dateEnd'   => $request->input('dateEnd'),
            'year'      => $request->input('year'),
            'orderby'   => $request->input('orderby'),
            'asesor'    => $request->input('asesor')
        ];

        $userRole = getUserRole();
        $userId = $userRole['user_id'];

        $hasPermission = DB::table('page_user')->where('user_id', $userId)->exists();

        if (!$hasPermission) {
            return response()->json([
                'message' => 'No tienes permisos para acceder a esta página.',
                'status'  => 403
            ], 403);
        }


        $query = Attendance::query();

        $query->withItems($filters);

        $query->withItems($filters);

        $items = $query->paginate(150)->through(function ($item) {
            return $this->mapAdvisory($item);
        });

        return response()->json([
            'data'   => $items,
            'status' => 200
        ]);
    }

    private function mapAdvisory($item)
    {
        return [
            'id' => $item->id,
            'attendance_list_count' => $item->attendanceList?->count() ?? 0,
            'eventsoffice_id' => $item->eventsoffice_id,
            'slug' => $item->slug,
            'title' => strtoupper($item->title),
            'finally' => $item->finally,
            'pnte' => $item->pnte->name,
            'id_pnte' => $item->pnte->id,
            'startDate' => Carbon::parse($item->startDate)->format('d/m/Y'),
            'endDate' => Carbon::parse($item->endDate)->format('d/m/Y'),
            'startDate2' => $item->startDate,
            'endDate2' => $item->endDate,
            'modality' => $item->modality,
            'city' => $item->region->name ?? null,
            'province' => $item->provincia->name ?? null,
            'district' => $item->distrito->name ?? null,
            'city_id' => $item->region->id ?? null,
            'address' => $item->address ?? null,
            'fecha' => $item->fecha ?? null,
            'hora' => $item->hora ?? null,
            'mercado' => $item->mercado ?? null,
            'province_id' => $item->provincia->id ?? null,
            'district_id' => $item->distrito->id ?? null,
            // 'profile' => strtoupper($item->profile->name . ' ' . $item->profile->lastname . ' ' . $item->profile->middlename),
            'people_id' => $item->asesor->id ?? null,
            'asesor' => $item->asesor
                ? strtoupper($item->asesor->name . ' ' . $item->asesor->lastname . ' ' . $item->asesor->middlename)
                : null,
            'description' => $item->description ?? null,
            'created_at' => Carbon::parse($item->created_at)->format('d/m/Y')
        ];
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
                'asesorId' => $item->asesor->id ?? null,
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

                $attendance = Attendance::create($data);

                return response()->json(['message' => 'Evento creado con éxito', 'status' => 200, 'id' => $attendance->id]);
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

            if ($attendance->finally == 1) {
                return response()->json(['message' => 'Esta lista ya no esta vigente.', 'status' => 500]);
            }

            return response()->json(['data' => [
                'slug' => $attendance->slug,
                'title' => $attendance->title,
                'address' => $attendance->address ?? null,
                'startDate' => $attendance->startDate,
                'endDate' =>  $attendance->endDate,
                'fecha' => $attendance->fecha ?? null,
                'hora' => $attendance->hora ?? null,

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
            $user->sick = $request->sick ?? null;
            $user->email = $request->email;
            $user->phone = $request->phone;

            $user->ruc = $request->ruc;
            $user->comercialName = $request->comercialName ?? null;
            $user->socialReason = $request->socialReason;
            $user->economicsector_id = $request->economicsector_id;
            $user->comercialactivity_id = $request->comercialactivity_id ?? null;
            $user->category_id = $request->category_id ?? null;
            $user->city_id = $request->city_id ?? null;
            // $user->slug = $request->slug ?? null;
            $user->howKnowEvent_id = $request->howKnowEvent_id ?? null;
            $user->comercialactivity = $request->comercialactivity;
            $user->attendancelist_id = $attendancelist_id;
            $user->mercado = $request->mercado ?? null;
            $user->fechaRegistro = $request->fechaRegistro ?? null;
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

        return response()->json(['data' => $data, 'nameEvent' => $attendance->title, 'typeEvent' => $attendance->eventsoffice_id]);
    }

    public function migrateEvents()
    {
        try {
            $events = Attendance::with([
                'region',
                'provincia',
                'distrito',
                'profile:id,user_id,name,lastname,middlename',
                'asesor'
            ])->get();

            foreach ($events as $event) {
                Event::create([
                    'id_pnte'    => $event->eventsoffice_id,
                    'title'      => $event->title,
                    'dateStart'  => Carbon::parse($event->startDate)->format('Y-m-d'),
                    'dateEnd'    => Carbon::parse($event->endDate)->format('Y-m-d'),
                    'description' => "
					<strong>Lugar:</strong>
					<p>{$event->region->name} - {$event->provincia->name} - {$event->distrito->name}</p>
					<p>{$event->address}</p>
					<br>
					<p style='margin-top: 10px !important;'>{$event->description}</p>
				",
                    'nameUser'   => $event->asesor ? $event->asesor->name . ' ' . $event->asesor->lastname : null,
                    'user_id'   => 1
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Eventos migrados con éxito'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al migrar los eventos',
                'error' => $e->getMessage()
            ]);
        }
    }

    // crea a partir del ID
    public function createEventoToAttendance($id)
    {
        try {
            $event = Attendance::with([
                'region',
                'provincia',
                'distrito',
                'profile:id,user_id,name,lastname,middlename',
                'asesor'
            ])->find($id);

            if (!$event) {
                return response()->json([
                    'status' => 404,
                    'message' => 'El evento no existe'
                ]);
            }

            Event::create([
                'id_pnte'    => $event->eventsoffice_id,
                'title'      => $event->title,
                'dateStart'  => Carbon::parse($event->startDate)->format('Y-m-d'),
                'dateEnd'    => Carbon::parse($event->endDate)->format('Y-m-d'),
                'description' => "
				<strong>Lugar:</strong>
				<p>{$event->region->name} - {$event->provincia->name} - {$event->distrito->name}</p>
				<p>{$event->address}</p>
				<br>
				<p style='margin-top: 10px !important;'>{$event->description}</p>
			",
                'nameUser'   => $event->asesor ? $event->asesor->name . ' ' . $event->asesor->lastname : null,
                'user_id'    => $event->user_id,
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Evento migrado con éxito'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al migrar el evento',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function eventFinally($id)
    {
        $event = Attendance::find($id);

        if (!$event) {
            return response()->json([
                'status' => 404,
                'message' => 'El evento no existe'
            ]);
        }

        $event->finally = 1;
        $event->save();

        return response()->json([
            'status' => 200,
            'message' => 'El evento se marco como finalizado'
        ]);
    }
}
