<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Event2;
use App\Models\EventRecurrence;
use App\Models\EventCategory;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Str;

class EventsController extends Controller
{

    public function createCategory(Request $request)
    {
        try {

            $user_role = getUserRole();
            $user_id = $user_role['user_id'];

            $data = $request->only('name', 'color');
            $data['user_id'] = $user_id;

            EventCategory::create($data);               // $data = $request->all();

            return response()->json(['message' => 'Categoría registrada correctamente', 'status' => 200]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al registrar', 'status' => $e]);
        }
    }

    public function listCategories()
    {
        try {
            $categories = EventCategory::all();
            return response()->json(['data' => $categories, 'status' => 200]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener las categorías', 'status' => 500, 'error' => $e->getMessage()]);
        }
    }

    public function statusCategories($id)
    {
        try {
            $category = EventCategory::findOrFail($id);

            $category->status = $category->status == '1' ? '0' : '1';
            $category->save();

            return response()->json(['message' => 'actualizado', 'status' => 200]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el estado', 'status' => 500, 'error' => $e->getMessage()]);
        }
    }

    public function createEvent(Request $request)
    {
        try {

            $user_role = getUserRole();
            $user_id = $user_role['user_id'];

            $data = $request->only(
                'nameEvent',
                'start',
                'end',
                'description',
                'linkVideo',
                'category_id',
                'repetir',
                'color',
                'allDay'
            );

            $data['user_id'] = $user_id;

            Event::create($data);

            return response()->json(['message' => 'Evento registrado', 'status' => 200]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al registrar', 'status' => $e]);
        }
    }

    public function listAllEvents()
    {
        $events = Event::listAllEvents();

        $data = $events->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->nameEvent,
                'start' => $item->startDate,
                'end' => $item->endDate,
                'backgroundColor' => $item->color,
                'allDay' => $item->allDay,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function index2(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        if (!$month || !$year) {
            return response()->json(['message' => 'El mes y el año son requeridos'], 400);
        }

        $events = Event::all();
        $eventsWithRepetition = [];

        // Obtener la fecha actual para comparar
        $currentDate = Carbon::now();

        foreach ($events as $event) {
            // Parsear las fechas de inicio y fin del evento
            $originalStart = Carbon::parse($event->start);
            $originalEnd = Carbon::parse($event->end);
            $createdAt = Carbon::parse($event->created_at);

            // Filtrar eventos repetidos anualmente
            if ($event->repetir === 'year') {
                if ($originalStart->month == $month && $year >= $originalStart->year) {
                    // Clonar el evento y ajustar las fechas
                    $newEvent = clone $event;
                    $newEvent->start = $originalStart->year($year);
                    $newEvent->end = $originalEnd->year($year);

                    // Asegurarse de que la fecha sea mayor o igual a la fecha de creación o actual
                    if ($newEvent->start->gte($createdAt)) {
                        $eventsWithRepetition[] = $newEvent;
                    }
                }
            }

            // Filtrar eventos repetidos mensualmente
            if ($event->repetir === 'month') {
                if ($originalStart->year <= $year) {
                    // Evitar listar eventos en meses anteriores dentro del mismo año
                    if ($year == $originalStart->year && $month < $originalStart->month) {
                        continue;
                    }

                    // Clonar el evento y ajustar las fechas al mes y año seleccionados
                    $newEvent = clone $event;
                    $newEvent->start = $originalStart->year($year)->month($month);
                    $newEvent->end = $originalEnd->year($year)->month($month);

                    // Asegurarse de que la fecha sea mayor o igual a la fecha de creación o actual
                    if ($newEvent->start->gte($createdAt)) {
                        $eventsWithRepetition[] = $newEvent;
                    }
                }
            }

            // Filtrar eventos sin repetición
            if ($event->repetir === null) {
                // Solo agregar si el evento fue creado en el mismo mes y año que se selecciona
                if ($createdAt->month == $month && $createdAt->year == $year) {
                    // Asegurarse de que el evento aún no haya pasado
                    if ($originalStart->gte($originalStart)) {
                        $eventsWithRepetition[] = $event;
                    }
                }
            }
        }

        // Retornar los eventos filtrados
        if (count($eventsWithRepetition) > 0) {
            $data = array_map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->nameEvent,
                    'start' => $event->start,
                    'end' => $event->end,
                    'backgroundColor' => $event->color,
                    'repetir' => $event->repetir,
                    'description' => $event->description,
                    'linkVideo' => $event->linkVideo,
                    'allDay' => $event->allDay,
                    'category_id' => $event->category_id
                ];
            }, $eventsWithRepetition);

            return response()->json($data);
        } else {
            return response()->json(['message' => 'No hay eventos para el mes y año seleccionados'], 404);
        }
    }

    public function store(Request $request)
    {
        try {

            $user_role = getUserRole();
            $user_id   = $user_role['user_id'];
            $data = $request->all();
            $data['user_id'] = $user_id;

            Event::create($data);

            return response()->json([
                'message' => 'Evento registrado correctamente',
                'status'  => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar',
                'status'  => 500,
                'error'  => $e
            ]);
        }
    }


    // public function index(Request $request) v.1.0
    // {

    //     return "Hello world";

    //     $filters = [
    //         'name'      => $request->input('name'),
    //         'year'      => $request->input('year'),
    //         // 'asesor'    => $request->input('asesor'),
    //         'dateStart' => $request->input('dateStart'),
    //         'dateEnd'   => $request->input('dateEnd'),
    //         'offices'   => $request->input('offices'),
    //         'type'      => $request->input('type')
    //     ];

    //     $query = Event::query();

    //     $query->withAdvisoryRangeDate($filters);

    //     $advisories = $query->paginate(150)->through(function ($advisory) {
    //         return $this->mapEvents($advisory);
    //     });

    //     return response()->json([
    //         'data'   => $advisories,
    //         'status' => 200
    //     ]);
    // }

    // public function index(Request $request)
    // {
    //     $filters = [
    //         'name'       => $request->input('name'),
    //         'year'       => $request->input('year'),
    //         'pnte'       => $request->input('pnte'),
    //         'modalidad'  => $request->input('modalidad'),
    //         'date'       => $request->input('date'),
    //         'rangeDate'  => $request->input('rangeDate'),
    //         'city'       => $request->input('city'),
    //         'province'   => $request->input('province'),
    //         'district'   => $request->input('district'),
    //     ];

    //     $query = Event::query();

    //     $query->withAdvisoryRangeDate($filters);

    //     $events = $query
    //         ->paginate($request->input('pageSize', 10))
    //         ->through(fn($item) => $this->mapEvents($item));

    //     return response()->json([
    //         'data'   => $events,
    //         'status' => 200
    //     ]);
    // }

    // public function index(Request $request)
    // {
    //     $perPage = $request->input('pageSize', 10);
    //     $page    = $request->input('page', 1);
    //     $year    = $request->input('year');

    //     // 🔥 TRAER TODOS
    //     $events = Event2::get();

    //     // 🔥 AGRUPAR IDS
    //     $attendanceIds = $events->where('tabla', 'attendancelist')->pluck('row_id');
    //     $mpIds         = $events->where('tabla', 'mp')->pluck('row_id');

    //     // 🔥 ATTENDANCE (con ubicación)
    //     $attendances = \App\Models\Attendance::whereIn('id', $attendanceIds)
    //         ->select('id', 'title', 'date', 'city_id', 'province_id', 'district_id', 'address', 'eventsoffice_id', 'user_id')
    //         ->with([
    //             'region:id,name',
    //             'provincia:id,name',
    //             'distrito:id,name',
    //             'pnte:id,name',
    //             'registrador:id,name,lastname,middlename'
    //         ])
    //         ->get()
    //         ->keyBy('id');

    //     // 🔥 MP EVENTS (con ubicación)
    //     $mps = \App\Models\MPEvent::whereIn('id', $mpIds)
    //         ->select('id', 'title', 'date', 'city_id', 'province_id', 'district_id', 'place', 'user_id')
    //         ->with([
    //             'region:id,name',
    //             'provincia:id,name',
    //             'distrito:id,name',
    //             'registrador:id,name,lastname,middlename'
    //         ])
    //         ->get()
    //         ->keyBy('id');

    //     // 🔥 MAP UNIFICADO
    //     $collection = $events->map(function ($item) use ($attendances, $mps) {

    //         $detalle = null;
    //         $tipo    = null;

    //         if ($item->tabla === 'attendancelist') {
    //             $detalle = $attendances[$item->row_id] ?? null;
    //             $tipo    = 'UGO';
    //         }

    //         if ($item->tabla === 'mp') {
    //             $detalle = $mps[$item->row_id] ?? null;
    //             $tipo    = 'MP';
    //         }

    //         return [
    //             'id'      => $item->id,
    //             'tabla'   => $item->tabla,
    //             'row_id'  => $item->row_id,
    //             'unidad'  => $item->unidad,
    //             'estado'  => $item->estado,
    //             'visible' => $item->visible,

    //             'tipo' => $tipo,

    //             'titulo' => $detalle->title ? mb_strtoupper($detalle->title, 'UTF-8') : null,
    //             'tipoActividad' => $detalle->pnte->name ?? null,

    //             'date'   => $detalle->date ?? null,

    //             // 🔥 UBICACIÓN NORMALIZADA
    //             'region'    => $detalle->region->name ?? null,
    //             'provincia' => $detalle->provincia->name ?? null,
    //             'distrito'  => $detalle->distrito->name ?? null,
    //             'direccion' => mb_strtoupper($detalle->address ?? $detalle->place ?? '', 'UTF-8'),
    //             'registrador' => $detalle->registrador ? mb_strtoupper($detalle->registrador->name . ' ' . $detalle->registrador->lastname . ' ' . $detalle->registrador->middlename, 'UTF-8') : null,
    //         ];
    //     });

    //     // 🔥 FILTRO POR YEAR
    //     if ($year) {
    //         $collection = $collection->filter(function ($item) use ($year) {
    //             return $item['date'] && substr($item['date'], 0, 4) == $year;
    //         });
    //     }

    //     // 🔥 ORDEN POR FECHA DESC (más reciente primero)
    //     $collection = $collection->sortByDesc('date')->values();

    //     // 🔥 PAGINADO MANUAL
    //     $total = $collection->count();

    //     $items = $collection
    //         ->slice(($page - 1) * $perPage, $perPage)
    //         ->values();

    //     return response()->json([
    //         'data' => [
    //             'data'         => $items,
    //             'total'        => $total,
    //             'current_page' => (int) $page,
    //             'per_page'     => (int) $perPage,
    //         ],
    //         'status' => 200
    //     ]);
    // }

    // METODO REUTILIZABLE
    private function getUnifiedEvents()
    {
        // 🔥 ATTENDANCE (UGO)
        $attendances = \App\Models\Attendance::select(
            'id',
            'title',
            'date',
            'city_id',
            'province_id',
            'district_id',
            'address',
            'eventsoffice_id',
            'user_id',
            'visible',
            'resultados',
            'cancelado',
            'reprogramado',
            'unidad'
        )
            ->with([
                'region:id,name',
                'provincia:id,name',
                'distrito:id,name',
                'pnte:id,name',
                'registrador:id,name,lastname,middlename'
            ])
            ->get()
            ->map(function ($item) {
                return [
                    'id'        => 'att-' . $item->id,
                    'tabla'     => 'attendancelist',
                    'row_id'    => $item->id,

                    'unidad'    => $item->unidad ?? 'UGO',
                    'visible'   => $item->visible ?? 0,
                    'estado'    => null,

                    'tipo'      => 'UGO',

                    'titulo'    => $item->title ? mb_strtoupper($item->title, 'UTF-8') : null,
                    'tipoActividad' => $item->pnte->name ?? null,

                    'date'      => $item->date,

                    'resultados'   => $item->resultados ?? null,
                    'cancelado'    => $item->cancelado ?? null,
                    'reprogramado' => $item->reprogramado ?? null,

                    'region'    => $item->region->name ?? null,
                    'provincia' => $item->provincia->name ?? null,
                    'distrito'  => $item->distrito->name ?? null,
                    'direccion' => mb_strtoupper($item->address ?? '', 'UTF-8'),

                    'registrador' => $item->registrador
                        ? mb_strtoupper(
                            $item->registrador->name . ' ' .
                                $item->registrador->lastname . ' ' .
                                $item->registrador->middlename,
                            'UTF-8'
                        )
                        : null,
                ];
            });

        // 🔥 UGSE (ANTES MP)
        $ugse = \App\Models\MPEvent::select(
            'id',
            'title',
            'date',
            'city_id',
            'province_id',
            'district_id',
            'place',
            'user_id',
            'visible',
            'resultados',
            'cancelado',
            'reprogramado',
            'unidad'
        )
            ->with([
                'city:id,name',
                'province:id,name',
                'district:id,name'
            ])
            ->get()
            ->map(function ($item) {
                return [
                    'id'        => 'ugse-' . $item->id,
                    'tabla'     => 'mp_eventos',
                    'row_id'    => $item->id,

                    'unidad'    => $item->unidad ?? 'UGSE',
                    'visible'   => $item->visible ?? 0,
                    'estado'    => null,

                    'tipo'      => 'UGSE',

                    'titulo'    => $item->title ? mb_strtoupper($item->title, 'UTF-8') : null,
                    'tipoActividad' => null,

                    'date'      => $item->date,

                    'resultados'   => $item->resultados ?? null,
                    'cancelado'    => $item->cancelado ?? null,
                    'reprogramado' => $item->reprogramado ?? null,

                    'region'    => $item->city->name ?? null,
                    'provincia' => $item->province->name ?? null,
                    'distrito'  => $item->district->name ?? null,
                    'direccion' => mb_strtoupper($item->place ?? '', 'UTF-8'),

                    'registrador' => null,
                ];
            });

        // 🔥 FAIR (NUEVO 🔥)
        $fairs = \App\Models\Fair::select(
            'id',
            'title',
            'fecha',
            'city_id',
            'province_id',
            'district_id',
            'place',
            'user_id',
            'visible',
            'resultados',
            'cancelado',
            'reprogramado',
            'unidad',
            'fairtype_id'
        )
            ->with([
                'region:id,name',
                'provincia:id,name',
                'distrito:id,name',
                'fairType:id,name',
                'profile:id,user_id,name,lastname,middlename'
            ])
            ->get()
            ->map(function ($item) {
                return [
                    'id'        => 'fair-' . $item->id,
                    'tabla'     => 'fairs',
                    'row_id'    => $item->id,

                    'unidad'    => $item->unidad ?? 'UGO',
                    'visible'   => $item->visible ?? 0,
                    'estado'    => null,

                    'tipo'      => 'FAIR',

                    'titulo'    => $item->title ? mb_strtoupper($item->title, 'UTF-8') : null,
                    'tipoActividad' => $item->fairType->name ?? null,

                    // 🔥 IMPORTANTE: FAIR usa "fecha"
                    'date'      => optional($item->fecha)->format('Y-m-d'),

                    'resultados'   => $item->resultados ?? null,
                    'cancelado'    => $item->cancelado ?? null,
                    'reprogramado' => $item->reprogramado ?? null,

                    'region'    => $item->region->name ?? null,
                    'provincia' => $item->provincia->name ?? null,
                    'distrito'  => $item->distrito->name ?? null,
                    'direccion' => mb_strtoupper($item->place ?? '', 'UTF-8'),

                    'registrador' => $item->profile
                        ? mb_strtoupper(
                            $item->profile->name . ' ' .
                                $item->profile->lastname . ' ' .
                                $item->profile->middlename,
                            'UTF-8'
                        )
                        : null,
                ];
            });

        // 🔥 UNIFICAR TODO
        return $attendances
            ->merge($ugse)
            ->merge($fairs);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('pageSize', 10);
        $page    = $request->input('page', 1);
        $year    = $request->input('year');
        $date    = $request->input('date');

        $collection = $this->getUnifiedEvents();

        // 🔍 YEAR
        if ($year) {
            $collection = $collection->filter(
                fn($item) =>
                $item['date'] && substr($item['date'], 0, 4) == $year
            );
        }

        // 🔍 DATE EXACTA
        if ($date) {
            $collection = $collection->filter(
                fn($item) =>
                $item['date'] === $date
            );
        }

        // 🔥 ORDEN REAL
        $collection = $collection
            ->sortByDesc('date')
            ->values();

        // 🔥 PAGINADO
        $total = $collection->count();

        $items = $collection
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return response()->json([
            'data' => [
                'data'         => $items,
                'total'        => $total,
                'current_page' => (int) $page,
                'per_page'     => (int) $perPage,
            ],
            'status' => 200
        ]);
    }
    // private function mapEvents($item)
    // {
    //     return [
    //         'id'                => $item->id,

    //     ];
    // }


    public function getEventsDots(Request $request)
    {
        $yearMonth = $request->input('year_month'); // "YYYY-MM"
        $cityId    = $request->input('city_id'); // opcional

        if (!$yearMonth) {
            return response()->json([
                ['key' => 'dot-ugse', 'dot' => 'red', 'dates' => []],
                ['key' => 'dot-ugo', 'dot' => 'blue', 'dates' => []]
            ]);
        }

        [$year, $month] = explode('-', $yearMonth);

        // 🔥 TRAER DATA UNIFICADA
        $events = $this->getUnifiedEvents();

        // 🔍 FILTRAR POR MES
        $events = $events->filter(function ($event) use ($year, $month) {
            return $event['date']
                && substr($event['date'], 0, 4) == $year
                && substr($event['date'], 5, 2) == str_pad($month, 2, '0', STR_PAD_LEFT);
        });

        // 🔍 FILTRO POR CIUDAD (opcional)
        if ($cityId) {
            $events = $events->filter(function ($event) use ($cityId) {
                return $event['region_id'] ?? null == $cityId;
            });
        }

        $redDates  = [];
        $blueDates = [];

        foreach ($events as $event) {

            if (!$event['date']) continue;

            // 🔥 CLAVE: usar unidad (no officePnte)
            $isUGO = $event['unidad'] === 'UGO';

            // formato ISO para calendario
            $dateStr = \Carbon\Carbon::parse($event['date'])->toISOString();

            if ($isUGO) {
                if (!in_array($dateStr, $blueDates)) {
                    $blueDates[] = $dateStr;
                }
            } else {
                if (!in_array($dateStr, $redDates)) {
                    $redDates[] = $dateStr;
                }
            }
        }

        return response()->json([
            ['key' => 'dot-ugse', 'dot' => 'red', 'dates' => $redDates],
            ['key' => 'dot-ugo', 'dot' => 'blue', 'dates' => $blueDates]
        ]);
    }

    // public function getEventsByDate(Request $request)
    // {
    //     // Obtener filtros desde la solicitud
    //     $dateSelected = $request->input('dateSelected'); // Fecha específica a buscar
    //     $offices = (array) $request->input('office', []); // Oficinas a filtrar (array)
    //     $cityId = $request->input('city_id'); // ID de la ciudad opcional

    //     // Si no se proporciona una fecha, retornar respuesta vacía
    //     if (!$dateSelected) {
    //         return response()->json([
    //             'message' => 'Debe proporcionar una fecha válida.',
    //             'events' => []
    //         ], 400);
    //     }

    //     if (!$offices) {
    //         return response()->json([
    //             'message' => 'Debe proporcionar una fecha válida.',
    //             'events' => []
    //         ], 400);
    //     }

    //     // Construcción de la consulta de eventos
    //     $eventsQuery = Event::with('officePnte', 'region')
    //         ->where(function ($query) use ($dateSelected) {
    //             $query->where('dateStart', '<=', $dateSelected)
    //                 ->where('dateEnd', '>=', $dateSelected);
    //         });

    //     // Filtro por oficinas si se proporcionan
    //     if (!empty($offices)) {
    //         $eventsQuery->whereHas('officePnte', function ($query) use ($offices) {
    //             $query->whereIn('office', $offices);
    //         });
    //     }

    //     // Filtro por city_id si se proporciona
    //     if (!empty($cityId)) {
    //         $eventsQuery->where('city_id', $cityId);
    //     }

    //     // Obtener los eventos
    //     $events = $eventsQuery->orderBy('dateStart', 'asc')->get();

    //     // Mapear los eventos para devolver solo los datos requeridos
    //     $formattedEvents = $events->map(function ($event) {
    //         // Definir el color del punto según la oficina
    //         $office = $event->officePnte->office ?? 'Desconocido';
    //         $color = ($office === 'UGO') ? 'blue' : 'red';

    //         // Generar la lista de fechas entre dateStart y dateEnd
    //         $start = Carbon::parse($event->dateStart);
    //         $end = Carbon::parse($event->dateEnd);
    //         $dates = [];

    //         while ($start->lte($end)) {
    //             $dates[] = $start->toDateString(); // Formato "YYYY-MM-DD"
    //             $start->addDay();
    //         }

    //         return [
    //             'id'            => $event->id,
    //             'id_pnte'       => $event->officePnte->name ?? '-',
    //             'office'        => $office,
    //             'region'        => $event->region->name ?? null,
    //             'province'      => $event->province->name ?? null,
    //             'district'      => $event->district->name ?? null,
    //             'place'         => $event->place ?? null,
    //             'dot'           => $color,
    //             'dates'         => $dates,
    //             'titleComplete' => $event->title,
    //             'title'         => Str::limit($event->title, 60, '...'),
    //             'organiza'      => $event->organiza ?? null,
    //             'numMypes'      => $event->numMypes ?? null,
    //             'start'         => $event->start ? Carbon::parse($event->start)->format('h:i A') : null,
    //             'end'           => $event->end ? Carbon::parse($event->end)->format('h:i A') : null,
    //             'description'   => Str::limit($event->description, 100, '...'),
    //             'descripionAll' => $event->description,
    //             'nameUser'      => $event->nameUser,
    //             'link'          => $event->link,
    //             'resultado'     => $event->resultado,
    //             'descriptionparse' => Str::limit(strip_tags($event->description), 100, '...'),
    //             'dateStart'     => $event->dateStart,
    //             'dateEnd'       => $event->dateEnd,
    //             'rescheduled'   => $event->rescheduled ?? null,
    //             'canceled'      => $event->canceled ?? null
    //         ];
    //     });

    //     // Retornar los eventos formateados
    //     return response()->json([
    //         'message' => 'Eventos obtenidos correctamente.',
    //         'status'  => 200,
    //         'data'    => $formattedEvents
    //     ]);
    // }

    public function getEventsByDate(Request $request)
    {
        $dateSelected = $request->input('dateSelected'); // "YYYY-MM-DD"

        if (!$dateSelected) {
            return response()->json([
                'message' => 'Debe proporcionar una fecha válida.',
                'events'  => []
            ], 400);
        }

        // 🔥 DATA UNIFICADA
        $events = $this->getUnifiedEvents();

        // 🔍 FILTRO POR FECHA EXACTA
        $filtered = $events->filter(
            fn($event) =>
            $event['date'] === $dateSelected
        );

        // 🔥 FORMATO CALENDARIO
        $formatted = $filtered->map(function ($event) {

            // 🔥 COLOR POR UNIDAD
            $dotColor = $event['unidad'] === 'UGO' ? 'blue' : 'red';

            return [
                'id'            => $event['id'],
                'title'         => $event['titulo'],

                // 🔥 AHORA ES UNIDAD
                'tipo'          => $event['unidad'],

                'region'        => $event['region'],
                'provincia'     => $event['provincia'],
                'distrito'      => $event['distrito'],
                'direccion'     => $event['direccion'],

                'tipoActividad' => $event['tipoActividad'],

                'dot' => $dotColor,

                'dates' => [$event['date']],

                // 🔥 CAMPOS UNIFICADOS
                'cancelado'    => $event['cancelado'] ?? null,
                'reprogramado' => $event['reprogramado'] ?? null,
                'resultados'   => $event['resultados'] ?? null,
            ];
        })->values();

        return response()->json([
            'message' => 'Eventos obtenidos correctamente.',
            'status'  => 200,
            'data'    => $formatted
        ]);
    }


    public function deleteEventById($idEvent)
    {
        $user_role = getUserRole();
        $user_id = $user_role['user_id'];


        $event = Event::find($idEvent);

        if ($event) {
            $event->delete();
            return response()->json(['message' => 'El evento ha sido eliminado', 'status' => 200]);
        } else {
            return response()->json(['error' => 'El evento no pudo ser encontrado', 'status' => 404]);
        }
    }



    // dianita sala de reuniones

    public function storeRoom(Request $request)
    {
        // try {
        //     $user = getUserRole();
        //     $user_id = $user['user_id'];

        //     $requestData = $request->all();
        //     $requestData['user_id'] = $user_id;

        //     // if (!empty($requestData['startDate'])) {
        //     //     $requestData['startDate'] = Carbon::parse($requestData['startDate'])->format('Y-m-d H:i:s');
        //     // }

        //     if (!empty($request->id)) {
        //         $room = Rooms::find($request->id);

        //         if ($room) {
        //             $room->update($requestData);

        //             return response()->json([
        //                 'message' => 'Sala de reuniones actualizada correctamente.',
        //                 'data' => $room
        //             ], 200);
        //         }

        //         return response()->json(['message' => 'Sala no encontrada.'], 404);
        //     }


        //     $room = Rooms::create($requestData);

        //     return response()->json([
        //         'message' => 'Sala de reuniones creada correctamente.',
        //         'data' => $room
        //     ], 201);
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'message' => 'Error al procesar la solicitud.',
        //         'error' => $e->getMessage()
        //     ], 500);
        // }
    }

    public function updateResultados(Request $request)
    {
        try {

            $request->validate([
                'resultado' => 'nullable|string',
                'tabla'     => 'required|string',
                'row_id'    => 'required|integer',
            ]);

            $tabla  = $request->tabla;
            $rowId  = $request->row_id;
            $valor  = $request->resultado;

            switch ($tabla) {

                case 'attendancelist':
                    $model = \App\Models\Attendance::find($rowId);
                    break;

                case 'mp_eventos':
                    $model = \App\Models\MPEvent::find($rowId);
                    break;

                case 'fairs':
                    $model = \App\Models\Fair::find($rowId);
                    break;

                default:
                    return response()->json([
                        'message' => 'Tabla no válida',
                        'status'  => 400
                    ], 400);
            }

            if (!$model) {
                return response()->json([
                    'message' => 'Registro no encontrado',
                    'status'  => 404
                ], 404);
            }

            // 🔥 ACTUALIZAR RESULTADO
            $model->resultados = $valor;
            $model->save();

            return response()->json([
                'message' => 'Resultado actualizado correctamente',
                'data'    => $model,
                'status'  => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar',
                'error'   => $e->getMessage(),
                'status'  => 500
            ], 500);
        }
    }

    public function destroy($id)
    {
        $workshop = Event::findOrFail($id);
        $workshop->delete();
        return response()->json(['message' => 'Evento eliminado correctamente', 'status' => 200]);
    }

    public function updateObservation($id, Request $request)
    {
        try {
            $event = Event::findOrFail($id);

            $data = $request->only(['dateStart', 'dateEnd', 'start', 'end', 'rescheduled', 'canceled']);

            // Lógica condicional para los campos rescheduled y canceled
            if ($request->has('rescheduled')) {
                $data['canceled'] = null;
            } elseif ($request->has('canceled')) {
                $data['rescheduled'] = null;
            }

            $event->update($data);

            return response()->json([
                'message' => 'Evento actualizado correctamente',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo actualizar el evento',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
