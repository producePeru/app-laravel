<?php

namespace App\Http\Controllers\Formalization;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormalization10Request;
use Illuminate\Http\Request;
use App\Models\Formalization10;

class Formalization10Controller extends Controller
{
    public function indexRuc10()
    {
        $formalization = Formalization10::withFormalizationAndRelations();

        return response()->json($formalization, 200);
    }

    public function storeRuc10(StoreFormalization10Request $request)
    {
        try {
            $data = $request->validated();

            // Agregar el user_id del usuario autenticado
            $data['user_id'] = auth()->id();

            Formalization10::create($data);

            return response()->json([
                'message' => 'Formalización registrada correctamente',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la formalización',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function destroy($id)
    {
        $item = Formalization10::find($id);

        if (!$item) {
            return response()->json(['message' => 'No se encontró este registro'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Registro eliminado correctamente'], 200);
    }

    public function getDataF10ById($id)
    {
        $f10 = Formalization10::find($id);

        if (!$f10) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        return response()->json(['data' => $f10, 'status' => 200]);
    }

    public function update(Request $request, $id)
    {
        $f10 = Formalization10::find($id);

        if (!$f10) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $request->validate([
            'detailprocedure_id' => 'required|integer',
            'modality_id' => 'required|integer',
            'economicsector_id' => 'required|integer',
            'comercialactivity_id' => 'required|integer',
            'city_id' => 'required|integer',
            'province_id' => 'required|integer',
            'district_id' => 'required|integer',
            'address' => 'nullable|string',
            'ruc' => 'nullable',
            'updated_by' => 'required|integer',
        ]);

        $f10->update($request->only([
            'detailprocedure_id',
            'modality_id',
            'economicsector_id',
            'comercialactivity_id',
            'city_id',
            'province_id',
            'district_id',
            'address',
            'ruc',
            'updated_by',
        ]));

        return response()->json(['message' => 'Datos actualizados correctamente', 'status' => 200]);
    }
}
