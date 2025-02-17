<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\FromCollection;

class VotationNotariesExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
            'C' => 11,
            'D' => 18,
            'E' => 10,
            'F' => 22,

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
        return 'Valorizaciones';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:X1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('e9e9e9');

        $sheet->getStyle('A1:X1')->getFont()->setBold(true);

        $sheet->getRowDimension(1)->setRowHeight(20);

        $sheet->getStyle('A1:X1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
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
            'DNI ASESOR',
            'NOMBRE DEL ASESOR',
            'CDE/MAC',
            'SUPERVISOR',

            'NRO DOCUMENTO',
            'TIPO DOCUMENTO',
            'NACIONALIDAD (NOMBRE DEL PAIS)',
            'FECHA DE NACIMIENTO',
            'APELLIDOS',
            'NOMBRES',
            'SEXO (F/M)',
            'DISCAPACIDAD (SI/NO)',
            'CELULAR',
            'CORREO',

            'NÚMERO DE RUC',
            'REGION DE LA MYPE',
            'PROVINCIA DE LA MYPE',
            'DISTRITO DE LA MYPE',
            'DIRECCIÓN',
            'SECTOR ECONOMICO',
            'ACTIVIDAD COMERCIAL',
            'REVISADO'
        ];
    }
}
