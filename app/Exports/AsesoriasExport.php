<?php

namespace App\Exports;

use App\Models\Advisory;
use Maatwebsite\Excel\Concerns\FromQuery;
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
            'W' => 12,
            'X' => 12,
            'Y' => 15,
            'Z' => 20,
            'AA' => 10,
            'AB' => 15,
            'AC' => 12,
            'AG' => 14,
        ];
    }

    public function title(): string
    {
        return 'Asesorías';
    }

    public function styles(Worksheet $sheet)
    {
        // Azul marino
        $sheet->getStyle('A1:G1')
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('002060');

        // Naranja
        $sheet->getStyle('H1:V1')
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('833c0c');

        // Verde
        $sheet->getStyle('W1:AE1')
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('00B050');

        // Negro
        $sheet->getStyle('AF1:AG1')
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('000000');

        // Texto blanco para todos los encabezados
        $sheet->getStyle('A1:AG1')
            ->getFont()
            ->getColor()
            ->setARGB('FFFFFF');

        // Negrita
        $sheet->getStyle('A1:AG1')
            ->getFont()
            ->setBold(true);

        // Ajuste de texto
        $sheet->getStyle('A1:AG1')
            ->getAlignment()
            ->setWrapText(true);

        // Centrado vertical
        $sheet->getStyle('A1:AG1')
            ->getAlignment()
            ->setVertical(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            );
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
            'Nombre y apellido de la Persona cuidadora',
            '¿Tiene hijos?  (SI / NO)',
            'Telefono',
            '¿Con qué cultura o etnia te identificas?',
            'Lengua Originaria',
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
        ]);
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
            'Nombre del país de origen',
            'Fecha de Nacimiento',
            'Apellido Paterno del Solicitante (socio o Gte General)',
            'Apellido Materno del Solicitante (socio o Gte General)',
            'Nombres del Solicitante (socio o Gte General)',
            'Genero',
            'Tiene alguna Discapacidad ? (SI / NO)',
            'Nombre y apellido de la Persona cuidadora',
            '¿Tiene hijos?  (SI / NO)',
            'Telefono',
            '¿Con qué cultura o etnia te identificas?',
            'Lengua Originaria',
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
