<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;


class AttendanceExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $result;

    public function __construct(Collection $result)
    {
        $this->result = $result;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 17,
            'C' => 15,
            'D' => 15,
            'E' => 10,
            'F' => 10,

            'G' => 16,
            'H' => 16,
            'I' => 10,
            'J' => 7,
            'K' => 13,
            'L' => 15,
            'M' => 28
        ];
    }

    public function title(): string
    {
        return 'Registrados';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:M1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('e9e9e9');

        $sheet->getStyle('A1:M1')->getFont()->setBold(true);

        $sheet->getRowDimension(1)->setRowHeight(20);

        $sheet->getStyle('A1:M1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

    }

    public function collection()
    {
        return $this->result;
    }

    public function headings(): array
    {
        return [
            '#',
            'FECHA DE REGISTRO',
            'APELLIDOS',
            'NOMBRES',
            'TIPO DE DOCUMENTO',
            'NÚMERO DE DOCUMENTO',
            'EMAIL',
            'CELULAR',
            'SEXO (F/M)',
            'DISCAPACITADO',
            'RUC',
            'SECTOR ECONÓMICO',
            'ACTIVIDAD COMERCIAL'
        ];
    }
}
