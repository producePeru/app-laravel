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

        $validated = $request->validate([
            'summary' => 'required|string',
            'start' => 'required|date',
            'end' => 'required|date|after:start',
        ]);

        $eventData = [
            'summary' => $validated['summary'],
            'start' => [
                'dateTime' => $validated['start'],
                'timeZone' => 'America/Lima',
            ],
            'end' => [
                'dateTime' => $validated['end'],
                'timeZone' => 'America/Lima',
            ],
        ];

        $event = $this->calendarService->createEvent($eventData);

        return response()->json($event);
    }
}
