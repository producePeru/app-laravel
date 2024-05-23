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
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class AsesoriasExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
            'D' => 14,
            'E' => 14,
            'F' => 14,

            'G' => 7,
            'H' => 10,
            'I' => 6,
            'J' => 11,
            'K' => 15,
            'L' => 15,
            'M' => 15,
            'N' => 10,
            'O' => 6,
            'P' => 10,
            'R' => 23,

            'S' => 14,
            'T' => 14,
            'U' => 14,
            'V' => 12,
            'W' => 18,
            'X' => 23
        ];
    }

    public function title(): string
    {
        return 'Asesorías';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('002060');
        $sheet->getStyle('G1:Q1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffc000');
        $sheet->getStyle('R1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('bdd6ee');
        $sheet->getStyle('S1:X1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00b050');
        $sheet->getStyle('Y1:Z1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('000000');


        $sheet->getStyle('I1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffc000');
        $sheet->getStyle('A1:F1')->getFont()->getColor()->setARGB('FFFFFF');
        $sheet->getStyle('S1:Z1')->getFont()->getColor()->setARGB('FFFFFF');
        $sheet->getStyle('A1:Z1')->getFont()->setBold(true);
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
                'Fecha de Registro' => Carbon::parse($item->created_at)->format('d/m/Y'),
                'Asesor (a) - Nombre Completo' => $asesor->name . ' ' . $asesor->lastname . ' ' . $asesor->middlename,
                'Región del CDE del Asesor' => $asesor->cde ? $asesor->cde->city : '-',
                'Provincia del CDE del Asesor' => $asesor->cde ? $asesor->cde->province : '-',
                'Distrito del CDE del Asesor' => $asesor->cde ? $asesor->cde->district : '-',

                //
                'Tipo de Documento de Identidad' => $solicitante->typedocument->name,
                'Número de Documento de Identidad' => $solicitante->documentnumber,
                'Nombre del país de origen' => 'PERÚ',
                'Fecha de Nacimiento' => $solicitante->birthday ? $solicitante->birthday : '-',
                'Apellido Paterno del Solicitante (socio o Gte General)' => $solicitante->lastname,
                'Apellido Materno del Solicitante (socio o Gte General)' => $solicitante->middlename,
                'Nombres del Solicitante (socio o Gte General)' => $solicitante->name,
                'Genero' => $solicitante->gender->name,
                'Tiene alguna Discapacidad ? (SI / NO)' => $solicitante->sick == 'no' ? 'NO' : 'SI',
                'Telefono' => $solicitante->phone ? $solicitante->phone : '-',
                'Correo electronico' => $solicitante->email ? $solicitante->email : '-',
                //
                'SUPERVISOR' => $supervisador->name . ' ' . $supervisador->lastname . ' ' . $supervisador->middlename,

                'Región del negocio' => $item->city->name,
                'Provincia del Negocio' => $item->province->name,
                'Distrito del Negocio' => $item->district->name,
                'N_RUC' => $item->ruc ? $item->ruc : '-',
                'Componente' => $item->component->name,
                'Tema' => $item->theme->name,
                'Nro de Reserva / Observacion' => $item->observations,

                'Modalidad' => $item->modality->name
            ];
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
            'Nombre del país de origen',
            'Fecha de Nacimiento',
            'Apellido Paterno del Solicitante (socio o Gte General)',
            'Apellido Materno del Solicitante (socio o Gte General)',
            'Nombres del Solicitante (socio o Gte General)',
            'Genero',
            'Tiene alguna Discapacidad ? (SI / NO)',
            'Telefono',
            'Correo electronico',

            'SUPERVISOR',

            'Región del negocio',
            'Provincia del Negocio',
            'Distrito del Negocio',
            'N_RUC',
            'Componente',
            'Tema',
            'Nro de Reserva / Observacion',

            'Modalidad'
        ];
    }
}
