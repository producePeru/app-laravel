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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
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
            'user_id' => $user_id
        ];
    }

    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $query = User::with([
            'profile',
            'profile.office',
            'profile.cde',
            'roles'
        ])->orderBy('created_at', 'desc');

        if ($search) {
            $query->whereHas('profile', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('middlename', 'like', "%{$search}%")
                    ->orWhere('documentnumber', 'like', "%{$search}%")
                    ->orWhere('birthday', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('cde', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $data = $query->paginate(50);

        return response()->json(['data' => $data]);
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
                1 => [
                    "home",
                    "asesorias",
                    "solicitudes",
                    "asesorias",
                    "asesorias-formalizaciones",
                    "solicitantes",
                    "notarias",
                    "asesores",
                    "supervisores",
                    "usuarios",
                    "usuarios-nuevo",
                    "usuarios-lista"
                ], //supervisor
                2 => ["home", "asesorias", "asesorias-formalizaciones", "solicitantes", "notarias"], //asesor
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
            // 'sick' => 'required|in:yes,no',
            'phone' => 'required|string|max:20',
            'gender_id' => 'required|integer',
            'cde_id' => 'required|integer',
            'office_id' => 'required|integer',
            // 'city_id' => 'required|integer',
            // 'province_id' => 'required|integer',
            // 'district_id' => 'required|integer',
            // 'address' => 'nullable|string',
        ]);

        $user->update($validatedData);

        return response()->json(['message' => 'Perfil actualizado con éxito', 'status' => 200]);
    }

    public function destroy($id)
    {
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];

        if ($id == 1) {
            return response()->json(['message' => 'No tienes permisos para eliminar', 'status' => 500]);
        }

        if ($role_id === 1 || $user_id === 1) {
            $user = User::findOrFail($id);
            $user->delete();
            return response()->json(['message' => 'Usuario eliminado correctamente'], 200);
        }
        return response()->json(['message' => 'No tienes permisos para eliminar', 'status' => 500]);
    }

    public function allAsesores(Request $request)
    {
        $roleId = 2;
        $perPage = 50;
        $page = $request->input('page', 1);

        $search = $request->input('search');

        $query = DB::table('role_user')
            ->join('users', 'role_user.user_id', '=', 'users.id')
            ->join('profiles', 'users.id', '=', 'profiles.user_id')
            ->join('cdes', 'profiles.cde_id', '=', 'cdes.id')
            ->join('offices', 'profiles.office_id', '=', 'offices.id')
            ->join('genders', 'profiles.gender_id', '=', 'genders.id')
            ->where('role_user.role_id', $roleId)
            ->select(
                'profiles.id as _id',
                'users.email',
                'profiles.name as profile_name',
                'profiles.lastname as profile_lastname',
                'profiles.middlename as profile_middlename',
                'profiles.documentnumber as profile_documentnumber',
                'profiles.phone as profile_phone',
                'profiles.birthday as profile_birthday',
                'cdes.name as cde_name',
                'offices.name as office_name',
                'genders.name as gender',
                'cdes.id as cde_id',
                'genders.id as gender_id',
                'offices.id as office_id',
            );

        // Aplicar la búsqueda
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.email', 'like', '%' . $search . '%')
                    ->orWhere('profiles.name', 'like', '%' . $search . '%')
                    ->orWhere('profiles.lastname', 'like', '%' . $search . '%')
                    ->orWhere('profiles.middlename', 'like', '%' . $search . '%')
                    ->orWhere('profiles.documentnumber', 'like', '%' . $search . '%')
                    ->orWhere('genders.name', 'like', '%' . $search . '%')
                    ->orWhere('cdes.name', 'like', '%' . $search . '%');
            });
        }

        $results = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $results->items(),
            'current_page' => $results->currentPage(),
            'per_page' => $results->perPage(),
            'total' => $results->total(),
            'last_page' => $results->lastPage(),
            'status' => 200
        ]);
    }


    public function showMyProfile()
    {
        $id_user = Auth::user()->id;

        $profile = Profile::where('user_id', $id_user)->first();

        return response()->json(['data' => $profile, 'status' => 200]);
    }

    //🚩🚩🚩
    public function asignViewsUser(Request $request, $id)
    {
        $role_user = getUserRole();

        if (in_array(5, $role_user['role_id'])) {

            try {
                $view = View::updateOrCreate(
                    ['user_id' => $id],
                    ['views' => json_encode($request->views)]
                );
                return response()->json(['message' => 'View assigned successfully', 'status' => 200]);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to assign view', 'error' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(['message' => 'User does not have the required role', 'status' => 403]);
        }
    }

    public function showViewsUser($id)
    {
        try {
            $view = View::where('user_id', $id)->first();

            if (!$view) {
                return response()->json(['message' => 'Not found'], 404);
            }

            $data = json_decode($view->views, true);

            return response()->json(['data' => $data, 'status' => 200]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve views', 'error' => $e->getMessage()], 500);
        }
    }


    public function registerNotario(Request $request)
    {

        // Insertar en la tabla role

        // Agregar vistas si el rol es 7
        if ($request->input('role_id') === 7) {
        }

        return response()->json(['message' => 'Usuario registrado exitosamente', 'status' => 200]);
    }



    // REGISTROS POR PARTES

    public function registerUsers(Request $request)
    {
        try {
            $validated = $request->validate([
                'documentnumber' => 'required|string|unique:users,dni',
                'email' => 'email|unique:users,email',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Errores de validación',
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        }

        $user = User::create([
            'email' => $request->input('email'),
            'dni' => $request->input('documentnumber'),
            'password' => bcrypt($request->input('password')),
        ]);

        return response()->json(['message' => 'Usuario creado', 'status' => 200, 'data' => $user]);
    }

    public function registerProfiles(Request $request)
    {
        Profile::create([
            'name'          => $request->input('name'),
            'lastname'      => $request->input('lastname'),
            'middlename'    => $request->input('middlename'),
            'documentnumber' => $request->input('documentnumber'),
            'birthday'      => $request->input('birthday'),
            'phone'         => $request->input('phone'),
            'gender_id'     => $request->input('gender_id'),
            'cde_id'        => $request->input('cde_id'),
            'office_id'     => $request->input('office_id'),
            'user_id'       => $request->input('user_id'),
            'notary_id'     => $request->input('notary_id')
        ]);

        return response()->json(['message' => 'Perfil creado', 'status' => 200]);
    }

    public function registerRoles(Request $request)
    {
        DB::table('role_user')->insert([
            'role_id' => $request->input('role_id'),
            'user_id' => $request->input('user_id'),
            'dniuser' => $request->input('documentnumber'),
        ]);

        return response()->json(['message' => 'Rol creado', 'status' => 200]);
    }

    public function registerViewsSeven(Request $request)        // asesores externos notarios
    {
        View::create([
            'views'     => json_encode(["home", "asesorias", "asesorias-formalizaciones", "solicitantes"]),
            'user_id'   => $request->input('user_id')
        ]);
        return response()->json(['message' => 'Vista asignadas', 'status' => 200]);
    }

    public function newUser(Request $request)
    {

        $user_role = getUserRole();

        $role_array = $user_role['role_id'];

        // 5 gold, 10 jefe de ferias

        if (in_array(5, $role_array) ||
            in_array(8, $role_array) ||
            in_array(10, $role_array)
        ){

            $dni = $request->documentnumber;

            $existingUser = User::where('dni', $dni)->first();

            if ($existingUser) {
                return response()->json(['message' => 'Este número de DNI ya se encuentra registrado', 'status' => 400]);
            }

            $user = new User();

            $user->dni = $request->documentnumber;
            $user->email = $request->email;
            $user->password = bcrypt($request->input('password'));
            // $user->role_id = $request->role_id ?? null;
            $user->save();

            $profile = new Profile();
            $profile->name = $request->name ?? null;
            $profile->lastname = $request->lastname ?? null;
            $profile->middlename = $request->middlename ?? null;
            $profile->documentnumber = $request->documentnumber ?? null;
            $profile->birthday = $request->birthday ?? null;
            $profile->sick = $request->sick ?? null;
            $profile->phone = $request->phone ?? null;
            $profile->gender_id = $request->gender_id ?? null;
            $profile->cde_id = $request->cde_id ?? null;
            $profile->office_id = $request->office_id ?? null;
            $profile->user_id = $user->id;
            $profile->save();


            if ($request->role_id == 8) {
                $views = ['convenios', 'estado-convenio-ugse', 'estado-convenio-ugse-compromisos', 'estado-convenio-ugse-detalles', 'usuarios', 'nuevo-registro'];
            }

            if ($request->role_id == 9) {
                $views = ['convenios', 'estado-convenio-ugse', 'estado-convenio-ugse-compromisos', 'estado-convenio-ugse-detalles'];
            }

            if ($request->role_id == 10) {
                $views = ['usuarios', 'nuevo-registro', 'ferias', 'ferias-empresariales', 'ferias-inscritos'];
            }

            if ($request->role_id == 11 || $request->role_id == 12) {
                $views = ['ferias', 'ferias-empresariales', 'ferias-inscritos'];
            }

            DB::table('role_user')->insert([
                'role_id' => $request->input('role_id'),
                'user_id' => $user->id,
                'dniuser' => $request->input('documentnumber'),
            ]);

            $view = new View();
            $view->views = json_encode($views);
            $view->user_id = $user->id;
            $view->save();

            return response()->json([
                'message' => 'Registrado correctamente',
                'user' => $user,
                'profile' => $profile,
                'status' => 200
            ]);
        }

        return response()->json([
            'message' => 'No tienes permiso',
            'status' => 500
        ]);
    }
}
