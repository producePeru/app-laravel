<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventRecurrence;
use App\Models\EventCategory;
use App\Models\Rooms;
use Carbon\Carbon;
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


    public function index()
    {
        try {

            $events = Event::with('officePnte')->orderBy('dateStart', 'desc')->paginate(50);

            $events->getCollection()->transform(function ($event) {
                $event->description = strip_tags($event->description);
                return $event;
            });

            return response()->json(['data' => $events, 'status' => 200]);
        } catch (QueryException $e) {
            return response()->json(['message' => 'La validación ha fallado', 'errors' => $e->errors()], 400);
        }
    }


    public function getEventsDots(Request $request, $yearMonth)
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            return response()->json(['error' => 'Formato de fecha inválido. Usa YYYY-MM.'], 400);
        }

        $officeMap = [
            'dte'    => 1,
            'ferias' => 2,
            'ftm'    => 3,
            'mp'     => 4,
            'pte'    => 5,
            'rd'     => 6,
            'ougse'  => 7,
            'dif'    => 8,
        ];

        $filterOffices = array_values(array_filter(array_map(
            fn($param) => $officeMap[$param] ?? null,
            $request->query()
        )));

        if (empty($filterOffices)) {
            return response()->json(['data' => [], 'status' => 200]);
        }

        $colors = [
            1 => 'gray',
            2 => 'red',
            3 => 'orange',
            4 => 'yellow',
            5 => 'green',
            6 => 'blue',
            7 => 'purple',
            8 => 'pink',
        ];

        $startDate = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $yearMonth)->endOfMonth();

        $events = Event::whereBetween('dateStart', [$startDate, $endDate])
            ->whereIn('id_pnte', $filterOffices)
            ->get();

        $formattedEvents = [];

        foreach ($events as $event) {
            $office = $event->id_pnte;
            $color = $colors[$office] ?? 'gray';

            if (!isset($formattedEvents[$color])) {
                $formattedEvents[$color] = [
                    'key' => $office,
                    'dot' => $color,
                    'dates' => []
                ];
            }

            $start = Carbon::parse($event->dateStart);
            $end = Carbon::parse($event->dateEnd);

            while ($start->lte($end)) {
                $formattedEvents[$color]['dates'][] = $start->toISOString();
                $start->addDay();
            }
        }

        return response()->json([
            'data' => array_values($formattedEvents),
            'status' => 200
        ]);
    }


    public function getEventsByDate(Request $request, $date)
    {
        $officeMap = [
            'dte'    => 1,
            'ferias' => 2,
            'ftm'    => 3,
            'mp'     => 4,
            'pte'    => 5,
            'rd'     => 6,
            'ougse'  => 7,
            'dif'    => 8,
        ];

        $officeKeys = $request->query();

        if (empty($officeKeys)) {
            return response()->json([
                'message' => 'No se recibieron parámetros válidos.',
                'status'  => 200,
                'data'    => []
            ]);
        }

        $officeIds = collect($officeKeys)->map(fn($key) => $officeMap[$key] ?? null)->filter()->values();

        $query = Event::whereDate('dateStart', '<=', $date)->whereDate('dateEnd', '>=', $date);

        if ($officeIds->isNotEmpty()) {
            $query->whereIn('id_pnte', $officeIds);
        }

        $colors = [
            1 => 'gray',
            2 => 'red',
            3 => 'orange',
            4 => 'yellow',
            5 => 'green',
            6 => 'blue',
            7 => 'purple',
            8 => 'pink',
        ];

        $events = $query->get()->map(function ($event) use ($colors) {
            $office = $event->id_pnte;
            $color = $colors[$office] ?? 'gray';

            $dates = [];
            $start = Carbon::parse($event->dateStart);
            $end = Carbon::parse($event->dateEnd);

            while ($start->lte($end)) {
                $dates[] = $start->toISOString();
                $start->addDay();
            }

            return [
                'id_pnte'       => $office,
                'dot'           => $color,
                'dates'         => $dates,
                'titleComplete' => $event->title,
                'title'         => Str::limit($event->title, 60, '...'),
                'organiza'      => $event->organiza,
                'numMypes'      => $event->numMypes,

                'start'         => $event->start ? Carbon::parse($event->start)->format('h:i A') : null,
                'end'           => $event->end ? Carbon::parse($event->end)->format('h:i A') : null,
                'description'   => Str::limit($event->description, 100, '...'),
                'descripionAll' => $event->description,
                'nameUser'      => $event->nameUser,
                'link'          => $event->link,
                'resultado'     => $event->resultado,
                'descriptionparse' => Str::limit(strip_tags($event->description), 100, '...'),
            ];
        });

        return response()->json([
            'message' => $events->isEmpty() ? 'No hay eventos para esta fecha.' : 'Eventos encontrados.',
            'status'  => 200,
            'data'    => $events
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
    public function listRooms()
    {
        $rooms = Rooms::all();

        return response()->json([
            'data' => $rooms,
            'status' => 200
        ]);
    }

    public function storeRoom(Request $request)
    {
        try {
            $user = getUserRole();
            $user_id = $user['user_id'];

            $requestData = $request->all();
            $requestData['user_id'] = $user_id;

            // if (!empty($requestData['startDate'])) {
            //     $requestData['startDate'] = Carbon::parse($requestData['startDate'])->format('Y-m-d H:i:s');
            // }

            if (!empty($request->id)) {
                $room = Rooms::find($request->id);

                if ($room) {
                    $room->update($requestData);

                    return response()->json([
                        'message' => 'Sala de reuniones actualizada correctamente.',
                        'data' => $room
                    ], 200);
                }

                return response()->json(['message' => 'Sala no encontrada.'], 404);
            }


            $room = Rooms::create($requestData);

            return response()->json([
                'message' => 'Sala de reuniones creada correctamente.',
                'data' => $room
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar la solicitud.',
                'error' => $e->getMessage()
            ], 500);
        }
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
}
