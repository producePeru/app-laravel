<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;


Carbon::setLocale('es');

class NotaryExport implements FromCollection, WithTitle, WithStyles, WithColumnWidths, WithEvents
{

    protected $notary;

    public function __construct($notary)
    {
        $this->notary = $notary;
    }
    public function collection()
    {
        return collect($this->notary);
    }

    public function title(): string
    {
        return 'NOTARIAS';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 27,
            'C' => 12,
            'D' => 12,
            'E' => 12,
            'F' => 27,
            'G' => 50,

            'H' => 16,
            'I' => 16,
            'J' => 16,
            'K' => 16,

            'L' => 16,
            'M' => 20,
            'N' => 30,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // // Aplicar fondo azul a la primera fila
        // $sheet->getStyle('A1:N1')->getFill()
        //     ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        //     ->getStartColor()->setARGB('002060');

        // // Poner en negrita y cambiar color de fuente a blanco en la primera fila
        // $sheet->getStyle('A1:N1')->getFont()->setBold(true)
        //     ->getColor()->setARGB('FFFFFF');

        // // Ajustar texto en las columnas que necesiten mostrar varias líneas
        // $columnasAjustadas = ['F', 'G', 'I', 'J', 'K', 'L', 'M', 'N']; // Puedes ajustar más columnas si es necesario
        // foreach ($columnasAjustadas as $columna) {
        //     $sheet->getStyle("{$columna}1:{$columna}1000") // Ajusta hasta la fila 1000 (o más si lo necesitas)
        //         ->getAlignment()->setWrapText(true);
        // }

        // // Alineación vertical al centro
        // $sheet->getStyle('A1:N1')->getAlignment()
        //     ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
    }


    // public function headings(): array
    // {
    //     return [
    //         [
    //             'No',
    //             'NOTARÍA',
    //             'REGIÓN',
    //             'PROVINCIA',
    //             'DISTRITO',
    //             'DIRECCIÓN',
    //             'GASTOS NOTARIALES',
    //             'TARIFA SOCIAL', '', '', '',
    //             'BIOMÉTRICO / HUELLA DIGITAL',
    //             'SOCIO O INTERVINIENTE ADICIONAL',
    //             'CONTÁCTO'
    //         ],

    //     ];
    // }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $startRow = 4; // Inicia desde la fila 4
                $rowIndex = $startRow;

                foreach ($this->notary as $row) {
                    $colIndex = 0;
                    foreach ($row as $value) {
                        $cell = chr(65 + $colIndex) . $rowIndex; // A4, B4, etc.
                        $event->sheet->setCellValue($cell, $value);
                        $colIndex++;
                    }
                    $rowIndex++;
                }
            },
        ];
    }
}
