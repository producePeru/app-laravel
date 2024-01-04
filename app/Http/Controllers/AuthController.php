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
    public function register(StoreAuthRequest $request)
    {
        $country = Country::find($request->countryCode);

        if (!$country) {
            return response()->json(['error' => 'El país no existe'], 404);
        }

        $user = User::where('_id', $request['_id'])->first();
        $role = $user->role;

        $lastInsertedId = User::max('id') + 1;
        $hashedId = hash('sha256', $lastInsertedId);

        if($role == 100) {
            $newUser = User::create([
                '_id' => $hashedId,
                'nick_name' => $request->nickName,
                'password' => Hash::make($request->password),
                'document_type' => $request->documentType,
                'document_number' => $request->documentNumber,
                'last_name' => $request->lastName,
                'middle_name' => $request->middleName,
                'name' => $request->name,
                'country_code' => $country->id,
                'birthdate' => $request->birthdate,
                'gender' => $request->gender,
                'is_disabled' => $request->isDisabled,
                'email' => $request->email,
                'phone_number' => $request->phoneNumber,
                'office_code' => $request->officeCode,
                'sede_code' => $request->sedeCode,
                'role' => $request->role,
                'created' => $user->id
            ]);
    
            $token = $newUser->createToken('auth_token')->plainTextToken;
    
            return response()->json(['message' =>'Registro completado', 'data' => hash('sha256', $newUser->id)], 200);    
        } else {
            return response()->json(['message' =>'No tienes permiso']);    
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
                'id' =>     $user->_id,
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
