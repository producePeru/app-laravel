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

class EventExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $events;

    public function __construct($events)
    {
        $this->events = $events;
    }
    public function collection()
    {
        return collect($this->events);
    }

    public function title(): string
    {
        return 'EVENTOS';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 10,
            'C' => 22,
            'D' => 20,
            'E' => 17,
            'F' => 34,
            'G' => 13,
            'H' => 12,
            'I' => 28,
            'J' => 40,
            'K' => 22,
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
        $columnasAjustadas = ['C', 'D', 'F', 'G', 'K']; // Puedes ajustar más columnas si es necesario
        foreach ($columnasAjustadas as $columna) {
            $sheet->getStyle("{$columna}1:{$columna}1000") // Ajusta hasta la fila 1000 (o más si lo necesitas)
                ->getAlignment()->setWrapText(true);
        }

        // Alineación vertical al centro
        $sheet->getStyle('A1:K1')->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
    }

    public function headings(): array
    {
        return [
            'No',
            'OFICINA',
            'TIPO DE ACTIVIDAD',
            'TÍTULO',
            'REGIÓN',
            'LUGAR',
            'FECHA',
            'HORARIO',
            'DESCRIPCIÓN',
            'RESULTADOS',
            'NOMBRE RESPONSABLE',
        ];
    }
}
