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
        $client = new \Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));

        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->addScope(\Google_Service_Drive::DRIVE_FILE);

        return redirect()->away($client->createAuthUrl());
    }

    public function handleGoogleCallback(Request $request)
    {
        $client = new \Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));

        $token = $client->fetchAccessTokenWithAuthCode($request->code);

        if (isset($token['error'])) {
            return "Error: " . $token['error'];
        }

        // Tu usuario dueño del Google Drive
        $user = User::first();
        $user->google_token = json_encode($token);
        $user->save();

        return "Google Drive conectado correctamente ✔";
    }

    // 3. Obtener un cliente autorizado (reutilizable)
    public function getAuthorizedClient(User $user)
    {
        $client = new \Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        $client->addScope(\Google_Service_Drive::DRIVE_FILE);

        $token = json_decode($user->google_token, true);
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $user->google_token = json_encode($client->getAccessToken());
            $user->save();
        }

        return $client;
    }
}
