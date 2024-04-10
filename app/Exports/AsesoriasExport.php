<?php

namespace App\Exports;

use App\Models\Advisory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AsesoriasExport implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function title(): string
    {
        return 'Asesorías';
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00B0F0');
        $sheet->getStyle('B1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('C4D79B');
        $sheet->getStyle('C1:J1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFC000');
        $sheet->getStyle('K1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF6699');
        $sheet->getStyle('L1:Q1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF00');
        $sheet->getStyle('R1:W1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('B7DEE8');
        $sheet->getStyle('X1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('92D050');
    }

    public function collection()
    {
        $results = Advisory::allNotaries();

        return $results->map(function ($item, $index) {
            return [
                'No' => $index + 1,
                'Fecha de Asesoria' => Carbon::parse($item->created_at)->format('d/m/Y'),
                'Asesor (a) - Nombre Completo' => $item->asesor->name . ' ' . $item->asesor->lastname . ' ' . $item->asesor->middlename,
                'CDE del Asesor' => $item->asesor->cde->name,
                'Provincia del CDE del Asesor' => $item->asesor->cde->name,
                'Distrito del  CDE del Asesor' => $item->asesor->cde->name,
                'Tipo de Documento de Identidad' => 'DNI',
                'Numero de Documento de Identidad' => $item->asesor->documentnumber,
                'Fecha de Nacimiento' => $item->asesor->birthday ? $item->asesor->birthday : null,
                'Nombre del Pais' => 'Perú',


                'Apellido Paterno del Solicitante (socio o Gte General)' => $item->people ? $item->people->lastname : null,
                'Apellido Materno del Solicitante (socio o Gte General)' => $item->people ? $item->people->lastname : null,
                'Nombres del Solicitante (socio o Gte General)' => $item->people ? $item->people->name : null,
                'Genero' => $item->people ? $item->people->gender->name : null,
                'Tiene alguna Discapacidad ? (SI / NO)' => $item->people && $item->people->sick == 'mo' ? 'NO' : 'SI',
                'Telefono' => $item->people ? $item->people->phone : null,
                'Correo electronico' => $item->people ? $item->people->email : null,
                'Region MYPE' => $item->city->name,
                'Provincia MYPE' => $item->province->name,
                'Distrito MYPE' => $item->district->name,
                'Componente' => $item->component->name,
                'Tema' => $item->theme->name,
                'Observación' => $item->observations,
                'MODALIDAD DE ATENCION' => $item->modality->name,
                

            ];
        });
    }


    public function headings(): array
    {
        return [
            'No',
            'Fecha de Asesoria',
            'Asesor (a) - Nombre Completo',
            'CDE del Asesor',
            'Provincia del CDE del Asesor',
            'Distrito del  CDE del Asesor',
            'Tipo de Documento de Identidad',
            'Numero de Documento de Identidad',
            'Fecha de Nacimiento',
            'Nombre del Pais',
            // 'Supervisor',
            'Apellido Paterno del Solicitante (socio o Gte General)',
            'Apellido Materno del Solicitante (socio o Gte General)',
            'Nombres del Solicitante (socio o Gte General)',
            'Genero',
            'Tiene alguna Discapacidad ? (SI / NO)',
            'Telefono',
            'Correo electronico',
            'Region MYPE',
            'Provincia MYPE',
            'Distrito MYPE',
            'Componente',
            'Tema',
            'Observación',
            'MODALIDAD DE ATENCION'
        ];
    }
}
