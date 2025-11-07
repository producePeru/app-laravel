<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Illuminate\Database\Eloquent\Builder;
use Box\Spout\Common\Type;

class FormalizationRuc20ExportService
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
            'REGION',
            'PROVINCIA',
            'DISTRITO',
            'DIRECCION',
            'RUC',
            'SECTOR_ECONOMICO',
            'ACTIVIDAD_COMERCIAL',

            'Fecha de Recepcion de Solicitud al PNTE (cuando el  asesor recepciona los documentos COMPLETOS, todo OK)',
            'Fecha de TRAMITE en SID SUNARP o SUNAT',
            'Nombre de Empresa Constituida',
            'Tipo de Regimen Societario',
            '¿Es una sociedad BIC? (SI / NO)',
            'Nro. De Solicitud',

            'NOTARIA',
            'TIPO_APORTE_CAPITAL',
            'MONTO_CAPITAL',
            'MODALIDAD',
        ];

        $writer->addRow(WriterEntityFactory::createRowFromArray($header));

        $globalIndex = 1;

        $query->orderByDesc('id')->chunkById($this->chunkSize, function ($rows) use (&$writer, &$globalIndex) {
            foreach ($rows as $f20) {
                $row = [
                    $globalIndex++,
                    optional($f20->created_at)->format('d/m/Y'),
                    strtoupper(trim(($f20->user->name ?? '') . ' ' . ($f20->user->lastname ?? '') . ' ' . ($f20->user->middlename ?? ''))),
                    $f20->sede->city ?? ($f20->sede->region->name ?? null),
                    $f20->sede->province ?? ($f20->sede->provincia->name ?? null),
                    $f20->sede->district ?? ($f20->sede->distrito->name ?? null),
                    isset($f20->sede->name) ? strtoupper($f20->sede->name) : null,
                    $f20->people->typedocument->avr ?? null,
                    $f20->people->documentnumber ?? null,
                    isset($f20->people->pais->name) ? strtoupper($f20->people->pais->name) : 'PERU',
                    $f20->people->birthday ? \Carbon\Carbon::parse($f20->people->birthday)->format('d/m/Y') : null,
                    strtoupper($f20->people->lastname ?? ''),
                    strtoupper($f20->people->middlename ?? ''),
                    strtoupper($f20->people->name ?? ''),
                    ($f20->people->gender->name ?? '') == 'FEMENINO' ? 'F' : 'M',
                    match (trim(strtolower($f20->people->sick ?? ''))) {
                        'yes' => 'SI',
                        'no'  => 'NO',
                        default => 'PREFIERO NO ESPECIFICAR'
                    },
                    match (trim(strtolower($f20->people->hasSoon ?? ''))) {
                        'si' => 'SI',
                        'no' => 'NO',
                        'na', '' => 'PREFIERO NO ESPECIFICAR',
                        default => 'PREFIERO NO ESPECIFICAR'
                    },
                    $f20->people->phone ?? null,
                    isset($f20->people->email) ? strtolower($f20->people->email) : '-',
                    'PPJJ 20',
                    'MILIAN MELENDEZ ALEJANDRIA',
                    $f20->city->name ?? null,
                    $f20->province->name ?? null,
                    $f20->district->name ?? null,
                    $f20->address ?? null,
                    $f20->mype->ruc ?? null,
                    $f20->economicsector->name ?? null,
                    $f20->comercialactivity->name ?? null,


                    $f20->dateReception ? \Carbon\Carbon::parse($f20->dateReception)->format('d/m/Y') : null,
                    $f20->dateTramite ? \Carbon\Carbon::parse($f20->dateTramite)->format('d/m/Y') : null,
                    strtoupper($f20->nameMype),
                    $f20->regime->name,
                    $f20->isbic,
                    $f20->numbernotary,


                    isset($f20->notary->name) ? strtoupper($f20->notary->name) : null,
                    optional($f20->typecapital)->name,
                    $f20->montocapital,
                    $f20->modality->name ?? null
                ];

                $writer->addRow(WriterEntityFactory::createRowFromArray($row));
            }
        });

        $writer->close();
    }
}
