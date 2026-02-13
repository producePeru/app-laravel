<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUgoParticipantRequest;
use App\Mail\UgoActividadCreadaMail;
use App\Models\Attendance;
use App\Models\AttendanceList;
use App\Models\City;
use App\Models\District;
use App\Models\Event;
use App\Models\Province;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use mysqli;

class AttendanceController extends Controller
{
    public function listEventsUgo(Request $request)
    {

        $permission = getPermission('eventos-ugo');

        if (!$permission['hasPermission']) {
            return response()->json([
                'message' => 'No tienes permiso para acceder a esta sección',
                'status' => 403
            ]);
        }

        $filters = [
            'name'      => $request->input('name'),
            'dateStart' => $request->input('dateStart'),
            'dateEnd'   => $request->input('dateEnd'),
            'year'      => $request->input('year'),
            'orderby'   => $request->input('orderby'),
            'asesor'    => $request->input('asesor')
        ];

        $query = Attendance::query();

        $query->withItems($filters);

        // $query->withItems($filters);

        $items = $query->paginate(100)->through(function ($item) {
            return $this->mapAdvisory($item);
        });

        $year  = 2026;

        $today = Carbon::today();

        $baseQuery = Attendance::query()
            ->withCount('attendanceList')
            ->whereYear('startDate', $year);

        if (!empty($filters['asesor'])) {
            $baseQuery->where('asesorId', $filters['asesor']);
        }


        $statistic = [

            'diaria' => (clone $baseQuery)
                ->whereDate('startDate', '<=', $today)
                ->whereDate('endDate', '>=', $today)
                ->count(),


            'consolidada' => (clone $baseQuery)
                ->whereDate('startDate', '>=', $today)
                ->count(),


            'pendiente' => (clone $baseQuery)
                ->whereDate('endDate', '<', $today)
                ->having('attendance_list_count', 0)
                ->count(),


            'finalizadas' => (clone $baseQuery)
                ->whereDate('endDate', '<', $today)
                ->having('attendance_list_count', '>=', 1)
                ->count(),
        ];


        return response()->json([
            'data'   => $items,
            'statisti' => $statistic,
            'status' => 200,
        ]);
    }

    private function mapAdvisory($item)
    {
        return [
            'id' => $item->id,
            // v2
            'tipo_actividad' => $item->pnte->name ?? null,
            'nombre_actividad' => strtoupper($item->title),
            'tema' => strtoupper($item->theme ?? null),
            'entidad' => strtoupper($item->entidad ?? null),
            'entidad_aliada' => strtoupper($item->entidad_aliada ?? null),
            'asesor' => $item->asesor
                ? strtoupper($item->asesor->name . ' ' . $item->asesor->lastname . ' ' . $item->asesor->middlename)
                : null,
            'beneficiarios' => $item->beneficiarios ?? null,
            'startDate' => Carbon::parse($item->startDate)->format('d/m/Y'),
            'endDate' => Carbon::parse($item->endDate)->format('d/m/Y'),
            'modality' => $item->modality == 'v' ? 'VIRTUAL' : 'PRESENCIAL',

            'city' => $item->region->name ?? null,
            'province' => $item->provincia->name ?? null,
            'district' => $item->distrito->name ?? null,
            'address' => $item->address ?? null,
            'pasaje' => $item->pasaje,
            'monto' => $item->monto ?? null,

            'tipo_actividad_id' => $item->pnte->id,
            'people_id' => $item->asesor->id ?? null,
            'city_id' => $item->region->id ?? null,
            'province_id' => $item->provincia->id ?? null,
            'district_id' => $item->distrito->id ?? null,

            'attendance_list_count' => $item->attendanceList?->count() ?? 0,
            'slug' => $item->slug,
            'created_at' => Carbon::parse($item->created_at)->format('d/m/Y H:i')

        ];
    }
    // 'eventsoffice_id' => $item->eventsoffice_id,
    // 'title' => strtoupper($item->title),
    // 'finally' => $item->finally,
    // 'pnte' => $item->pnte->name,
    // 'id_pnte' => $item->pnte->id,
    // 'startDate2' => $item->startDate,
    // 'endDate2' => $item->endDate,
    // 'fecha' => $item->fecha ?? null,
    // // 'profile' => strtoupper($item->profile->name . ' ' . $item->profile->lastname . ' ' . $item->profile->middlename),
    // 'hora' => $item->hora ?? null,
    // 'mercado' => $item->mercado ?? null,

