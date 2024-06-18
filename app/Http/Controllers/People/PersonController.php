<?php

namespace App\Http\Controllers\People;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use App\Models\From;
use App\Models\People;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PersonController extends Controller
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
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];
        $filters = [
            'search' => $request->input('search'),
        ];

        if ($role_id === 1 || $user_id === 1 || $user_id === 3) {
            $people = People::withProfileAndRelations($filters);
            return response()->json($people, 200);
        }


        if ($role_id === 2) {
            $people = People::withProfileAndUser($user_id, $filters);
            return response()->json($people, 200);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'documentnumber' => 'required|string',
                'lastname' => 'required|string',
                'middlename' => 'required|string',
                'name' => 'required|string',
                'country' => 'required|string',
                'phone' => 'nullable|string',
                'email' => 'nullable|email|unique:people',
                'birthday' => 'nullable|date',
                'sick' => 'nullable|in:yes,no',
                'facebook' => 'nullable|string',
                'linkedin' => 'nullable|string',
                'instagram' => 'nullable|string',
                'tiktok' => 'nullable|string',
                'city_id' => 'required|integer',
                'province_id' => 'required|integer',
                'district_id' => 'required|integer',
                'address' => 'nullable|string',
                'typedocument_id' => 'required|integer',
                'gender_id' => 'nullable|integer',
                'hasSoon' => 'string',
            ]);

            $person = People::create($data);

            $user = User::find($request->input('people_id'));
            $user->people()->attach($person->id);

            // Attach de la entidad From a la persona
            $person->from()->attach($request->input('from_id'), ['people_id' => $person->id, 'from_id' => $request->input('from_id')]);

            return response()->json(['message' => 'Usuario creado correctamente', 'status' => 200]);
        }
        catch (\Illuminate\Validation\ValidationException $e) {         // DEVUELVE ERRORES DE LA VALIDACION

            if (isset($e->errors()['email'])) {
                return response()->json(['error' => $e->errors()['email'][0], 'status' => 400]);
            }
            if (isset($e->errors()['documentnumber'])) {
                return response()->json(['status' => 401]);
            }
            return $e;
        }
        catch (QueryException $e) {
            return response()->json(['message' => 'El usuario se registró pero la relación ha fallado', 'error' => $e], 400);
        }
    }

    public function destroy($id)
    {
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];

        if ($role_id === 1 || $user_id === 1) {
            People::destroy($id);
            return response()->json(['message' => 'Usuario y sus relaciones eliminados correctamente'], 200);
        } else {
            return response()->json(['message' => 'La eliminación de este usuario no es posible. Por favor, busca orientación de tu administrador.', 'status' => 500]);
        }
    }

    public function dniFoundUser($type, $dni)
    {
        $person = People::where('typedocument_id', $type)
                ->where('documentnumber', $dni)
                ->with([
                    'idformalization10' => function($query) {
                        $query->with('detailprocedure', 'modality', 'economicsector');
                    },
                    'idformalization20' => function($query) {
                        $query->with('economicsector', 'userupdater.profile');
                    },
                    'idadvisory' => function($query) {
                        $query->with('component','theme', 'modality');
                    }
                ])
                ->first();

        if ($person) {
            return response()->json(['data' => $person, 'status' => 200]);
        } else {
            return response()->json(['message' => 'El usuario no existe', 'status' => 404]);
        }
    }

    public function update(Request $request, $id)
    {
        $user = People::find($id);

        if (!$user) {
            return response()->json(['message' => 'El usuario no tiene un perfil'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'birthday' => 'nullable|date',
            'gender_id' => 'nullable|integer',
            'city_id' => 'required|integer',
            'province_id' => 'required|integer',
            'district_id' => 'required|integer',
            'sick' => 'nullable|in:yes,no',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|string',
            'hasSoon' => 'nullable|string',
        ]);

        $user->update($validatedData);

        return response()->json(['message' => 'Perfil actualizado con éxito', 'status' => 200]);
    }

}
