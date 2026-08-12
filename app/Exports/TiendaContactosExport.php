<?php

namespace App\Exports;

use App\Models\TiendaContacto;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class TiendaContactosExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    public function collection()
    {
        return TiendaContacto::with('tienda')->get();
    }

    public function headings(): array
    {
        return [
            'Nombre contacto',
            'Celular contacto',
            'Correo contacto',
            'Productos',

            'Nombre empresa',

            'RUC empresa',
            'Celular empresa',
            'Correo empresa',
        ];
    }

    public function map($contacto): array
    {
        return [
            $contacto->nombre,
            $contacto->celular,
            $contacto->correo,
            $contacto->productos,

            $contacto->tienda?->nombre,

            $contacto->tienda?->ruc,
            $contacto->tienda?->celular,
            $contacto->tienda?->correo,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                // Última fila
                $lastRow = $sheet->getHighestRow();

                // Última columna
                $lastColumn = $sheet->getHighestColumn();

                // ==========================================
                // CABECERA
                // ==========================================

                $sheet->getStyle('A1:H1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => [
                            'rgb' => 'FFFFFF',
                        ],
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => [
                            'rgb' => 'FD2B73',
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                    ],
                ]);

                // Altura de cabecera
                $sheet->getRowDimension(1)->setRowHeight(30);

                // ==========================================
                // TODO EL CONTENIDO
                // ==========================================

                if ($lastRow >= 2) {

                    $sheet->getStyle("A2:{$lastColumn}{$lastRow}")
                        ->getAlignment()
                        ->setVertical('center');

                    // Bordes
                    $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
                        ->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => 'thin',
                                    'color' => [
                                        'rgb' => 'D9D9D9',
                                    ],
                                ],
                            ],
                        ]);

                    // Filas alternadas
                    for ($row = 2; $row <= $lastRow; $row++) {

                        if ($row % 2 === 0) {
                            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                                ->applyFromArray([
                                    'fill' => [
                                        'fillType' => 'solid',
                                        'startColor' => [
                                            'rgb' => 'FFF3F7',
                                        ],
                                    ],
                                ]);
                        }
                    }
                }

                // ==========================================
                // FILTROS
                // ==========================================

                $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");

                // ==========================================
                // CONGELAR CABECERA
                // ==========================================

                $sheet->freezePane('A2');

                // ==========================================
                // ALINEACIONES
                // ==========================================

                $sheet->getStyle("E2:E{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal('center');

                $sheet->getStyle("H2:H{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal('center');

                // ==========================================
                // AJUSTAR DESCRIPCIÓN Y PRODUCTOS
                // ==========================================

                $sheet->getStyle("D2:D{$lastRow}")
                    ->getAlignment()
                    ->setWrapText(true);

                $sheet->getStyle("G2:G{$lastRow}")
                    ->getAlignment()
                    ->setWrapText(true);
            },
        ];
    }
}
