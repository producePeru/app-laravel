<?php

namespace App\Exports;

use App\Models\Formalization10;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

Carbon::setLocale('es');

class FormalizationRUC10Export implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{

    protected $fs10;

    public function __construct($fs10)
    {
        $this->fs10 = $fs10;
    }
    public function collection()
    {
        return collect($this->fs10);
    }

    public function title(): string
    {
        return 'FormalizacionesRUC10';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 15,
            'C' => 27,
            'D' => 14,
            'E' => 14,
            'F' => 14,
            'G' => 13,

            'H' => 8,
            'I' => 15,
            'J' => 11,
            'K' => 11,
            'L' => 16,
            'M' => 22,
            'N' => 22,
            'O' => 8,
            'P' => 8,
            'Q' => 11,
            'R' => 14,

            'S' => 14,
            'T' => 14,
            'U' => 14,
            'V' => 12,
            'W' => 18,
            'X' => 12,
            'Y' => 12,
            'Z' => 13,
            'AA' => 20,
            'AB' => 11,
            'AC' => 10,
            'AD' => 15
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Azul marino
        $sheet->getStyle('A1:G1')
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('002060');

        // Marrón
        $sheet->getStyle('H1:V1')
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('833C0C');

        // Verde militar
        $sheet->getStyle('W1:AE1')
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('375623');

        // Negro
        $sheet->getStyle('AF1:AG1')
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('000000');

        // Fuente blanca y negrita
        $sheet->getStyle('A1:AG1')->getFont()->setBold(true);
        $sheet->getStyle('A1:AG1')->getFont()->getColor()->setARGB('FFFFFF');

        // Ajuste de texto y centrado vertical
        $sheet->getStyle('A1:AG1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:AG1')->getAlignment()->setVertical(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        );
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
            'Cde del Asesor',

            'Tipo de Documento de Identidad',
            'Número de Documento de Identidad',
            'Nombre del País',
            'Fecha de Nacimiento',
            'Apellido Paterno del  Solicitante (socio o Gte General)',
            'Apellido Materno del  Solicitante (socio o Gte General)',
            'Nombres del Solicitante (socio o Gte General)',
            'Género',
            'Tiene alguna Discapacidad ? (SI / NO)',
            'Nombre y apellido de la Persona cuidadora',
            '¿Tiene hijos?  (SI / NO)',
            'Celular',
            '¿Con qué cultura o etnia te identificas?',
            'Lengua Originaria',
            'Correo electrónico',

            'Tipo formalización',
            'SUPERVISOR',
            'Región del negocio',
            'Provincia del Negocio',
            'Distrito del Negocio',
            'Direccion del Negocio',
            'N_RUC',
            'Sector economico',
            'Atividad comercial',

            'Detalle del trámite',
            'MODALIDAD DE ATENCION'
        ];
    }
}
