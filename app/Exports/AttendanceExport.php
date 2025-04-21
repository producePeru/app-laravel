<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Carbon\Carbon;

Carbon::setLocale('es');

class AttendanceExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $items;

    public function __construct($items)
    {
        $this->item = $items;
    }
    public function collection()
    {
        return collect($this->item);
    }

    public function title(): string
    {
        return 'EVENTOS UGO';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 26,
            'C' => 13,
            'D' => 17,
            'E' => 17,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 24,
            'K' => 24,
            'L' => 60,
            'M' => 17,
            'N' => 25,
            'O' => 25,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Aplicar fondo azul a la primera fila
        $sheet->getStyle('A1:O1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('002060');

        // Poner en negrita y cambiar color de fuente a blanco en la primera fila
        $sheet->getStyle('A1:O1')->getFont()->setBold(true)
            ->getColor()->setARGB('FFFFFF');

        // Ajustar texto en las columnas que necesiten mostrar varias líneas
        $columnasAjustadas = ['B', 'G', 'I', 'J', 'K', 'L', 'M', 'N', 'O']; // Puedes ajustar más columnas si es necesario
        foreach ($columnasAjustadas as $columna) {
            $sheet->getStyle("{$columna}1:{$columna}1000") // Ajusta hasta la fila 1000 (o más si lo necesitas)
                ->getAlignment()->setWrapText(true);
        }

        // Alineación vertical al centro
        $sheet->getStyle('A1:O1')->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
    }

    public function headings(): array
    {
        return [
            'Nº',
            'TÍTULO',
            'REGISTRADOS',
            'FECHA DE INICIO',
            'FECHA DE FIN',
            // 'MODALIDAD',
            'CIUDAD',
            'PROVINCIA',
            'DISTRITO',
            'LUGAR',
            'ASESOR',
            'REGISTRADO POR',
            'DESCRIPCIÓN',
            'FECHA DE REGISTRO',

            'LINK DE LISTA DE PARTICIPANTES',
            'LINK DE REGISTRO DE PARTICIPANTES',
        ];
    }
}