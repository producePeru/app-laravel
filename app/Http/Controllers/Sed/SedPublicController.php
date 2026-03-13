<?php

namespace App\Http\Controllers\Sed;

use App\Http\Controllers\Controller;
use App\Models\Fair;
use App\Models\SedSurvey;
use Illuminate\Support\Facades\Log;

class SedPublicController extends Controller
{
    public function getSedSurvey($slug)
    {
        try {

            $fair = Fair::where('slug', $slug)->firstOrFail();

            $questions = SedSurvey::where('sed_id', $fair->id)
                ->with(['question.options'])
                ->get()
                ->map(function ($item) {

                    $q = $item->question;

                    if (!$q || $q->tableName !== 'sed') {
                        return null;
                    }

                    return [
                        'type' => $q->type,
                        'label' => $q->label,
                        'model' => $q->model,
                        'required' => (bool) $q->required,
                        'md' => 12,
                        'options' => $q->options->map(function ($opt) {
                            return [
                                'label' => $opt->label,
                                'value' => $opt->value
                            ];
                        })->values()
                    ];
                })
                ->filter()
                ->values();

            return response()->json([
                'status' => 200,
                'questions' => $questions
            ]);
        } catch (\Throwable $e) {

            Log::error('Error obteniendo encuesta SED', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => $e->getMessage(),
                'status' => 500,
                'message' => 'Error al obtener la encuesta'
            ], 500);
        }
    }
}
