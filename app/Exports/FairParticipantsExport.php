<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class FairParticipantsExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{

    protected $result;

    public function __construct(Collection $result)
    {
        $this->result = $result;
    }

    public function title(): string
    {
        return 'Participantes en Feria';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            // 'B' => 12,
            'C' => 13,
            'D' => 12,
            'AB' => 12,
            'AC' => 22,
            'AD' => 12
            // 'E' => 23,
            // 'F' => 23,
            // 'G' => 20,
            // 'H' => 10,
            // 'I' => 11,
            // 'J' => 14,
            // 'K' => 22,
            // 'L' => 14,
            // 'M' => 17,
            // 'N' => 20,
            // 'O' => 12,

            // 'P' => 15,
            // 'Q' => 15,
            // 'R' => 15,

            // 'S' => 23,
            // 'T' => 23,
            // 'U' => 23,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:V1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00b0f0');
        $sheet->getStyle('AL1:AV1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ebf8a4');
        // $sheet->getStyle('D1:J1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00b0f0');
        // $sheet->getStyle('K1:M1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('2f5496');
        // $sheet->getStyle('N1:Q1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00b0f0');
        // $sheet->getStyle('R1:S1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ff0000');
        // $sheet->getStyle('T1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('faad14');


        $sheet->getStyle('A1:V1')->getFont()->getColor()->setARGB('FFFFFF');

        $sheet->getStyle('A1:AV1')->getFont()->setBold(true);


        // $sheet->getStyle('C1')->getAlignment()->setWrapText(true);
        // $sheet->getStyle('H1')->getAlignment()->setWrapText(true);
        // $sheet->getStyle('I1')->getAlignment()->setWrapText(true);
        // $sheet->getStyle('J1')->getAlignment()->setWrapText(true);
        // $sheet->getStyle('K1')->getAlignment()->setWrapText(true);
        // $sheet->getStyle('M1')->getAlignment()->setWrapText(true);
        // $sheet->getStyle('N1')->getAlignment()->setWrapText(true);







        $sheet->getRowDimension(1)->setRowHeight(32); // Ajusta el número 20 a la altura deseada


        $sheet->getStyle('A1:AV1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);


    }
    public function collection()
    {
        return $this->result;
    }

    public function headings(): array
    {
        return [
            'No',
            '¿PARTICIPA?',
            'FECHA DE POSTULACIÓN',
            'RUC',
            'NOMBRE COMERCIAL',
            'RAZÓN SOCIAL',
            'SECTOR EMPRESARIAL',
            // '% PLANTA PROPIA',
            // '% MAQUILA',
            // 'PRODUCCIÓN MENSUAL',
            // '¿PERTENECE A ALGÚN GREMIO EMPRESARIAL?',
            // 'NOMBRE DEL GREMIO EMPRESARIAL',
            // '¿TIENE PUNTOS DE VENTA?',
            // '¿CUÁNTOS PUNTOS DE VENTA TIENE?',
            'REGIÓN',
            'PROVINCIA',
            'DISTRITO',
            'DIRECCIÓN',
            'PÁGINA WEB',
            'FACEBOOK',
            'INSTAGRAM',
            'DESCRIPCIÓN',

            'TIPO DE DOCUMENTO',
            'NUM. DOCUMENTO',
            'APELLIDO PATERNO',
            'APELLIDO MATERNO',
            'NOMBRES',
            'CELULAR',
            'EMAIL',
            'FECHA DE NACIEMIENTO',
            '¿TIENE ALGUNA DISCAPACIDAD?',
            'NACIONALIDAD',
            'REGIÓN',
            'PROVINCIA',
            'DISTRITO',
            'DIRECCIÓN',
            'GENERO',

            'Cuenta con el servicio de pagos electrónicos mediante POS (Tarjeta de crédito y/o Débito)',
            'Su negocio cuenta con el servicio de pagos por medio de monederos electrónicos (Yape, PLIM, etc.)',
            'Su negocio realiza ventas a través de tiendas virtuales, ya sea por medio de página web (Mercado Libre, Amazon, Redes Sociales, Whatsapp, etc)',
            'Su negocio realiza entregas a domicilio (delivery)',
            'Su negocio emite factura electrónica',
            'Se ha formalizado a través del Programa Nacional Tu Empresa',
            '¿Su marca se encuentra registrada en INDECOPI?',
            // 'Ha participado en alguna feria virtual/presencial y/o rueda de negocios en los últimos años? Especificar',
            // 'Evento donde participó',
            // 'Ha participado en algún servicio que ofrece PRODUCE (taller, capacitación, o asistencia técnica)',
            // 'Ingrese nombre del servicio que participó en PRODUCE (taller, capacitación, o asistencia técnica)'
        ];
    }
}
