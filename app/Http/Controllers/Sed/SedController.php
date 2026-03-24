<?php

namespace App\Http\Controllers\Sed;

use App\Http\Controllers\Controller;
use App\Models\Fair;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SedSurvey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SedController extends Controller
{
    public function storeSedSurvey(Request $request)
    {
        DB::beginTransaction();

        try {

            $payload = $request->all();

            // 1. Obtener SED
            $fair = Fair::where('slug', $payload['slug'])->firstOrFail();
            $sedId = $fair->id;

            foreach ($payload['questions'] as $q) {

                // 2. Buscar pregunta por label
                $question = Question::where('label', $q['label'])->first();

                if (!$question) {

                    // generar model único
                    $nextId = Question::max('id') + 1;
                    $model = "question_" . $nextId;

                    $question = Question::create([
                        'tableName' => 'sed',
                        'label' => $q['label'],
                        'type' => $q['type'],
                        'model' => $model,
                        'required'  => $q['required'] ? 1 : 0
                    ]);
                }

                // 3. Opciones
                if (!empty($q['options'])) {

                    foreach ($q['options'] as $opt) {

                        QuestionOption::updateOrCreate(
                            [
                                'question_id' => $question->id,
                                'value' => $opt['value']
                            ],
                            [
                                'label' => $opt['label'],
                                'status' => $opt['status'] ?? 1
                            ]
                        );
                    }
                }

                // 4. Relación SedSurvey (sin title)
                SedSurvey::updateOrCreate(
                    [
                        'sed_id' => $sedId,
                        'question_id' => $question->id
                    ],
                    []
                );
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Encuesta registrada correctamente'
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error registrando encuesta SED', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error al registrar la encuesta.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

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
                        'id' => $q->id, // agregado
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

    public function updateSedQuestion(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $data = $request->validate([
                'type' => 'required|string',
                'label' => 'required|string',
                'model' => 'required|string',
                'required' => 'required|boolean',
                'options' => 'nullable|array',
                'options.*.label' => 'required|string',
                'options.*.value' => 'required|string'
            ]);

            // 1️⃣ Buscar pregunta
            $question = Question::with('options')->findOrFail($id);

            // 2️⃣ Actualizar pregunta
            $question->update([
                'type' => $data['type'],
                'label' => $data['label'],
                'model' => $data['model'],
                'required' => $data['required']
            ]);

            // 3️⃣ Manejar opciones
            $existingOptions = $question->options->keyBy('value');

            $payloadValues = collect($data['options'])->pluck('value')->toArray();

            foreach ($data['options'] as $opt) {

                if ($existingOptions->has($opt['value'])) {

                    // actualizar
                    $existingOptions[$opt['value']]->update([
                        'label' => $opt['label']
                    ]);
                } else {

                    // crear nueva opción
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'label' => $opt['label'],
                        'value' => $opt['value']
                    ]);
                }
            }

            // 4️⃣ Eliminar opciones que ya no existen
            QuestionOption::where('question_id', $question->id)
                ->whereNotIn('value', $payloadValues)
                ->delete();

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Pregunta actualizada correctamente'
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error actualizando pregunta SED', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Error al actualizar la pregunta'
            ], 500);
        }
    }

    public function deleteSedQuestion($id)
    {
        try {

            $question = Question::findOrFail($id);

            $question->delete(); // soft delete

            return response()->json([
                'status' => 200,
                'message' => 'Pregunta eliminada correctamente'
            ]);
        } catch (\Throwable $e) {

            Log::error('Error eliminando pregunta SED', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Error al eliminar la pregunta'
            ], 500);
        }
    }

    public function storeSedQuestion(Request $request)
    {
        DB::beginTransaction();

        try {

            $data = $request->validate([
                'slug'                    => 'required|string',
                'values.slug'             => 'required|string',
                'values.questions'        => 'required|array',
                'values.questions.*.id'   => 'nullable|integer',
                'values.questions.*.type' => 'required|string',
                'values.questions.*.label'    => 'required|string',
                'values.questions.*.required' => 'required|boolean',
                'values.questions.*.options'  => 'nullable|array',
                'values.questions.*.options.*.label' => 'required|string',
                'values.questions.*.options.*.value' => 'required',
            ]);

            // 1️⃣ Obtener SED por slug
            $fair = Fair::where('slug', $data['slug'])->firstOrFail();

            foreach ($data['values']['questions'] as $q) {

                // 2️⃣ ¿Tiene ID? → Editar, sino → Crear
                if (!empty($q['id'])) {

                    // --- EDITAR ---
                    $question = Question::with('options')->findOrFail($q['id']);

                    $question->update([
                        'type'     => $q['type'],
                        'label'    => $q['label'],
                        'required' => $q['required'] ? 1 : 0,
                    ]);

                    // Sincronizar opciones
                    if (!empty($q['options'])) {

                        $existingOptions = $question->options->keyBy('value');
                        $payloadValues   = collect($q['options'])->pluck('value')->map(fn($v) => (string) $v)->toArray();

                        foreach ($q['options'] as $opt) {
                            $strValue = (string) $opt['value'];

                            if ($existingOptions->has($strValue)) {
                                $existingOptions[$strValue]->update(['label' => $opt['label']]);
                            } else {
                                QuestionOption::create([
                                    'question_id' => $question->id,
                                    'label'       => $opt['label'],
                                    'value'       => $strValue
                                ]);
                            }
                        }

                        // Eliminar opciones que ya no existen
                        QuestionOption::where('question_id', $question->id)
                            ->whereNotIn('value', $payloadValues)
                            ->delete();
                    }
                } else {

                    // --- CREAR ---
                    $nextId = (Question::max('id') ?? 0) + 1;
                    $model  = 'question_' . $nextId;

                    $question = Question::create([
                        'type'      => $q['type'],
                        'label'     => $q['label'],
                        'model'     => $model,
                        'required'  => $q['required'] ? 1 : 0,
                        'tableName' => 'sed',
                    ]);

                    // Crear opciones
                    if (!empty($q['options'])) {
                        foreach ($q['options'] as $opt) {
                            $nextOptId = (QuestionOption::max('id') ?? 0) + 1;

                            QuestionOption::create([
                                'question_id' => $question->id,
                                'label'       => $opt['label'],
                                'value'       => 'option_' . $nextOptId
                            ]);
                        }
                    }

                    // Guardar en SedSurvey
                    SedSurvey::updateOrCreate(
                        [
                            'sed_id'      => $fair->id,
                            'question_id' => $question->id
                        ],
                        []
                    );
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'message' => 'Encuesta guardada correctamente'
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error guardando encuesta SED', [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
                'file'  => $e->getFile()
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Error al guardar la encuesta',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
