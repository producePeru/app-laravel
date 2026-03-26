<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SedAnswersExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
  protected $rows;
  protected $questions;
  protected $fair;

  public function __construct($rows, $questions, $fair)
  {
    $this->rows      = $rows;
    $this->questions = $questions;
    $this->fair      = $fair;
  }

  public function collection()
  {
    return $this->rows;
  }

  public function headings(): array
  {
    return array_merge(
      [
        'TÍTULO',
        'MODALIDAD',
        'FECHA',
        'LUGAR',
        'HORARIO',
        'TIPO ASISTENTE',
        'RUC',
        'RAZÓN SOCIAL',
        'NOMBRE COMERCIAL',
        'SECTOR ECONÓMICO',
        'RUBRO',
        'ACTIVIDAD COMERCIAL',
        'REGIÓN',
        'PROVINCIA',
        'DISTRITO',
        'DIRECCIÓN',
        'TIPO DOCUMENTO',
        'DNI',
        'NOMBRE',
        'GÉNERO',
        'DISCAPACIDAD',
        'TELÉFONO',
        'EMAIL',
        'CARGO',
        'FECHA CUMPLEAÑOS',
        '¿Usa redes sociales para su negocio?',
        '¿Tiene página web o tienda virtual?',
        '¿Tu negocio usa pagos digitales (yape, plim, POS, etc.)?',
        '¿Usas software para contabilidad/inventario?',
        '¿Qué espera aprender en esta capacitación?',
      ],
      $this->questions->toArray()
    );
  }

  public function map($row): array
  {
    $base = [
      $this->fair->title          ?? '',
      $this->fair->modality->name ?? '',
      $this->fair->fecha ? \Carbon\Carbon::parse($this->fair->fecha)->format('d/m/Y') : '',
      $this->fair->place          ?? '',
      $this->fair->hours          ?? '',
      $row['TIPO ASISTENTE']      ?? '',
      $row['RUC']                 ?? '',
      $row['RAZÓN SOCIAL']        ?? '',
      $row['NOMBRE COMERCIAL']    ?? '',
      $row['SECTOR ECONÓMICO']    ?? '',
      $row['RUBRO']               ?? '',
      $row['ACTIVIDAD COMERCIAL'] ?? '',
      $row['REGIÓN']              ?? '',
      $row['PROVINCIA']           ?? '',
      $row['DISTRITO']            ?? '',
      $row['DIRECCIÓN']           ?? '',
      $row['TIPO DOC']            ?? '',
      $row['DNI']                 ?? '',
      $row['NOMBRE']              ?? '',
      $row['GÉNERO']              ?? '',
      $row['DISCAPACIDAD']        ?? '',
      $row['TELÉFONO']            ?? '',
      $row['EMAIL']               ?? '',
      $row['CARGO']               ?? '',
      $row['FECHA CUMPLEAÑOS'] ? \Carbon\Carbon::parse($row['FECHA CUMPLEAÑOS'])->format('d/m/Y') : '',
      $row['PREGUNTA 1']          ?? '',
      $row['PREGUNTA 2']          ?? '',
      $row['PREGUNTA 3']          ?? '',
      $row['PREGUNTA 4']          ?? '',
      $row['PREGUNTA 5']          ?? '',   // 👈 columna 30 = AD
    ];

    // Columnas dinámicas desde AE en adelante
    foreach ($this->questions as $question) {
      $base[] = $row[$question] ?? '';
    }

    return $base;
  }

  public function styles(Worksheet $sheet)
  {
    // Columnas A-Z ancho 16 por defecto
    foreach (range('A', 'Z') as $col) {
      $sheet->getColumnDimension($col)->setWidth(16);
    }

    // 👇 Columnas Z en adelante (preguntas fijas Z=26, AA=27 ... + dinámicas)
    // Preguntas fijas ocupan Z(26), AA(27), AB(28), AC(29), AD(30)
    foreach (range(26, 30) as $colIndex) {
      $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
      $sheet->getColumnDimension($col)->setWidth(26);  // 👈 más anchas
    }

    // Columnas dinámicas desde AE(31) en adelante
    foreach ($this->questions as $index => $question) {
      $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(31 + $index);
      $sheet->getColumnDimension($col)->setWidth(40);  // 👈 más anchas
    }

    $sheet->getDefaultRowDimension()->setRowHeight(30);

    $totalCols = 30 + $this->questions->count();
    $lastCol   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);
    $lastRow   = $this->rows->count() + 1;

    // WrapText en todo el rango
    $sheet->getStyle("A1:{$lastCol}{$lastRow}")
      ->getAlignment()
      ->setWrapText(true)
      ->setVertical('center');

    // Columnas centradas
    foreach (['B', 'C', 'E', 'F', 'G', 'Q', 'S', 'U', 'V', 'R', 'T', 'Y'] as $col) {
      $sheet->getStyle("{$col}2:{$col}{$lastRow}")
        ->getAlignment()
        ->setHorizontal('center');
    }

    // 👇 WrapText extra en columnas anchas (Z en adelante)
    $wrapStartCol = 26;
    $wrapEndCol   = 30 + $this->questions->count();
    for ($i = $wrapStartCol; $i <= $wrapEndCol; $i++) {
      $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
      $sheet->getStyle("{$col}1:{$col}{$lastRow}")
        ->getAlignment()
        ->setWrapText(true)
        ->setVertical('center')
        ->setHorizontal('center');
    }

    return [
      1 => [
        'font' => [
          'bold'  => true,
          'color' => ['rgb' => 'FFFFFF'],
          'size'  => 11,
        ],
        'fill' => [
          'fillType'   => 'solid',
          'startColor' => ['rgb' => '02c494'],
        ],
        'alignment' => [
          'horizontal' => 'center',
          'vertical'   => 'center',
        ],
      ],
    ];
  }
}
