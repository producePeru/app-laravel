<?php

namespace App\Exports;

use App\Models\Formalization20;
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
    public $dateStart;
    public $dateEnd;
    public $idAsesor;

    private function getUserRole()
    {
        $user_id = Auth::user()->id;

        $roleUser = DB::table('role_user')
        ->where('user_id', $user_id)
        ->first();

        if ($user_id != $roleUser->user_id) {
            return response()->json(['message' => 'Este rol no es correcto', 'status' => 404]);
        }

        return [
            "role_id" => $roleUser->role_id,
            'user_id' => $user_id
        ];
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

            'G' => 4,
            'H' => 10,
            'I' => 6,
            'J' => 11,
            'K' => 15,
            'L' => 15,
            'M' => 15,
            'N' => 4,
            'O' => 4,
            'P' => 10,
            'R' => 11,

            'S' => 22,
            'T' => 14,
            'U' => 14,
            'V' => 14,
            'W' => 18,
            'X' => 12,
            'Y' => 15,
            'Z' => 15,
            'AA' => 10,
            'AB' => 10,
            'AC' => 25,
            'AD' => 5,
            'AE' => 10,
            'AG' => 11,

        ];
    }

    public function title(): string
    {
        return 'FormalizacionesRUC20';
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('002060');
        $sheet->getStyle('G1:Q1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('833c0c');
        $sheet->getStyle('R1:Z1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('375623');
        $sheet->getStyle('AA1:AG1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('305496');

        $sheet->getStyle('A1:AG1')->getFont()->setBold(true);
        $sheet->getStyle('A1:AG1')->getFont()->getColor()->setARGB('FFFFFF');
    }

    public function collection()
    {
        $userRole = getUserRole();
        $roleIdArray = $userRole['role_id'];
        $user_id = $userRole['user_id'];

        if (in_array(1, $roleIdArray) || $user_id === 1) {
            if($this->idAsesor) {
                $query = Formalization20::ByUserId($this->idAsesor)->allFormalizations20([
                    'dateStart' => $this->dateStart,
                    'dateEnd' => $this->dateEnd,
                ]);
            } else {
                $query = Formalization20::allFormalizations20([
                    'dateStart' => $this->dateStart,
                    'dateEnd' => $this->dateEnd,
                ]);
            }
        }

        if (in_array(2, $roleIdArray)) {
            $query = Formalization20::ByUserId($user_id)->allFormalizations20([
                'dateStart' => $this->dateStart,
                'dateEnd' => $this->dateEnd,
            ]);
        }

        // $results = Formalization20::allFormalizations20();

        $results = $query->map(function ($item, $index) {

            $asesor = $item->supervisado ? $item->supervisado->supervisadoUser->profile : $item->asesorsupervisor;
            $supervisador = $item->supervisor ? $item->supervisor->supervisorUser->profile : $item->asesorsupervisor;
            $solicitante = $item->people;

            return array_merge([
                'No' => $index + 1,
                'Fecha de Registro' => Carbon::parse($item->created_at)->format('d/m/Y'),
                'Asesor (a) - Nombre Completo' => strtoupper($asesor->name . ' ' . $asesor->lastname . ' ' . $asesor->middlename),
                'Región del CDE del Asesor' => $asesor->cde ? $asesor->cde->city : '-',
                'Provincia del CDE del Asesor' => $asesor->cde ? $asesor->cde->province : '-',
                'Distrito del CDE del Asesor' => $asesor->cde ? $asesor->cde->district : '-',
            ], $solicitante ? [
                'Tipo de Documento de Identidad' => $solicitante->typedocument->avr,
                'Número de Documento de Identidad' => $solicitante->documentnumber,
                'Nombre del país de origen' => $solicitante->typedocument->avr === 'DNI' ? 'PERÚ' : strtoupper($solicitante->country),
                'Fecha de Nacimiento' => $solicitante->birthday ? date('d/m/Y', strtotime($solicitante->birthday)) : '-',
                'Apellido Paterno del Solicitante (socio o Gte General)' => strtoupper($solicitante->lastname),
                'Apellido Materno del Solicitante (socio o Gte General)' => strtoupper($solicitante->middlename),
                'Nombres del Solicitante (socio o Gte General)' => strtoupper($solicitante->name),
                'Genero' => $solicitante->gender->name === 'Masculino' ? 'M' : 'F',
                'Tiene alguna Discapacidad ? (SI / NO)' => $solicitante->sick == 'no' ? 'NO' : 'SI',
                'Celular' => $solicitante->phone ? $solicitante->phone : '-',
                'Correo electrónico' => $solicitante->email ? $solicitante->email : '-',
            ] : [], [
                'Tipo formalización' => 'PPJJ (RUC 20)',
                'Supervisor' => strtoupper($supervisador->name . ' ' . $supervisador->lastname . ' ' . $supervisador->middlename),
                'Region del negocio' => $item->city ? $item->city->name : '-',
                'Provincia del Negocio' => $item->province ? $item->province->name : '-',
                'Distrito del Negocio' => $item->district ? $item->district->name : '-',
                'Direccion del Negocio' => $item->address ? strtoupper($item->address) : '-',
                'N_RUC' => $item->ruc ? $item->ruc : 'EN TRÁMITE',
                'Sector económico' => $item->economicsector ? strtoupper($item->economicsector->name) : '-',
                'Atividad comercial' => $item->comercialactivity ? strtoupper($item->comercialactivity->name) : '-',
                'Fecha de Recepcion' => $item->dateReception ? date('d/m/Y', strtotime($item->dateReception)) : '-',
                'Fecha de TRAMITE en SID SUNARP o SUNAT' => $item->dateTramite ? date('d/m/Y', strtotime($item->dateTramite)) : '-',
                'Nombre de Empresa Constituida' => strtoupper($item->nameMype),
                'Tipo de Regimen Societario' => strtoupper($item->regime ? $item->regime->name : '-'),
                'Nro. De Solicitud' => $item->numbernotary ? $item->numbernotary : '-',
                // 'Código SUNARP' => $item->codesunarp ? $item->codesunarp : '-',
                'Notaria' => $item->notary ? $item->notary->name : '-',
                'MODALIDAD DE ATENCION' => $item->modality ? $item->modality->name : '-'
            ]);


        });

        return $results;

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
            'Nro. De Solicitud',

            // 'Código SUNARP',
            // 'Nombre de Empresa Constituida',
            'Notaria',
            'MODALIDAD DE ATENCION'
        ];
    }
}
