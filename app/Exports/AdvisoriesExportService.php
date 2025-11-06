<?php

namespace App\Exports;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Box\Spout\Common\Entity\Style\Style;
use Box\Spout\Common\Type;

class AdvisoriesExportService
{
  protected int $chunkSize = 1000;

  /**
   * Genera el archivo (streaming) usando un query ya preparado.
   * @param Builder $query    Query Eloquent con filtros aplicados.
   * @param string $pathFile  Ruta completa donde escribir (ej: public/exports/asesorias_xxx.xlsx)
   * @param string $type      'xlsx' o 'csv'
   */
  public function generateFromQuery(Builder $query, string $pathFile, string $type = 'xlsx'): void
  {
    // Crear writer (Spout)
    $writer = WriterEntityFactory::createWriter(
      strtolower($type) === 'csv' ? Type::CSV : Type::XLSX
    );

    $writer->openToFile($pathFile);

    // Header - ajusta según tus columnas
    $header = [
      'INDEX',
      'FECHA',
      'ASESOR',
      'ASESOR_CDE_REGION',
      'ASESOR_CDE_PROVINCIA',
      'ASESOR_CDE_DISTITO',
      'ASESOR_CDE',
      'EMP_DOCUMENTO_TIPO',
      'EMP_DOCUMENT0_NUMERO',
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
      'SUPERVISOR',
      'CIUDAD',
      'REGIÓN',
      'DISTRITO',
      'RUC',
      'SECTORI_ECONOMICO',
      'ACTIVIDAD_COMERCIAL',
      'COMPONENTE',
      'TEMA',
      'OBSERVATIONS',
      'MODALITY'
    ];

    $rowHeader = WriterEntityFactory::createRowFromArray($header);
    $writer->addRow($rowHeader);

    $total = (clone $query)->count();
    $globalIndex = 1;

    // Usar chunkById para evitar problemas con order/offsets
    $query->orderByDesc('id') // chunkById needs an orderBy id or primary key ordering
      ->chunkById($this->chunkSize, function ($rows) use (&$writer, &$globalIndex) {
        foreach ($rows as $advisory) {
          // Aquí replicas la lógica de mapeo que ya tienes, pero ligero
          $row = [
            $globalIndex++,
            optional($advisory->created_at)->format('d/m/Y'),
            strtoupper(trim(($advisory->user->name ?? '') . ' ' . ($advisory->user->lastname ?? '') . ' ' . ($advisory->user->middlename ?? ''))),
            $advisory->sede->city ?? ($advisory->sede->region->name ?? null),
            $advisory->sede->province ?? ($advisory->sede->provincia->name ?? null),
            $advisory->sede->district ?? ($advisory->sede->distrito->name ?? null),
            isset($advisory->sede->name) ? strtoupper($advisory->sede->name) : null,
            $advisory->people->typedocument->avr ?? null,
            $advisory->people->documentnumber ?? null,
            isset($advisory->people->pais->name) ? strtoupper($advisory->people->pais->name) : 'PERU',
            $advisory->people->birthday ? \Carbon\Carbon::parse($advisory->people->birthday)->format('d/m/Y') : null,
            strtoupper($advisory->people->lastname ?? ''),
            strtoupper($advisory->people->middlename ?? ''),
            strtoupper($advisory->people->name ?? ''),
            ($advisory->people->gender->name ?? '') == 'FEMENINO' ? 'F' : 'M',
            match (trim(strtolower($advisory->people->sick ?? ''))) {
              'yes' => 'SI',
              'no' => 'NO',
              default => 'PREFIERO NO ESPECIFICAR'
            },
            match (trim(strtolower($advisory->people->hasSoon ?? ''))) {
              'si' => 'SI',
              'no' => 'NO',
              'na', '' => 'PREFIERO NO ESPECIFICAR',
              default => 'PREFIERO NO ESPECIFICAR'
            },
            $advisory->people->phone ?? null,
            isset($advisory->people->email) ? strtolower($advisory->people->email) : '-',
            'MILIAN MELENDEZ ALEJANDRIA',
            $advisory->city->name ?? null,
            $advisory->province->name ?? null,
            $advisory->district->name ?? null,
            $advisory->ruc ?? null,
            $advisory->economicsector->name ?? null,
            $advisory->comercialactivity->name ?? null,
            $advisory->component->name ?? null,
            isset($advisory->theme->name) ? strtoupper($advisory->theme->name) : null,
            $advisory->observations ? 'Z' . $advisory->observations : '-',
            $advisory->modality->name ?? null,
          ];

          $writer->addRow(WriterEntityFactory::createRowFromArray($row));
        }
      });

    $writer->close();
  }
}
