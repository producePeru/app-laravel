<?php

namespace App\Http\Controllers\Advisory;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdvisoryRequest;
use Illuminate\Http\Request;
use App\Models\Advisory;

class AdvisoryController extends Controller
{
    public function index()
    {

        $advisory = Advisory::withProfileAndRelations();

        return response()->json($advisory, 200);
    }

    public function store(StoreAdvisoryRequest $request)
    {
        try {

            $validatedData = $request->validated();

            // Agregar el user_id del usuario autenticado
            $validatedData['user_id'] = auth()->id();

            // Crear la asesoría
            $advisory = Advisory::create($validatedData);

            return response()->json([
                'data' => $advisory,
                'message' => 'Asesoría creada correctamente',
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear la asesoría',
                'message' => 'Consulta con tu administrador',
                'status' => 500
            ], 500);
        }
    }

    public function destroy($id)
    {
        $item = Advisory::find($id);

        if (!$item) {
            return response()->json(['message' => 'No se encontró la asesoría'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Asesoría eliminada correctamente'], 200);
    }

    public function getDataAdvisoryById($id)
    {
        $advisory = Advisory::find($id);

        if (!$advisory) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $advisory->makeVisible(['people_id', 'component_id', 'theme_id', 'modality_id', 'province_id', 'city_id', 'district_id']);

        return response()->json(['data' => $advisory, 'status' => 200]);
    }

    public function update(Request $request, $id)
    {
        $advisory = Advisory::find($id);

        if (!$advisory) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $request->validate([
            'economicsector_id' => 'required|integer|exists:economicsectors,id',
            'comercialactivity_id' => 'required|integer|exists:comercialactivities,id',
            'ruc' => 'nullable|numeric',
            'observations' => 'nullable|string',
            'component_id' => 'required|integer',
            'theme_id' => 'required|integer',
            'modality_id' => 'required|integer',
            'city_id' => 'required|integer',
            'province_id' => 'required|integer',
            'district_id' => 'required|integer',
            'updated_by' => 'required|integer'
        ]);

        $data = $request->only([
            'economicsector_id',
            'comercialactivity_id',
            'ruc',
            'observations',
            'component_id',
            'theme_id',
            'modality_id',
            'city_id',
            'province_id',
            'district_id',
            'updated_by',
        ]);

        if (isset($data['ruc'])) {
            $data['ruc'] = (string) $data['ruc'];
        }

        $advisory->update($data);

        return response()->json(['message' => 'Datos actualizados correctamente', 'status' => 200]);
    }
}
