<?php

namespace App\Http\Controllers\People;

use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use App\Models\From;
use App\Models\People;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PersonController extends Controller
{
    public function index($userId, $dni)
    {
        $roleUser = DB::table('role_user')
                ->where('user_id', $userId)
                ->where('dniuser', $dni)
                ->first();

        if (!$roleUser) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        if ($roleUser->role_id === 1) {
            $people = People::withProfileAndRelations();
            return response()->json($people, 200);
        }

        if ($roleUser->role_id === 2) {
            $people = People::withProfileAndUser($userId);
            return response()->json($people, 200);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'documentnumber' => 'required|string|unique:people',
                'lastname' => 'required|string',
                'middlename' => 'required|string',
                'name' => 'required|string',
                'phone' => 'nullable|string',
                'email' => 'nullable|email|unique:people',
                'birthday' => 'nullable|date',
                'sick' => 'nullable|integer',
                'facebook' => 'nullable|string',
                'linkedin' => 'nullable|string',
                'instagram' => 'nullable|string',
                'tiktok' => 'nullable|string',
                'city_id' => 'required|integer',
                'province_id' => 'required|integer',
                'district_id' => 'required|integer',
                'typedocument_id' => 'required|integer',
                'gender_id' => 'nullable|integer',
            ]);

            $person = People::create($data);

            $user = User::find($request->input('people_id'));
            $user->people()->attach($person->id);

            // Attach de la entidad From a la persona
            $person->from()->attach($request->input('from_id'), ['people_id' => $person->id, 'from_id' => $request->input('from_id')]);

            return response()->json(['message' => 'Usuario creado correctamente'], 200);
        } catch (QueryException $e) {
            return response()->json(['message' => 'El usuario se registró pero la relación ha fallado', 'error' => $e], 400);
        }
    }

    public function destroy($id)
    {
        $person = People::findOrFail($id);

        $person->delete();

        return response()->json(['message' => 'Usuario y sus relaciones eliminados correctamente'], 200);
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
            'email' => 'nullable|string'
        ]);

        $user->update($validatedData);

        return response()->json(['message' => 'Perfil actualizado con éxito', 'status' => 200]);
    }

}
