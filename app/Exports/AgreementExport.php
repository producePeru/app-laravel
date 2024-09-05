<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class AgreementExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $result;

    public function __construct(Collection $result)
    {
        $this->result = $result;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 11,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 25,
            'F' => 80,

            'G' => 20,
            'H' => 27,
            'I' => 20,
            'J' => 20,
            'K' => 27,
            'L' => 50,
        ];
    }

    public function title(): string
    {
        return 'Convenios';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:L1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('e9e9e9');

        $sheet->getStyle('A1:L1')->getFont()->setBold(true);

        // Ajustar la altura de la fila de la cabecera (fila 1 en este caso)
        $sheet->getRowDimension(1)->setRowHeight(20); // Ajusta el número 20 a la altura deseada

        // $sheet->getStyle('G1')->getAlignment()->setWrapText(true);
        // $sheet->getStyle('J1')->getAlignment()->setWrapText(true);
        // $sheet->getStyle('A1:L1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:L1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);


        // $styles = [];
        // $highestRow = $sheet->getHighestRow();

        // return $styles;
    }

    public function collection()
    {
        return $this->result;
    }

    public function headings(): array
    {
        return [
            'N°',
            'REGION',
            'PROVINCIA',
            'DISTRITO',
            'CDE - CDE AGENTE - OTRO',
            'ENTIDAD ALIADA',
            'FECHA DE SUSCRIPCIÓN DE CONVENIO',
            'INICIO DE CONVENIO VIGENTE',
            'N° DE AÑOS VIGENTE',
            'FIN DE CONVENIO',
            'ESTADO',
            'OBSERVACIONES'
        ];
    }
}
