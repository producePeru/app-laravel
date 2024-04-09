<?php

namespace App\Http\Controllers\People;

use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use App\Models\From;
use App\Models\People;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PersonController extends Controller
{
    public function index()
    {

        $people = People::withProfileAndRelations();

        return response()->json($people, 200);
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
                'gender_id' => 'required|integer',
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
                        $query->with('detailprocedure', 'modality', 'economicsector', 'user.profile');
                    },
                    'idformalization20' => function($query) {
                        $query->with('economicsector', 'user.profile', 'userupdater.profile');
                    },
                    'idadvisory' => function($query) {
                        $query->with('component','theme', 'modality', 'user.profile');
                    }
                ])
                ->first();

        if ($person) {

            return response()->json(['data' => $person, 'status' => 200]);
        } else {
            return response()->json(['message' => 'El usuario no existe', 'status' => 404]);
        }
    }

    // public function show($id)
    // {

    //     $people = People::withProfileAndRelations();

    //     return response()->json($people, 200);
    // }
}
