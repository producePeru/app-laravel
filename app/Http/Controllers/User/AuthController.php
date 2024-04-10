<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use GuzzleHttp\Client;
use PhpParser\Node\Stmt\Return_;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
            $token = $user->createToken('AuthToken')->plainTextToken;
            $profile = $user->profile->only(['id', 'name', 'lastname', 'middlename', 'documentnumber', 'user_id']);
            $role = $user->roles;

            // Construir un array con las vistas decodificadas
            $views = $user->views->map(function ($view) {
                return json_decode($view->views, true);
            })->flatten();

            return response()->json(['token' => $token, 'profile' => $profile, 'role' => $role, 'views' => $views], 200);
        }

        return response()->json(['message' => 'Credenciales incorrectas'], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente'], 200);
    }

    public function dniDataUser($num)
    {
        $apiUrl = "https://api.apis.net.pe/v2/reniec/dni?numero={$num}";

        try {
            $client = new Client();
            $response = $client->request('GET', $apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer apis-token-6688.nekxM8GmGEHYD9qosrnbDWNxQlNOzaT5',
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['status' => 404]);
        }
    }
}
