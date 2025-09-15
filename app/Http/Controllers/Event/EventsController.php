<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
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


    public function index(Request $request)
    {
        $filters = [
            'name'      => $request->input('name'),
            'year'      => $request->input('year'),
            // 'asesor'    => $request->input('asesor'),
            'dateStart' => $request->input('dateStart'),
            'dateEnd'   => $request->input('dateEnd'),
            'offices'   => $request->input('offices'),
            'type'      => $request->input('type')
        ];

        $query = Event::query();

        $query->withAdvisoryRangeDate($filters);

        $advisories = $query->paginate(150)->through(function ($advisory) {
            return $this->mapEvents($advisory);
        });

        return response()->json([
            'data'   => $advisories,
            'status' => 200
        ]);
    }

    private function mapEvents($item)
    {
        return [
            'id'                => $item->id,
            'office_id'         => $item->officePnte->id,
            'city'              => $item->region->name ?? null,
            'province'          => $item->province->name ?? null,
            'district'          => $item->district->name ?? null,
            'place'             => $item->place,
            'office'            => $item->officePnte->office,
            'area'              => $item->officePnte->name,
            'title'             => $item->title,
            'dateStart'         => $item->dateStart,
            'dateEnd'           => $item->dateEnd,
            'start'             => $item->start,
            'end'               => $item->end,
            'description'       => strip_tags($item->description),
            'resultado'         => strip_tags($item->resultado),
            'nameUser'          => $item->nameUser,
            'link'              => $item->link,
            'all'               => $item
        ];
    }


    public function getEventsDots(Request $request)
    {
        $yearMonth = $request->input('year_month'); // Formato esperado: "YYYY-MM"
        $offices = (array) $request->input('office', []); // Forzar siempre un array
        $cityId = $request->input('city_id'); // Obtener city_id

        if (!$yearMonth) {
            return response()->json([
                'key' => 'dot',
                'dot' => null,
                'dates' => []
            ]);
        }

        if (!$offices) {
            return response()->json([
                'key' => 'dot-offices',
                'dot' => null,
                'dates' => []
            ]);
        }

        [$year, $month] = explode('-', $yearMonth);
        $year = (int) $year;
        $month = (int) $month;

        $startOfMonth = Carbon::create($year, $month, 1, 0, 0, 0, 'UTC')->startOfMonth();
        $endOfMonth = Carbon::create($year, $month, 1, 0, 0, 0, 'UTC')->endOfMonth();



        $eventsQuery = Event::with('officePnte')
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('dateStart', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('dateEnd', [$startOfMonth, $endOfMonth])
                    ->orWhere(function ($query) use ($startOfMonth, $endOfMonth) {
                        $query->where('dateStart', '<=', $startOfMonth)
                            ->where('dateEnd', '>=', $endOfMonth);
                    })
                    ->orWhereRaw("DATE(dateStart) = DATE(dateEnd) AND DATE(dateStart) BETWEEN ? AND ?", [
                        $startOfMonth->toDateString(),
                        $endOfMonth->toDateString()
                    ]);
            });

        if (!empty($offices)) {
            $eventsQuery->whereHas('officePnte', function ($subQuery) use ($offices) {
                $subQuery->whereIn('office', $offices);
            });
        }

        if (!empty($cityId)) {
            $eventsQuery->where('city_id', $cityId);
        }

        $events = $eventsQuery->orderBy('id', 'desc')->get();

        if ($events->isEmpty()) {
            return response()->json([
                ['key' => 'dot-ugse', 'dot' => 'red', 'dates' => []],
                ['key' => 'dot-ugo', 'dot' => 'blue', 'dates' => []]
            ]);
        }

        $redDates = [];
        $blueDates = [];

        foreach ($events as $event) {
            $officeName = $event->officePnte->office ?? null;
            $dotColor = $officeName === 'UGO' ? 'blue' : 'red';

            $start = Carbon::parse($event->dateStart)->max($startOfMonth);
            $end = Carbon::parse($event->dateEnd)->min($endOfMonth);

            while ($start->lte($end)) {
                $dateStr = $start->toISOString();

                if ($dotColor === 'red' && !in_array($dateStr, $redDates)) {
                    $redDates[] = $dateStr;
                } elseif ($dotColor === 'blue' && !in_array($dateStr, $blueDates)) {
                    $blueDates[] = $dateStr;
                }

                $start->addDay();
            }
        }

        return response()->json([
            ['key' => 'dot-ugse', 'dot' => 'red', 'dates' => $redDates],
            ['key' => 'dot-ugo', 'dot' => 'blue', 'dates' => $blueDates]
        ]);
    }


































    public function getEventsByDate(Request $request)
    {
        // Obtener filtros desde la solicitud
        $dateSelected = $request->input('dateSelected'); // Fecha específica a buscar
        $offices = (array) $request->input('office', []); // Oficinas a filtrar (array)
        $cityId = $request->input('city_id'); // ID de la ciudad opcional

        // Si no se proporciona una fecha, retornar respuesta vacía
        if (!$dateSelected) {
            return response()->json([
                'message' => 'Debe proporcionar una fecha válida.',
                'events' => []
            ], 400);
        }

        if (!$offices) {
            return response()->json([
                'message' => 'Debe proporcionar una fecha válida.',
                'events' => []
            ], 400);
        }

        // Construcción de la consulta de eventos
        $eventsQuery = Event::with('officePnte', 'region')
            ->where(function ($query) use ($dateSelected) {
                $query->where('dateStart', '<=', $dateSelected)
                    ->where('dateEnd', '>=', $dateSelected);
            });

        // Filtro por oficinas si se proporcionan
        if (!empty($offices)) {
            $eventsQuery->whereHas('officePnte', function ($query) use ($offices) {
                $query->whereIn('office', $offices);
            });
        }

        // Filtro por city_id si se proporciona
        if (!empty($cityId)) {
            $eventsQuery->where('city_id', $cityId);
        }

        // Obtener los eventos
        $events = $eventsQuery->orderBy('dateStart', 'asc')->get();

        // Mapear los eventos para devolver solo los datos requeridos
        $formattedEvents = $events->map(function ($event) {
            // Definir el color del punto según la oficina
            $office = $event->officePnte->office ?? 'Desconocido';
            $color = ($office === 'UGO') ? 'blue' : 'red';

            // Generar la lista de fechas entre dateStart y dateEnd
            $start = Carbon::parse($event->dateStart);
            $end = Carbon::parse($event->dateEnd);
            $dates = [];

            while ($start->lte($end)) {
                $dates[] = $start->toDateString(); // Formato "YYYY-MM-DD"
                $start->addDay();
            }

            return [
                'id'            => $event->id,
                'id_pnte'       => $event->officePnte->name ?? '-',
                'office'        => $office,
                'region'        => $event->region->name ?? null,
                'province'      => $event->province->name ?? null,
                'district'      => $event->district->name ?? null,
                'place'         => $event->place ?? null,
                'dot'           => $color,
                'dates'         => $dates,
                'titleComplete' => $event->title,
                'title'         => Str::limit($event->title, 60, '...'),
                'organiza'      => $event->organiza ?? null,
                'numMypes'      => $event->numMypes ?? null,
                'start'         => $event->start ? Carbon::parse($event->start)->format('h:i A') : null,
                'end'           => $event->end ? Carbon::parse($event->end)->format('h:i A') : null,
                'description'   => Str::limit($event->description, 100, '...'),
                'descripionAll' => $event->description,
                'nameUser'      => $event->nameUser,
                'link'          => $event->link,
                'resultado'     => $event->resultado,
                'descriptionparse' => Str::limit(strip_tags($event->description), 100, '...'),
                'dateStart'     => $event->dateStart,
                'dateEnd'       => $event->dateEnd,
                'rescheduled'   => $event->rescheduled ?? null,
                'canceled'      => $event->canceled ?? null
            ];
        });

        // Retornar los eventos formateados
        return response()->json([
            'message' => 'Eventos obtenidos correctamente.',
            'status'  => 200,
            'data'    => $formattedEvents
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

    public function update(Request $request, $id)
    {
        try {
            $event = Event::findOrFail($id);

            $event->update($request->all());

            return response()->json([
                'message' => 'Evento actualizado correctamente',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar la solicitud.',
                'error' => $e->getMessage()
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
