<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supervisor;
use Illuminate\Support\Facades\DB;

class SupervisorController extends Controller
{
    public function index()
    {

        $supervisor = Supervisor::withProfileAndRelations();

        return response()->json($supervisor, 200);
    }


    // quiero saber si el usuario ya completó el formulario con preguntas y respuestas hacerca de
    // formalizaciones, es importante para el Asistente Virtual
    public function userHasCompletedFormalizationForm()
    {
        try {

            $userId = auth()->id(); // ID del usuario autenticado
            $user = auth()->user();

            // Obtener el cde_id del usuario
            $cdeId = $user->cde_id;

            // Obtener el 'name' de la tabla 'cdes' usando el cde_id
            $cdeName = DB::table('cdes')
                ->where('id', $cdeId)
                ->value('name');

            // Verificar si el 'name' contiene 'agente'
            if (stripos($cdeName, 'agente') !== false) {
                // Si el 'name' contiene 'agente', devolver false
                return response()->json([
                    'completed' => true,
                    'status' => 200
                ]);
            }

            $hasQuestions = DB::table('questions_answers')
                ->where('user_id', $userId)
                ->exists();

            return response()->json([
                'completed' => $hasQuestions,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al verificar si el usuario completó el formulario.',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }


    public function listQuestionsAnswersFormalizations()
    {
        try {
            $user = auth()->user();

            // Construir query base
            $query = DB::table('questions_answers')
                ->join('users', 'questions_answers.user_id', '=', 'users.id')
                ->select(
                    'questions_answers.*',
                    'users.name as asesor_name',
                    'users.lastname as asesor_lastname'
                )->orderBy('questions_answers.created_at', 'desc');

            // Filtrar por rol
            if ($user->rol == 2) {
                $query->where('questions_answers.user_id', $user->id);
            }

            // Paginar de a 150, mapear resultados
            $items = $query
                ->orderBy('questions_answers.user_id')
                ->paginate(150)
                ->through(fn($item) => $this->mapQuestionsAnswers($item));

            return response()->json([
                'data'   => $items,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al listar las preguntas de formalización.',
                'error'   => $e->getMessage(),
                'status'  => 500
            ], 500);
        }
    }

    private function mapQuestionsAnswers($item)
    {
        return [
            'id' => $item->id,
            'asesor' => $item->asesor_name . ' ' . $item->asesor_lastname,
            'question' => $item->question,
            'answer' => $item->answer,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at
        ];
    }
}
