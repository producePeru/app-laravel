<?php

namespace App\Exports;

use App\Models\CpRegistros;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Contracts\Queue\ShouldQueue;

class CpRegistrosExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithChunkReading,
    ShouldQueue
{
    public function query()
    {
        return CpRegistros::query()
            ->with([
                'city:id,name',
                'province:id,name',
                'district:id,name',
                'economicsectors:id,name',
                'comercialactivity:id,name',
                'component:id,name',
                'theme:id,name',
                'modality:id,name',
                'people:id,name,lastname,middlename,typedocument_id,documentnumber,country_id,birthday,gender_id,sick,phone,email,created_at',
                'people.typedocument:id,avr',
                'people.pais:id,name',
                'people.gender:id,name',
                'asesor:id,name,lastname,middlename',
                'cde:id,city,province,district',
            ])
            ->orderBy('created_at', 'ASC');
    }

    public function map($item): array
    {
        $people = $item->people;

        return [
            $item->id,
            $item->ruc,
            $item->razonSocial,
            $item->economicsectors?->name,
            $item->cde?->city,
            $item->cde?->province,
            $item->cde?->district,
            trim("{$item->asesor?->name} {$item->asesor?->lastname} {$item->asesor?->middlename}"),
            $people?->typedocument?->avr,
            $people?->documentnumber,
            $people?->pais?->name,
            $people?->birthday ? Carbon::parse($people->birthday)->format('d/m/Y') : null,

            $people->lastname,
            $people->middlename,
            $people->name,
            $people?->gender?->name,
            $people?->sick,
            $people?->phone,
            $people?->email,
            $item->periodo,
            $item->cantidad,
            $item->city?->name,
            $item->province?->name,
            $item->district?->name,
            $item->ubicacion,

            Carbon::parse($item->created_at)->format('d/m/Y'),

            $item->component?->name,
            $item->theme?->name,
            $item->modality?->name,
            mb_strtoupper(Carbon::parse($people->created_at)->locale('es')->translatedFormat('M'), 'UTF-8'),
            Carbon::parse($item->created_at)->format('m'),

            $item->comercialactivity->name,

            Carbon::parse($item->created_at)->format('Y'),
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'RUC',
            'RAZON SOCIAL',
            'SECTOR PRIORIZADO',
            'REGION ASESOR',
            'PROVINCIA ASESOR',
            'DISTRITO ASESOR',
            'ASESOR',
            'TIPO DOC',
            'NRO DOC',
            'PAIS',
            'FECHA NACIMIENTO',

            'APELLIDO_PATERNO',
            'APELLIDO_MATERNO',
            'NOMBRES_MYPE',

            'GENERO',
            'DISCAPACIDAD',
            'TELEFONO',
            'EMAIL',
            'PERIODO',
            'CANTIDAD',
            'MYPE REGION',
            'MYPE PROVINCIA',
            'MYPE DISTRITO',
            'UBICACION',

            'FECHA_ASESORIA_CAPACITACION',


            'COMPONENTE',
            'TEMA',
            'MODALIDAD',
            'MES',
            'MES_NUMERO',
            'ACTIVIDAD',

            'AÑO',
        ];
    }

    public function chunkSize(): int
    {
        return 2000; // óptimo para 100k+
    }
}
