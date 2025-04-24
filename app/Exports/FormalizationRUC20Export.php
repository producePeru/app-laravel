<?php

namespace App\Exports;

use App\Models\Formalization20;
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

class FormalizationRUC20Export implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $fs20;

    public function __construct($fs20)
    {
        $this->fs20 = $fs20;
    }
    public function collection()
    {
        return collect($this->fs20);
    }

    public function title(): string
    {
        return 'FormalizacionesRUC20';
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
            'G' => 11,

            'H' => 10,
            'I' => 14,
            'J' => 11,
            'K' => 15,
            'L' => 24,
            'M' => 24,
            'N' => 24,
            'O' => 8,
            'P' => 13,
            'Q' => 11,
            'R' => 13,

            'S' => 12,
            'T' => 22,
            'U' => 14,
            'V' => 14,
            'W' => 14,
            'X' => 18,
            'Y' => 12,
            'Z' => 15,
            'AA' => 15,
            'AB' => 10,
            'AC' => 20,
            'AD' => 25,
            'AE' => 11,
            'AG' => 10,
            'AF' => 8,
            'AH' => 14,
            'AI' => 12,
            'AJ' => 22,
            'AK' => 12,
            'AL' => 12
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('002060');        // azul
        $sheet->getStyle('H1:R1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('833c0c');
        $sheet->getStyle('S1:Z1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('375623');
        $sheet->getStyle('AA1:AH1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('305496');
        $sheet->getStyle('AI1:AK1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('c00000');
        $sheet->getStyle('AL1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('000000');

        $sheet->getStyle('A1:AL1')->getFont()->setBold(true);
        $sheet->getStyle('A1:AL1')->getFont()->getColor()->setARGB('FFFFFF');

        $sheet->getStyle('A1:AL1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:AL1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
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
            'Supervisor',
            'Region del negocio',
            'Provincia del Negocio',
            'Distrito del Negocio',
            'Direccion del Negocio',
            'N_RUC',
            'Sector económico',
            'Atividad comercial',

            'Fecha de Recepcion de Solicitud al PNTE (cuando el  asesor recepciona los documentos COMPLETOS, todo OK)',
            'Fecha de TRAMITE en SID SUNARP o SUNAT',
            'Nombre de Empresa Constituida',
            'Tipo de Regimen Societario',
            '¿Es una sociedad BIC? (SI / NO)',
            'Nro. De Solicitud',

            // 'Código SUNARP',
            // 'Nombre de Empresa Constituida',
            'Notaria',
            'Tipo de aporte de capital: Monetario/Monetario con declaración jurada/Bienes/Mixto',
            'Monto de capital social',
            'MODALIDAD DE ATENCION'
        ];
    }
}
