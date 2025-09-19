<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ActividadesUgoController extends Controller
{
    public function allActivitiesTheYear($year = 2025)
    {
        try {
            $activities = Attendance::with([
                'asesor:id,name,lastname,middlename',
                'pnte:id,name',
                'region:id,name',
                'provincia:id,name',
                'distrito:id,name',
                'attendanceList:id,attendancelist_id,typedocument_id,economicsector_id,gender_id,documentnumber,lastname,middlename,name,phone,email,ruc,comercialName,socialReason',
                'attendanceList.typedocument:id,name',
                'attendanceList.gender:id,avr',
                'attendanceList.economicsector:id,name'
            ])
                ->whereYear('created_at', $year)
                ->orderBy('id', 'desc')
                ->get();

            // Aplanar asistentes
            $list = $activities->flatMap(function ($item, $index) {
                return $item->attendanceList->map(function ($asistente) use ($item) {
                    return [
                        'asesor'              => $item->asesor ? $item->asesor->name . ' ' . $item->asesor->lastname . ' ' . $item->asesor->middlename : null,
                        'tipo_actividad'      => $item->pnte->name ?? null,
                        'nombre_actividad'    => $item->title ?? null,
                        'fecha'               => $item->startDate ? Carbon::parse($item->startDate)->format('d/m/Y') : null,
                        'region'              => $item->region->name ?? '-',
                        'provincia'           => $item->provincia->name ?? '-',
                        'distrito'            => $item->distrito->name ?? '-',

                        'lugar'               => $item->address ?? '-',
                        'tipo_documento'      => $asistente->typedocument->name ?? '-',
                        'documentnumber'      => $asistente->documentnumber ?? '-',
                        'apellido_p'          => $asistente->lastname ?? '-',
                        'apellido_m'          => $asistente->middlename ?? '-',
                        'nombre'              => $asistente->name ?? '-',
                        'celular'             => $asistente->phone ?? '-',
                        'email'               => $asistente->email ?? '-',
                        'sexo'                => $asistente->gender->avr ?? '-',
                        'ruc'                 => $asistente->ruc ?? '-',
                        'nombre_comercial'    => $asistente->comercialName ?? '-',
                        'actividad_comercial' => $asistente->economicsector->name ?? '-',
                    ];
                });
            })->values();

            // Cargar plantilla
            $templatePath = storage_path('app/plantillas/actividades_full_template.xlsx');
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            $startRow = 2;
            foreach ($list as $i => $row) {
                $col = 'A';

                // 🔹 agregamos índice (i+1) como primera columna
                $sheet->setCellValue("{$col}" . ($startRow + $i), $i + 1);
                $col++;

                foreach (array_values($row) as $value) {
                    $sheet->setCellValue("{$col}" . ($startRow + $i), $value);
                    $col++;
                }
            }

            return new StreamedResponse(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 200, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="actividades_' . $year . '.xlsx"',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al exportar: ' . $e->getMessage()
            ], 500);
        }
    }
}
