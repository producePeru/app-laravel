<?php

namespace App\Http\Controllers;
use App\Models\Supervisor;
use App\Models\User;

use Illuminate\Http\Request;

class SupervisorController extends Controller
{
    public function index()
    {
        $exponents = Supervisor::with([
            'departament' => function ($query) { $query->select('idDepartamento', 'descripcion'); },
            'province' => function ($query) { $query->select('idProvincia', 'descripcion'); },
            'district' => function ($query) { $query->select('idDistrito', 'descripcion'); }
        ])->paginate(20);
        return $exponents;
    }

    public function store(Request $request)
    {
        $data = $request;

        $user = User::where('_id', $request['created_by'])->first();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $data['created_by'] = $user->id;

        try {
            $supervisor = Supervisor::create($data->all());
            return response()->json(['message' => 'Creado correctamente'], 200);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error al crear. Por favor, inténtalo de nuevo.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al crear.'], 500);
        }
    }

    public function show($id)
    {
        $supervisor = Supervisor::find($id);
        try {
            return response()->json(['data' => $supervisor], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'No encontrado'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $supervisor = Supervisor::find($id);

        if (!$supervisor) {
            return response()->json(['message' => 'Supervisor not found'], 404);
        }

        $requestData = $request->except('created_by');

        $supervisor->update($requestData);

        return response()->json(['message' => 'Se actualizó correctamente']);
    }

    public function list()
    {
        $supervisors = Supervisor::all();
        $formatted = $supervisors->map(function ($item) {
            return [
                'value' => $item->id,
                'label' => $item->name . ' ' . $item->last_name . ' ' . $item->middle_name
            ];
        });

        return response()->json(['data' => $formatted]);
    }
}
