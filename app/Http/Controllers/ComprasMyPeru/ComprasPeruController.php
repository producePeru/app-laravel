<?php

namespace App\Http\Controllers\ComprasMyPeru;

use App\Http\Controllers\Controller;
use App\Mail\EmailParticipacionComprasMyPeruMail;
use App\Models\ActividadPnte;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ComprasPeruController extends Controller
{
    public function cursos(): JsonResponse
    {
        $actividades = ActividadPnte::with([
            'tipoActividad:id,name',
            'nombreActividad:id,name',
            'regionRel:id,name',
            'provinciaRel:id,name',
            'distritoRel:id,name',
            'representante:id,name,lastname',
            'modalidad:id,name',
        ])
            ->where('unidad', 1)
            ->where('tipo_actividad_id', 1)
            ->where('nombre_actividad_id', 6)
            ->get();

        $hoy = Carbon::today();

        $formatted = $actividades
            ->map(function ($actividad) use ($hoy) {

                // Solo fechas de hoy en adelante (se descartan las pasadas)
                $fechasProximas = collect($actividad->fechas ?? [])
                    ->map(function ($fecha) {
                        if (is_array($fecha)) {
                            $fecha = $fecha['fecha'] ?? $fecha['date'] ?? null;
                        }

                        if (! is_string($fecha) || trim($fecha) === '') {
                            return null;
                        }

                        try {
                            return Carbon::parse($fecha)->toDateString();
                        } catch (\Throwable $e) {
                            return null;
                        }
                    })
                    ->filter()
                    ->unique()
                    ->filter(fn ($fecha) => Carbon::parse($fecha)->gte($hoy))
                    ->sort()
                    ->values();

                // Si todas sus fechas ya pasaron, no se lista
                if ($fechasProximas->isEmpty()) {
                    return null;
                }

                return [
                    'id' => $actividad->id,
                    'slug' => $actividad->slug,
                    'activo' => (int) $actividad->activo,
                    'fechas' => $fechasProximas->all(),
                    'cantidad_dias' => $actividad->cantidad_dias,
                    'tipo_actividad' => $actividad->tipoActividad->name ?? null,
                    'nombre_actividad' => $actividad->nombreActividad->name ?? null,
                    'title' => $actividad->tema,
                    'tema' => $actividad->tema,
                    'modalidad' => $actividad->modalidad->name ?? null,
                    'descripcion' => $actividad->descripcion,
                    'region' => $actividad->regionRel->name ?? $actividad->region,
                    'provincia' => $actividad->provinciaRel->name ?? $actividad->provincia,
                    'distrito' => $actividad->distritoRel->name ?? $actividad->distrito,
                    'direccion' => $actividad->lugar,
                    'horario' => $actividad->horario,
                    'entidad_organizadora' => $actividad->entidad_organizadora,
                    'entidad_aliada' => $actividad->entidad_aliada,
                    'mypes_beneficiadas' => $actividad->mypes_beneficiadas,
                    'total_participantes' => $actividad->total_participantes,
                    'link' => $actividad->link,

                    'asesor' => $actividad->representante
                        ? mb_strtoupper(
                            trim("{$actividad->representante->name} {$actividad->representante->lastname}"),
                            'UTF-8'
                        )
                        : null,
                ];
            })
            ->filter()
            ->sortBy(fn ($item) => $item['fechas'][0])
            ->values();

        return response()->json([
            'status' => 200,
            'message' => 'Compras MYPErú obtenidos correctamente.',
            'data' => $formatted,
        ]);
    }

    public function detalle($slug): JsonResponse
    {
        $hoy = Carbon::today();

        $actividad = ActividadPnte::with([
            'tipoActividad:id,name',
            'nombreActividad:id,name',
            'regionRel:id,name',
            'provinciaRel:id,name',
            'distritoRel:id,name',
            'representante:id,name,lastname',
            'modalidad:id,name',
        ])
            ->where('unidad', 1)
            ->where('tipo_actividad_id', 1)
            ->where('nombre_actividad_id', 6)
            ->where('slug', $slug)
            ->first();

        if (! $actividad) {
            return response()->json([
                'status' => 404,
                'message' => 'Evento no encontrado.',
                'data' => null,
            ], 404);
        }

        $fechasProximas = collect($actividad->fechas ?? [])
            ->map(function ($fecha) {
                if (is_array($fecha)) {
                    $fecha = $fecha['fecha'] ?? $fecha['date'] ?? null;
                }
                if (! is_string($fecha) || trim($fecha) === '') {
                    return null;
                }
                try {
                    return Carbon::parse($fecha)->toDateString();
                } catch (\Throwable $e) {
                    return null;
                }
            })
            ->filter()
            ->unique()
            ->filter(fn ($fecha) => Carbon::parse($fecha)->gte($hoy))
            ->sort()
            ->values();

        $data = [
            'id' => $actividad->id,
            'slug' => $actividad->slug,
            'activo' => (int) $actividad->activo,
            'fechas' => $fechasProximas->all(),
            'cantidad_dias' => $actividad->cantidad_dias,
            'tipo_actividad' => $actividad->tipoActividad->name ?? null,
            'nombre_actividad' => $actividad->nombreActividad->name ?? null,
            'title' => $actividad->tema,
            'tema' => $actividad->tema,
            'modalidad' => $actividad->modalidad->name ?? null,
            'descripcion' => $actividad->descripcion,
            'region' => $actividad->regionRel->name ?? $actividad->region,
            'provincia' => $actividad->provinciaRel->name ?? $actividad->provincia,
            'distrito' => $actividad->distritoRel->name ?? $actividad->distrito,
            'direccion' => $actividad->lugar,
            'horario' => $actividad->horario,
            'entidad_organizadora' => $actividad->entidad_organizadora,
            'entidad_aliada' => $actividad->entidad_aliada,
            'mypes_beneficiadas' => $actividad->mypes_beneficiadas,
            'total_participantes' => $actividad->total_participantes,
            'link' => $actividad->link,
            'asesor' => $actividad->representante
                ? mb_strtoupper(trim("{$actividad->representante->name} {$actividad->representante->lastname}"), 'UTF-8')
                : null,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'Detalle de Compras MYPErú obtenido correctamente.',
            'data' => $data,
        ]);
    }

    public function emailParticipacionComprasperu(Request $request): JsonResponse
    {
        $slug = $request->input('slug');
        $correo = $request->input('correo') ?? $request->input('correo_electronico');
        $nombres = $request->input('nombres');

        if (! $slug || ! $correo || ! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'status' => 422,
                'message' => 'Los parámetros slug y correo son obligatorios y el correo debe ser válido.',
            ], 422);
        }

        $actividad = ActividadPnte::with('modalidad:id,name')->where('slug', $slug)->first();

        if (! $actividad) {
            return response()->json([
                'status' => 404,
                'message' => 'Capacitación no encontrada.',
            ], 404);
        }

        try {
            $this->enviarEmailParticipacionCooperativasUgseco($actividad, $correo, $nombres);

            return response()->json([
                'status' => 200,
                'message' => 'Correo enviado correctamente.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al enviar correo de participación cooperativas UGSEco: '.$e->getMessage(), [
                'slug' => $slug,
                'correo' => $correo,
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'No se pudo enviar el correo.',
            ], 500);
        }
    }

    private function enviarEmailParticipacionCooperativasUgseco(
        ActividadPnte $actividad,
        ?string $correo,
        ?string $nombres = null
    ): void {
        if (! $correo || ! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('El correo del participante no es válido.');
        }

        Mail::mailer('hostinger3k')
            ->to($correo)
            ->send(new EmailParticipacionComprasMyPeruMail(
                $nombres ?: null,
                $this->datosCorreoCapacitacionCooperativas($actividad)
            ));
    }

    private function datosCorreoCapacitacionCooperativas(ActividadPnte $actividad): array
    {
        $fechas = collect($actividad->fechas ?? [])
            ->filter()
            ->sort()
            ->values();

        $fecha = $fechas->first();

        $fechaFormateada = $fecha
            ? ucfirst(Carbon::parse($fecha)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY'))
            : 'Por confirmar';

        $horario = $actividad->horario;
        $link = $actividad->link;

        $modalidad = $actividad->modalidad->name ?? null;
        $esVirtual = strtoupper(trim((string) $modalidad)) === 'VIRTUAL';
        $esPresencial = strtoupper(trim((string) $modalidad)) === 'PRESENCIAL';

        if (is_array($horario)) {
            $partes = [];

            foreach ($horario as $item) {
                if (is_string($item)) {
                    $partes[] = trim($item);

                    continue;
                }

                if (! is_array($item)) {
                    continue;
                }

                $inicio = $item['horaInicio'] ?? $item['hora_inicio'] ?? null;
                $fin = $item['horaFin'] ?? $item['hora_fin'] ?? null;
                $parte = trim(implode(' - ', array_filter([$inicio, $fin])));

                if ($parte !== '') {
                    $partes[] = $parte;
                }

                if (! $link) {
                    $link = $item['meetLink'] ?? $item['meet_link'] ?? null;
                }
            }

            $horario = implode(' / ', $partes);
        }

        if (! is_string($horario)) {
            $horario = '';
        }

        return [
            'tema' => $actividad->tema ?: 'Capacitación Compras MYPErú',
            'fecha' => $fechaFormateada,
            'horario' => trim($horario) ?: 'Por confirmar',
            'modalidad' => $modalidad ? ucfirst(strtolower($modalidad)) : 'Por confirmar',
            'direccion' => $esPresencial ? ($actividad->lugar ?: null) : null,
            'link' => $esVirtual ? ($link ?: null) : null,
            'asistencia_url' => 'https://capacitaciones.soportepnte.com/ugseco/asistencia?slug='.urlencode((string) $actividad->slug),
        ];
    }
}
