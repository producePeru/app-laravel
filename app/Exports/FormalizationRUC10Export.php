<?php

namespace App\Exports;

use App\Models\Formalization10;
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

class FormalizationRUC10Export implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    public $dateStart;
    public $dateEnd;

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
            'D' => 10,
            'E' => 10,
            'F' => 10,

            'G' => 4,
            'H' => 10,
            'I' => 6,
            'J' => 11,
            'K' => 15,
            'L' => 15,
            'M' => 15,
            'N' => 5,
            'O' => 5,
            'P' => 5,
            'Q' => 10,
            'R' => 12,

            'S' => 13,
            'T' => 10,
            'U' => 11,
            'V' => 11,
            'W' => 18,
            'X' => 12,
            'Y' => 12,
            'Z' => 11,
            'AA' => 20,
            'AB' => 11,
            'AC' => 12
        ];
    }

    public function title(): string
    {
        return 'FormalizacionesRUC10';
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('002060');
        $sheet->getStyle('G1:R1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('833c0c');
        $sheet->getStyle('S1:AA1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('375623');
        $sheet->getStyle('AB1:AC1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('305496');

        $sheet->getStyle('A1:AC1')->getFont()->setBold(true);
        $sheet->getStyle('A1:AC1')->getFont()->getColor()->setARGB('FFFFFF');
    }

    public function collection()
    {
        $userRole = getUserRole();
        $roleIdArray = $userRole['role_id'];
        $user_id = $userRole['user_id'];

        if (in_array(1, $roleIdArray) || $user_id === 1) {
            $query = Formalization10::allFormalizations10([
                'dateStart' => $this->dateStart,
                'dateEnd' => $this->dateEnd,
            ]);
        }

        if (in_array(2, $roleIdArray)) {
            $query = Formalization10::ByUserId($user_id)->allFormalizations10([
                'dateStart' => $this->dateStart,
                'dateEnd' => $this->dateEnd,
            ]);
        }

        $results = $query->map(function ($item, $index) {

            $asesor = $item->supervisado ? $item->supervisado->supervisadoUser->profile : $item->asesorsupervisor;
            $supervisador = $item->supervisor ? $item->supervisor->supervisorUser->profile : $item->asesorsupervisor;
            $solicitante = $item->people;

             // cede
             $regionCDE    =  $item->sede     ? $item->sede->city     : $asesor->cde->city;
             $provinciaCDE =  $item->sede ? $item->sede->province : $asesor->cde->province;
             $distritoCDE  =  $item->sede ? $item->sede->district : $asesor->cde->district;

            return array_merge([
                'No' => $index + 1,
                'Fecha de Registro' => Carbon::parse($item->created_at)->format('d/m/Y'),
                'Asesor (a) - Nombre Completo' => strtoupper($asesor->name . ' ' . $asesor->lastname . ' ' . $asesor->middlename),

                'Región del CDE del Asesor' => $regionCDE,
                'Provincia del CDE del Asesor' => $provinciaCDE,
                'Distrito del CDE del Asesor' => $distritoCDE,

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
                '¿Tiene hijos?  (SI / NO)' => $solicitante->hasSoon,
                'Celular' => $solicitante->phone ? $solicitante->phone : '-',
                'Correo electrónico' => $solicitante->email ? strtoupper($solicitante->email) : '-',
            ] : [], [
                'Tipo formalización' => 'PPNN (RUC 10)',
                'SUPERVISOR' => strtoupper($supervisador->name . ' ' . $supervisador->lastname . ' ' . $supervisador->middlename),
                'Región del negocio' => $item->city->name,
                'Provincia del Negocio' => $item->province->name,
                'Distrito del Negocio' => $item->district->name,
                'Direccion del Negocio' => $item->address ? strtoupper($item->address) : '-',
                'N_RUC' => $item->ruc ? $item->ruc : '-',
                'Sector economico' => strtoupper($item->economicsector->name),
                'Atividad comercial' => strtoupper($item->comercialactivity->name),
                'Detalle del tramite PPNN (RUC 10)' => $item->detailprocedure->name,
                'MODALIDAD DE ATENCION' => $item->modality->name,
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
