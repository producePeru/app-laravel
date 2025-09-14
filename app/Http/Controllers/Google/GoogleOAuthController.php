<?php

namespace App\Http\Controllers\Google;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Drive;
use App\Models\User;

class GoogleOAuthController extends Controller
{
    public function redirectToGoogle()
    {
        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->addScope(Google_Service_Drive::DRIVE_FILE);
        $client->setAccessType('offline'); // Para obtener refresh_token
        $client->setPrompt('consent');     // Forzar refresh_token la primera vez

        return redirect()->away($client->createAuthUrl());
    }

    // 2. Callback de Google
    public function handleGoogleCallback(Request $request)
    {
        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        $token = $client->fetchAccessTokenWithAuthCode($request->code);

        if (isset($token['error'])) {
            return response()->json(['error' => $token['error']], 400);
        }

        // 🔑 Guardar el token en el usuario actual
        $user = User::first(); // Aquí ajusta si usas auth()->id()
        $user->google_token = json_encode($token);
        $user->save();

        return response()->json([
            'message' => 'Autenticación completada',
            'token' => $token
        ]);
    }

    // 3. Obtener un cliente autorizado (reutilizable)
    public function getAuthorizedClient(User $user)
    {
        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->addScope(Google_Service_Drive::DRIVE_FILE);

        $token = json_decode($user->google_token, true);
        $client->setAccessToken($token);

        // Refrescar si expiró
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $user->google_token = json_encode($client->getAccessToken());
            $user->save();
        }

        return $client;
    }
}
