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

            $query = UgsePostulante::where('event_id', $fair->id)
                ->withBasicFilters($filters)
                ->with([
                    'company:id,ruc,socialReason',
                    'businessman:id,typedocument_id,documentnumber,name,lastname,middlename,birthday,gender_id',
                    'businessman.typedocument:id,name',
                    'businessman.gender:id,name',
                    'economicsector',
                    'category',
                    'city',
                    'typedocument',
                    'gender',
                    'howKnowEvent',
                    'event',
                    'sedQuestion'
                ])->orderBy('created_at', 'desc');

            $postulantes = $query->get();

            $rows = $postulantes->map(function ($item, $index) {
                return [
                    $index + 1,
                    $item->event->title,
                    $item->ruc,
                    $item->attended,
                    $item->comercialName,
                    $item->company->socialReason ?? $item->socialReason,

                    $item->economicsector?->name,           // sector economico
                    $item->category?->name,                 // rubro
                    $item->comercialactivity?->name,        // actividad comercial

                    $item->city?->name ?? null,
                    $item->province->name ?? null,
                    $item->district->name ?? null,
                    $item->address,
                    $item->typeAsistente == 1 ? 'Representante' : 'Invitado',
                    $item->businessman->typedocument->name ?? '-',

                    $item->businessman->documentnumber ?? $item->documentnumber,
                    $item->businessman->name ?? $item->name,
                    $item->businessman->lastname ?? $item->lastname,
                    $item->businessman->middlename ?? $item->middlename,

                    $item->businessman
                        ? ($item->businessman->gender->name === 'FEMENINO' ? 'F' : 'M')
                        : ($item->gender_id == 1 ? 'M' : 'F'),
                    $item->sick == 'no' ? 'No' : 'Si',
                    $item->phone,
                    $item->email,

                    $item->businessman->birthday ?? $item->birthday,

                    $item->age ?? null,
                    $item->positionCompany,
                    $item->howKnowEvent?->name,
                    $item->instagram,
                    $item->facebook,
                    $item->web,
                    $item->created_at ? Carbon::parse($item->created_at)->format('d/m/Y h:i A') : '',

                    $item->sedQuestion->question_1 ?? '-',
                    $item->sedQuestion->question_2 ?? '-',
                    $item->sedQuestion->question_3 ?? '-',
                    $item->sedQuestion->question_4 ?? '-',
                    $item->sedQuestion->question_5 ?? '-'
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

            // ✅ CORRECTO: solo este return
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


    private function getQuestionOptions()
    {
        return [
            'question_1' => [
                'sin_interes' => 'A. No tengo interés en la tecnología; mi negocio depende solo de mi presencia física y el boca a boca.',
                'redes_sociales' => 'B. Uso Facebook o WhatsApp porque otros lo hacen, pero no tengo un plan ni metas de ventas digitales.',
                'estrategia_digital' => 'C. Tengo una estrategia digital clara y uso datos de mis ventas pasadas para decidir qué comprar o vender.',
                'plan_transformacion' => 'D. Tengo un Plan de Transformación Digital escrito y mi modelo de negocio se adapta rápidamente a los cambios del mercado tecnológico.',
            ],

            'question_2' => [
                'sin_interes' => 'A. Solo yo tomo las decisiones y no usamos herramientas digitales para coordinar el trabajo.',
                'redes_sociales' => 'B. Mis empleados usan sus WhatsApp personales para atender clientes, pero no han recibido capacitación en herramientas de gestión.',
                'capacitacion' => 'C. Capacito a mi equipo en el uso de herramientas digitales y todos usamos un sistema común para registrar pedidos y tareas.',
                'lideres_digitales' => 'D. Contamos con líderes digitales en el equipo, todos tienen altas competencias digitales y tomamos decisiones basadas en reportes de datos en tiempo real.',
            ],

            'question_3' => [
                'celular' => 'A. Solo tengo un celular básico para llamadas y no confío en los pagos digitales ni en internet.',
                'internet_basico' => 'B. Tengo internet básico y uso computadoras personales para tareas simples (Word/Excel básico) sin protocolos de seguridad.',
                'internet_alta_velocidad' => 'C. Tengo internet de alta velocidad, uso software con licencia y protejo mi información con contraseñas y respaldos frecuentes.',
                'nube' => 'D. Uso servicios en la nube (Cloud), mi infraestructura está integrada y tengo sistemas de ciberseguridad para proteger los datos de mis clientes.',
            ],

            'question_4' => [
                'anotado' => 'A. Todo lo anoto en cuadernos o lo tengo en la memoria; a veces pierdo el control de lo que falta.',
                'excel' => 'B. Registro mis ventas en Excel al final del día, pero mi inventario y contabilidad los llevo por separado o en físico.',
                'software' => 'C. Uso un software o App específica para controlar mi stock, mis ventas y emitir comprobantes electrónicos de forma automática.',
                'integrado' => 'D. Mi sistema está totalmente integrado: me avisa automáticamente cuando queda poco stock y genera reportes contables y de producción sin errores.',
            ],

            'question_5' => [
                'local' => 'A. Solo me encuentran si pasan por mi local; no guardo datos de contacto de quienes me compran.',
                'excel' => 'B. Respondo consultas por Facebook o WhatsApp, pero no tengo un catálogo digital ni analizo si los clientes están satisfechos.',
                'software' => 'C. Tengo presencia en Google Maps, uso catálogos digitales y acepto múltiples pagos (Yape, Plin, POS). Mido la satisfacción de mis clientes.',
                'integrado' => 'D. Tengo una tienda online o CRM donde el cliente compra directamente y utilizo sus datos para enviarles ofertas personalizadas.',
            ],
        ];
    }

    private function getAnswerLabel($question, $value)
    {
        $options = $this->getQuestionOptions();

        return $options[$question][$value] ?? '-';
    }

    public function exportListCooperativas(Request $request, $slug)
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
                    'postulante.typedocument:id,avr',
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
                    // 'postulante.sedQuestion',
                    'postulante.sedQuestion' => function ($q) use ($fair) {
                        $q->where('event_id', $fair->id);
                    }
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

                $roles = [
                    'd' => 'DIRIGENTE',
                    's' => 'SOCIO',
                    'm' => 'MIEMBRO',
                ];

                $p = $item->postulante;

                $rol = $p->sedQuestion->rolCooperativa ?? null;

                return [
                    $index + 1,
                    $fair->title,

                    $item->attendance ?? '-',
                    $p->sedQuestion->rucCooperativa,
                    $p->sedQuestion->cooperativa,
                    // $p->sedQuestion->rolCooperativa,
                    $roles[$rol] ?? '-',

                    $p->ruc,
                    $p->company->socialReason ?? $p->socialReason,
                    $p->comercialName,
                    $p->economicsector?->name,
                    $p->category?->name,
                    $p->comercialactivity?->name,
                    $p->city?->name ?? null,
                    $p->province->name ?? null,
                    $p->district->name ?? null,
                    $p->address,
                    $item->typeAsistente == 1 ? 'Representante' : 'Invitado',
                    $p->typedocument->avr ?? null,
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
                    // $p->instagram,
                    // $p->facebook,
                    // $p->web,

                    $item->created_at
                        ? Carbon::parse($item->created_at)->format('d/m/Y h:i A')
                        : '',

                    $this->getAnswerLabel('question_1', $p->sedQuestion->question_1 ?? null),
                    $this->getAnswerLabel('question_2', $p->sedQuestion->question_2 ?? null),
                    $this->getAnswerLabel('question_3', $p->sedQuestion->question_3 ?? null),
                    $this->getAnswerLabel('question_4', $p->sedQuestion->question_4 ?? null),
                    $this->getAnswerLabel('question_5', $p->sedQuestion->question_5 ?? null),
                ];
            });

            $templatePath = storage_path('app/plantillas/sed_template_cooperativa.xlsx');
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
