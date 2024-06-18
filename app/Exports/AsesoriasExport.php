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

            'G' => 4,
            'H' => 10,
            'I' => 6,
            'J' => 11,
            'K' => 15,
            'L' => 15,
            'M' => 15,
            'N' => 4,
            'O' => 4,
            'P' => 4,
            'Q' => 11,
            'R' => 20,

            'S' => 14,
            'T' => 14,
            'U' => 14,
            'V' => 12,
            'W' => 12,
            'X' => 12,
            'Y' => 15,
            'Z' => 20,
            'AA' => 10,
            'AB' => 12,
            'AC' => 12
        ];
    }

    public function title(): string
    {
        return 'Asesorías';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('002060');
        $sheet->getStyle('G1:R1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffc000');
        $sheet->getStyle('S1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('bdd6ee');
        $sheet->getStyle('T1:AA1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00b050');
        $sheet->getStyle('AB1:AC1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('000000');


        $sheet->getStyle('I1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffc000');
        $sheet->getStyle('A1:F1')->getFont()->getColor()->setARGB('FFFFFF');
        $sheet->getStyle('S1:AD1')->getFont()->getColor()->setARGB('FFFFFF');
        $sheet->getStyle('A1:AC1')->getFont()->setBold(true);
    }

    public function collection()
    {

        $userRole = getUserRole();
        $roleIdArray = $userRole['role_id'];
        $user_id = $userRole['user_id'];


        if (in_array(1, $roleIdArray) || $user_id === 1) {
            $query = Advisory::descargaExcelAsesorias([
                'dateStart' => $this->dateStart,
                'dateEnd' => $this->dateEnd,
            ]);
        }

        if (in_array(2, $roleIdArray)) {
            $query = Advisory::ByUserId($user_id)->descargaExcelAsesorias([
                'dateStart' => $this->dateStart,
                'dateEnd' => $this->dateEnd,
            ]);
        }

        $results = $query->map(function ($item, $index) {

            $asesor = $item->supervisado ? $item->supervisado->supervisadoUser->profile : $item->asesorsupervisor;
            $supervisador = $item->supervisor ? $item->supervisor->supervisorUser->profile : $item->asesorsupervisor;
            $solicitante = $item->people;

            $nombreCompleto = strtoupper(
                ($asesor->name ?? '') . ' ' .
                ($asesor->lastname ?? '') . ' ' .
                ($asesor->middlename ?? '')
            );
            $regionCDE = $asesor->cde && $asesor->cde->city ? $asesor->cde->city : null;
            $provinciaCDE = $asesor->cde && $asesor->cde->province ? $asesor->cde->province : null;
            $distritoCDE = $asesor->cde && $asesor->cde->district ? $asesor->cde->district : null;

            return array_merge([
                'No' => $index + 1,
                'Fecha de Registro' => Carbon::parse($item->created_at)->format('d/m/Y'),
                'Asesor (a) - Nombre Completo' => trim($nombreCompleto),
                'Región del CDE del Asesor' => $regionCDE,
                'Provincia del CDE del Asesor' => $provinciaCDE,
                'Distrito del CDE del Asesor' => $distritoCDE,
            ], $solicitante ? [
                'Tipo de Documento de Identidad' => $solicitante->typedocument->avr,
                'Número de Documento de Identidad' => $solicitante->documentnumber,
                'Nombre del país de origen' => $solicitante->typedocument->avr === 'DNI' ? 'PERÚ' : strtoupper($solicitante->country),
                'Fecha de Nacimiento' => date('d/m/Y', strtotime($solicitante->birthday)),
                'Apellido Paterno del Solicitante (socio o Gte General)' => strtoupper($solicitante->lastname),
                'Apellido Materno del Solicitante (socio o Gte General)' => strtoupper($solicitante->middlename),
                'Nombres del Solicitante (socio o Gte General)' => strtoupper($solicitante->name),
                'Genero' => $solicitante->gender->name === 'Masculino' ? 'M' : 'F',
                'Tiene alguna Discapacidad ? (SI / NO)' => $solicitante->sick == 'no' ? 'NO' : 'SI',
                '¿Tiene hijos?  (SI / NO)' => $solicitante->hasSoon,
                'Telefono' => $solicitante->phone ? $solicitante->phone : '-',
                'Correo electrónico o "NO TIENE"' => $solicitante->email ? $solicitante->email : 'NO TIENE',
            ] : [], [
                'SUPERVISOR' => strtoupper($supervisador->name . ' ' . $supervisador->lastname . ' ' . $supervisador->middlename),
                'Región del negocio' => $item->city ? $item->city->name : '-',
                'Provincia del Negocio' => $item->province ? $item->province->name : '-',
                'Distrito del Negocio' => $item->district ? $item->district->name : '-',
                'N_RUC' => $item->ruc ? $item->ruc : '-',
                'Sector Económico' => $item->economicsector ? strtoupper($item->economicsector->name) : '-',
                'Actividad Comercial Inicial' => $item->comercialactivity ? strtoupper($item->comercialactivity->name) : '-',
                'Componente' => $item->component ? strtoupper($item->component->name) : '-',
                'Tema' => $item->theme->name ? strtoupper($item->theme->name) : '-',
                'Nro de Reserva / Observacion' => $item->observations ? $item->observations : '-',
                'Modalidad' => $item->modality->name,
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
            'Nombre del país de origen',
            'Fecha de Nacimiento',
            'Apellido Paterno del Solicitante (socio o Gte General)',
            'Apellido Materno del Solicitante (socio o Gte General)',
            'Nombres del Solicitante (socio o Gte General)',
            'Genero',
            'Tiene alguna Discapacidad ? (SI / NO)',
            '¿Tiene hijos?  (SI / NO)',
            'Telefono',
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
