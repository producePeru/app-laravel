<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Page;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use App\Models\View;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{

    public function registerNewUser(StoreUserRequest $request)
    {
        try {
            $data = $request->validated();

            // Generar email basado en la inicial del nombre + apellido
            $email = strtolower(substr($data['name'], 0, 1) . $data['lastname']) . '@pnte.com';

            // Validar que el correo no exista
            if (User::where('email', $email)->exists()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['email' => ['El correo generado ya existe.']],
                    'status' => 422
                ]);
            }

            // Crear usuario
            $user = User::create([
                'dni' => $data['dni'],
                'name' => $data['name'],
                'lastname' => $data['lastname'],
                'middlename' => $data['middlename'] ?? null,
                'birthday' => $data['birthday'],
                'gender_id' => $data['gender_id'],
                'office_id' => $data['office_id'],
                'rol' => $data['rol'] ?? null,
                'cde_id' => $data['cde_id'],
                'phone' => $data['phone'] ?? null,
                'email' => $email,
                'password' => Hash::make($data['dni']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado correctamente',
                'user' => $user,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage(),
                'status' => 500
            ]);
        }
    }

    public function usersPnte(Request $request)
    {
        try {

            $permission = getPermission('usuarios-pnte');

            // if (!$permission['hasPermission']) {
            //     return response()->json([
            //         'message' => 'No tienes permiso para acceder a esta sección',
            //         'status' => 403
            //     ]);
            // }

            $filters = [
                'name' => $request->input('name')
            ];

            $query = User::where(['active' => 1])->orderBy('id', 'desc');

            $query->withDataItems($filters);

            $users = $query->paginate(150)->through(function ($advisory) {
                return $this->mapEvents($advisory);
            });

            return response()->json([
                'data'   => $users,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener los usuarios',
                'message' => $e->getMessage(),
                'status' => 500,
                'trace' => config('app.debug') ? $e->getTrace() : null
            ], 500);
        }
    }

    public function usersUgo(Request $request)
    {
        try {

            $permission = getPermission('usuarios-ugo');

            if (!$permission['hasPermission']) {
                return response()->json([
                    'message' => 'No tienes permiso para acceder a esta sección',
                    'status' => 403
                ]);
            }

            $filters = [
                'name'      => $request->input('name')
            ];

            $query = User::where(['active' => 1, 'office_id' => 1])->orderBy('id', 'desc');

            $query->withDataItems($filters);

            $users = $query->paginate(150)->through(function ($advisory) {
                return $this->mapEvents($advisory);
            });

            return response()->json([
                'data'   => $users,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener los usuarios',
                'message' => $e->getMessage(),
                'status' => 500,
                'trace' => config('app.debug') ? $e->getTrace() : null
            ], 500);
        }
    }

    private function mapEvents($item)
    {
        return [
            'id'                => $item->id,
            'email'             => $item->email,
            'name'              => isset($item->name) ? strtoupper($item->name) : null,
            'lastname'          => $item->lastname,
            'middlename'        => $item->middlename,
            'fullname'          => trim(
                (isset($item->lastname) ? strtoupper($item->lastname) : '') . ' ' .
                    (isset($item->middlename) ? strtoupper($item->middlename) : '')
            ) ?: null,

            'dni'               => $item->dni ?? null,
            'phone'             => $item->phone ?? null,
            'personalemail'     => $item->personalemail ?? null,
            'birthday'          => $item->birthday ? $item->birthday : null,
            'cde_id'           => $item->cde_id ?? null,
            'cde_name'         => isset($item->cde->name) ? strtoupper($item->cde->name) : null,
            'office_id'        => $item->office_id ?? null,
            'office_name'      => isset($item->office->name) ? strtoupper($item->office->name) : null,
            'gender_id'        => $item->gender_id ?? null
        ];
    }

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

    public function updateUserPnte(UpdateUserRequest $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $user->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado correctamente.',
                'user' => $user,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al actualizar.',
                'error' => $e->getMessage(),
                'status' => 500
            ]);
        }
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
            ->whereIn('role_user.role_id', [1, 2, 7, 13])                                       // 1 supervisor, 2 asesor, 7 notarios
            ->select(
                'role_user.role_id as role_user',
                'users.id as user_id',
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
            )->orderBy('profiles.created_at', 'desc');

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
            'notary_id'     => $request->input('notary_id'),
            'agentname'     => $request->input('agentname'),
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
            'views'     => json_encode(["home", "asesorias", "asesorias-formalizaciones", "asesorias-listado", "solicitantes"]),
            'user_id'   => $request->input('user_id')
        ]);
        return response()->json(['message' => 'Vista asignadas', 'status' => 200]);
    }

    public function newUser(Request $request)
    {

        $user_role = getUserRole();

        $role_array = $user_role['role_id'];

        // 5 gold, 10 jefe de ferias

        if (
            in_array(5, $role_array) ||
            in_array(8, $role_array) ||
            in_array(10, $role_array)
        ) {

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

    public function deleteUserPnte($idUser)
    {
        try {
            $user = User::find($idUser);

            if ($user) {
                $user->active = 0;
                $user->save();

                return response()->json(['message' => 'Usuario desactivado correctamente', 'status' => 200]);
            }

            return response()->json(['message' => 'Usuario no encontrado'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al desactivar el usuario',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateCde(Request $request)
    {
        try {
            // Validar datos del payload
            $validated = $request->validate([
                'cde_id'  => 'required|integer',
                'user_id' => 'required|integer',
            ]);

            $user = User::find($validated['user_id']);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado.',
                ], 404);
            }

            // Actualizar el cde_id del usuario
            $user->cde_id = $validated['cde_id'];
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'CDE actualizado correctamente.',
                'status'  => 200,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en updateCde: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al actualizar el CDE.',
                'error'   => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }


    public function updatedPersonalInfo(Request $request, $id, $dni)
    {
        // 1️⃣ Verificar que el usuario autenticado coincida con el ID recibido
        $userSession = Auth::user();

        if ($userSession->id != $id) {
            return response()->json([
                'message' => 'No autorizado.'
            ], 403);
        }

        // 2️⃣ Validación del payload
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'regex:/^[a-zA-Z0-9._%+-]+@produce\.gob\.pe$/'
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20'
            ]
        ]);

        // 3️⃣ Buscar usuario
        $user = User::findOrFail($id);

        // 4️⃣ Actualizar campos
        $user->personalemail = $validated['email'];
        $user->phone = $validated['phone'] ?? null;

        $user->save();

        return response()->json([
            'message'   => 'Información actualizada correctamente.',
            'data'      => $user,
            'status'    => 200
        ], 200);
    }

    public function getPersonalInfo()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'No autenticado.'
            ], 401);
        }

        return response()->json([
            'email' => $user->personalemail,
            'phone' => $user->phone
        ], 200);
    }
}
