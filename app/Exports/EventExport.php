<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\Exportable;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Carbon\Carbon;

Carbon::setLocale('es');

class EventExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithColumnWidths
{

    use Exportable;

    public function __construct(protected Collection $data) {}

    // ─── DATOS ────────────────────────────────────────────────────────────────
    public function collection(): Collection
    {
        $mesNombre = function (?string $date): string {
            if (!$date) return '';
            $meses = [
                1 => 'ENERO',
                2 => 'FEBRERO',
                3 => 'MARZO',
                4 => 'ABRIL',
                5 => 'MAYO',
                6 => 'JUNIO',
                7 => 'JULIO',
                8 => 'AGOSTO',
                9 => 'SEPTIEMBRE',
                10 => 'OCTUBRE',
                11 => 'NOVIEMBRE',
                12 => 'DICIEMBRE',
            ];
            return $meses[(int) date('n', strtotime($date))] ?? '';
        };

        $diffDays = function (?string $inicio, ?string $fin): string {
            if (!$inicio || !$fin) return '';
            $d1 = \DateTime::createFromFormat('Y-m-d', $inicio);
            $d2 = \DateTime::createFromFormat('Y-m-d', $fin);
            if (!$d1 || !$d2) return '';
            return (string) ($d2->diff($d1)->days + 1);
        };

        $formatDate = fn(?string $date): string =>
        $date ? date('d/m/Y', strtotime($date)) : '';

        $i = 1;
        return $this->data->map(function ($item) use (&$i, $mesNombre, $formatDate, $diffDays) {
            $dates  = $item['dates'] ?? [];
            $inicio = $dates[0]      ?? null;
            $fin    = end($dates)    ?: null;

            return [
                $i++,
                $item['unidad']             ?? '',
                $mesNombre($inicio),                          // C  MES EN LETRAS
                $formatDate($inicio),                         // D  FECHA DE INICIO dd/mm/yyyy
                $formatDate($fin),                            // E  FECHA DE FIN   dd/mm/yyyy
                $diffDays($inicio, $fin),                     // F  CANTIDAD DE DIAS DE LA ACTIVIDAD
                $item['tipoActividad']      ?? '',
                $item['titulo']             ?? '',
                mb_strtoupper($item['activityTheme'] ?? '', 'UTF-8'),
                $item['region']             ?? '',
                $item['provincia']          ?? '',
                $item['distrito']           ?? '',
                $item['direccion']          ?? '',

                strtoupper($item['entidad'] ?? null) ?? null,

                $item['entidad_aliada']     ?? '',
                $item['asesor']             ?? '',

                match (true) {
                    in_array(strtolower($item['pasaje'] ?? ''), ['s', 'si', 'sí']) => 'SÍ',
                    in_array(strtolower($item['pasaje'] ?? ''), ['n', 'no'])       => 'NO',
                    ($item['monto'] ?? 0) > 0                                      => 'SÍ',
                    default                                                         => '',
                },

                ($item['monto'] ?? 0) > 0 ? number_format($item['monto'], 2, '.', ',') : '0',

                $item['beneficiarios']      ?? '',

                match (strtoupper($item['modalidad'] ?? '')) {
                    'P', 'PRESENCIAL' => 'PRESENCIAL',
                    'V', 'VIRTUAL'    => 'VIRTUAL',
                    default           => '',
                },

                $item['hours'] ?? '',     // horario


                $item['attendance_list_count'] ?? '',
                $item['capacitador'] ?? '',
                '',
                $item['cancelado']
                    ? 'CANCELADO'
                    : ($item['reprogramado'] ? 'REPROGRAMADO' : 'ACTIVO'),
                $item['created_at']         ?? '',

                ($item['unidad'] === 'UGO' && $item['slug']) ? 'https://programa.soporte-pnte.com/admin/actividades-ugo/eventos-inscritos/' . $item['slug'] : '',

                ($item['unidad'] === 'UGO' && $item['slug']) ? 'https://inscripcion.soporte-pnte.com/actividades-ugo/' . $item['slug'] : '',

                $item['registrador']        ?? '',
            ];
        });
    }

