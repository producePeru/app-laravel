<?php

namespace App\Http\Controllers\Dgtdif;

use App\Http\Controllers\Controller;
use App\Models\DgtifSurveys;
use Illuminate\Http\Request;

class SurveysController extends Controller
{

    public function index()
    {
        $surveys = DgtifSurveys::orderBy('created_at', 'desc')
            ->get()
            ->map(function ($survey) {
                return [
                    'id' => $survey->id,
                    'question1' => $survey->question1,
                    'question2' => $survey->question2,
                    'question3' => $survey->question3,
                    'question4' => $survey->question4,
                    'total' => $survey->total,
                    'status' => $survey->status,
                    'created_at' => $survey->created_at->format('d/m/Y H:i')
                ];
            });

        return response()->json([
            'data' => $surveys,
            'status' => 200
        ]);
    }




    public function store(Request $request)
    {
        // Validar los datos recibidos
        $validated = $request->validate([
            'answers' => 'required|array|size:4',
            'answers.*' => 'required|integer|min:0|max:255',
            'total' => 'required|integer|min:0|max:255',
            'result' => 'required|in:Baja,Media,Alta',
        ]);

        // Insertar los datos en la tabla
        $survey = DgtifSurveys::create([
            'question1' => $validated['answers'][0],
            'question2' => $validated['answers'][1],
            'question3' => $validated['answers'][2],
            'question4' => $validated['answers'][3],
            'total' => $validated['total'],
            'status' => $validated['result'],
        ]);

        return response()->json([
            'message' => 'Survey saved successfully.',
            'data' => $survey,
        ], 201);
    }
}
