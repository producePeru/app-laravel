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
            'B' => 11,
            'C' => 27,
            'D' => 10,
            'E' => 10,
            'F' => 10,

            'G' => 4,
            'H' => 10,
            'I' => 6,
            'J' => 11,
            'K' => 15,
            'L' => 15,
            'M' => 15,
            'N' => 5,
            'O' => 5,
            'P' => 5,
            'Q' => 10,
            'R' => 12,

            'S' => 13,
            'T' => 10,
            'U' => 11,
            'V' => 11,
            'W' => 18,
            'X' => 12,
            'Y' => 12,
            'Z' => 11,
            'AA' => 20,
            'AB' => 11,
            'AC' => 12
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
