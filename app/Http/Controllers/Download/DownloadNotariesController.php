<?php

namespace App\Http\Controllers\Download;

use App\Exports\NotaryExport;
use App\Http\Controllers\Controller;
use App\Models\Notary;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadNotariesController extends Controller
{
    public function exportNotaries(Request $request)
    {

        try {
            $filters = $request->query();

            $userRole = getUserRole();
            $roleIds  = $userRole['role_id'];
            $userId   = $userRole['user_id'];

            $query = Notary::query();

            $query->withItems($filters);

            ini_set('memory_limit', '2G');
            set_time_limit(300);

            $notaries = [];
            $globalIndex = 1;

            $query->chunk(1000, function ($rows) use (&$notaries, &$globalIndex) {
                foreach ($rows as $notary) {

                    $notaries[] = [
                        'index'                 => $globalIndex++,
                        'notaria'               => strtoupper($notary->nameNotary) ?? null,
                        'city'                  => $notary->city->name ?? null,
                        'province'              => $notary->province->name ?? null,
                        'district'              => $notary->district->name ?? null,
                        'addressNotary'         => strtoupper($notary->addressNotary) ?? null,
                        'gastos'                => $notary->gasto1 ?? null,
                        'gasto2'                => $notary->gasto2 ?? null,
                        'gasto2Detail'          => $notary->gasto2Detail ?? null,
                        'gasto3'                => $notary->gasto3 ?? null,
                        'gasto3Detail'          => $notary->gasto3Detail ?? null,
                        'gasto4'                => $notary->gasto4 ?? null,
                        'gasto4Detail'          => $notary->gasto4Detail ?? null,
                        'testimonio'            => $notary->testimonio ?? null,
                        'legalization'          => $notary->legalization ?? null,
                        'biometric'             => $notary->biometric ?? null,
                        'aclaratory'            => $notary->aclaratory ?? null,
                        'socio'                 => $notary->socio ?? null,
                        'conditions'            => $notary->conditions ?? null,
                        'contactName'           => $notary->contactName ?? null,
                        'contactEmail'          => $notary->contactEmail ?? null,
                        'contactPhone'          => $notary->contactPhone ?? null,
                        'normalTarifa'          => $notary->normalTarifa ?? null,
                    ];
                }
            });

            // return Excel::download(new NotaryExport($notaries), 'notaries.xlsx');

            // 🔹 Cargar la plantilla existente
            $templatePath = storage_path('app/plantillas/notarias_template.xlsx');
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // 🔹 Insertar datos desde la fila 4
            $startRow = 4;
            foreach ($notaries as $i => $notaryRow) {
                $col = 'A';
                foreach ($notaryRow as $value) {
                    $sheet->setCellValue("{$col}" . ($startRow + $i), $value);
                    $col++;
                }
            }

            // 🔹 Retornar como descarga
            return new StreamedResponse(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 200, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="notarias.xlsx"',
            ]);




        } catch (\Exception $e) {
            return response()->json([
                'message' => 'OcurriÓ un error al generar el reporte.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
