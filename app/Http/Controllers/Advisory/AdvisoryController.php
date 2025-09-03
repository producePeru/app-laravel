<?php

namespace App\Http\Controllers\Advisory;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdvisoryRequest;
use Illuminate\Http\Request;
use App\Models\Advisory;
use Illuminate\Support\Facades\Auth;

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

    public function updateValuesAdvisory(Request $request, $id)
    {
        try {
            // 1) Verificar usuario autenticado
            $userId = Auth::id();

            if (!$userId) {
                return response()->json(['message' => 'No autenticado'], 401);
            }

            // 2) Buscar registro
            $advisory = Advisory::find($id);
            if (!$advisory) {
                return response()->json(['message' => 'No encontrado'], 404);
            }

            // 3) Validación (sin updated_by en el request)
            $validated = $request->validate([
                'economicsector_id'     => 'required|integer|exists:economicsectors,id',
                'comercialactivity_id'  => 'required|integer|exists:comercialactivities,id',
                'ruc'                   => 'nullable|digits:11',
                'observations'          => 'nullable',
                'component_id'          => 'required|integer',
                'theme_id'              => 'required|integer',
                'modality_id'           => 'required|integer',
                'city_id'               => 'required|integer',
                'province_id'           => 'required|integer',
                'district_id'           => 'required|integer',
            ]);

            // 4) Preparar datos y castear RUC a string
            $data = [
                'economicsector_id'    => $validated['economicsector_id'],
                'comercialactivity_id' => $validated['comercialactivity_id'],
                'ruc'                  => isset($validated['ruc']) ? (string) $validated['ruc'] : null,
                'observations'         => $validated['observations'] ?? null,
                'component_id'         => $validated['component_id'],
                'theme_id'             => $validated['theme_id'],
                'modality_id'          => $validated['modality_id'],
                'city_id'              => $validated['city_id'],
                'province_id'          => $validated['province_id'],
                'district_id'          => $validated['district_id'],
                'updated_by'           => $userId, // ← de la sesión
            ];

            // 5) Guardar
            $advisory->update($data);

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
            // Manejo genérico de error
            return response()->json([
                'message' => 'Error interno en el servidor',
                'error'   => $e->getMessage(),
                'status'  => 500,
            ], 500);
        }
    }
}
