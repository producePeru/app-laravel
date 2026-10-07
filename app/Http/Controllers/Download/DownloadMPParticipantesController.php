<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\MPAttendance;
use App\Models\MPEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                'search' => $request->input('search'),
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
                    'participant.comercialActivity',
                ])
                ->orderBy('created_at', 'DESC');

            // 4. Aplicar filtro multicampo
            if (! empty($filters['search'])) {
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

            // 6. Mapear filas en el orden de la plantilla actualizada
            $rows = $attendances->map(function ($item, $index) {

                $p = $item->participant;
                $e = $item->event;

                // DURACIÓN DE LA CAPACITACION (HORAS) = hourEnd - hourStart (MPEvent)
                $duracionHoras = null;
                if (! empty($e->hourStart) && ! empty($e->hourEnd)) {
                    try {
                        $minutos = Carbon::parse($e->hourStart)->diffInMinutes(Carbon::parse($e->hourEnd));
                        $duracionHoras = round(abs($minutos) / 60, 2);
                    } catch (\Throwable $th) {
                        $duracionHoras = null;
                    }
                }

                return [
                    $index + 1, // N°
                    $e->date ? Carbon::parse($e->date)->format('d/m/Y') : null, // FECHA CAPACITACIÓN
                    $e->title, // TEMA CAPACITACIÓN
                    $e->component == 1 ? 'GESTIÓN EMPRESARIAL' : 'HABILIDADES PERSONALES', // COMPONENTE DE CAPACITACIÓN
                    $duracionHoras, // DURACIÓN DE LA CAPACITACION (HORAS) = hourEnd - hourStart
                    $e->modality->name ?? null, // MODALIDAD
                    $e->capacitador->name ?? null, // NOMBRE CAPACITADOR
                    $e->city->name ?? null, // REGION DE CAPACITACION (SOLO EN CAPACITACIÓN PRESENCIAL)
                    $e?->district?->name ?? null, // DISTRITO CAPACITACION (SOLO EN CAPACITACIÓN PRESENCIAL)
                    $e->place ?? null, // UBICACIÓN (LOCAL,SEDE)

                    $p->ruc ?? '-', // RUC
                    $p->social_reason, // RAZÓN SOCIAL
                    $p->comercialActivity->name ?? null, // ACTIVIDAD COMERCIAL
                    $p->rubro->name ?? null, // RUBRO DE LA MYPE
                    $p->economicSector->name ?? null, // SECTOR ECONÓMICO
                    $p->city->name ?? null, // REGIÓN (MYPE)
                    $p->province->name ?? null, // PROVINCIA (MYPE)
                    $p->dictrict->name ?? null, // DISTRITO (MYPE)
                    $p->roleCompany->name ?? null, // VINCULACIÓN CON LA MYPE
                    $p->typeDocument->avr ?? null, // TIPO DE DOCUMENTO
                    $p->doc_number, // N DOCUMENTO
                    $p->names, // NOMBRES
                    $p->last_name, // APELLIDO PATERNO
                    $p->middle_name, // APELLIDO MATERNO
                    $p->date_of_birth ? Carbon::parse($p->date_of_birth)->format('d/m/Y') : null, // FECHA NACIMIENTO
                    $p->country->name ?? null, // PAÍS DE NACIMIENTO
                    $p->gender->name ?? null, // GÉNERO
                    $p->sick, // DISCAPACIDAD FÍSICA (SI/NO)
                    $p->degree->name ?? null, // NIVEL EDUCATIVO
                    $p->civilStatus->name ?? null, // ESTADO CIVIL
                    $p->num_soons, // NÚMERO DE HIJOS
                    $p->phone, // CELULAR
                    $p->email, // CORREO ELECTRÓNICO

                    $item->attendance ? '✔' : '✖', // ASISTENCIA (check o x)
                    $item->attendance && $item->updated_at ? Carbon::parse($item->updated_at)->format('d/m/Y H:i:s') : '', // FECHA Y HORA DE ASISTENCIA (updated_at de MPAttendance)
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
                    $sheet->setCellValue("{$col}".($startRow + $i), $value);
                    $col++;
                }
            }

            // 9. StreamedResponse → descarga correcta
            return new StreamedResponse(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="mujer_produce_participantes.xlsx"',
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Error al exportar: '.$e->getMessage(),
            ], 500);
        }
    }
}
