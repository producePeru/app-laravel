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
                'summary' => 'required|string',
                'start' => 'required|date',
                'end' => 'required|date|after:start',
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

            // Crear el evento utilizando el servicio
            $event = $this->calendarService->createEvent($eventData);

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




    public function listEvents(Request $request)
    {
        try {
            $search = $request->input('search');
            $pageToken = $request->input('pageToken');
            $events = $this->calendarService->listEvents(null, 50, $pageToken, $search);
            return response()->json($events);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteEvent($eventId)
    {
        try {
            $response = $this->calendarService->deleteEvent($eventId);
            return response()->json(['status' => 200, 'message' => 'Evento eliminado', 'response' => $response]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
