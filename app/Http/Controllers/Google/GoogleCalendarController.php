<?php

namespace App\Http\Controllers\Google;

use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;

class GoogleCalendarController extends Controller
{
    protected $calendarService;

    public function __construct(GoogleCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function redirectToGoogle()
    {
        $authUrl = $this->calendarService->getAuthUrl();
        return redirect()->away($authUrl);
    }

    public function handleGoogleCallback(Request $request)
    {
        $authCode = $request->input('code');
        $accessToken = $this->calendarService->authenticate($authCode);

        // Puedes guardar el token en la base de datos o en la sesión

        return redirect('/'); // Redirige a donde quieras
    }

    public function listEvents(Request $request)
    {
        $accessToken = $request->input('access_token');
        $eventData = $request->input('event');

        try {
            $event = $this->calendarService->createEvent($accessToken, $eventData);
            return response()->json($event, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
