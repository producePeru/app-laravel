<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ResumenUsuariosExport implements FromCollection, WithHeadings, WithStyles
{
    protected $resultados;

    public function __construct(array $resultados)
    {
        $this->resultados = $resultados;
    }

    public function collection()
    {
        return collect($this->resultados);
    }

    public function headings(): array
    {
        return [
            'Índice',
            'Nombre',
            'Asignadas',
            'Completadas',
            'Pendientes',
            'Productividad',
            'Supervisor',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilos para los encabezados
        $sheet->getStyle('A1:G1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['argb' => 'FF808080'], // Gris
            ],
            'font' => [
                'color' => ['argb' => 'FFFFFFFF'], // Blanco
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Definición de colores pastel para cada supervisor
        $supervisorColors = [
            'CYNTHIA MARISOL MOLERO FLORES' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFFFC0CB'], // Rosa pastel
                ],
            ],
            'ERIKA LISBETH CHOY ORTIZ' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFADD8E6'], // Azul claro
                ],
            ],
            'HANNAH MARIA QWISTGAARD PANICCIA' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFFFEFD5'], // Melón
                ],
            ],
            'KATHIA IRIS PINEDO SAONA' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFFFD700'], // Amarillo
                ],
            ],
        ];

        $rowCount = count($this->resultados);
        for ($row = 2; $row <= $rowCount + 1; $row++) { // Comenzamos desde la fila 2 porque la 1 es la cabecera
            $supervisor = $this->resultados[$row - 2]['supervisor']; // Obtener el supervisor de la fila

            if (isset($supervisorColors[$supervisor])) {
                $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($supervisorColors[$supervisor]);
            }

            // Aplicar bordes a las filas
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'], // Color negro
                    ],
                ],
            ]);
        }

        // Ajustar el tamaño de las columnas
        $sheet->getColumnDimension('A')->setWidth(10);  // Tamaño 10
        $sheet->getColumnDimension('B')->setWidth(40);  // Tamaño 40
        $sheet->getColumnDimension('C')->setWidth(16);  // Tamaño 16
        $sheet->getColumnDimension('D')->setWidth(16);  // Tamaño 16
        $sheet->getColumnDimension('E')->setWidth(16);  // Tamaño 16
        $sheet->getColumnDimension('F')->setWidth(16);  // Tamaño 16
        $sheet->getColumnDimension('G')->setWidth(40);  // Tamaño 40

        // Centrar contenido de columnas C, D, E, F
        foreach (['C', 'D', 'E', 'F'] as $columnID) {
            $sheet->getStyle("{$columnID}2:{$columnID}" . ($rowCount + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }
}
