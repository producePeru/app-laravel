<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\Fair;
use App\Models\SedAsistente;
use App\Models\UgsePostulante;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
}
