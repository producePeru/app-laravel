<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Token;


class TokenController extends Controller
{
    public function index(Request $request)
    {
        try {

            // Obtener usuario autenticado
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status'  => 401,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Obtener solo los tokens del usuario logueado
            $tokens = Token::where('user_id', $user->id)
                ->orderBy('id', 'desc')
                ->paginate(50, ['token']); // SOLO devolver la columna token

            return response()->json([
                'data'   => $tokens,
                'status' => 200
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'message' => 'Error interno al procesar la solicitud',
                'error'   => $e->getMessage()
            ], 500);
        }
    }



    public function store(Request $request)
    {
        try {

            // Validar el request
            $request->validate([
                'token' => 'required|string|max:100'
            ]);

            // Obtener usuario logueado
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Crear registro del token con user_id
            Token::create([
                'token'   => $request->token,
                'user_id' => $user->id
            ]);

            return response()->json([
                'status'  => 200,
                'message' => 'Registro exitoso'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'message' => 'Error al procesar la solicitud',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function updateStatus($id)
    {
        Token::query()->update(['status' => 0]);

        $token = Token::findOrFail($id);
        $token->status = 1;
        $token->save();

        return response()->json(['message' => 'Status updated successfully', 'status' => 200]);
    }

    // public function destroy($id)
    // {
    //     $token = Token::findOrFail($id);
    //     $token->delete();

    //     return response()->json(null, 204);
    // }
}
