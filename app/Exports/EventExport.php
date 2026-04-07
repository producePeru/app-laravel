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
            'B' => 12,
            'C' => 25,
            'D' => 30,
            'E' => 20,
            'F' => 20,
            'G' => 20,
            'H' => 35,
            'I' => 18,
            'J' => 18,
            'K' => 40,
            'L' => 40,
            'M' => 25,
            'N' => 15,
            'O' => 25,
            'P' => 25,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // 🎨 HEADER (igual que ya tenías)
        $sheet->getStyle('A1:' . $highestColumn . '1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('002060');

        $sheet->getStyle('A1:' . $highestColumn . '1')->getFont()
            ->setBold(true)
            ->getColor()->setARGB('FFFFFF');

        // 📏 WRAP TEXT EN TODAS LAS CELDAS
        $sheet->getStyle('A1:' . $highestColumn . $highestRow)
            ->getAlignment()
            ->setWrapText(true);

        // 📐 ALINEACIÓN
        $sheet->getStyle('A1:' . $highestColumn . $highestRow)
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // 🔥 AUTO HEIGHT (CLAVE)
        for ($row = 1; $row <= $highestRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(-1);
        }
    }

    public function headings(): array
    {
        return [
            'No',
            'OFICINA',
            'TIPO DE ACTIVIDAD',
            'TÍTULO',
            'REGIÓN',
            'PROVINCIA',
            'DISTRITO',
            'DIRECCIÓN',
            'FECHA',
            'HORARIO',
            'DESCRIPCIÓN',
            'RESULTADOS',
            'NOMBRE RESPONSABLE',
            'MODALIDAD',
            'CANCELADO',
            'REPROGRAMADO',
        ];
    }
}
