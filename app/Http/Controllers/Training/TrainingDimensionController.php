<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\TrainingDimension;
use Illuminate\Http\Request;

class TrainingDimensionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dimension = TrainingDimension::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 200,
            'data'   => $dimension
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $dimension = TrainingDimension::create($validated);
        return response()->json([
            'message' => 'Training Dimension created successfully',
            'data' => $dimension,
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
        $dimension = TrainingDimension::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
        ]);

        $dimension->update($validated);

        return response()->json([
            'message' => 'Training Dimension updated successfully',
            'data' => $dimension,
            'status' => 200
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $dimension = TrainingDimension::findOrFail($id);
        $dimension->delete();

        return response()->json(['message' => 'Eliminado correctamente', 'status' => 200]);
    }
}
