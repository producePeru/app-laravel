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
       return "hi";
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
    public function show($workshopId)
    {
        $workshop = Workshop::find($workshopId);
        
        $testin = Testin::find($workshop->testin_id);
        
        try {
            return response()->json(['data' => $testin], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Test de entrada no encontrado'], 404);
        } 
    }

    public function getQuestions($workshopId)
    {
        $workshop = Workshop::find($workshopId);
        
        $testin = Testin::find($workshop->testin_id);

        $data = [
            'question1' => $testin->question1,
            'question1_opt1' => $testin->question1_opt1,
            'question1_opt2' => $testin->question1_opt2,
            'question1_opt3' => $testin->question1_opt2,
            'question2' => $testin->question2,
            'question2_opt1' => $testin->question2_opt1,
            'question2_opt2' => $testin->question2_opt2,
            'question2_opt3' => $testin->question2_opt3,
            'question3' => $testin->question3,
            'question3_opt1' => $testin->question3_opt1,
            'question3_opt2' => $testin->question3_opt2,
            'question3_opt3' => $testin->question3_opt3,
            'question4' => $testin->question4,
            'question4_opt1' => $testin->question4_opt1,
            'question4_opt2' => $testin->question4_opt2,
            'question4_opt3' => $testin->question4_opt3,
            'question5' => $testin->question5,
            'question5_opt1' => $testin->question5_opt1,
            'question5_opt2' => $testin->question5_opt2,
            'question5_opt3' => $testin->question5_opt3
        ];
        try {
            return response()->json(['data' => $data], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Test de entrada no encontrado'], 404);
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
    public function destroy(Testin $testin, $id)
    {
        return "HHHH";
    }
}
