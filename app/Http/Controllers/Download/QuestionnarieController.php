<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class QuestionnarieController extends Controller
{
    public function questionsAnswersAdvisorsFormalizations()
    {
        try {
            $user = auth()->user();

            if ($user->rol != 1) {
                return response()->json([
                    'message' => 'No tienes permiso para realizar esta acción',
                    'status'  => 403
                ], 403);
            }

            // ✅ Solo si es rol 1 ejecuta la exportación
            $items = DB::table('questions_answers')
                ->join('users', 'questions_answers.user_id', '=', 'users.id')
                ->select(
                    'questions_answers.*',
                    'users.name as asesor_name',
                    'users.lastname as asesor_lastname'
                )
                ->orderBy('questions_answers.created_at', 'desc')
                ->get();

            $rows = $items->map(function ($item, $index) {
                return [
                    'N°'        => $index + 1,
                    'Pregunta'  => $item->question,
                    'Respuesta' => $item->answer,
                ];
            });

            $templatePath = storage_path('app/plantillas/cuestionario_formalizaciones_av.xlsx');
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();
            $startRow = 2;

            foreach ($rows as $i => $row) {
                $col = 'A';
                foreach ($row as $value) {
                    $sheet->setCellValue("{$col}" . ($startRow + $i), $value);
                    $col++;
                }
            }

            // ✅ Descarga del archivo
            return new StreamedResponse(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 200, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="cuestionario_formalizaciones.xlsx"',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al exportar: ' . $e->getMessage(),
                'status'  => 500
            ], 500);
        }
    }
}
