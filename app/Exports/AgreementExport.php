<?php

namespace App\Exports;

use App\Models\Agreement;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class AgreementExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    public function collection()
    {
        $data = Agreement::with([
            'estadoOperatividad',
            'estadoConvenio',
            'region',
            'provincia',
            'distrito',
            'acciones',
            'archivosConvenios'
        ])->join('cities', 'agreements.city_id', '=', 'cities.id'
        )->select('agreements.*')
        ->orderBy('cities.name', 'asc')
        ->get();

        $data->transform(function ($item) {
            $item->initials = implode("\n", json_decode($item->initials));
            return $item;
        });

        $results = $data->map(function ($item, $index) {

            $startDate = Carbon::parse($item->startDate);
            $endDate = Carbon::parse($item->endDate);

            $yearsDifference = $endDate->diffInYears($startDate);
            $remainingMonths = $endDate->diffInMonths($startDate) % 12;
            $remainingDays = $endDate->diffInDays($startDate->copy()->addYears($yearsDifference)->addMonths($remainingMonths));

            if ($yearsDifference > 0) {
                if ($remainingMonths > 0 || $remainingDays > 0) {
                    $yearsDifferenceText = "{$yearsDifference} " . ($yearsDifference > 1 ? 'años' : 'año');
                    $remainingDaysText = $remainingDays > 0 ? " y {$remainingDays} " . ($remainingDays > 1 ? 'días' : 'día') : '';
                    $result = $yearsDifferenceText . $remainingDaysText;
                } else {
                    $result = "{$yearsDifference} " . ($yearsDifference > 1 ? 'años' : 'año');
                }
            } else {
                $result = "{$remainingDays} " . ($remainingDays > 1 ? 'días' : 'día');
            }

            $list = collect($item->acciones)->pluck('description')->map(function ($item) {
                return "- $item";
            })->implode("\n");

            return [
                'N°' => $index + 1,
                'REGIÓN' => $item->region->name,
                'PROVINCIA' => $item->provincia->name,
                'DISTRITO' => $item->distrito->name,
                'DENOMINACIÓN' => $item->denomination,
                'ENTIDAD ALIADA' => $item->alliedEntity,
                'INICIO DE OPERACIONES' => $item->homeOperations,
                'DIRECCIÓN' => $item->address,
                'REFERENCIA' => $item->reference,
                'ASESORES EMPRESARIALES ASIGNADOS' => '-',
                'RESOLUCIÓN DE AUTORIZACIÓN PARA CONDICIÓN DE CDE' => $item->resolution,
                'ENTIDADES' => $item->initials,
                'Inicio Convenio Vigente' => $startDate->format('d/m/Y'),
                'Nro de Años del Convenio' => $result,
                'Fin del Convenio' => $endDate->format('d/m/Y'),
                'ACCIONES' => $list ? $list : ' '
            ];
        });

        return $results;
    }

    public function headings(): array
    {
        return [
            'N°',
            'REGIÓN',
            'PROVINCIA',
            'DISTRITO',
            'DENOMINACIÓN',
            'ENTIDAD ALIADA',
            'INICIO DE OPERACIONES',
            'DIRECCIÓN',
            'REFERENCIA',
            'ASESORES EMPRESARIALES ASIGNADOS',
            'RESOLUCIÓN DE AUTORIZACIÓN PARA CONDICIÓN DE CDE',
            'ENTIDADES',
            'Inicio Convenio Vigente',
            'Nro de Años del Convenio',
            'Fin del Convenio',
            'ACCIONES'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:P1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('305496');
        $sheet->getStyle('A1:P1')->getFont()->getColor()->setARGB('FFFFFF');
        $sheet->getStyle('A1:P1')->getFont()->setBold(true);

        $styles = [];
        $highestRow = $sheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $styles[$row] = ['fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => ($row % 2 == 0) ? 'EEEEEE' : 'FFFFFF']
            ]];
        }

        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 8,
            'C' => 8,
            'D' => 8,
            'E' => 14,
            'F' => 14,
            'G' => 10,
            'H' => 12,
            'I' => 12,
            'J' => 8,
            'K' => 15,
            'L' => 15,
            'M' => 10,
            'N' => 13,
            'O' => 10,
            'P' => 15
        ];
    }
}