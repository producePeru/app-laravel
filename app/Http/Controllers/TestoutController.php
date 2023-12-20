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

    public function getQuestions($workshopId)
    {
        $workshop = Workshop::find($workshopId);
  
        $testout = Testout::find($workshop->testout_id);

        $data = [
            'question1' => $testout->question1,
            'question1_opt1' => $testout->question1_opt1,
            'question1_opt2' => $testout->question1_opt2,
            'question1_opt3' => $testout->question1_opt2,
            'question2' => $testout->question2,
            'question2_opt1' => $testout->question2_opt1,
            'question2_opt2' => $testout->question2_opt2,
            'question2_opt3' => $testout->question2_opt3,
            'question3' => $testout->question3,
            'question3_opt1' => $testout->question3_opt1,
            'question3_opt2' => $testout->question3_opt2,
            'question3_opt3' => $testout->question3_opt3,
            'question4' => $testout->question4,
            'question4_opt1' => $testout->question4_opt1,
            'question4_opt2' => $testout->question4_opt2,
            'question4_opt3' => $testout->question4_opt3,
            'question5' => $testout->question5,
            'question5_opt1' => $testout->question5_opt1,
            'question5_opt2' => $testout->question5_opt2,
            'question5_opt3' => $testout->question5_opt3,

            'satistaction1' => $testout->satistaction1,
            'satistaction2' => $testout->satistaction2,
            'satistaction3' => $testout->satistaction3,

            'is_comments' => $testout->is_comments,
            'comments' => $testout->comments
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
