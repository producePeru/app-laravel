<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Token;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'role_id' => $roleUser->role_id,
            'user_id' => $user_id,
            'user_dni' => $roleUser->dniuser,
        ];
    }

    public function login(Request $request)
    {
        try {

            $request->validate([
                'login' => 'required|string',
                'password' => 'required|string',
            ]);

            $login = $request->login;

            $user = filter_var($login, FILTER_VALIDATE_EMAIL)
                ? User::with(['role', 'cde'])
                    ->where('email', $login)
                    ->first()
                : User::with(['role', 'cde'])
                    ->where('dni', $login)
                    ->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {

                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales inválidas.',
                ], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso.',

                'token' => $token,

                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'lastname' => $user->lastname,
                    'middlename' => $user->middlename,
                    'email' => $user->email,
                    'personalemail' => $user->personalemail,
                    'rol' => $user->rol,

                    'cde' => $user->cde
                        ? [
                            'id' => $user->cde->id,
                            'name' => $user->cde->name,
                        ]
                        : null,
                ],

                'role_name' => $user->role?->name,

                'role_modules' => $user->role?->modules ?? [],

            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error en el login.',
                'error' => $e->getMessage(),
            ], 500);

        }
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

            if (! $tokenRecord) {
                return response()->json(['status' => 404, 'error' => 'Token no encontrado o inactivo']);
            }

            $client = new Client;
            $response = $client->request('GET', $apiUrl, [
                'headers' => [
                    'Authorization' => $tokenRecord->token,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data) {
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
            } elseif ($e->getCode() == 401) {
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
            'dni' => 'required|string',
        ]);

        if ($user_dni == $request->dni) {
            $user = Auth::user();

            if (! Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'La contraseña actual no es válida'], 400);
            }

            $user->password = bcrypt($request->password);
            $user->save();

            return response()->json(['message' => 'Contraseña restablecida correctamente'], 200);
        }
    }

    public function updatePassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:users,id',
                'password' => 'required|string|min:8',
            ]);

            $user = User::findOrFail($validated['id']);

            $user->password = Hash::make($validated['password']);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada con éxito.',
                'status' => 200,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al actualizar la contraseña.',
                'error' => $e->getMessage(),
                'status' => 500,
            ]);
        }
    }
}
