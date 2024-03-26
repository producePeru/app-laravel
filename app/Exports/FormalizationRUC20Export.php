<?php

namespace App\Exports;

use App\Models\Formalization20;
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

class FormalizationRUC20Export implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    /**
     * @return \Illuminate\Support\Collectionc 
     */
    public function title(): string
    {
        return 'FormalizacionesRUC20';
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00B0F0');
        $sheet->getStyle('B1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('C4D79B');
        $sheet->getStyle('C1:J1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFC000');
        $sheet->getStyle('K1:P1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF00');
        $sheet->getStyle('Q1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF6699');
        $sheet->getStyle('R1:AC1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('B7DEE8');
        $sheet->getStyle('AD1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('92D050');
    }

    public function collection()
    {
        Carbon::setLocale('es');

        $adviseries = Formalization20::with(
            'categories', 'acreated', 'supervisorx', 'departmentx', 'provincex', 'districtx', 'prodecuredetail', 'economicsectors', 'notary'
            )
            ->orderBy('created_at', 'desc')
            ->where('status', 1)
            ->get();

        return $adviseries->map(function ($item, $index) {

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
                
                'Apellidos del Solicitante (socio o Gte General)' => $solicitante->last_name . ' ' . $solicitante->middle_name,
                'Nombres del Solicitante (socio o Gte General)' => $solicitante->name,
                'Genero Solicitante' => $solicitante->gender,
                'Tiene alguna Discapacidad ? (SI / NO)' => $solicitante && $solicitante->lession == 1 ? 'SI' : 'NO',
                'Celular' => $solicitante ? $solicitante->phone : null,
                'Correo electronico' => $solicitante ? $solicitante->email : null,
                'Supervisor' => $supervisador ? $supervisador->last_name . ' ' . $supervisador->middle_name . ' ' . $supervisador->name : null,
                
                'Tipo formalización' => 'PPJJ (RUC 20)',
                
    
                'Sector económico' => $item->economicsectors->name,
                'Atividad comercial' => $item->categories->name,
                'MYPE region' => $item->departmentx->descripcion,
                'MYPE provincia' => $item->provincex->descripcion,
                'MYPE distrito' => $item->districtx->descripcion,
                'MYPE dirección' => $item->address,
                'MYPE nombre' => $item->social_reason,
                'Tipo de regimen' => strtoupper($item->type_regimen),
                'Número envio notaría' => $item->num_notary,
                'Notaria' => $item->notary ? $item->notary->name : null,
                'RUC' => $item->ruc,
                'Modalidad' => $item->modality == 1 ? 'PRESENCIAL' : 'VIRTUAL',
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

            'Apellidos del Solicitante (socio o Gte General)',
            'Nombres del Solicitante (socio o Gte General)',
            'Genero Solicitante',
            'Tiene alguna Discapacidad ? (SI / NO)',
            'Celular',
            'Correo electronico',
            'Supervisor',
            
            'Tipo formalización',
            
            'Sector económico',
            'Atividad comercial',
            'MYPE region',

            'MYPE provincia',
            'MYPE distrito',
            'MYPE dirección',
            'MYPE nombre',
            'Tipo de regimen',
            'Número envio notaría',
            'Notaria',
            'RUC',
            'Modalidad'
        ];
    }
}