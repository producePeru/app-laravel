<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventRecurrence;
use App\Models\EventCategory;
use Carbon\Carbon;

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


    public function index(Request $request)
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
            $originalStart = Carbon::parse($event->start);

            // Filtrar eventos repetidos anualmente
            if ($event->repetir === 'year') {
                $originalMonth = $originalStart->month;

                if ($originalMonth == $month) {
                    $newEvent = clone $event;
                    $newEvent->start = $originalStart->year($year);
                    $newEvent->end = Carbon::parse($event->end)->year($year);

                    // Solo incluir eventos si la fecha es igual o posterior a la actual
                    if (Carbon::parse($newEvent->start) >= $currentDate) {
                        $eventsWithRepetition[] = $newEvent;
                    }
                }
            }

            // Filtrar eventos repetidos mensualmente
            if ($event->repetir === 'month') {
                if ($originalStart->year <= $year) {
                    $newEvent = clone $event;
                    $newEvent->start = $originalStart->year($year)->month($month);
                    $newEvent->end = Carbon::parse($event->end)->year($year)->month($month);

                    if ($newEvent->start->month == $month && Carbon::parse($newEvent->start) >= $currentDate) {
                        $eventsWithRepetition[] = $newEvent;
                    }
                }
            }

            // Filtrar eventos sin repetición
            if ($event->repetir === null) {
                $eventMonth = $originalStart->month;
                $eventYear = $originalStart->year;

                if ($eventMonth == $month && $eventYear == $year && $originalStart >= $currentDate) {
                    $eventsWithRepetition[] = $event;
                }
            }
        }

        // Retornar los eventos filtrados
        if (count($eventsWithRepetition) > 0) {
            $data = array_map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->nameEvent,
                    'start' => Carbon::parse($event->start)->format('Y-m-d H:i'),
                    'end' => Carbon::parse($event->end)->format('Y-m-d H:i'),
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

}
