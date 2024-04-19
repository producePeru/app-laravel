<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use App\Models\View;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withProfileAndRelations();

        return response()->json($users, 200);
    }



    public function store(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:6',
            ]);

            $user = new User();
            $user->email = $request->input('email');
            $user->password = bcrypt($request->input('password'));
            $user->save();

            // $user->roles()->attach($request->input('role_id'));
            $user->roles()->attach($request->input('role_id'), ['dniuser' => $request->input('documentnumber')]);

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

            $viewsByRole = [
                1 => ["home", "asesorias", "solicitudes", "asesorias", "asesorias-formalizaciones",
                    "solicitantes", "notarias", "asesores", "supervisores", "usuarios", "usuarios-nuevo", "usuarios-lista"], //supervisor
                2 => ["home", "asesorias", "asesorias-formalizaciones", "solicitantes"], //asesor
                3 => ["drive-mis-archivos", "drive-subir-archivo", "drive-mis-carpetas", "usuarios-nuevo"], //driver admin
                4 => ["drive-mis-archivos", "drive-subir-archivo"], //driver user
            ];

            $views = $viewsByRole[$request->role_id] ?? [];

            if (!empty($views)) {
                $view = new View();
                $view->user_id = $user->id;
                $view->views = json_encode($views);
                $view->save();
            }

            if ($request->role_id === 1) {
                $view = new Supervisor();
                $view->user_id = $user->id;
                $view->save();
            }

            if ($request->supervisor_id) {
                DB::table('supervisor_user')->insert([
                    'supervisor_id' => $request->supervisor_id,
                    'supervisado_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            return response()->json(['message' => 'Usuario creado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear el usuario: ' . $e->getMessage()], 500);
        }
    }


    public function update(Request $request, $id)
    {
        $user = Profile::findOrFail($id);

        if (!$user) {
            return response()->json(['message' => 'El usuario no tiene un perfil'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'birthday' => 'nullable|date',
            'sick' => 'required|in:yes,no',
            'phone' => 'required|string|max:20',
            'gender_id' => 'required|integer',
            'cde_id' => 'required|integer',
            'office_id' => 'required|integer',
        ]);

        $user->update($validatedData);

        return response()->json(['message' => 'Perfil actualizado con éxito', 'status' => 200]);

    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Eliminar al usuario (esto desencadenará la eliminación en cascada)
        $user->delete();

        return response()->json(['message' => 'Usuario y sus relaciones eliminados correctamente'], 200);
    }

    public function allAsesores()
    {
        $users = User::withProfileAsesories();

        return response()->json($users, 200);
    }
}
