<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadRetroAlimentacionController extends Controller
{
    /**
     * POST download/reporte-retroalimentacion/{id}
     * Descarga el reporte de calificaciones de un tema usando la plantilla
     * storage/app/plantillas/retroalimentacion-ugo.xlsx (cabeceras en fila 1,
     * datos desde fila 2, una fila por colega que calificó).
     * Solo el creador del tema (o el supervisor) puede descargarlo.
     */
    public function exportReporte(Request $request, $id)
    {
        try {
            $tema = DB::table('retro_temas')->where('id', $id)->first();
            if (! $tema) {
                return response()->json(['message' => 'Tema no encontrado'], 404);
            }

            $me = auth()->user();
            $esSupervisor = (int) ($me->supervisor_id ?? 0) === 1;
            if ((int) $tema->user_id !== (int) $me->id && ! $esSupervisor) {
                return response()->json(['message' => 'Solo el creador del tema puede descargar este reporte'], 403);
            }

            $templatePath = storage_path('app/plantillas/retroalimentacion-ugo.xlsx');
            if (! file_exists($templatePath)) {
                return response()->json(['message' => 'La plantilla retroalimentacion-ugo.xlsx no fue encontrada en el servidor.'], 404);
            }

            $creador = $this->userData($tema->user_id);

            $calificaciones = DB::table('retro_calificaciones')
                ->where('retro_tema_id', $id)
                ->orderBy('created_at', 'asc')
                ->get();

            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            $fechaExpo = $tema->fecha
                ? Carbon::parse($tema->fecha)->format('d/m/Y')
                : '';

            $startRow = 2;
            $total = $calificaciones->count();
            $compromiso = $tema->compromisos ?? '';

            foreach ($calificaciones as $i => $cal) {
                $row = $startRow + $i;
                $asesor = $this->userData($cal->user_id);

                // A: N° | B: Asesor entrenador (creador) | C: Sede regional (cde creador)
                // D: Región (city del cde) | E: Tema | F: Fecha exposición
                // G: Asesor que califica | H: CDE del que califica
                // I-N: ratings r1..r6 | O-Q: detener, continuar, empezar
                // R: compromiso de mejora personal (solo en la primera fila)
                $values = [
                    $i + 1,
                    $creador['nombre'],
                    $creador['cde'],
                    $creador['region'],
                    $tema->tema,
                    $fechaExpo,
                    $asesor['nombre'],
                    $asesor['cde'],
                    $cal->r1_dominio,
                    $cal->r2_valor_mype,
                    $cal->r3_formalizacion,
                    $cal->r4_tiempo,
                    $cal->r5_claridad,
                    $cal->r6_ppt,
                    $cal->detener,
                    $cal->continuar,
                    $cal->empezar,
                    $i === 0 ? $compromiso : null,
                ];

                $col = 'A';
                foreach ($values as $value) {
                    $sheet->setCellValue("{$col}{$row}", $value);
                    $col++;
                }
            }

            // El compromiso es único por tema: si no hay calificaciones se escribe
            // igual en R2 (en una sola celda, sin combinar ni ajustar texto).
            if ($total === 0) {
                $sheet->setCellValue("R{$startRow}", $compromiso);
            }

            $filename = 'retroalimentacion_tema_' . $id . '_' . now()->format('Ymd_His') . '.xlsx';

            return new StreamedResponse(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al exportar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Nombre completo (upper) + sede regional (cde) + región (city del cde).
     * Tolerante a esquemas: si cdes no tiene city_id, región queda vacía.
     */
    private function userData($userId): array
    {
        $user = DB::table('users')->where('id', $userId)->first();
        if (! $user) {
            return ['nombre' => '', 'cde' => '', 'region' => ''];
        }

        $nombre = mb_strtoupper(trim(
            ($user->name ?? '') . ' ' . ($user->lastname ?? '') . ' ' . ($user->middlename ?? '')
        ), 'UTF-8');

        $cdeName = '';
        $regionName = '';
        if (! empty($user->cde_id)) {
            $cde = DB::table('cdes')->where('id', $user->cde_id)->first();
            if ($cde) {
                $cdeName = $cde->name ?? '';
                if (Schema::hasColumn('cdes', 'city_id') && ! empty($cde->city_id)) {
                    $city = DB::table('cities')->where('id', $cde->city_id)->first();
                    $regionName = $city->name ?? '';
                }
            }
        }

        return ['nombre' => $nombre, 'cde' => $cdeName, 'region' => $regionName];
    }
}