    // ─── CABECERAS ────────────────────────────────────────────────────────────
    public function headings(): array
    {
        return [
            'Nro.',                                                                     // A
            'UNIDAD',                                                                   // B
            "MES\n (autollenado)",                                                      // C
            'FECHA DE INICIO',                                                          // D
            'FECHA DE FIN',                                                             // E
            "CANTIDAD DE DIAS DE LA ACTIVIDAD\n (autollenado)",                         // F
            'TIPO DE ACTIVIDAD',                                                        // G
            'NOMBRE DE ACTIVIDAD',                                                      // H
            'TEMA',                                                                     // I
            'REGION DE LA ACTIVIDAD',                                                   // J
            'PROVINCIA DE LA ACTIVIDAD',                                                // K
            'DISTRITO DE LA ACTIVIDAD',                                                 // L
            'LUGAR DE LA ACTIVIDAD',                                                    // M
            'NOMBRE DE ENTIDAD ORGANIZADORA',                                           // N
            'NOMBRE DE ENTIDAD O INSTITUCIÓN ALIADA / PARTICIPANTE',                    // O
            "REPRESENTANTE DE PRODUCE QUE PARTICIPA (APELLIDOS Y NOMBRES)\nEJEMPLO: DIAZ ROMERO, PIEDAD LUISA", // P
            "¿REQUERIRA PASAJES?\n(SÍ / NO)",                                           // Q
            'COLOCAR SOLO EL MONTO DE GASTOS EN PASAJES EN SOLES IDA + VUELTA (BUS Y/O AVION)', // R
            'MYPE Y/O EMPRENDEDORES BENEFICIADOS ESPERADOS',                            // S
            "MODALIDAD \n(VIRTUAL / PRESENCIAL)",                                       // T
            'HORARIO',                                                          // U
            'TOTAL DE INSCRITOS',                                                       // V
            'CAPACITADOR',                                                     // W
            'TOTAL FORMALIZACIONES (UGO)',                                               // X
            'ESTADO',                                                                   // Y
            'FECHA CREADA LA ACTIVIDAD',                                                // Z
            'LINK DE INSCRITOS A LA ACTIVIDAD',                                         // AA
            'LINK DE FORMULARIO DE REGISTRO',                                           // AB
            'REGISTRADO POR',                                                           // AC
        ];
    }

    // ─── ANCHOS DE COLUMNA ────────────────────────────────────────────────────
    public function columnWidths(): array
    {
        return [
            'A'  => 6,
            'B'  => 12,
            'C'  => 12,
            'D'  => 14,
            'E'  => 14,
            'F'  => 14,
            'G'  => 20,
            'H'  => 35,
            'I'  => 25,
            'J'  => 22,
            'K'  => 22,
            'L'  => 22,
            'M'  => 30,
            'N'  => 28,
            'O'  => 35,
            'P'  => 35,
            'Q'  => 14,
            'R'  => 30,
            'S'  => 20,
            'T'  => 16,
            'U'  => 16,
            'V'  => 14,
            'W'  => 16,
            'X'  => 18,
            'Y'  => 14,
            'Z'  => 20,
            'AA' => 30,
            'AB' => 30,
            'AC' => 16,
        ];
    }

    // ─── ESTILOS ──────────────────────────────────────────────────────────────
    public function styles(Worksheet $sheet): void
    {
        $lastRow = $sheet->getHighestRow();

        // ── Altura fila de cabecera
        $sheet->getRowDimension(1)->setRowHeight(65.25);

        // ── Colores de cabecera por grupo de columnas
        $headerGroups = [
            'FF0C343D' => ['A', 'B', 'D', 'E', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T'],
            'FFFF0000' => ['C', 'F'],
            'FF38761D' => ['U', 'V', 'W', 'X'],
            'FFED7D31' => ['Y'],
            'FFFFC000' => ['Z'],
            'FF00B0F0' => ['AA', 'AB'],
        ];

        foreach ($headerGroups as $color => $cols) {
            foreach ($cols as $col) {
                $cell = $sheet->getCell($col . '1');

                $cell->getStyle()->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($color);

                $cell->getStyle()->getFont()
                    ->setName('Arial Narrow')
                    ->setSize(11)
                    ->setBold(false)
                    ->getColor()->setARGB('FFFFFFFF');

                $cell->getStyle()->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
            }
        }

        $centeredCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'J', 'K', 'L', 'Q', 'S', 'R', 'V', 'U', 'T', 'Y', 'Z'];

        foreach ($centeredCols as $col) {
            if ($lastRow >= 2) {
                $sheet->getStyle($col . '2:' . $col . $lastRow)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        // ── Columna AC (sin color de fondo, Calibri, texto negro)
        $sheet->getCell('AC1')->getStyle()->getFont()
            ->setName('Calibri')
            ->setSize(11)
            ->setBold(true)
            ->getColor()->setARGB('FF000000');
        $sheet->getCell('AC1')->getStyle()->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        // ── Filas de datos: Arial Narrow 10, centrado vertical, wrap
        if ($lastRow >= 2) {
            $dataRange = 'A2:AC' . $lastRow;

            $sheet->getStyle($dataRange)->getFont()
                ->setName('Arial Narrow')
                ->setSize(10);

            $sheet->getStyle($dataRange)->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);

            // Bordes finos en toda la tabla
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color'       => ['argb' => 'FFD3D3D3'],
                    ],
                ],
            ];
            $sheet->getStyle('A1:AC' . $lastRow)->applyFromArray($borderStyle);

            // Alternar color de fila para legibilidad
            for ($row = 2; $row <= $lastRow; $row++) {
                if ($row % 2 === 0) {
                    $sheet->getStyle('A' . $row . ':AC' . $row)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFF2F2F2');
                }
            }
        }

        // ── Freeze primera fila
        $sheet->freezePane('A2');

        // ── Nombre de la hoja
        $sheet->setTitle('Eventos');
    }
}
