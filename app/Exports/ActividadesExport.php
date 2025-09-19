<?php

namespace App\Exports;

use App\Models\Attendance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ActividadesExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $year;

    public function __construct($year = 2025)
    {
        $this->year = $year;
    }

    public function collection()
    {
        $attendances = Attendance::with([
            'asesor:id,name,lastname,middlename',
            'pnte:id,name',
            'region:id,name',
            'provincia:id,name',
            'distrito:id,name',
            'attendanceList:id,attendancelist_id,typedocument_id,economicsector_id,gender_id,documentnumber,lastname,middlename,name,phone,email,ruc,comercialName,socialReason',
            'attendanceList.typedocument:id,name',
            'attendanceList.gender:id,avr',
            'attendanceList.economicsector:id,name'
        ])
            ->whereYear('created_at', $this->year)
            ->orderBy('id', 'desc')
            ->get();

        $rows = collect();

        foreach ($attendances as $attendance) {
            // Si NO tiene asistentes, igual agregamos fila vacía
            if ($attendance->attendanceList->isEmpty()) {
                $rows->push([
                    $attendance->asesor ? $attendance->asesor->name . ' ' . $attendance->asesor->lastname . ' ' . $attendance->asesor->middlename : '-',
                    $attendance->pnte->name ?? '-',
                    $attendance->title ?? '-',
                    $attendance->startDate ? Carbon::parse($attendance->startDate)->format('d/m/Y') : '-',
                    $attendance->region->name ?? '-',
                    $attendance->provincia->name ?? '-',
                    $attendance->distrito->name ?? '-',
                    $attendance->address ?? '-',
                    '--',
                    '--',
                    '--',
                    '--',
                    '--',
                    '--',
                    '--',
                    '--',
                    '--',
                    '--',
                    '--',
                ]);
            } else {
                foreach ($attendance->attendanceList as $asistente) {
                    $rows->push([
                        $attendance->asesor ? $attendance->asesor->name . ' ' . $attendance->asesor->lastname . ' ' . $attendance->asesor->middlename : '-',
                        $attendance->pnte->name ?? '-',
                        $attendance->title ?? '-',
                        $attendance->startDate ? Carbon::parse($attendance->startDate)->format('d/m/Y') : '-',
                        $attendance->region->name ?? '-',
                        $attendance->provincia->name ?? '-',
                        $attendance->distrito->name ?? '-',
                        $attendance->address ?? '-',
                        $asistente->typedocument->name ?? '-',
                        $asistente->documentnumber ?? '-',
                        $asistente->lastname ?? '-',
                        $asistente->middlename ?? '-',
                        $asistente->name ?? '-',
                        $asistente->phone ?? '-',
                        $asistente->email ?? '-',
                        $asistente->gender->avr ?? '-',
                        $asistente->ruc ?? '-',
                        $asistente->comercialName ?? '-',
                        $asistente->economicsector->name ?? '-',
                    ]);
                }
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Asesor',
            'Tipo Actividad',
            'Nombre Actividad',
            'Fecha',
            'Región',
            'Provincia',
            'Distrito',
            'Lugar',
            'Tipo Documento',
            'N° Documento',
            'Apellido Paterno',
            'Apellido Materno',
            'Nombre',
            'Celular',
            'Email',
            'Sexo',
            'RUC',
            'Nombre Comercial',
            'Actividad Comercial',
        ];
    }
}
