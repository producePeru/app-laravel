<?php

namespace App\Http\Controllers;

use App\Models\Testin;
use App\Models\Workshop;
use App\Http\Requests\StoreTestinRequest;
use App\Http\Requests\UpdateTestinRequest;
use Illuminate\Http\Request;

class TestinController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       
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
     * 
     */
    public function store(StoreTestinRequest $request)
    {
        
    }

    public function createTestin(Request $request, $workshopId)
    {
        $workshop = Workshop::find($workshopId);

        if (!$workshop) {
            return response()->json(['message' => 'Taller no encontrado'], 404);
        }

        if ($workshop->testin_id !== null) {
            return response()->json(['message' => 'El Test ya está asociado a este Taller'], 422);
        }

        try {
           
            $testin = Testin::create($request->all());
            $workshop->update(['testin_id' => $testin->id]);
            
            return response()->json(['message' => 'Test creado correctamente'], 201);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error al crear el Exponnente. Por favor, inténtalo de nuevo.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al crear este test de entrada.', $e], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Testin $testin)
    {
        try {
            return response()->json(['testin' => $testin], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Testin no encontrado'], 404);
        } 

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Testin $testin)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTestinRequest $request, Testin $testin)
    {
        try {
            $testin->update($request->all());
            return response()->json(['message' => 'Test de entrada actualizado correctamente']);    
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error al actualizar este test.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al actualizar este test de entrada.', $e], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Testin $testin)
    {
        //
    }
}
