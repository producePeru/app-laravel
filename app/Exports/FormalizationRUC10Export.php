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
            'G' => 18,

            'H' => 11,
            'I' => 15,
            'J' => 11,
            'K' => 19,
            'L' => 28,
            'M' => 22,
            'N' => 21,
            'O' => 11,
            'P' => 7,
            'Q' => 11,
            'R' => 11,

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
        $sheet->getStyle('A1:G1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('002060');    // azul
        $sheet->getStyle('H1:S1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('833c0c');    // marron
        $sheet->getStyle('T1:AB1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('375623');   // verde
        $sheet->getStyle('AC1:AD1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('305496');  // azul


        $sheet->getStyle('A1:AD1')->getFont()->setBold(true);                                                                                       // negrita
        $sheet->getStyle('A1:AD1')->getFont()->getColor()->setARGB('FFFFFF');                                                                       // color fuente


        $sheet->getStyle('A1:AD1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:AD1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
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
