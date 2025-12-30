<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Illuminate\Database\Eloquent\Builder;
use Box\Spout\Common\Type;

class FormalizationRuc10ExportService
{
    protected int $chunkSize = 1000;

    public function generateFromQuery(Builder $query, string $pathFile, string $type = 'xlsx'): void
    {
        $writer = WriterEntityFactory::createWriter(
            strtolower($type) === 'csv' ? Type::CSV : Type::XLSX
        );

        $writer->openToFile($pathFile);

        $header = [
            'INDEX',
            'FECHA',
            'ASESOR',
            'ASESOR_CDE_REGION',
            'ASESOR_CDE_PROVINCIA',
            'ASESOR_CDE_DISTRITO',
            'ASESOR_CDE',
            'EMP_TIPO_DOC',
            'EMP_NUM_DOC',
            'EMP_PAIS',
            'EMP_FEC_NAC',
            'EMP_APE_PATERNO',
            'EMP_APE_MATERNO',
            'EMP_NOMBRE',
            'EMP_GENERO',
            'EMP_DISCAPACIDAD',
            'EMP_HIJOS',
            'EMP_CELULAR',
            'EMP_CORREO',
            'TIPO_FORMALIZACION',
            'SUPERVISOR',
            'CIUDAD',
            'PROVINCIA',
            'DISTRITO',
            'DIRECCION',
            'RUC',
            'SECTOR_ECONOMICO',
            'ACTIVIDAD_COMERCIAL',
            'DETALLE_TRAMITE',
            'MODALIDAD'
        ];

        $writer->addRow(WriterEntityFactory::createRowFromArray($header));

        $globalIndex = 1;

        $query->orderByDesc('id')->chunkById($this->chunkSize, function ($rows) use (&$writer, &$globalIndex) {
            foreach ($rows as $f10) {
                $row = [
                    $globalIndex++,
                    optional($f10->created_at)->format('d/m/Y'),
                    strtoupper(trim(($f10->user->name ?? '') . ' ' . ($f10->user->lastname ?? '') . ' ' . ($f10->user->middlename ?? ''))),
                    $f10->sede->city ?? ($f10->sede->region->name ?? null),
                    $f10->sede->province ?? ($f10->sede->provincia->name ?? null),
                    $f10->sede->district ?? ($f10->sede->distrito->name ?? null),
                    isset($f10->sede->name) ? strtoupper($f10->sede->name) : null,
                    $f10->people->typedocument->avr ?? null,
                    $f10->people->documentnumber ?? null,
                    isset($f10->people->pais->name) ? strtoupper($f10->people->pais->name) : 'PERU',
                    $f10->people->birthday ? \Carbon\Carbon::parse($f10->people->birthday)->format('d/m/Y') : null,
                    strtoupper($f10->people->lastname ?? ''),
                    strtoupper($f10->people->middlename ?? ''),
                    strtoupper($f10->people->name ?? ''),
                    ($f10->people->gender->name ?? '') == 'FEMENINO' ? 'F' : 'M',
                    match (trim(strtolower($f10->people->sick ?? ''))) {
                        'yes' => 'SI',
                        'no'  => 'NO',
                        default => 'PREFIERO NO ESPECIFICAR'
                    },
                    match (trim(strtolower($f10->people->hasSoon ?? ''))) {
                        'si' => 'SI',
                        'no' => 'NO',
                        'na', '' => 'PREFIERO NO ESPECIFICAR',
                        default => 'PREFIERO NO ESPECIFICAR'
                    },
                    $f10->people->phone ?? null,
                    isset($f10->people->email) ? strtolower($f10->people->email) : '-',
                    'PPNN 10',
                    'MILIAN MELENDEZ ALEJANDRIA',
                    $f10->city->name ?? null,
                    $f10->province->name ?? null,
                    $f10->district->name ?? null,
                    $f10->address ?? null,
                    $f10->ruc ?? null,
                    $f10->economicsector->name ?? null,
                    $f10->comercialactivity->name ?? null,
                    $f10->detailprocedure->name ?? null,
                    $f10->modality->name ?? null,
                ];

                $writer->addRow(WriterEntityFactory::createRowFromArray($row));
            }
        });

        $writer->close();
    }
}
