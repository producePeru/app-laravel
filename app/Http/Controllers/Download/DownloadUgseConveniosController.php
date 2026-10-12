<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\Convenio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadUgseConveniosController extends Controller
{
    /**
     * Descarga el Excel de convenios UGSE usando la plantilla
     * storage/app/plantillas/convenios-ugse-template.xlsx (datos desde A5).
     * POST download/export-convenios-ugse
     * Body opcional: { search, estado } (mismos filtros del listado)
     */
    public function exportExcel(Request $request)
    {
        try {
            $search = trim((string) $request->input('search', ''));
            $estado = trim((string) $request->input('estado', ''));

            $convenios = Convenio::with([
                'contactos',
                'compromisos' => fn ($q) => $q->orderBy('tipo')->orderBy('orden'),
                'adendas' => fn ($q) => $q->orderBy('numero'),
                'gestion.responsableProduce',
            ])
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($w) use ($search) {
                        $w->where('institucion', 'LIKE', "%{$search}%")
                            ->orWhere('nombre_convenio', 'LIKE', "%{$search}%")
                            ->orWhere('objeto', 'LIKE', "%{$search}%");
                    });
                })
                ->when($estado !== '', fn ($q) => $q->where('estado', $estado))
                ->orderBy('id')
                ->get();

            $hoy = Carbon::now()->startOfDay();

            $rows = $convenios->map(function ($c, $index) use ($hoy) {
                $contactos = $c->contactos ?? collect();
                $gestion = $c->gestion;

                $ultimaAdenda = ($c->adendas ?? collect())->sortByDesc('numero')->first();
                $inicio = $c->inicio_vigencia ? Carbon::parse($c->inicio_vigencia)->startOfDay() : null;

                $responsableProduce = $gestion?->responsableProduce;
                $nombreResponsableProduce = $responsableProduce
                    ? trim("{$responsableProduce->lastname} {$responsableProduce->middlename} {$responsableProduce->name}")
                    : '-';

                return [
                    $index + 1,                                                          // A  Nro.
                    $c->institucion ?? '-',                                              // B  Institución
                    $this->textoContactos($contactos, ['repProduce']),                  // C  Rep. PRODUCE
                    $this->textoContactos($contactos, ['repContraparte', 'repContraparte2']), // D Rep. contraparte
                    $this->textoContactos($contactos, ['coordProduce']),                // E  Coord. PRODUCE
                    $this->textoContactos($contactos, ['coordContraparte', 'coordContraparte2']), // F Coord. contraparte
                    $c->nombre_convenio ?? '-',                                         // G  Nombre del convenio
                    $c->objeto ?? '-',                                                  // H  Objeto
                    $this->textoCompromisos($c->compromisos ?? collect(), 'produce'),   // I  Compromisos PRODUCE
                    $this->textoCompromisos($c->compromisos ?? collect(), 'contraparte'), // J Compromisos contraparte
                    $this->textoCompromisos($c->compromisos ?? collect(), 'partes'),    // K  Compromisos partes
                    $this->fecha($c->fecha_emision),                                    // L  Fecha de emisión
                    $this->fecha($c->inicio_vigencia),                                  // M  Inicio de vigencia
                    $c->tipo_renovacion ? strtoupper($c->tipo_renovacion) : '-',        // N  Tipo de renovación
                    $c->plazo_renovacion_anios ? $c->plazo_renovacion_anios.' año(s)' : '-', // O Plazo renovación
                    $ultimaAdenda                                                        // P  Última adenda
                        ? "Adenda {$ultimaAdenda->numero}: {$this->fecha($ultimaAdenda->desde)} → {$this->fecha($ultimaAdenda->hasta)}"
                        : '-',
                    $this->fecha($c->vencimiento),                                      // Q  Vencimiento
                    $c->estado ?? '-',                                                  // R  Status
                    $inicio ? (int) $inicio->diffInDays($hoy) : '-',                    // S  Días transcurridos a hoy
                    $gestion?->meta ?? '-',                                             // T  Meta
                    $gestion?->financiamiento ?? '-',                                   // U  Financiamiento
                    $gestion?->plan_actividades ?? '-',                                 // V  Plan de actividades
                    is_null($gestion?->plan_validado)                                    // W  Plan validado
                        ? '-'
                        : ($gestion->plan_validado ? 'SI' : 'NO'),
                    $gestion?->plazo_reportes ?? '-',                                   // X  Plazo reportes/informes
                    $gestion?->para_resolucion ?? '-',                                  // Y  Para resolución
                    $nombreResponsableProduce,                                           // Z  Resp. PRODUCE seguimiento
                    $this->textoResponsableContraparte($gestion),                        // AA Resp. contraparte
                    $gestion?->avances ?? '-',                                          // AB Avances 2026
                    $gestion?->observaciones ?? '-',                                    // AC Observaciones generales
                ];
            });

            $templatePath = storage_path('app/plantillas/convenios-ugse-template.xlsx');
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            $startRow = 5;

            foreach ($rows as $i => $row) {
                $col = 'A';
                foreach ($row as $value) {
                    $sheet->setCellValue("{$col}".($startRow + $i), $value);
                    $col++;
                }
            }

            // Ajuste de lectura para celdas con saltos de línea
            $lastRow = $startRow + max(count($rows) - 1, 0);
            $sheet->getStyle("A{$startRow}:AC{$lastRow}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("A{$startRow}:AC{$lastRow}")->getAlignment()->setVertical('top');

            return new StreamedResponse(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="convenios-ugse.xlsx"',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al exportar: '.$e->getMessage(),
            ], 500);
        }
    }

    private function textoContactos($contactos, array $tipos): string
    {
        $partes = $contactos->whereIn('tipo', $tipos)->map(function ($c) {
            $datos = array_filter([
                $c->nombre,
                $c->correo,
                $c->celular,
            ]);

            return $datos ? implode(' / ', $datos) : null;
        })->filter()->values();

        return $partes->isNotEmpty() ? $partes->implode("\n") : '-';
    }

    private function textoCompromisos($compromisos, string $tipo): string
    {
        $partes = $compromisos->where('tipo', $tipo)->values()->map(
            fn ($c, $i) => ($i + 1).'. '.$c->compromiso
        );

        return $partes->isNotEmpty() ? $partes->implode("\n") : '-';
    }

    private function textoResponsableContraparte($gestion): string
    {
        if (! $gestion) {
            return '-';
        }

        $datos = array_filter([
            $gestion->responsable_contraparte,
            $gestion->responsable_contraparte_cargo,
            $gestion->responsable_contraparte_correo,
            $gestion->responsable_contraparte_celular,
        ]);

        return $datos ? implode(' / ', $datos) : '-';
    }

    private function fecha($value): string
    {
        if (! $value) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Exception $e) {
            return (string) $value;
        }
    }
}
