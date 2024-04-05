<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withProfileAndRelations();

        return response()->json($users, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = new User();
        $user->email = $request->input('email');
        $user->password = bcrypt($request->input('password'));
        $user->save();

        $user->roles()->attach($request->input('role_id'));

        $profile = new Profile();
        $profile->name = $request->name;
        $profile->lastname = $request->lastname;
        $profile->middlename = $request->middlename;
        $profile->documentnumber = $request->documentnumber;
        $profile->birthday = $request->birthday;
        $profile->sick = $request->sick;
        $profile->phone = $request->phone;
        $profile->user_id = $user->id;
        $profile->gender_id = $request->gender_id;
        $profile->cde_id = $request->cde_id;
        $profile->office_id = $request->office_id;
        $profile->save();

        $profile = new Role();

        return response()->json(['message' => 'Usuario creado correctamente'], 200);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (!$user->profile) {
            return response()->json(['message' => 'El usuario no tiene un perfil'], 404);
        }

        $profile = $user->profile;

        $profile->name = $request->input('name');
        $profile->lastname = $request->input('lastname');
        $profile->middlename = $request->input('middlename');
        $profile->birthday = $request->input('birthday');
        $profile->sick = $request->input('sick');
        $profile->phone = $request->input('phone');
        $profile->gender_id = $request->input('gender_id');
        $profile->cde_id = $request->input('cde_id');
        $profile->office_id = $request->input('office_id');
        $profile->save();

        // Actualizar el role del usuario
        $user->roles()->sync([$request->input('role_id')]);

        return response()->json(['message' => 'Perfil actualizado correctamente'], 200);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Eliminar al usuario (esto desencadenará la eliminación en cascada)
        $user->delete();

        return response()->json(['message' => 'Usuario y sus relaciones eliminados correctamente'], 200);
    }
}
