<?php

namespace App\Http\Controllers\Google;

use App\Http\Controllers\Controller;
use App\Services\GoogleCalendarService;
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
    // Validación de la solicitud
    $validated = $request->validate([
        'summary' => 'required|string',
        'start' => 'required|date',
        'end' => 'required|date|after:start',
        'colorId' => 'nullable|string', // 'nullable' para permitir valores opcionales
        'description' => 'nullable|string', // 'nullable' para permitir valores opcionales
    ]);

    // Preparación de los datos del evento
    $eventData = [
        'summary' => $validated['summary'],
        'colorId' => $validated['colorId'] ?? '1', // Asignar un valor predeterminado si no está presente
        'description' => $validated['description'] ?? '', // Asignar una descripción vacía si no está presente
        'start' => [
            'dateTime' => $validated['start'],
            'timeZone' => 'America/Lima',
        ],
        'end' => [
            'dateTime' => $validated['end'],
            'timeZone' => 'America/Lima',
        ],
    ];

    // Crear el evento utilizando el servicio
    $event = $this->calendarService->createEvent($eventData);

    // Retornar la respuesta como JSON
    return response()->json($event);
}


    public function listEvents()
    {
        try {
            $events = $this->calendarService->listEvents();
            return response()->json($events);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
