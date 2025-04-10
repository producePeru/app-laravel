<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

Carbon::setLocale('es');

class NotaryExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
            'G' => 30,
            'H' => 30,
            'I' => 15,
            'J' => 22,
            'K' => 46
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Aplicar fondo azul a la primera fila
        $sheet->getStyle('A1:K1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('002060');

        // Poner en negrita y cambiar color de fuente a blanco en la primera fila
        $sheet->getStyle('A1:K1')->getFont()->setBold(true)
            ->getColor()->setARGB('FFFFFF');

        // Ajustar texto en las columnas que necesiten mostrar varias líneas
        $columnasAjustadas = ['F', 'G', 'I', 'J', 'K']; // Puedes ajustar más columnas si es necesario
        foreach ($columnasAjustadas as $columna) {
            $sheet->getStyle("{$columna}1:{$columna}1000") // Ajusta hasta la fila 1000 (o más si lo necesitas)
                ->getAlignment()->setWrapText(true);
        }

        // Alineación vertical al centro
        $sheet->getStyle('A1:K1000')->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
    }


    public function headings(): array
    {
        return [
            'No',
            'NOTARÍA',
            'REGIÓN',
            'PROVINCIA',
            'DISTRITO',
            'DIRECCIÓN',
            'GASTOS NOTARIALES',
            'CONDICIONES',
            'BIOMÉTRICO / HUELLA DIGITAL',
            'SOCIO O INTERVINIENTE ADICIONAL',
            'CONTÁCTO'
        ];
    }
}
