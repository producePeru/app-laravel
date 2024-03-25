<?php

namespace App\Exports;

use App\Models\Advisery;
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
        Carbon::setLocale('es');

        $adviseries = Advisery::with('acreated', 'theme', 'departmentx', 'provincex', 'districtx', 'person', 'components', 'supervisorx')
            ->orderBy('created_at', 'desc')
            ->where('status', 1)
            ->get();

        return $adviseries->map(function ($item, $index) {

            // $registrador = People::where('number_document', $item->acreated->document_number)->first();
            // $supervisador = $item->supervisor ? People::where('id', $item->supervisor->id_supervisor)->first() : null;
            $registrador = $item->acreated;
            
            $supervisador = $item->supervisorx ? People::where('id', $item->supervisorx->id_supervisor)->first() : null;


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
                'Fecha de Asesoria' => Carbon::parse($item->created_at)->format('d/m/Y'),
                'Asesor (a) - Nombre Completo' => $registrador ? $registrador->last_name . ' ' . $registrador->middle_name . ', ' . $registrador->name : '',
                'Region del CDE del Asesor' =>  $departamento ? $departamento->descripcion : null,
                'Provincia del CDE del Asesor' =>  $provincia ? $provincia->descripcion : null,
                'Distrito del  CDE del Asesor' =>  $distrito ? $distrito->descripcion : null,
                'Tipo de Documento de Identidad' => $registrador ? $registrador->document_type : null,
                'Numero de Documento de Identidad' => $registrador ? $registrador->number_document : null,
                'Fecha de Nacimiento' => $registrador ? $registrador->birthdate : null,
                'Nombre del Pais' => 'Perú',
                'Supervisor' => $supervisador ? $supervisador->last_name. ' '. $supervisador->middle_name. ' '. $supervisador->name : ' - ',
                'Apellidos del Solicitante (socio o Gte General)' => $item->person ? $item->person->last_name . ' ' . $item->person->middle_name : null,
                'Nombres del Solicitante (socio o Gte General)' => $item->person ? $item->person->name : null,
                'Genero' => $item->person ? $item->person->gender : null,
                'Tiene alguna Discapacidad ? (SI / NO)' => $item->person && $item->person->lession == 1 ? 'SI' : 'NO',
                'Telefono' => $item->person ? $item->person->phone : null,
                'Correo electronico' => $item->person ? $item->person->email : null,
                'Region MYPE' => $item->departmentx->descripcion,
                'Provincia MYPE' => $item->provincex->descripcion,
                'Distrito MYPE' => $item->districtx->descripcion,
                'Componente' => $item->components->name,
                'Tema' => $item->theme->name,
                'Observación' => $item->description,
                'MODALIDAD DE ATENCION' => $item->modality == 1 ? 'VIRTUAL' : 'PRESENCIAL',
            ];
        });
    }


    public function headings(): array
    {
        return [
            'No',
            'Fecha de Asesoria',
            'Asesor (a) - Nombre Completo',
            'Region del CDE del Asesor',
            'Provincia del CDE del Asesor',
            'Distrito del  CDE del Asesor',
            'Tipo de Documento de Identidad',
            'Numero de Documento de Identidad',
            'Fecha de Nacimiento',
            'Nombre del Pais',
            'Supervisor',
            'Apellidos del Solicitante (socio o Gte General)',
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
