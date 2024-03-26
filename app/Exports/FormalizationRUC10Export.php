<?php

namespace App\Exports;

use App\Models\Formalization10;
use App\Models\Departament;
use App\Models\District;
use App\Models\People;
use App\Models\Province;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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
        $sheet->getStyle('C1:J1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFC000');
        $sheet->getStyle('K1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF6699');
        $sheet->getStyle('L1:Q1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF00');
        $sheet->getStyle('R1:X1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('B7DEE8');
        $sheet->getStyle('Y1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('92D050');
    }

    public function collection()
    {
        Carbon::setLocale('es');

        $adviseries = Formalization10::with(
            'categories', 'acreated', 'supervisorx', 'departmentx', 'provincex', 'districtx', 'prodecuredetail', 'economicsectors'
            )
            ->orderBy('created_at', 'desc')
            ->where('status', 1)
            ->get();

        return $adviseries->map(function ($item, $index) {

            // $registrador = People::where('number_document', $item->acreated->document_number)->first();
            
            $registrador = $item->acreated;

            $supervisador = $item->supervisorx ? People::where('id', $item->supervisorx->id_supervisor)->first() : null;
            
            $solicitante = People::where('id', $item->id_person)->first();

            $departamento = null;
            $provincia = null;
            $distrito = null;

            if ($registrador) {
                $departamento = Departament::where('idDepartamento', $registrador->department)->first();
            }

            if ($registrador) {
                $provincia = Province::where('idProvincia', $registrador->province)->first();
            }

            if ($registrador) {
                $distrito = District::where('idDistrito', $registrador->district)->first();
            }

            return [
                'No' => $index + 1,
                'Fecha de Producto' => Carbon::parse($item->created_at)->format('d/m/Y'),
                'Asesor (a) - Nombre Completo' => $registrador ? $registrador->last_name . ' ' . $registrador->middle_name . ', ' . $registrador->name : '',
                'Region del CDE del Asesor' =>  $departamento ? $departamento->descripcion : null,
                'Provincia del CDE del Asesor' =>  $provincia ? $provincia->descripcion : null,
                'Distrito del  CDE del Asesor' =>  $distrito ? $distrito->descripcion : null,
                'Tipo de Documento de Identidad' => $registrador ? $registrador->document_type : null,
                'Numero de Documento de Identidad' => $registrador ? $registrador->number_document : null,
                'Nombre del Pais' => 'Perú',
                'Fecha de Nacimiento' => $registrador ? $registrador->birthdate : null,
                
                'Supervisor' => $supervisador ? $supervisador->last_name . ' ' . $supervisador->middle_name . ' ' . $supervisador->name : null,

                'Apellidos del Solicitante (socio o Gte General)' => $solicitante->last_name . ' ' . $solicitante->middle_name,
                'Nombres del Solicitante (socio o Gte General)' => $solicitante->name,
                'Genero Solicitante' => $solicitante->gender,
                'Tiene alguna Discapacidad ? (SI / NO)' => $solicitante && $solicitante->lession == 1 ? 'SI' : 'NO',
                'Celular' => $solicitante ? $solicitante->phone : null,
                'Correo electronico' => $solicitante ? $solicitante->email : null,
                'Tipo formalización' => 'PPNN (RUC 10)',
                
                
                
                'Region MYPE' => $item->departmentx->descripcion,
                'Provincia MYPE' => $item->provincex->descripcion,
                'Distrito MYPE' => $item->districtx->descripcion,

                'Detalle del trámite' => $item->prodecuredetail->name,
                'Sector economico' => $item->economicsectors->name,
                'Atividad comercial' => $item->categories->name,
                'Modalidad' => $item->modality == 1 ? 'VIRTUAL' : 'PRESENCIAL'
            ];
        });
    }


    public function headings(): array
    {
        return [
            'No',
            'Fecha de Producto',
            'Asesor (a) - Nombre Completo',
            'Region del CDE del Asesor',
            'Provincia del CDE del Asesor',
            'Distrito del  CDE del Asesor',
            'Tipo de Documento de Identidad',
            'Numero de Documento de Identidad',
            'Nombre del Pais',
            'Fecha de Nacimiento',

            'Supervisor',

            'Apellidos del Solicitante (socio o Gte General)',
            'Nombres del Solicitante (socio o Gte General)',
            'Genero Solicitante',
            'Tiene alguna Discapacidad ? (SI / NO)',
            'Celular',
            'Correo electronico',
            'Tipo formalización',
            'Region MYPE',
            'Provincia MYPE',
            'Distrito MYPE',

            'Detalle del trámite',
            'Sector economico',
            'Atividad comercial',
            'Modalidad'
        ];
    }
}