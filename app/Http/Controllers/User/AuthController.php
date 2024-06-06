<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use PhpParser\Node\Stmt\Return_;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private function getUserRole()
    {
        $user_id = Auth::user()->id;

        $roleUser = DB::table('role_user')
        ->where('user_id', $user_id)
        ->first();

        if ($user_id != $roleUser->user_id) {
            return response()->json(['message' => 'Este rol no es correcto', 'status' => 404]);
        }

        return [
            "role_id" => $roleUser->role_id,
            'user_id' => $user_id,
            'user_dni' => $roleUser->dniuser
        ];
    }

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
            $email = $user->email;

            // Construir un array con las vistas decodificadas
            $views = $user->views->map(function ($view) {
                return json_decode($view->views, true);
            })->flatten();

            return response()->json(['token' => $token, 'profile' => $profile, 'email' => $email, 'role' => $role, 'views' => $views], 200);
        }

        return response()->json(['message' => 'Credenciales incorrectas'], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente'], 200);
    }

    public function dniDataUser2($num)
    {
        $apiUrl = "https://api.apis.net.pe/v2/reniec/dni?numero={$num}";

        try {
            $client = new Client();
            $response = $client->request('GET', $apiUrl, [
                'headers' => [
                    'Authorization' => 'apis-token-8840.KxvOhP9QQt-oV3cIMWgVUP1691s4SYP8',
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['status' => 404, 'error' => $e->getMessage()]);
        }
    }

    public function dniDataUser($type, $num)
    {
        // $token = "8FFHIKZBunh3TvTdTmeq0G2pnfC2qsv7hXZm8eoCZ4vr5EbrZ5mjmjL0fssdv0ZG";
        $token = 'SS5XlFCtFBxyIUYjIitORpYenW875gZnsFIaSRBwc8Z0hhBOsXlb9zRmav8rAs4s';

        $apiUrl = "https://api.sunat.dev/{$type}/{$num}?apikey={$token}";
        try {
            $client = new Client();
            $response = $client->request('GET', $apiUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);
            $data = json_decode($response->getBody(), true);
            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['status' => 404]);
        }
    }

    public function passwordReset(Request $request)
    {
        $user_id = $this->getUserRole()['user_id'];
        $user_dni = $this->getUserRole()['user_dni'];

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|confirmed|min:8',
            'dni' => 'required|string'
        ]);

        if($user_dni == $request->dni) {
            $user = Auth::user();

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'La contraseña actual no es válida'], 400);
            }

            $user->password = bcrypt($request->password);
            $user->save();

            return response()->json(['message' => 'Contraseña restablecida correctamente'], 200);
        }
    }
}
