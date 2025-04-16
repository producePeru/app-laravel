<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\FromCollection;

class AttendanceListExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
            'B' => 45,
            'C' => 11,
            'D' => 13,
            'E' => 13,
            'F' => 13,

            'G' => 16,
            'H' => 16,
            'I' => 20,
            'J' => 14,
            'K' => 27,
            'L' => 15,
            'Q' => 15,

        ];
    }

    public function title(): string
    {
        return 'Eventos UGO';
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
            'Título',
            'Registrados',
            'Fecha de inicio',
            'Fecha de fin',
            'Modalidad',
            'Ciudad',
            'Provincia',
            'Distrito',
            'Asesor',
            'Registrado por',
            'Descripción',
            'Fecha de registro'
        ];
    }
}