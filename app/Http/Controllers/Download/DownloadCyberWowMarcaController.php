<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\CyberwowBrand;
use App\Models\CyberwowParticipant;
use App\Models\Fair;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;


class DownloadCyberWowMarcaController extends Controller
{
    public function exportCyberWowMarca($slug)
    {
        try {
            // 🧩 Ruta de la plantilla base
            $templatePath = storage_path('app/plantillas/cybwewow_formato_marca.xlsx');
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // 👤 Usuario autenticado
            $userId = Auth::id();

            // 🔗 LEFT JOIN: Trae todos los participantes (aunque no tengan marca)
            $data = DB::table('cyberwowparticipants as p')
                ->leftJoin('cyberwowbrand as b', 'p.id', '=', 'b.company_id')
                ->leftJoin('images as i256', 'b.logo256_id', '=', 'i256.id')
                ->leftJoin('images as i160', 'b.logo160_id', '=', 'i160.id')
                ->where('p.user_id', $userId)
                ->select(
                    'p.id as participante_id',
                    'p.nombreComercial',
                    'p.razonSocial',
                    'p.documentnumber',
                    'p.name',
                    'p.phone',
                    'p.email',
                    'p.ruc',
                    'b.isService',
                    'b.description as brand_description',
                    'b.url as brand_url',
                    'i256.url as logo256_url',
                    'i160.url as logo160_url'
                )
                ->orderBy('p.id')
                ->get();

            if ($data->isEmpty()) {
                return response()->json(['message' => 'No se encontraron participantes asociados al usuario.'], 404);
            }

            // 🧾 Generar las filas para exportar
            $rows = $data->map(function ($item, $index) {
                return [
                    $index + 1,                                           // IDX
                    $item->ruc ?? '-',
                    $item->nombreComercial ?? '-',
                    $item->razonSocial ?? '-',
                    $item->documentnumber ?? '-',
                    $item->name ?? '-',
                    $item->phone ?? '-',
                    $item->email ?? '-',
                    'emprendedor',                                        // TIPO SPONSOR
                    $item->isService === 's'
                        ? 'Si'
                        : ($item->isService === 'n' ? 'No' : '-'),       // ES SERVICIO?
                    $item->logo256_url ?? '-',                            // LOGOTIPO_256
                    $item->logo160_url ?? '-',                            // LOGOTIPO_160
                    $item->brand_url ?? '-',                              // URL ENLACE
                    $item->brand_description ?? '-',                      // DESCRIPCIÓN
                ];
            });

            // ✍️ Escribir en el Excel a partir de la fila 2
            $startRow = 2;
            foreach ($rows as $i => $row) {
                $col = 'A';
                foreach ($row as $value) {
                    $sheet->setCellValue("{$col}" . ($startRow + $i), $value);
                    $col++;
                }
            }

            // 🚀 Enviar el archivo como descarga
            $fileName = 'cyberwow_marcas_' . $slug . '.xlsx';

            return new StreamedResponse(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 200, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al exportar: ' . $e->getMessage()
            ], 500);
        }
    }


    public function exportCyberWowOfertas($slug)
    {
        try {
            // 📄 Ruta de la plantilla base
            $templatePath = storage_path('app/plantillas/cyberwow_formato_ofertas.xlsx');
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // 👤 Usuario autenticado
            $userId = Auth::id();

            // 🔗 LEFT JOIN PARTICIPANTE + OFERTAS
            $data = DB::table('cyberwowparticipants as p')
                ->leftJoin('cyberwowoffers as o', 'p.id', '=', 'o.company_id')
                ->leftJoin('images as img', 'o.img', '=', 'img.id')
                ->leftJoin('images as imgfull', 'o.imgFull', '=', 'imgfull.id')
                ->where('p.user_id', $userId)
                ->select(
                    'p.id as participante_id',
                    'p.nombreComercial',
                    'o.title',
                    'o.link',
                    'o.category',
                    'o.dia',
                    'o.beneficio',
                    'o.moneda',
                    'o.precioAnterior',
                    'o.precioOferta',
                    'o.descripcion',
                    'img.url as img_url',
                    'imgfull.url as imgfull_url'
                )
                ->orderBy('p.id')
                ->get();

            // Agrupar ofertas por participante
            $grouped = $data->groupBy('participante_id');

            $rows = collect();
            $index = 1;

            foreach ($grouped as $participantId => $offers) {
                $nombreComercial = $offers->first()->nombreComercial ?? '-';

                // Si tiene ofertas
                if ($offers->whereNotNull('title')->count() > 0) {
                    foreach ($offers as $item) {
                        $rows->push([
                            $index++,
                            $nombreComercial,
                            $item->title ?? '-',
                            $item->link ?? '-',
                            $item->category ?? '-',
                            'Oferta',
                            $item->dia ?? '-',
                            $item->beneficio ?? '-',
                            $item->moneda ?? '-',
                            $item->precioAnterior ?? '-',
                            $item->precioOferta ?? '-',
                            $item->descripcion ?? '-',
                            $item->img_url ?? '-',
                            $item->imgfull_url ?? '-',
                        ]);
                    }
                } else {
                    // No tiene ofertas → agregar fila vacía
                    $rows->push([
                        $index++,
                        $nombreComercial,
                        '-',
                        '-',
                        '-',
                        'Oferta',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                    ]);
                }
            }

            // ✍️ Escribir los datos en el Excel desde la fila 2
            $startRow = 2;
            foreach ($rows as $i => $row) {
                $col = 'A';
                foreach ($row as $value) {
                    $sheet->setCellValue("{$col}" . ($startRow + $i), $value);
                    $col++;
                }
            }

            // 🚀 Descargar archivo Excel
            return new StreamedResponse(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 200, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="cyberwow_ofertas.xlsx"',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al exportar: ' . $e->getMessage(),
            ], 500);
        }
    }




    public function exportCyberWowBrandsAll($slug)
    {
        // 1️⃣ Buscar el ID de la feria (Fair)
        $fair = Fair::where('slug', $slug)->first();

        if (!$fair) {
            return response()->json(['error' => 'Fair no encontrada'], 404);
        }

        // 2️⃣ Buscar todas las marcas relacionadas con ese wow_id
        $brands = CyberwowBrand::with(['logo256', 'logo160', 'participant'])
            ->where('wow_id', $fair->id)
            ->get([
                'id',
                'isService',
                'red',
                'description',
                'url',
                'logo256_id',
                'logo160_id',
                'wow_id',
                'user_id',
                'company_id',
            ]);

        if ($brands->isEmpty()) {
            return response()->json(['message' => 'No se encontraron marcas para este CyberWow.'], 404);
        }

        // 3️⃣ Mapear datos incluyendo el nombre del logo y datos de la empresa (participant)
        $data = $brands->map(function ($item) {
            return [
                'RUC' => $item->participant ? $item->participant->ruc : null,
                'MARCA' => $item->participant ? $item->participant->nombreComercial : null,
                'RAZÓN SOCIAL' => $item->participant ? $item->participant->nombreComercial : null,
                'ES SERVICIO?' => $item->isService == 's' ? 'SI' : 'NO',
                'LOGOTIPO (256)' => $item->logo256 ? $item->logo256->name : null,
                'LOGOTIPO (160)' => $item->logo160 ? $item->logo160->name : null,
                'URL ENLACE' => $item->url,
                'DESCRIPCIÓN' => $item->description
                // 'Red' => $item->red,
                // Datos del participante (empresa)
                // Otros IDs de referencia
                // 'Usuario ID' => $item->user_id,
                // 'Empresa ID' => $item->company_id,
            ];
        });

        // 4️⃣ Generar y descargar Excel
        $fileName = 'CyberWow_Brands_' . $fair->slug . '.xlsx';

        return Excel::download(new class($data) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings
        {
            protected $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function collection()
            {
                return collect($this->data);
            }

            public function headings(): array
            {
                return array_keys($this->data->first() ?? []);
            }
        }, $fileName);
    }
}
