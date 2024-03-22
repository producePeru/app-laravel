<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreAuthRequest;
use App\Models\User;
use App\Models\Created;
use App\Models\Country;
use Illuminate\Support\Facades\Crypt;
use \stdClass;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    public function registerNewUser(Request $request)
    {
        $existingUser = User::where('document_number', $request->document_number)->orWhere('email', $request->email)->first();
        if ($existingUser) {
            return response()->json(['error' => 'El DNI o correo electrónico ya está registrado'], 400);
        }
        $data = User::create($request->all());
        return response()->json(['message' => 'Usuario creado correctamente', 'data' => $data], 201);
    }


    public function registerNewUserCreatedBy(Request $request)
    {
        $existingRelation = Created::where('id_user', $request->id_user)->where('id_creator', $request->id_creator)->first();
        if ($existingRelation) {
            return response()->json(['error' => 'La relación entre el usuario y el creador ya existe'], 400);
        }
        Created::create($request->all());
        return response()->json(['message' => 'Relación creada correctamente'], 201);
    }


    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $nickCredentials = ['document_number' => $request->input('email'), 'password' => $request->input('password')];
        if (!Auth::attempt($credentials) && !Auth::attempt($nickCredentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $data = [
            'access_token' => $user->createToken('app')->plainTextToken,
            'id' => $user->id,
            'document_number' => $user->document_number,
            'last_name' => $user->last_name,
            'middle_name' => $user->middle_name,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role
        ];
        return response()->json(['data' => $data], 201);
    }
    

    public function logout()
    {
        auth()->user()->tokens()->delete();
        return ['message' => 'Cerraste la session'];
    }

    public function changePasswordUser(Request $request, $id, $dni)
    {
        try {
            $request->validate([
                'current_password' => 'required',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // $user = User::findOrFail($id);
            $user = User::where('id', $id)
            ->where('document_number', $dni)
            ->first();

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['error' => 'La contraseña actual es incorrecta'], 401);
            }
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json(['message' => 'Contraseña cambiada con éxito']);

        } catch (\Throwable $e) {
            return response()->json(['error' => $e]);
        }
    }

    
    
}
