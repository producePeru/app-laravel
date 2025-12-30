<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\TrainingSpecialist;
use Illuminate\Http\Request;

class TrainingSpecialistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $specialists = TrainingSpecialist::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 200,
            'data'   => $specialists
        ], 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'ocupation' => 'required|string|max:100',
            'color' => 'required|string|max:10',
        ]);

        $specialist = TrainingSpecialist::create($validated);

        return response()->json([
            'message' => 'Especialista registrado con éxito',
            'data' => $specialist,
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
        $specialist = TrainingSpecialist::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'ocupation' => 'sometimes|required|string|max:100',
            'color' => 'sometimes|required|string|max:10',
        ]);

        $specialist->update($validated);

        return response()->json([
            'message' => 'Especialista en formación actualizado con éxito',
            'specialist' => $specialist,
            'status' => 200
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $specialist = TrainingSpecialist::findOrFail($id);
        $specialist->delete();

        return response()->json(['message' => 'Eliminado correctamente', 'status' => 200]);
    }
}
