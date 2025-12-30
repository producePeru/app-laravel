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

class CdeExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $cde;

    public function __construct($cde)
    {
        $this->cde = $cde;
    }
    public function collection()
    {
        return collect($this->cde);
    }

    public function title(): string
    {
        return 'NOTARIAS';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 30,
            'C' => 17,
            'D' => 17,
            'E' => 17,
            'F' => 34,
            'G' => 28
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Aplicar fondo azul a la primera fila
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('002060');

        // Poner en negrita y cambiar color de fuente a blanco en la primera fila
        $sheet->getStyle('A1:G1')->getFont()->setBold(true)
            ->getColor()->setARGB('FFFFFF');

        // Ajustar texto en las columnas que necesiten mostrar varias líneas
        $columnasAjustadas = ['F', 'G', 'I', 'J', 'K']; // Puedes ajustar más columnas si es necesario
        foreach ($columnasAjustadas as $columna) {
            $sheet->getStyle("{$columna}1:{$columna}1000") // Ajusta hasta la fila 1000 (o más si lo necesitas)
                ->getAlignment()->setWrapText(true);
        }

        // Alineación vertical al centro
        $sheet->getStyle('A1:G1')->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
    }

    public function headings(): array
    {
        return [
            'No',
            'NOMBRE',
            'CIUDAD',
            'PROVINCIA',
            'DISTRITO',
            'DIRECCIÓN',
            'TIPO DE CDE',
        ];
    }
}
