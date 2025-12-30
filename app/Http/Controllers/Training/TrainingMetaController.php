<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\TrainingMeta;
use Illuminate\Http\Request;

class TrainingMetaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $meta = TrainingMeta::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 200,
            'data'   => $meta
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validar primero el formato "YYYY-MM"
        $request->validate([
            'month' => 'required|regex:/^\d{4}-\d{2}$/',
            'capacitaciones' => 'required|integer|min:0|max:99999',
            'participantes' => 'required|integer|min:0|max:99999',
            'empresas' => 'required|integer|min:0|max:99999',
        ]);

        // Convertir "YYYY-MM" a "YYYY-MM-01" para MySQL DATE
        $request->merge([
            'month' => $request->month . '-01',
        ]);

        $trainingMeta = TrainingMeta::create($request->all());

        return response()->json([
            'message' => 'Meta registrada correctamente',
            'data' => $trainingMeta,
            'status' => 200
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $trainingMeta = TrainingMeta::find($id);
        if (!$trainingMeta) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $request->validate([
            'month' => 'sometimes|regex:/^\d{4}-\d{2}$/',
            'capacitaciones' => 'sometimes|integer|min:0|max:99999',
            'participantes' => 'sometimes|integer|min:0|max:99999',
            'empresas' => 'sometimes|integer|min:0|max:99999',
        ]);

        // Si viene el mes en formato YYYY-MM → convertir a YYYY-MM-01
        if ($request->has('month')) {
            $request->merge([
                'month' => $request->month . '-01',
            ]);
        }

        $trainingMeta->update($request->all());

        return response()->json([
            'message' => 'Meta actualizada con éxito',
            'data' => $trainingMeta,
            'status' => 200
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $trainingMeta = TrainingMeta::find($id);
        if (!$trainingMeta) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $trainingMeta->delete();
        return response()->json(['message' => 'Eliminado correctamente', 'status' => 200]);
    }
}