    // 'description' => $item->description ?? null,


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
        DB::beginTransaction();

        try {
            // Usuario autenticado
            $user = Auth::user();
            $user_id = $user->id;

            $data = $request->all();

            /**
             * ASIGNACIÓN DE user_id y asesorId
             */
            $data['user_id'] = $user_id;

            if ($user->rol == 2) {
                // Si es asesor, se asigna a sí mismo
                $data['asesorId'] = $user_id;
            }

            if ($user->rol == 1) {
                // Si es admin, el asesorId viene en el payload
                // (se asume que ya viene validado)
                $data['asesorId'] = $data['asesorId'] ?? null;
            }

            /* SLUG */
            $slug = Str::slug($data['title']);

            if (Attendance::where('slug', $slug)->exists()) {
                $slug .= '-' . now()->format('His');
            }

            $data['slug'] = $slug;

            /**
             * NORMALIZACIÓN POR MODALIDAD
             */
            // if ($data['modality'] === 'v') {
            //     $data['city_id']     = null;
            //     $data['province_id'] = null;
            //     $data['district_id'] = null;
            //     $data['address']     = null;
            //     $data['pasaje']      = null;
            //     $data['monto']       = null;
            // }

            // if ($data['modality'] === 'p') {
            //     if (($data['pasaje'] ?? 'n') === 'n') {
            //         $data['monto'] = null;
            //     }
            // }

            /**
             * CREATE
             */
            $attendance = Attendance::create($data);

            DB::commit();

            return response()->json([
                'status'  => 200,
                'message' => 'Actividad creada correctamente',
                'data'    => $attendance
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al crear la actividad',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function update(Request $request, string $id)
    {
        try {
            $user = Auth::user();
            $registro = Attendance::findOrFail($id);
            $data = $request->all();

            /**
             * VALIDACIONES POR ROL
             */
            if ($user->rol == 2) {

                if (
                    $registro->user_id != $user->id ||
                    $registro->asesorId != $user->id
                ) {
                    return response()->json([
                        'message' => 'No tiene permisos para editar este registro',
                        'status'  => 403
                    ]);
                }

                // No puede cambiar estos campos
                unset($data['user_id'], $data['asesorId']);
            }

            /**
             * 🚫 NUNCA actualizar slug
             */
            unset($data['slug']);

            $registro->update($data);

            return response()->json([
                'message' => 'Registro actualizado con éxito',
                'status'  => 200,
                'data'    => $registro
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error de actualización',
                'status'  => 500,
                'error'   => $e->getMessage()
            ], 500);
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
                'startDate' => $attendance->startDate
                    ? Carbon::parse($attendance->startDate)->format('d/m/Y')
                    : null,

                'endDate' => $attendance->endDate
                    ? Carbon::parse($attendance->endDate)->format('d/m/Y')
                    : null,

                'fecha' => $attendance->fecha ?? null,
                'hora' => $attendance->hora ?? null,

                'theme' => $attendance->theme
                // 'startDate' => $attendance->startDate

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

        if (!$attendance) {
            return response()->json([
                'message' => 'No se encontró la lista de asistencia proporcionada.',
                'status' => 404
            ]);
        }

        $attendancelist_id = $attendance->id;

        // Verificar si el usuario ya existe en la lista
        $existingUser = AttendanceList::where('attendancelist_id', $attendancelist_id)
            ->where('documentnumber', $request->documentnumber)
            ->first();

        if ($existingUser) {
            return response()->json([
                'message' => 'El documento ya está registrado en esta lista de asistencia.',
                'status' => 400
            ]);
        }

        // Agregar el ID de la lista al array del request
        $data = $request->all();
        $data['attendancelist_id'] = $attendancelist_id;

        // Crear el usuario
        $user = AttendanceList::create($data);

        return response()->json([
            'message' => 'Registrado exitosamente.',
            'status' => 200,
            'data' => $user
        ]);
    }

    public function attendaceApplicants(Request $request, $slug)
    {
        $search = $request->input('search');

        $attendance = Attendance::where('slug', $slug)->first();

        if (!$attendance) {
            return response()->json(['message' => 'Fair not found'], 404);
        }

        $query = AttendanceList::with([
            'typedocument:id,avr',
            'gender:id,avr',
            'economicsector:id,name',
            'country:id,name',
            'list'
        ])
            ->where('attendancelist_id', $attendance->id)
            ->searchApplicants($search)
            ->orderBy('created_at', 'desc');

        $data = $query->paginate(100);

        $data->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'typedocument_name'     => $item->typedocument->avr,
                'documentnumber'        => $item->documentnumber,
                'lastname'              => mb_strtoupper($item->lastname),
                'middlename'            => mb_strtoupper($item->middlename),
                'name'                  => mb_strtoupper($item->name),
                'gender_name'           => $item->gender->avr,
                'sick'                  => mb_strtoupper($item->sick ?? null),
                'email'                 => $item->email ?? null,
                'phone'                 => $item->phone ?? null,
                'country_name'          => mb_strtoupper($item->country->name ?? null),
                'is_asesoria'           => $item->is_asesoria,
                'was_formalizado'       => $item->was_formalizado,

                'typedocument_id'       => $item->typedocument->id,
                'gender_id'             => $item->gender->id,

                'created_at'            => Carbon::parse($item->created_at)->format('d-m-Y H:i'),

                'ruc'                   => $item->ruc ?? null,
                'comercialName'        => $item->comercialName ?? null,
                'comercialActivity'     => $item->comercialActivity,
                'mercado'               => $item->mercado
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

    public function updateParticipantDataUgoEvent(UpdateUgoParticipantRequest $request, $id)
    {
        try {
            $participant = AttendanceList::findOrFail($id);

            $participant->update($request->validated());

            return response()->json([
                'message' => 'Datos del participante actualizados correctamente.',
                'data' => $participant,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el participante.',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function removeParticipantUgoEvent($id)
    {
        try {

            $participant = AttendanceList::findOrFail($id);

            $participant->delete();

            return response()->json([
                'message' => 'Participante eliminado correctamente.',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al intentar eliminar el participante.',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }








    // LISTA DE LOS ASESORES - EVENTOS ASIGNADOS A ELLOS 
    public function eventsAssignedAdvisor()
    {
        $userId = Auth::id();


        // return $userId;

        // Base query con relación si quieres cargar algo más
        $query = Attendance::where('asesorId', $userId)
            ->orderBy('id', 'desc');

        // Paginar y mapear
        $items = $query->paginate(100)->through(function ($item) {
            return $this->mapAdvisories($item);
        });

        return response()->json([
            'data'   => $items,
            'status' => 200
        ]);
    }

    private function mapAdvisories($item)
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

    // dashboard evento del asesor
    public function eventsAssignedDashboard()
    {
        $userId = Auth::id();

        // Total de eventos asignados al asesor
        $totalEventos = Attendance::where('asesorId', $userId)->count();

        // Total de inscritos (sumando todos los eventos del asesor)
        $totalInscritos = Attendance::where('asesorId', $userId)
            ->withCount('attendanceList')
            ->get()
            ->sum('attendance_list_count');

        // Total de regiones distintas (city_id)
        $totalRegiones = Attendance::where('asesorId', $userId)
            ->distinct('city_id')
            ->count('city_id');

        return response()->json([
            'status' => 200,
            'data' => [
                'total_eventos'   => $totalEventos,
                'total_inscritos' => $totalInscritos,
                'total_regiones'  => $totalRegiones,
            ]
        ]);
    }

    public function updateValuesSelect(Request $request)
    {
        $request->validate([
            'slug'   => 'required|string',
            'rowId'  => 'required|integer',
            'column' => 'required|string|in:is_asesoria,was_formalizado',
            'value'  => 'nullable|in:s,n',
        ]);

        // 1️⃣ Buscar evento por slug
        $attendance = Attendance::where('slug', $request->slug)->first();

        if (!$attendance) {
            return response()->json([
                'message' => 'Evento no encontrado'
            ], 404);
        }

        // 2️⃣ Buscar postulante dentro de ese evento
        $row = AttendanceList::where('id', $request->rowId)
            ->where('attendancelist_id', $attendance->id)
            ->first();

        if (!$row) {
            return response()->json([
                'message' => 'Registro no encontrado para este evento'
            ], 404);
        }

        // 3️⃣ Actualizar columna dinámica
        $row->{$request->column} = $request->value;
        $row->save();

        return response()->json([
            'message' => 'Actualizado correctamente',
            'data' => [
                'id'     => $row->id,
                'column' => $request->column,
                'value'  => $request->value
            ]
        ], 200);
    }

    public function sendAttendanceMail(Request $request)
    {
        $mailer = 'notificaciones';

        $emailDestinoS = ['tuempresa_temp406@produce.gob.pe', 'tuempresa_temp220@produce.gob.pe', 'tuempresa_temp423@produce.gob.pe'];

        $my_email = Auth::user()->personalemail;

        if (!empty($my_email)) {
            $emailDestinoS[] = $my_email;
        }

        $data = $request->all();

        // Determinar si es nuevo o actualización
        $isEdit = isset($data['original']);

        $changes = [];

        if ($isEdit) {
            foreach ($data['original'] as $field => $oldValue) {
                if (isset($data[$field]) && $data[$field] != $oldValue) {
                    $changes[$field] = [
                        'antes' => $oldValue,
                        'ahora' => $data[$field]
                    ];
                }
            }
        }

        // Instancia SOLO para pasar datos al correo (no se guarda)
        $attendance = new Attendance($data);

        $asesor = User::find($data['asesorId']);

        $link = 'https://inscripcion.soporte-pnte.com/actividades-ugo/' . $data['slug'];

        Mail::mailer($mailer)
            ->to($emailDestinoS)
            ->send(new UgoActividadCreadaMail(
                $attendance,
                $asesor,
                $link,
                $isEdit,
                $changes
            ));

        return response()->json(['ok' => true]);
    }


    public function eventsByRegion(Request $request)
    {
        $year = $request->get('year'); // ej: 2026

        return response()->json([
            'status' => true,
            'data'   => City::numberEventsByRegion($year)
        ]);
    }



    // REPORTES ****
    public function listEventsUgoByCity($city_id)
    {
        $permission = getPermission('eventos-ugo');

        if (!$permission['hasPermission']) {
            return response()->json([
                'message' => 'No tienes permiso para acceder a esta sección',
                'status' => 403
            ]);
        }

        $year  = 2026;
        $today = Carbon::today();

        // 🔹 Query base por ciudad
        $baseQuery = Attendance::query()
            ->withCount('attendanceList')
            ->where('city_id', $city_id)
            ->whereYear('startDate', $year);

        // 🔹 Listado
        $items = (clone $baseQuery)
            ->with([
                'pnte',
                'asesor',
                'region',
                'provincia',
                'distrito',
                'attendanceList'
            ])
            ->get()
            ->map(function ($item) {
                return $this->mapAdvisory($item);
            });

        // 🔹 ESTADÍSTICA ORIGINAL (SE MANTIENE)
        $statistic = [

            'diaria' => (clone $baseQuery)
                ->whereDate('startDate', '<=', $today)
                ->whereDate('endDate', '>=', $today)
                ->count(),

            'consolidada' => (clone $baseQuery)
                ->whereDate('startDate', '>=', $today)
                ->count(),

            'pendiente' => (clone $baseQuery)
                ->whereDate('endDate', '<', $today)
                ->having('attendance_list_count', 0)
                ->count(),

            'finalizadas' => (clone $baseQuery)
                ->whereDate('endDate', '<', $today)
                ->having('attendance_list_count', '>=', 1)
                ->count(),
        ];

        // 🔹 NUEVO: Estadística por modalidad
        $modalidad = Attendance::where('city_id', $city_id)
            ->whereYear('startDate', $year)
            ->selectRaw("
            SUM(CASE WHEN modality = 'p' THEN 1 ELSE 0 END) as presencial,
            SUM(CASE WHEN modality = 'v' THEN 1 ELSE 0 END) as virtual
        ")
            ->first();

        return response()->json([
            'data'      => $items,
            'statistic' => $statistic,
            'modalidad' => $modalidad,
            'status'    => 200,
        ]);
    }
}
