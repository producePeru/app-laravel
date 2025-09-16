<?php

namespace App\Http\Controllers\Formalization;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormalization10Request;
use Illuminate\Http\Request;
use App\Models\Formalization10;
use App\Models\Formalization20;
use Illuminate\Support\Facades\Auth;

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

    public function updateValueRuc10(Request $request, $id)
    {
        try {
            // 1) Verificar usuario autenticado
            $userId = Auth::id();

            if (!$userId) {
                return response()->json(['message' => 'No autenticado'], 401);
            }

            // 2) Buscar registro
            $f10 = Formalization10::find($id);
            if (!$f10) {
                return response()->json(['message' => 'No encontrado'], 404);
            }

            // 3) Validación (sin updated_by en el request)
            $validated = $request->validate([
                'detailprocedure_id'   => 'required|integer',
                'modality_id'          => 'required|integer',
                'economicsector_id'    => 'required|integer',
                'comercialactivity_id' => 'required|integer',
                'city_id'              => 'required|integer',
                'province_id'          => 'required|integer',
                'district_id'          => 'required|integer',
                'address'              => 'nullable|string',
                'ruc'                  => 'nullable|digits:11',
            ]);

            // 4) Preparar datos
            $data = [
                'detailprocedure_id'   => $validated['detailprocedure_id'],
                'modality_id'          => $validated['modality_id'],
                'economicsector_id'    => $validated['economicsector_id'],
                'comercialactivity_id' => $validated['comercialactivity_id'],
                'city_id'              => $validated['city_id'],
                'province_id'          => $validated['province_id'],
                'district_id'          => $validated['district_id'],
                'address'              => $validated['address'] ?? null,
                'ruc'                  => isset($validated['ruc']) ? (string) $validated['ruc'] : null,
                'updated_by'           => $userId, // ← tomado de la sesión
            ];

            // 5) Guardar
            $f10->update($data);

            return response()->json([
                'message' => 'Datos actualizados correctamente',
                'status'  => 200,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
                'status'  => 422,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno en el servidor',
                'error'   => $e->getMessage(),
                'status'  => 500,
            ], 500);
        }
    }
}
