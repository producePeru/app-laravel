<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Workshop;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $workshops = Workshop::all();
        return response()->json(['data' => $workshops, 'status' => 200]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'workshopName' => 'required|string|max:250',
            'date' => 'required|date',
            'hour' => 'required|string|max:20',
            'link' => 'required|string|max:255',
            'description' => 'nullable|string',
            'expositor' => 'nullable|string|max:100'
        ]);

        $workshop = Workshop::create($validated);
        return response()->json(['message' => 'Taller registrado correctamente', 'status' => 200]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $workshop = Workshop::findOrFail($id);
        return response()->json($workshop);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'workshopName' => 'required|string|max:250',
            'date' => 'required|date',
            'hour' => 'required|string|max:20',
            'link' => 'required|string|max:255',
            'description' => 'nullable|string',
            'expositor' => 'nullable|string|max:100',
        ]);

        $workshop = Workshop::findOrFail($id);
        $workshop->update($validated);
        return response()->json(['status' => 200, 'message' => 'Taller editado']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $workshop = Workshop::findOrFail($id);
        $workshop->delete();
        return response()->json(['message' => 'Taller eliminado correctamente']);
    }
}
