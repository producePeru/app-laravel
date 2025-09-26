<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\CyberwowParticipant;
use App\Models\Fair;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CyberWowParticipantesController extends Controller
{
    public function exportList(Request $request, $slug)
    {
        try {

            $fair = Fair::where('slug', $slug)->firstOrFail();

            $query = CyberwowParticipant::with([
                'region:id,name',
                'provincia:id,name',
                'distrito:id,name',
                'sectorEconomico:id,name',
                'actividadComercial:id,name',
                'rubro:id,name',
                'tipoDocumento:id,name',
                'genero:id,avr',
                'pais:id,name',
                'medioEntero:id,name'
            ])->where('event_id', $fair->id)
                ->orderBy('created_at', 'desc');

            if ($request->filled('search')) {
                $search = $request->input('search');

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('lastname', 'LIKE', "%{$search}%")
                        ->orWhere('middlename', 'LIKE', "%{$search}%")
                        ->orWhere('documentnumber', 'LIKE', "%{$search}%")
                        ->orWhere('ruc', 'LIKE', "%{$search}%")
                        ->orWhere('razonSocial', 'LIKE', "%{$search}%")
                        ->orWhere('nombreComercial', 'LIKE', "%{$search}%");
                });
            }


            $postulantes = $query->get();

            $rows = $postulantes->map(function ($item, $index) {

                $socials = collect($item->socials)->mapWithKeys(fn($s) => [$s['name'] => $s['link']]);

                return [
                    $index + 1,
                    $item->ruc,
                    $item->razonSocial,
                    $item->nombreComercial,
                    $item->region->name,
                    $item->provincia->name,
                    $item->distrito->name,
                    $item->direccion,

                    // 👇 redes sociales
                    $socials->get('Facebook') ?? '-',
                    $socials->get('TikTok') ?? '-',
                    $socials->get('Instagram') ?? '-',
                    $socials->get('Web') ?? '-',

                    $item->sectorEconomico->name,
                    $item->rubro->name,
                    $item->actividadComercial->name,
                    $item->descripcion,
                    $item->tipoDocumento->name,
                    $item->documentnumber,
                    $item->name,
                    $item->middlename,
                    $item->lastname,
                    $item->genero->avr,
                    $item->phone,
                    $item->email,
                    Carbon::parse($item->birthday)->format('d/m/Y'),
                    $item->age,
                    $item->cargo ?? '-',
                    $item->sick,
                    $item->pais->name,
                    $item->question_1,
                    $item->question_2,
                    $item->question_3,
                    $item->question_4,
                    $item->question_5,
                    $item->question_6,
                    $item->question_7,

                    $item->medioEntero->name
                ];
            });


            // return $rows;
            $templatePath = storage_path('app/plantillas/cyberwow_template.xlsx');
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
