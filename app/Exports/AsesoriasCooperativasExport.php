<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class AsesoriasCooperativasExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $advisories;

    public function __construct($advisories)
    {
        $this->advisories = $advisories;
    }
    public function collection()
    {
        return collect($this->advisories);
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

            'H' => 21,
            'I' => 15,
            'J' => 11,
            'K' => 27,
            'L' => 28,
            'M' => 22,
            'N' => 22,
            'O' => 21,
            'P' => 12,
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
            'AB' => 15,
            'AC' => 12,
            'AD' => 15,
            'AE' => 15,
            'AF' => 30
        ];
    }

    public function title(): string
    {
        return 'Asesorías';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('002060');
        $sheet->getStyle('H1:S1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffc000');
        // $sheet->getStyle('S1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('bdd6ee'); #ffebfd
        $sheet->getStyle('T1:AB1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00b050');
        $sheet->getStyle('AC1:AD1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('000000');

        $sheet->getStyle('AE1:AF1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffebfd');

        $sheet->getStyle('I1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffc000');
        $sheet->getStyle('A1:G1')->getFont()->getColor()->setARGB('FFFFFF');
        $sheet->getStyle('T1:AD1')->getFont()->getColor()->setARGB('FFFFFF');
        $sheet->getStyle('A1:AF1')->getFont()->setBold(true);

        $sheet->getStyle('A1:AF1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:AF1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
    }

    public function query()
    {
        // Asegúrate de cambiar 'asesorias' por el nombre real de tu tabla
        return DB::table('asesorias')->select([
            'No',
            'Fecha de Registro',
            'Asesor (a) - Nombre Completo',
            'Región del CDE del Asesor',
            'Provincia del CDE del Asesor',
            'Distrito del CDE del Asesor',
            'Cde del Asesor',

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
            'Modalidad',
            'cooperativa ruc',
            'cooperativa nombre'
        ]);
    }


    public function headings(): array
    {
        return [
            'N°',
            'FECHA DE REGISTRO',
            'ASESOR (A) - NOMBRE COMPLETO',
            'REGIÓN DEL CDE DEL ASESOR',
            'PROVINCIA DEL CDE DEL ASESOR',
            'DISTRITO DEL CDE DEL ASESOR',
            'CDE DEL ASESOR',

            'TIPO DE DOCUMENTO DE IDENTIDAD',
            'NÚMERO DE DOCUMENTO DE IDENTIDAD',
            'NOMBRE DEL PAÍS DE ORIGEN',
            'FECHA DE NACIMIENTO',
            'APELLIDO PATERNO DEL SOLICITANTE (SOCIO O GTE GENERAL)',
            'APELLIDO MATERNO DEL SOLICITANTE (SOCIO O GTE GENERAL)',
            'NOMBRES DEL SOLICITANTE (SOCIO O GTE GENERAL)',
            'GENERO',
            'TIENE ALGUNA DISCAPACIDAD ? (SI / NO)',
            '¿TIENE HIJOS?  (SI / NO)',
            'TELEFONO',
            'CORREO ELECTRÓNICO O "NO TIENE"',

            'SUPERVISOR',

            'REGIÓN DEL NEGOCIO',
            'PROVINCIA DEL NEGOCIO',
            'DISTRITO DEL NEGOCIO',
            'N_RUC',
            'SECTOR ECONÓMICO',
            'ACTIVIDAD COMERCIAL INICIAL',
            'COMPONENTE',
            'TEMA',

            'NRO DE RESERVA / OBSERVACION',
            'MODALIDAD',
            'COOPERATIVA RUC',
            'COOPERATIVA NOMBRE'
        ];
    }
}
