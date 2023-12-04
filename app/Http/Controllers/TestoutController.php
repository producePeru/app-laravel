<?php

namespace App\Http\Controllers;

use App\Models\Testout;
use App\Models\Workshop;
use App\Http\Requests\StoreTestoutRequest;
use App\Http\Requests\UpdateTestoutRequest;
use Illuminate\Http\Request;

class TestoutController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(StoreTestoutRequest $request)
    {
        //
    }

    public function createTestout(Request $request, $workshopId)
    {
        $workshop = Workshop::find($workshopId);

        if (!$workshop) {
            return response()->json(['message' => 'Taller no encontrado'], 404);
        }

        if ($workshop->testout_id !== null) {
            return response()->json(['message' => 'El Test de salida ya está asociado a este Taller'], 422);
        }

        try {
            $testout = Testout::create($request->all());
            $workshop->update(['testout_id' => $testout->id]);
            
            return response()->json(['message' => 'Test creado correctamente'], 201);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error al crear el test de salida. Por favor, inténtalo de nuevo.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al crear este test de salida.', $e], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $testout = Testout::find($id);
        
        try {
            return response()->json(['data' => $testout], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Test de salida no encontrado'], 404);
        } 
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Testout $testout)
    {
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTestoutRequest $request, Testout $testout)
    {
        try {
            $testout->update($request->all());
            return response()->json(['message' => 'Test de salida actualizado correctamente']);    
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error al actualizar este test.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al actualizar este test de entrada.', $e], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Testout $testout)
    {
        //
    }
}
