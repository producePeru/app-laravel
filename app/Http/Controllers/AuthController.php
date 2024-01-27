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
    public function registerUser(StoreAuthRequest $request)
    {
        try {
            $newUser = User::create([
                'nick_name' => $request->nick_name,
                'password' => Hash::make($request->password),
                'document_type' => $request->document_type,
                'document_number' => $request->document_number,
                'last_name' => $request->last_name,
                'middle_name' => $request->middle_name,
                'name' => $request->name,
                'country_code' => $request->country_code,
                'birthdate' => $request->birthdate,
                'gender' => $request->gender,
                'is_disabled' => $request->is_disabled,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'office_code' => $request->office_code,
                'sede_code' => $request->sede_code,
                'role' => $request->role,
                'created_by' => $request->created_by
            ]);
        
            return response()->json(['message' => 'Usuario creado correctamente'], 200);
        
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error al crear. Por favor, inténtalo de nuevo.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al crear.'.$e->getMessage()], 500);
        }   
    }

    public function login(Request $request)
    {

        $credentials = $request->only('email', 'password');
    
        $nickCredentials = ['nick_name' => $request->input('email'), 'password' => $request->input('password')];

        if (!Auth::attempt($credentials) && !Auth::attempt($nickCredentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        // $crypt = Crypt::encryptString($user->id);
        // return $decrypted = Crypt::decryptString($as);

        if($user->role === 100) {                             
            $token = $user->createToken('admin-token', ['super']);  //administrador
            $role = "a0604689fce52a03ec566d93aa92ae9d";
        }
        if($user->role === 1) {                             
            $token = $user->createToken('admin-token', ['create', 'update', 'delete']);  //administrador
            $role = "c645a0fe6e69450e3ab7507d7b4a3ce3";
        }
        if($user->role === 2) {
            $token = $user->createToken('update-token', ['create', 'update']);  //usuario
            $role = "efddf7e3213ec1fc147d2cfd39dcdfdb";
        }
        if($user->role === 3) {
            $token = $user->createToken('invitado-token', ['read']);  //invitado
            $role = "ee050deda41094f530c40213f3f77791";
        }

        return response()->json([
            'data' => [
                'access_token' => $token->plainTextToken,
                'id' =>     Crypt::encryptString($user->id),
                'role' =>   $role,
                'name'  =>  $user->name,
                'nick'  =>  $user->nick_name,
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
