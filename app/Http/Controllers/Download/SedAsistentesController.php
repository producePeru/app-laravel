<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\Fair;
use App\Models\SedAsistente;
use App\Models\SedQuestion;
use App\Models\sedQuestionAnswer;
use App\Models\UgsePostulante;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SedAsistentesController extends Controller
{
    public function exportList(Request $request, $slug)
    {
        try {
            $fair = Fair::where('slug', $slug)->firstOrFail();

            $filters = $request->query();

            // 🔥 AHORA DESDE sed_asistencias
            $query = SedAsistente::where('sed_id', $fair->id)
                ->with([
                    'postulante.company:id,ruc,socialReason',
                    'postulante.businessman:id,typedocument_id,documentnumber,name,lastname,middlename,birthday,gender_id',
                    'postulante.businessman.typedocument:id,name',
                    'postulante.businessman.gender:id,name',
                    'postulante.economicsector',
                    'postulante.category',
                    'postulante.comercialactivity',
                    'postulante.city',
                    'postulante.province',
                    'postulante.district',
                    'postulante.typedocument',
                    'postulante.gender',
                    'postulante.howKnowEvent',
                    'postulante.event',
                    'postulante.sedQuestion'
                ])
                // 🔥 MÁS RECIENTE PRIMERO
                ->orderBy('id', 'desc');

            // 🔎 Filtros
            if (!empty($filters['name'])) {
                $query->whereHas('postulante', function ($q) use ($filters) {
                    $q->where('ruc', 'like', '%' . $filters['name'] . '%')
                        ->orWhere('documentnumber', 'like', '%' . $filters['name'] . '%');
                });
            }

            if (!empty($filters['dateStart']) && !empty($filters['dateEnd'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($filters['dateStart'])->startOfDay(),
                    Carbon::parse($filters['dateEnd'])->endOfDay()
                ]);
            }

            $asistencias = $query->get();

            $rows = $asistencias->map(function ($item, $index) use ($fair) {

                $p = $item->postulante; // 🔥 shortcut

                return [
                    $index + 1,
                    $fair->title,
                    $p->ruc,

                    // 🔥 AHORA attendance REAL
                    $item->attendance,

                    $p->comercialName,
                    $p->company->socialReason ?? $p->socialReason,

                    $p->economicsector?->name,
                    $p->category?->name,
                    $p->comercialactivity?->name,

                    $p->city?->name ?? null,
                    $p->province->name ?? null,
                    $p->district->name ?? null,
                    $p->address,

                    // 🔥 typeAsistente DESDE sed_asistencias
                    $item->typeAsistente == 1 ? 'Representante' : 'Invitado',

                    $p->businessman->typedocument->name ?? '-',

                    $p->businessman->documentnumber ?? $p->documentnumber,
                    $p->businessman->name ?? $p->name,
                    $p->businessman->lastname ?? $p->lastname,
                    $p->businessman->middlename ?? $p->middlename,

                    $p->businessman
                        ? ($p->businessman->gender->name === 'FEMENINO' ? 'F' : 'M')
                        : ($p->gender_id == 1 ? 'M' : 'F'),

                    $p->sick == 'no' ? 'No' : 'Si',
                    $p->phone,
                    $p->email,

                    $p->businessman->birthday ?? $p->birthday,
                    $p->age ?? null,
                    $p->positionCompany,
                    $p->howKnowEvent?->name,
                    $p->instagram,
                    $p->facebook,
                    $p->web,

                    $item->created_at
                        ? Carbon::parse($item->created_at)->format('d/m/Y h:i A')
                        : '',

                    $p->sedQuestion->question_1 ?? '-',
                    $p->sedQuestion->question_2 ?? '-',
                    $p->sedQuestion->question_3 ?? '-',
                    $p->sedQuestion->question_4 ?? '-',
                    $p->sedQuestion->question_5 ?? '-'
                ];
            });

            $templatePath = storage_path('app/plantillas/sed_template.xlsx');
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

            return new StreamedResponse(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 200, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="postulantes-feria.xlsx"',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al exportar: ' . $e->getMessage()
            ], 500);
        }
    }





    // ESTE ES PARA LA PRUEBAS DEL TESTEO
    public function exportSedAnswers(Request $request)
    {
        try {
            $request->validate([
                'slug' => 'required|string'
            ]);

            // 1️⃣ Buscar evento
            $fair = Fair::where('slug', $request->slug)->firstOrFail();
            $fair->load('modality');

            // 2️⃣ Listar todos los asistentes del evento
            $asistentes = SedAsistente::where('sed_id', $fair->id)->get();

            // 3️⃣ Traer respuestas de encuesta
            $answers   = sedQuestionAnswer::where('sed_id', $fair->id)->get();
            $questions = $answers->pluck('question')->unique()->values();

            // Agrupar respuestas por dni
            $answersGrouped = $answers->groupBy('dni');

            // 4️⃣ Construir filas basadas en asistentes
            $rows = $asistentes->map(function ($asistente) use ($fair, $answersGrouped) {

                // Buscar postulante por dni del asistente
                $queryPostulante = UgsePostulante::with([
                    'economicsector',
                    'category',
                    'comercialactivity',
                    'city',
                    'province',
                    'district',
                    'typedocument',
                    'gender',
                ]);


                if (is_null($asistente->mype_id)) {
                    // Si mype_id es NULL, buscamos solo por DNI
                    $postulante = $queryPostulante->where('documentnumber', $asistente->dni)->first();
                } else {
                    // Si existe mype_id, validamos por ID y por DNI
                    $postulante = $queryPostulante->where('id', $asistente->mype_id)
                        ->where('documentnumber', $asistente->dni)
                        ->first();
                }







                $sedQuestion = SedQuestion::where('event_id', $fair->id)
                    ->where('documentnumber', $asistente->dni)
                    ->first();

                // Respuestas dinámicas del participante
                $participantAnswers = $answersGrouped->get($asistente->dni, collect());
                $answerMap          = $participantAnswers->pluck('answer', 'question');

                $base = [
                    'TIPO ASISTENTE'      => $asistente->typeAsistente == 1 ? 'REPRESENTANTE' : 'INVITADO',
                    'RUC'                 => $postulante->ruc ?? '',
                    'RAZÓN SOCIAL'        => $postulante->socialReason ?? '',
                    'NOMBRE COMERCIAL'    => $postulante->comercialName ?? '',
                    'SECTOR ECONÓMICO'    => $postulante->economicsector->name ?? '',
                    'RUBRO'               => $postulante->category->name ?? '',
                    'ACTIVIDAD COMERCIAL' => $postulante->comercialactivity->name ?? '',
                    'REGIÓN'              => $postulante->city->name ?? '',
                    'PROVINCIA'           => $postulante->province->name ?? '',
                    'DISTRITO'            => $postulante->district->name ?? '',
                    'DIRECCIÓN'           => $postulante->address ?? '',
                    'TIPO DOC'            => $postulante->typedocument->avr ?? '',
                    'DNI'                 => $asistente->dni,
                    'NOMBRE'              => $postulante ? strtoupper(trim(
                        $postulante->name . ' ' . $postulante->lastname . ' ' . $postulante->middlename
                    )) : '',
                    'GÉNERO'              => $postulante->gender->avr ?? '',
                    'DISCAPACIDAD'        => $postulante->sick ?? '',
                    'TELÉFONO'            => $postulante->phone ?? '',
                    'EMAIL'               => $postulante->email ?? '',
                    'CARGO'               => $postulante->positionCompany ?? '',
                    'FECHA CUMPLEAÑOS'    => $postulante->birthday ?? '',
                    'PREGUNTA 1'          => $sedQuestion->question_1 ?? '',
                    'PREGUNTA 2'          => $sedQuestion->question_2 ?? '',
                    'PREGUNTA 3'          => $sedQuestion->question_3 ?? '',
                    'PREGUNTA 4'          => $sedQuestion->question_4 ?? '',
                    'PREGUNTA 5'          => $sedQuestion->question_5 ?? '',
                ];

                return $base + $answerMap->toArray();
            });

            // 5️⃣ Generar y descargar
            $filename = 'respuestas_' . $fair->slug . '_' . now()->format('Ymd_His') . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\SedAnswersExport($rows, $questions, $fair),
                $filename
            );
        } catch (\Throwable $e) {

            Log::error('Error exportando respuestas SED', [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Error al exportar',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
