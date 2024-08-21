<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class ActionPlansExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $result;

    public function __construct(Collection $result)
    {
        $this->result = $result;
    }

    public function title(): string
    {
        return 'Planes de Acción';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 20,
            'C' => 20,
            'D' => 23,
            'E' => 23,
            'F' => 23,
            'G' => 32,
            'H' => 13,
            'I' => 6,
            'J' => 6,

            'K' => 15,
            'L' => 15,
            'M' => 15,
            'N' => 20,
            'O' => 12,
            'P' => 12,
            'Q' => 15,
            'R' => 15,

            'S' => 23,
            'T' => 12
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00b0f0');
        $sheet->getStyle('B1:C1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ff0000');
        $sheet->getStyle('D1:J1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00b0f0');
        $sheet->getStyle('K1:M1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('2f5496');
        $sheet->getStyle('N1:Q1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00b0f0');
        $sheet->getStyle('R1:S1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ff0000');
        $sheet->getStyle('T1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('faad14');


        $sheet->getStyle('A1:T1')->getFont()->getColor()->setARGB('FFFFFF');

        $sheet->getStyle('A1:AT1')->getFont()->setBold(true);


        $sheet->getStyle('D1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('E1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('F1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('N1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('R1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('S1')->getAlignment()->setWrapText(true);

        $sheet->getStyle('A1:T1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:T1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

    }

    public function collection()
    {
        return $this->result;
    }

    public function headings(): array
    {
        return [
            'No',
            'CENTRO TU EMPRESA',
            'ASESOR',
            'REGION DEL EMPRENDEDOR O MYPE',
            'PROVINCIA EMPRENDEDOR O MYPE',
            'DISTRITO EMPRENDEDOR O MYPE',
            'NOMBRE EMPRENDEDOR O MYPE',
            'RUC',
            'Genero',
            'Tiene alguna Discapacidad ? (SI / NO)',
            'COMPONENTE 1',
            'COMPONENTE 2',
            'COMPONENTE 3',
            'NÚMERO DE SESIONES REALIZADAS',
            'DÍA DE INICIO',
            'DÍA FIN',
            'TOTAL DE DÍAS',
            'ACTA DE COMPROMISO',
            'CULMINÓ EL PLAN DE ACCIÓN Y ENVIÓ CORREO'
        ];
    }
}
