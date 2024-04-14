<?php

namespace App\Exports;

use App\Models\Formalization10;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
Carbon::setLocale('es');

class FormalizationRUC10Export implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function title(): string
    {
        return 'FormalizacionesRUC10';
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00B0F0');
        $sheet->getStyle('B1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('C4D79B');
        $sheet->getStyle('C1:H1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFC000');
        $sheet->getStyle('I1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF6699');
        $sheet->getStyle('J1:P1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF00');
        $sheet->getStyle('Q1:W1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('B7DEE8');
        $sheet->getStyle('X1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('92D050');
    }

    public function collection()
    {
        $results = Formalization10::allFormalizations10();

        return $results->map(function ($item, $index) {

            $asesor = $item->supervisado->supervisadoUser->profile;
            $supervisador = $item->supervisor->supervisorUser->profile;
            $solicitante = $item->people;

            return [
                'No' => $index + 1,
                'Fecha de Asesoria' => Carbon::parse($item->created_at)->format('d/m/Y'),
                
                'Asesor (a) - Nombre Completo' => $asesor->name . ' ' . $asesor->lastname . ' ' . $asesor->middlename,
                'CDE del Asesor' => $asesor->cde->name,
                // 'Provincia del CDE del Asesor' =>  $provincia ? $provincia->descripcion : null,
                // 'Distrito del  CDE del Asesor' =>  $distrito ? $distrito->descripcion : null,
                
                'Tipo de Documento de Identidad' => 'DNI',
                'Numero de Documento de Identidad' => $asesor->documentnumber,
                'Fecha de Nacimiento' => $asesor->birthday,
                'Nombre del Pais' => 'Perú',
                
                'Supervisor' => $supervisador->name . ' ' . $supervisador->lastname . ' ' . $supervisador->middlename,

                'Apellido Paterno del Solicitante (socio o Gte General)' => $solicitante->lastname,
                'Apellido Materno del Solicitante (socio o Gte General)' => $solicitante->middlename,
                'Nombres del Solicitante (socio o Gte General)' => $solicitante->name,
                'Genero' => $solicitante->gender->name,
                'Tiene alguna Discapacidad ? (SI / NO)' => $solicitante->sick == 'no' ? 'NO' : 'SI',
                'Telefono' => $solicitante->phone,
                'Correo electronico' => $solicitante->email,
                
                'Tipo formalización' => 'PPNN (RUC 10)',
                
                'Region MYPE' => $item->city->name,
                'Provincia MYPE' => $item->province->name,
                'Distrito MYPE' => $item->district->name,

                'Detalle del trámite' => $item->detailprocedure->name,
                'Sector economico' => $item->economicsector->name,
                'Atividad comercial' => $item->comercialactivity->name,

                'MODALIDAD DE ATENCION' => $item->modality->name
            ];
        });
    }


    public function headings(): array
    {
        return [
            'No',
            'Fecha de Producto',
            'Asesor (a) - Nombre Completo',
            'CDE del Asesor',
            // 'Provincia del CDE del Asesor',
            // 'Distrito del  CDE del Asesor',
            'Tipo de Documento de Identidad',
            'Numero de Documento de Identidad',
            'Fecha de Nacimiento',
            'Nombre del Pais',

            'Supervisor',

            'Apellido Paterno del Solicitante (socio o Gte General)',
            'Apellido Materno del Solicitante (socio o Gte General)',
            'Nombres del Solicitante (socio o Gte General)',
            'Genero',
            'Tiene alguna Discapacidad ? (SI / NO)',
            'Telefono',
            'Correo electronico',

            'Tipo formalización',
            'Region MYPE',
            'Provincia MYPE',
            'Distrito MYPE',

            'Detalle del trámite',
            'Sector economico',
            'Atividad comercial',
            'MODALIDAD DE ATENCION'
        ];
    }
}