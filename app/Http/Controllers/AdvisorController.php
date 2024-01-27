<?php

namespace App\Http\Controllers;
use App\Models\Advisor;
use App\Models\User;

use Illuminate\Http\Request;

class AdvisorController extends Controller
{
    public function index()
    {
        $exponents = Advisor::with([
            'departament' => function ($query) { $query->select('idDepartamento', 'descripcion'); },
            'province' => function ($query) { $query->select('idProvincia', 'descripcion'); },
            'district' => function ($query) { $query->select('idDistrito', 'descripcion'); },
            'sede' => function ($query) { $query->select('id', 'name'); },
            'supervisor' => function ($query) { $query->select('id', 'name', 'last_name', 'middle_name'); }
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
            $advisor = Advisor::create($data->all());
            return response()->json(['message' => 'Creado correctamente'], 200);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error al crear. Por favor, inténtalo de nuevo.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al crear.'. $e], 500);
        }
    }

    public function show($id)
    {
        $advisor = Advisor::find($id);
        try {
            return response()->json(['data' => $advisor], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'No encontrado'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $advisor = Advisor::find($id);

        if (!$advisor) {
            return response()->json(['message' => 'Asesor not found'], 404);
        }

        $requestData = $request->except('created_by');

        $advisor->update($requestData);

        return response()->json(['message' => 'Se actualizó correctamente']);
    }
}
