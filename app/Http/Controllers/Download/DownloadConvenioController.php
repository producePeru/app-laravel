<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\Convenio;
use App\Services\ConvenioCortes;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadConvenioController extends Controller
{
    /**
     * Reporte de seguimiento y evaluación del convenio en el formato de la
     * plantilla template-convenio-compromisos.xlsx.
     *
     * - B3: nombre del convenio.
     * - Compromisos dinámicos por tipo (produce / contraparte / partes):
     *   se agregan las filas necesarias copiando el estilo de la plantilla.
     * - Columna G: un link por evidencia (PhpSpreadsheet solo admite un
     *   hyperlink por celda: se enlaza el primer archivo y los demás van
     *   como texto en líneas separadas).
     */
    public function exportReporte(Request $request, $id)
    {
        $convenio = Convenio::with([
            'contactos',
            'compromisos.avances.medios',
            'compromisos.medios',
            'adendas',
            'gestion',
        ])->find($id);

        if (! $convenio) {
            return response()->json([
                'status' => 404,
                'message' => 'Convenio no encontrado.',
            ], 404);
        }

        $templatePath = storage_path('app/plantillas/template-convenio-compromisos.xlsx');

        if (! file_exists($templatePath)) {
            return response()->json([
                'status' => 404,
                'message' => 'Plantilla no encontrada.',
            ], 404);
        }

        // La plantilla trae un gráfico: hay que incluirlo al leer y al
        // escribir, si no se pierde en el archivo descargado.
        $reader = IOFactory::createReader('Xlsx');
        $reader->setIncludeCharts(true);
        $spreadsheet = $reader->load($templatePath);
        $sheet = $spreadsheet->getSheetByName('Convenio INDECOPI') ?? $spreadsheet->getActiveSheet();

        $fmtFecha = fn ($v) => $v ? Carbon::parse($v)->format('d/m/Y') : '';

        // ── Cabecera ──────────────────────────────────────────────
        $sheet->setCellValue('B3', $convenio->nombre_convenio);
        $sheet->setCellValue('B4', $fmtFecha($convenio->fecha_emision));

        $culminacion = $fmtFecha($convenio->vencimiento);
        $maxAdenda = collect($convenio->adendas)->map(fn ($a) => $a->hasta)->filter()->max();
        if ($maxAdenda) {
            $culminacion .= "\nAmpliación de adenda hasta ".Carbon::parse($maxAdenda)->format('d/m/Y').'.';
        }
        $sheet->setCellValue('F4', $culminacion);

        if ($convenio->plazo_renovacion_anios) {
            $sheet->setCellValue(
                'B5',
                $convenio->plazo_renovacion_anios.' AÑOS contados a partir de la fecha de la última firma'
            );
        }

        $sheet->setCellValue('B6', 'UNIDAD DE GESTIÓN DE SERVICIOS EMPRESARIALES - UGSE');

        $sheet->setCellValue('B7', $this->textoContactos($convenio, ['repProduce', 'coordProduce']));
        $sheet->setCellValue('B8', $this->textoContactos($convenio, ['repContraparte', 'repContraparte2', 'coordContraparte', 'coordContraparte2']));
        $sheet->setCellValue('B9', $convenio->estado);

        // Plan de trabajo: SI/NO con X en C10 (SI) o E10 (NO).
        $tienePlan = $convenio->gestion && ($convenio->gestion->plan_validado || $convenio->gestion->plan_actividades);
        $sheet->setCellValue('C10', $tienePlan ? 'X' : '');
        $sheet->setCellValue('E10', $tienePlan ? '' : 'X');

        // ── Compromisos dinámicos (datos del corte actual) ──────────
        // El seguimiento es por cortes semestrales: el reporte muestra
        // lo avanzado en el corte vigente (o el último si ya finalizó).
        $resumenCortes = ConvenioCortes::resumen($convenio);
        $corteReporte = $resumenCortes['corte_actual']
            ?? collect($resumenCortes['cortes'])->max('numero')
            ?? 1;

        $institucion = mb_strtoupper(trim((string) $convenio->institucion), 'UTF-8') ?: 'CONTRAPARTE';
        $secciones = [
            ['tipo' => 'produce', 'header' => 11, 'primera' => 12, 'titulo' => '9. COMPROMISOS PNTE'],
            ['tipo' => 'contraparte', 'header' => 13, 'primera' => 14, 'titulo' => "10. COMPROMISOS {$institucion}"],
            ['tipo' => 'partes', 'header' => 15, 'primera' => 16, 'titulo' => '11. COMPROMISOS DE LAS PARTES'],
        ];

        $porTipo = collect($convenio->compromisos)->groupBy('tipo');
        $desplazamiento = 0;

        foreach ($secciones as $sec) {
            $headerRow = $sec['header'] + $desplazamiento;
            $primeraRow = $sec['primera'] + $desplazamiento;
            $items = ($porTipo[$sec['tipo']] ?? collect())->sortBy('orden')->values();

            $sheet->setCellValue("A{$headerRow}", $sec['titulo']);

            // Garantiza al menos 1 fila aunque no haya compromisos.
            $total = max($items->count(), 1);
            $extras = $total - 1;
            if ($extras > 0) {
                $sheet->insertNewRowBefore($primeraRow + 1, $extras);
                for ($r = $primeraRow; $r < $primeraRow + $total; $r++) {
                    $sheet->mergeCells("A{$r}:D{$r}");
                    $sheet->duplicateStyle($sheet->getStyle("A{$primeraRow}:G{$primeraRow}"), "A{$r}:G{$r}");
                }
                $desplazamiento += $extras;
            } else {
                $sheet->mergeCells("A{$primeraRow}:D{$primeraRow}");
            }

            if ($items->isEmpty()) {
                $this->pintarFilaCompromiso($sheet, $primeraRow, '', '', '', collect());
                continue;
            }

            foreach ($items as $i => $item) {
                $r = $primeraRow + $i;
                $letra = chr(97 + ($item->orden > 0 ? $item->orden - 1 : $i) % 26).') ';
                $avance = $item->avances->firstWhere('corte', $corteReporte);
                $realizado = (string) ($avance->realizado ?? ($corteReporte == 1 ? $item->realizado : '') ?? '');
                $actividad = (string) ($avance->actividad ?? ($corteReporte == 1 ? $item->actividad : '') ?? '');
                $medios = $avance ? collect($avance->medios) : collect();
                if ($corteReporte == 1) {
                    $medios = $medios->concat(
                        collect($item->medios)->filter(fn ($m) => is_null($m->avance_id))->values()
                    )->values();
                }
                $this->pintarFilaCompromiso(
                    $sheet,
                    $r,
                    $letra.$item->compromiso,
                    $realizado,
                    $actividad,
                    $medios
                );
            }
        }

        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        $writer->setPreCalculateFormulas(false);

        $nombre = 'reporte-convenio-'.$convenio->id.'-'.now()->format('Ymd_His').'.xlsx';

        return new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$nombre.'"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    private function textoContactos(Convenio $convenio, array $tipos): string
    {
        $lineas = [];
        foreach ($tipos as $tipo) {
            foreach ($convenio->contactos->where('tipo', $tipo) as $c) {
                $dato = trim(implode(' ', array_filter([$c->nombre, $c->celular])));
                if ($c->correo) {
                    $dato .= ($dato !== '' ? ' - ' : '').$c->correo;
                }
                if ($dato !== '') {
                    $lineas[] = $dato;
                }
            }
        }

        return $lineas !== [] ? implode("\n", $lineas) : 'No registrado';
    }

    private function pintarFilaCompromiso($sheet, int $r, string $texto, string $realizado, string $actividad, $medios): void
    {
        $sheet->setCellValue("A{$r}", $texto);
        $sheet->setCellValue("E{$r}", $realizado);
        $sheet->setCellValue("F{$r}", $actividad);
        // RichText vacío corrompe la tabla de strings: solo usarlo con archivos.
        $sheet->setCellValue("G{$r}", $medios->isNotEmpty() ? $this->richTextMedios($medios) : '');

        // Enlaza el primer archivo (un hyperlink por celda).
        $primero = $medios->first();
        if ($primero) {
            $sheet->getCell("G{$r}")->getHyperlink()->setUrl(asset($primero->ruta));
        }

        foreach (["A{$r}", "E{$r}", "F{$r}", "G{$r}"] as $coord) {
            $sheet->getStyle($coord)->getAlignment()->setWrapText(true)->setVertical('top');
        }

        // Alto estimado por contenido (A ~85 car./línea, F ~50, G una línea por archivo).
        $lineasA = max(1, (int) ceil(mb_strlen($texto) / 85));
        $lineasF = max(1, (int) ceil(mb_strlen($actividad) / 50));
        $lineasG = max(1, $medios->count());
        $sheet->getRowDimension($r)->setRowHeight(max(30, max($lineasA, $lineasF, $lineasG) * 15));
    }

    private function richTextMedios($medios): RichText
    {
        $rt = new RichText();
        foreach ($medios->values() as $i => $m) {
            $run = $rt->createTextRun(($i > 0 ? "\n" : '').($i + 1).') '.$m->nombre_original);
            $run->getFont()->setColor(new Color(Color::COLOR_BLUE))->setUnderline(true)->setSize(10);
        }

        return $rt;
    }
}
