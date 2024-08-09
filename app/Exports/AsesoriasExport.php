<?php

namespace App\Exports;
use App\Models\Advisory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class AsesoriasExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
            'B' => 11,
            'C' => 27,
            'D' => 14,
            'E' => 14,
            'F' => 14,

            'G' => 4,
            'H' => 10,
            'I' => 6,
            'J' => 11,
            'K' => 15,
            'L' => 15,
            'M' => 15,
            'N' => 4,
            'O' => 4,
            'P' => 4,
            'Q' => 11,
            'R' => 20,

            'S' => 14,
            'T' => 14,
            'U' => 14,
            'V' => 12,
            'W' => 12,
            'X' => 12,
            'Y' => 15,
            'Z' => 20,
            'AA' => 10,
            'AB' => 12,
            'AC' => 12
        ];
    }

    public function title(): string
    {
        return 'Asesorías';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('002060');
        $sheet->getStyle('G1:R1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffc000');
        $sheet->getStyle('S1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('bdd6ee');
        $sheet->getStyle('T1:AA1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00b050');
        $sheet->getStyle('AB1:AC1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('000000');


        $sheet->getStyle('I1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffc000');
        $sheet->getStyle('A1:F1')->getFont()->getColor()->setARGB('FFFFFF');
        $sheet->getStyle('S1:AD1')->getFont()->getColor()->setARGB('FFFFFF');
        $sheet->getStyle('A1:AC1')->getFont()->setBold(true);
    }

    public function collection()
    {
        return $this->result;
    }


    public function headings(): array
    {
        return [
            'No',
            'Fecha de Registro',
            'Asesor (a) - Nombre Completo',
            'Región del CDE del Asesor',
            'Provincia del CDE del Asesor',
            'Distrito del CDE del Asesor',

            'Tipo de Documento de Identidad',
            'Número de Documento de Identidad',
            'Nombre del país de origen',
            'Fecha de Nacimiento',
            'Apellido Paterno del Solicitante (socio o Gte General)',
            'Apellido Materno del Solicitante (socio o Gte General)',
            'Nombres del Solicitante (socio o Gte General)',
            'Genero',
            'Tiene alguna Discapacidad ? (SI / NO)',
            '¿Tiene hijos?  (SI / NO)',
            'Telefono',
            'Correo electrónico o "NO TIENE"',

            'SUPERVISOR',

            'Región del negocio',
            'Provincia del Negocio',
            'Distrito del Negocio',
            'N_RUC',
            'Sector Económico',
            'Actividad Comercial Inicial',
            'Componente',
            'Tema',

            'Nro de Reserva / Observacion',
            'Modalidad'
        ];
    }
}
