<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreAuthRequest;
use App\Models\User;
use App\Models\Country;
use Validator;
use \stdClass;


class AuthController extends Controller
{
    public function register(StoreAuthRequest $request)
    {
        $country = Country::find($request->countryCode);

        if (!$country) {
            return response()->json(['error' => 'El país no existe'], 404);
        }

        $user = User::create([
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
            'role' => $request->role
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['data' => $user, 'access_token' => $token, 'token_type' => 'Bearer']);
    }

    public function login(Request $request)
    {
        if(!Auth::attempt($request->only('email', 'password')))
        {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(
            [
                'message' => 'Hi '.$user->name,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ]
        );
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        return ['message' => 'Cerraste la session'];
    }
    
}
