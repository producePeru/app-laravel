<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\Fair;
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

            $query = UgsePostulante::where('event_id', $fair->id)
                ->withBasicFilters($filters)
                ->with([
                    'economicsector',
                    'category',
                    'city',
                    'typedocument',
                    'gender',
                    'howKnowEvent',
                    'event',
                ]);

            $postulantes = $query->get();

            $rows = $postulantes->map(function ($item, $index) {
                return [
                    $index + 1,
                    $item->event->subTitle,
                    $item->ruc,
                    $item->attended,
                    $item->comercialName,
                    $item->socialReason,
                    $item->economicsector?->name,
                    $item->comercialactivity?->name,
                    $item->category?->name,
                    $item->city?->name ?? null,
                    $item->province->name ?? null,
                    $item->district->name ?? null,
                    $item->address,
                    $item->typeAsistente == 1 ? 'Representante' : 'Invitado',
                    $item->typedocument?->name,
                    $item->documentnumber,
                    $item->lastname,
                    $item->middlename,
                    $item->name,
                    $item->gender?->name,
                    $item->sick == 'no' ? 'No' : 'Si',
                    $item->phone,
                    $item->email,
                    $item->birthday,
                    $item->age ?? null,
                    $item->positionCompany,
                    $item->howKnowEvent?->name,
                    $item->instagram,
                    $item->facebook,
                    $item->web,
                    $item->created_at ? Carbon::parse($item->created_at)->format('d/m/Y h:i A') : '',
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
}
