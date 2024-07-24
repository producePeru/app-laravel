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
use App\Models\Token;

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
            $profile = $user->profile->only(['id', 'name', 'lastname', 'middlename', 'documentnumber', 'user_id', 'cde_id']);
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

    // ok //🚩 flag
    public function dniDataUser2($num)
    {

        $apiUrl = "https://api.apis.net.pe/v2/reniec/dni?numero={$num}";

        try {

            $tokenRecord = Token::where('status', 1)->first();

            if (!$tokenRecord) {
                return response()->json(['status' => 404, 'error' => 'Token no encontrado o inactivo']);
            }

            $client = new Client();
            $response = $client->request('GET', $apiUrl, [
                'headers' => [
                    'Authorization' => $tokenRecord->token,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if($data) {
                $tokenRecord->increment('count');
                return response()->json(['data' => $data, 'status' => 200]);
            } else {
                return response()->json(['status' => 403, 'error' => 'No se encontró el DNI']);
            }

            return response()->json(['data' => $user]);
        } catch (\Exception $e) {
            $tokenRecord->increment('count_bad');

            if ($e->getCode() == 429) {
                return response()->json(['status' => 429, 'message' => 'Ha superado el límite de solicitudes permitidas. Por favor, inténtalo el próximo mes.']);
            } else if ($e->getCode() == 401) {
                return response()->json(['status' => 401, 'message' => 'Token Incorrecto']);
            } else {
                return response()->json(['error' => $e->getMessage()]);
            }
        }
    }


    public function dniDataUser($type, $num)
    {
        // $token = 'IKxniM9qDMLhbwvC2D9YMoa40BtoOegM0Q5F4slRSfWdGToEt4AaaVxS8s9APpZ9';

        // $apiUrl = "https://api.sunat.dev/{$type}/{$num}?apikey={$token}";
        // try {
        //     $client = new Client();
        //     $response = $client->request('GET', $apiUrl, [
        //         'headers' => [
        //             'Accept' => 'application/json',
        //         ],
        //     ]);
        //     $data = json_decode($response->getBody(), true);
        //     return response()->json(['data' => $data]);
        // } catch (\Exception $e) {
        //     return response()->json(['status' => 404]);
        // }
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
