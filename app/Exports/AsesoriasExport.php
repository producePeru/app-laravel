<?php

namespace App\Exports;

use App\Models\Advisory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AsesoriasExport implements FromCollection, WithHeadings, WithTitle, WithStyles
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

    public function title(): string
    {
        return 'Asesorías';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00B0F0');
        $sheet->getStyle('B1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('C4D79B');
        $sheet->getStyle('C1:H1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFC000');
        $sheet->getStyle('I1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF6699');
        $sheet->getStyle('J1:P1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF00');
        $sheet->getStyle('Q1:V1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('B7DEE8');
        $sheet->getStyle('W1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('92D050');
    }

    public function collection()
    {

        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];


        if ($role_id == 1) {
            $query = Advisory::descargaExcelAsesorias([
                'dateStart' => $this->dateStart,
                'dateEnd' => $this->dateEnd,
            ]);
        }

        if ($role_id != 1) {
            $query = Advisory::ByUserId($user_id)->descargaExcelAsesorias([
                'dateStart' => $this->dateStart,
                'dateEnd' => $this->dateEnd,
            ]);
        }

        $results = $query->map(function ($item, $index) {

            $asesor = $item->supervisado ? $item->supervisado->supervisadoUser->profile : $item->asesorsupervisor;
            $supervisador = $item->supervisor ? $item->supervisor->supervisorUser->profile : $item->asesorsupervisor;
            $solicitante = $item->people;


            return [
                'No' => $index + 1,
                'Fecha de Asesoria' => Carbon::parse($item->created_at)->format('d/m/Y'),

                'Asesor (a) - Nombre Completo' => $asesor->name . ' ' . $asesor->lastname . ' ' . $asesor->middlename,
                'CDE del Asesor' => $asesor->cde->name,
                // 'Provincia del CDE del Asesor' => $item->asesor->cde->name,
                // 'Distrito del  CDE del Asesor' => $item->asesor->cde->name,
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

                'Region MYPE' => $item->city->name,
                'Provincia MYPE' => $item->province->name,
                'Distrito MYPE' => $item->district->name,

                'Componente' => $item->component->name,
                'Tema' => $item->theme->name,
                'Observación' => $item->observations,

                'MODALIDAD DE ATENCION' => $item->modality->name
            ];
        });

        return $results;
    }


    public function headings(): array
    {
        return [
            'No',
            'Fecha de Asesoria',
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
