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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
Carbon::setLocale('es');

class FormalizationRUC10Export implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
            'B' => 15,
            'C' => 27,
            'D' => 14,
            'E' => 14,
            'F' => 14,

            'G' => 18,
            'H' => 21,
            'I' => 15,
            'J' => 11,
            'K' => 28,
            'L' => 28,
            'M' => 22,
            'N' => 8,
            'O' => 21,
            'P' => 12,
            'Q' => 11,
            'R' => 20,

            'S' => 14,
            'T' => 14,
            'U' => 14,
            'V' => 12,
            'W' => 18,
            'X' => 12,
            'Y' => 12,
            'Z' => 11,
            'AA' => 20,
            'AB' => 11,
            'AC' => 14
        ];
    }

    public function title(): string
    {
        return 'FormalizacionesRUC10';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('002060');
        $sheet->getStyle('G1:R1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('833c0c');
        $sheet->getStyle('S1:AA1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('375623');
        $sheet->getStyle('AB1:AC1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('305496');

        $sheet->getStyle('A1:AC1')->getFont()->setBold(true);
        $sheet->getStyle('A1:AC1')->getFont()->getColor()->setARGB('FFFFFF');

        $sheet->getStyle('A1:AC1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:AC1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
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
            'Nombre del País',
            'Fecha de Nacimiento',
            'Apellido Paterno del  Solicitante (socio o Gte General)',
            'Apellido Materno del  Solicitante (socio o Gte General)',
            'Nombres del Solicitante (socio o Gte General)',
            'Género',
            'Tiene alguna Discapacidad ? (SI / NO)',
            '¿Tiene hijos?  (SI / NO)',
            'Celular',
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
