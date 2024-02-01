<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreAuthRequest;
use App\Models\User;
use App\Models\Country;
use Validator;
use Illuminate\Support\Facades\Crypt;
use \stdClass;


class AuthController extends Controller
{
    public function registerUpdateUser(Request $request)
    {
        $isNewUser = User::where('document_number', $request->document_number)->first();

        if ($isNewUser) {

            $data = $request->except(['created_by', 'password']);
            $data['update_by'] = Crypt::decryptString($request->updated_by);

            // return $data;

            $isNewUser->update($data);
            
            $message = "Datos actualizados exitosamente.";
            $userId = $isNewUser->id;

        } else {

            $rol = Crypt::decryptString($request->created_by);

            $user = User::where('id', $rol)->first();

            if($user->role == 100) {
                
                $data = array_merge($request->except('update_by'), ['created_by' => Crypt::decryptString($request->created_by)]);

                $newUser = User::create($data);
                
                $message = "Usuario creado exitosamente.";
                $userId = $newUser->id;

            } else {

                return response()->json(['message' => 'No estas autorizado']);
            }
            
        }

        return response()->json(['message' => $message, 'user_id' => $userId]);
    }


    public function login(Request $request)
    {

        $credentials = $request->only('email', 'password');
    
        $nickCredentials = ['document_number' => $request->input('email'), 'password' => $request->input('password')];

        if (!Auth::attempt($credentials) && !Auth::attempt($nickCredentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        $status = $user->status;

        if($status !== 1) {
            return ['message' => 'Ya no tienes acceso al sistema, consulta con tu administrador', 'status' => 404];
        }

        if($user->role === 100) {                             
            $token = $user->createToken('admin-token', ['super']);                              //administrador
            $role = "super";
        }
        if($user->role === 1) {                             
            $token = $user->createToken('admin-token', ['create', 'update', 'delete']);         //administrador
            $role = "administrador";
        }
        if($user->role === 2) {
            $token = $user->createToken('update-token', ['create', 'update']);                  //usuario
            $role = "usuario";
        }
        if($user->role === 3) {
            $token = $user->createToken('invitado-token', ['read']);                            //invitado
            $role = "invitado";
        }

        return response()->json([
            'data' => [
                'access_token' => $token->plainTextToken,
                'id' =>     Crypt::encryptString($user->id),
                'role' =>   $role,
                'name'  =>  $user->name,
                'dni'  =>  $user->document_number,
                'email' =>  $user->email
            ],
        ]);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        return ['message' => 'Cerraste la session'];
    }
    
}
