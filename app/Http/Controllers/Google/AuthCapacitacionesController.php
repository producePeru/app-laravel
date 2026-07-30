<?php

namespace App\Http\Controllers\Google;

use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendarApi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class AuthCapacitacionesController extends Controller
{
    protected function client(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect_uri'));
        $client->setAccessType('offline');
        // IMPORTANTE: fuerza a Google a devolver el refresh_token
        // (si no, solo lo entrega la PRIMERA vez que el usuario autoriza)
        $client->setPrompt('consent');
        $client->addScope(GoogleCalendarApi::CALENDAR_EVENTS);

        return $client;
    }

    public function redirect(): RedirectResponse
    {
        return redirect($this->client()->createAuthUrl());
    }

    public function callback(Request $request)
    {
        // 1. Si Google rechazó o el usuario canceló
        if ($request->has('error')) {
            return response()->json([
                'status' => 'error',
                'google_error' => $request->get('error_description') ?? $request->get('error')
            ], 400);
        }

        $code = $request->get('code');

        if (!$code) {
            return response()->json(['error' => 'No se recibió el código en la petición'], 400);
        }

        try {
            $client = $this->client();
            // Intercambia el código de autorización por el array con tokens
            $token = $client->fetchAccessTokenWithAuthCode($code);

            // Si la librería de Google atrapa un error interno en la respuesta
            if (isset($token['error'])) {
                return response()->json([
                    'error_de_google' => $token['error'],
                    'descripcion' => $token['error_description'] ?? 'Error al intercambiar el código'
                ], 400);
            }

            // ¡ÉXITO! Aquí verás tu refresh_token
            return response()->json($token);
        } catch (\Exception $e) {
            return response()->json([
                'exception' => $e->getMessage()
            ], 500);
        }
    }
}
