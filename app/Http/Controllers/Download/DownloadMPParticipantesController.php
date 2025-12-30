<?php

namespace App\Http\Controllers\Download;

use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Http\Controllers\Controller;
use App\Models\MPAttendance;
use Illuminate\Http\Request;
use App\Models\MPEvent;
use Carbon\Carbon;


class DownloadMPParticipantesController extends Controller
{
    // Lista de asistentes de mujer produce

    public function mpAttendanceExport(Request $request, $slug)
    {
        try {

            // 1. Buscar evento
            $event = MPEvent::where('slug', $slug)->firstOrFail();

            // 2. Filtro único
            $filters = [
                'search' => $request->input('search')
            ];

            // 3. Query base
            $query = MPAttendance::where('event_id', $event->id)
                ->with([
                    'event.capacitador',
                    'event.modality',
                    'event.city',
                    'event.province',
                    'event.district',
                    'participant.city',
                    'participant.province',
                    'participant.dictrict',
                    'participant.typeDocument',
                    'participant.country',
                    'participant.civilStatus',
                    'participant.gender',
                    'participant.degree',
                    'participant.roleCompany',

                    'participant.economicSector',
                    'participant.rubro',
                    'participant.comercialActivity'
                ])
                ->orderBy('created_at', 'DESC');

            // 4. Aplicar filtro multicampo
            if (!empty($filters['search'])) {
                $search = $filters['search'];

                $query->whereHas('participant', function ($q) use ($search) {
                    $q->where('ruc', 'LIKE', "%{$search}%")
                        ->orWhere('social_reason', 'LIKE', "%{$search}%")
                        ->orWhere('doc_number', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            // 5. Ejecutar consulta
            $attendances = $query->get();

            // 6. Mapear filas según el ejemplo
            $rows = $attendances->map(function ($item, $index) {

                $p = $item->participant;

                return [
                    $index + 1,
                    // $item->event->title,
                    Carbon::parse($item->event->date)->format('d/m/Y'),
                    $item->event->capacitador->name ?? null,

                    $p->ruc,

                    $p->city->name ?? null,
                    $p->province->name ?? null,
                    $p->dictrict->name ?? null,

                    $p->typeDocument->avr ?? null,
                    $p->doc_number,
                    $p->country->name ?? null,
                    Carbon::parse($p->date_of_birth)->format('d/m/Y'),
                    $p->names,
                    $p->last_name,
                    $p->middle_name,
                    $p->roleCompany->name ?? null,
                    $p->civilStatus->name ?? null,
                    $p->num_soons,
                    $p->gender->name ?? null,
                    $p->sick,
                    $p->degree->name ?? null,
                    $p->phone,
                    $p->email,

                    $p->comercialActivity->name ?? null,
                    $p->rubro->name ?? null,
                    $p->economicSector->name ?? null,
                    $p->social_reason,

                    $item->event->modality->name,

                    $item->event->city->name ?? null,
                    $item->event->province->name ?? null,
                    $item->event->moddistrictality->name ?? null,
                    $item->event->place ?? null,
                    $item->event->hours ?? null,
                    $item->event->component == 1 ? 'GESTIÓN EMPRESARIAL' : 'HABILIDADES PERSONALES',
                    $item->event->title,

                    $item->attendance ? '✔' : '✖',
                    $item->created_at ? Carbon::parse($item->created_at)->format('d/m/Y H:i:s') : '',
                ];
            });

            // 7. Cargar plantilla Excel
            $templatePath = storage_path('app/plantillas/mujer_produce_participantes.xlsx');
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // 8. Rellenar datos (empezando en la fila 2)
            $startRow = 2;

            foreach ($rows as $i => $row) {
                $col = 'A';
                foreach ($row as $value) {
                    $sheet->setCellValue("{$col}" . ($startRow + $i), $value);
                    $col++;
                }
            }

            // 9. StreamedResponse → descarga correcta
            return new StreamedResponse(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 200, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="mujer_produce_participantes.xlsx"',
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'message' => 'Error al exportar: ' . $e->getMessage()
            ], 500);
        }
    }
}
