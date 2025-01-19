<?php

namespace App\Http\Controllers\Google;

use App\Http\Controllers\Controller;
use App\Services\GoogleCalendarService;
use DateTime;
use Illuminate\Http\Request;

class GoogleCalendarController extends Controller
{
    protected $calendarService;

    public function __construct(GoogleCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function createEvent(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|string',
                'summary' => 'required|string',
                'start' => 'required|date',
                'end' => 'required|date',
                'allDay' => 'required|boolean', // Determina si es todo el día (1 o 0)
                'description' => 'nullable|string', // 'nullable' para permitir valores opcionales
            ]);

            // Preparar los datos del evento
            $eventData = [
                'summary' => $validated['summary'],
                'description' => $validated['description'] ?? '', // Descripción opcional
            ];

            if ($validated['allDay']) {
                // Incrementar la fecha de 'end' en 1 día para abarcar hasta el final del día
                $endDate = (new DateTime($validated['end']))->modify('+1 day')->format('Y-m-d');

                // Evento de todo el día
                $eventData['start'] = [
                    'date' => $validated['start'], // Solo la fecha
                ];
                $eventData['end'] = [
                    'date' => $endDate, // Fecha ajustada
                ];
            } else {
                // Evento con hora
                $eventData['start'] = [
                    'dateTime' => $validated['start'],
                    'timeZone' => 'America/Lima',
                ];
                $eventData['end'] = [
                    'dateTime' => $validated['end'],
                    'timeZone' => 'America/Lima',
                ];
            }

            $idCalendar = match ($validated['type']) {
                'rooms' => "88b1022cca25012285652fcfcc1c9af8c2ea7d7ef31005e32b14b61c2604f8e3@group.calendar.google.com",
                'events' => "7bec50060b58f4543de7386bdbdde4f931e7486f03dabd54e332258c82744b6c@group.calendar.google.com",
                default => throw new \Exception("Tipo no válido para idCalendar"),
            };


            $event = $this->calendarService->createEvent($idCalendar, $eventData);

            return response()->json(['status' => 200, 'message' => 'Evento creado', 'event' => $event]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Captura errores de validación y retorna un mensaje claro
            return response()->json([
                'success' => false,
                'message' => 'Error en la validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Captura cualquier otro error y devuelve información adicional
            return response()->json([
                'success' => false,
                'message' => 'Se produjo un error al procesar la solicitud',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




    public function listEvents(Request $request, $type)
    {
        try {

            $idCalendar = match ($type) {
                'rooms' => "88b1022cca25012285652fcfcc1c9af8c2ea7d7ef31005e32b14b61c2604f8e3@group.calendar.google.com",
                'events' => "7bec50060b58f4543de7386bdbdde4f931e7486f03dabd54e332258c82744b6c@group.calendar.google.com",
                default => throw new \Exception("Tipo no válido: $type"),
            };

            $search = $request->input('search');
            $pageToken = $request->input('pageToken');

            $events = $this->calendarService->listEvents($idCalendar, 50, $pageToken, $search);

            return response()->json($events);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }




    public function deleteEvent($eventId, $type)
    {
        try {

            $idCalendar = match ($type) {
                'room' => "88b1022cca25012285652fcfcc1c9af8c2ea7d7ef31005e32b14b61c2604f8e3@group.calendar.google.com",
                'event' => "7bec50060b58f4543de7386bdbdde4f931e7486f03dabd54e332258c82744b6c@group.calendar.google.com",
                default => throw new \Exception("Tipo no válido: $type"),
            };

            $response = $this->calendarService->deleteEvent($eventId, $idCalendar);

            return response()->json(['status' => 200, 'message' => 'Registro eliminado', 'response' => $response]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
