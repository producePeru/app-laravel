<?php

namespace App\Http\Controllers\ComprasMyPeru;

use App\Http\Controllers\Controller;
use App\Mail\EmailParticipacionComprasMyPeruMail;
use App\Models\ActividadPnte;
use App\Models\EmpresarioActividad;
use App\Models\PntTest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

                // Obtener fechas desde campo fechas y desde horario
                $fechasDesdeCampo = collect($actividad->fechas ?? [])
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
                    ->filter();

                $fechasDesdeHorario = collect($actividad->horario ?? [])
                    ->when(is_array($actividad->horario), function ($c) {
                        return $c->map(function ($item) {
                            if (! is_array($item)) {
                                return null;
                            }
                            $f = $item['fecha'] ?? null;
                            if (! $f) {
                                return null;
                            }
                            try {
                                return Carbon::parse($f)->toDateString();
                            } catch (\Throwable $e) {
                                return null;
                            }
                        })->filter();
                    });

                $todasFechas = $fechasDesdeCampo->merge($fechasDesdeHorario)->unique();

                $fechasProximas = $todasFechas
                    ->filter(function ($fecha) use ($hoy) {
                        try {
                            return Carbon::parse($fecha)->gte($hoy);
                        } catch (\Throwable $e) {
                            return false;
                        }
                    })
                    ->sort()
                    ->values();

                // Si todas sus fechas ya pasaron, no se lista
                if ($fechasProximas->isEmpty()) {
                    return null;
                }

                // Formatear horario a string legible
                $horarioRaw = $actividad->horario;
                $horarioFormateado = '';
                if (is_array($horarioRaw)) {
                    $partes = [];
                    foreach ($horarioRaw as $item) {
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
                    }
                    $horarioFormateado = implode(' / ', $partes);
                } elseif (is_string($horarioRaw)) {
                    $horarioFormateado = $horarioRaw;
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
                    'horario' => $horarioFormateado,
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

        $fechasDesdeCampo = collect($actividad->fechas ?? [])
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
            ->filter();

        $fechasDesdeHorario = collect($actividad->horario ?? [])
            ->when(is_array($actividad->horario), function ($c) {
                return $c->map(function ($item) {
                    if (! is_array($item)) {
                        return null;
                    }
                    $f = $item['fecha'] ?? null;
                    if (! $f) {
                        return null;
                    }
                    try {
                        return Carbon::parse($f)->toDateString();
                    } catch (\Throwable $e) {
                        return null;
                    }
                })->filter();
            });

        $todasFechas = $fechasDesdeCampo->merge($fechasDesdeHorario)->unique();

        $fechasProximas = $todasFechas
            ->filter(function ($fecha) use ($hoy) {
                try {
                    return Carbon::parse($fecha)->gte($hoy);
                } catch (\Throwable $e) {
                    return false;
                }
            })
            ->sort()
            ->values();

        // Formatear horario
        $horarioRaw = $actividad->horario;
        $horarioFormateado = '';
        if (is_array($horarioRaw)) {
            $partes = [];
            foreach ($horarioRaw as $item) {
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
            }
            $horarioFormateado = implode(' / ', $partes);
        } elseif (is_string($horarioRaw)) {
            $horarioFormateado = $horarioRaw;
        }

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
            'horario' => $horarioFormateado,
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

    public function getTestEntrada($slug)
    {
        try {

            $keyGroq = env('KEY_GROQ');

            $test = PntTest::where('slug', $slug)->first();

            if (! $test) {
                return response()->json([
                    'status' => 404,
                    'message' => "No existe un test con el slug '{$slug}'.",
                    'x' => $keyGroq,
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Test obtenido correctamente.',
                'data' => [
                    'test_entrada' => $test->test_entrada,
                    'test_salida' => $test->test_salida,
                ],
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function saveTest(Request $request)
    {
        $request->validate([
            'slug' => 'required|string|max:255',
            'test_entrada' => 'nullable|array',
            'test_salida' => 'nullable|array',
            'preguntas_rating' => 'nullable|array',
            'caso_practico' => 'nullable|string',
        ]);

        try {

            DB::beginTransaction();

            // Buscar por slug o crear un registro vacío
            $test = PntTest::firstOrCreate(
                ['slug' => $request->slug],
                [
                    'test_entrada' => null,
                    'test_salida' => null,
                    'caso_practico' => null,
                ]
            );

            $data = [];

            // Solo actualizar los campos enviados
            if ($request->has('test_entrada')) {
                $data['test_entrada'] = $request->test_entrada;
            }

            if ($request->has('preguntas_rating')) {
                $data['test_salida'] = $request->preguntas_rating;
            } elseif ($request->has('test_salida')) {
                $data['test_salida'] = $request->test_salida;
            }

            if ($request->has('caso_practico')) {
                $data['caso_practico'] = $request->caso_practico;
            }

            if (! empty($data)) {
                $test->update($data);
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => $test->wasRecentlyCreated
                    ? 'Test creado correctamente.'
                    : 'Test actualizado correctamente.',
                'data' => [
                    'id' => $test->id,
                    'slug' => $test->slug,
                ],
            ], 200);
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('saveTest: '.$e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function comprasMyPeruTestEnd(Request $request, $slug)
    {
        try {

            $test = PntTest::where('slug', $slug)->first();

            if (! $test) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No existe un test para el slug indicado.',
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Test obtenido correctamente.',
                'data' => [
                    'test_entrada' => $test->test_entrada,
                    'test_salida' => $test->test_salida,
                    'caso_practico' => $test->caso_practico,
                ],
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function comprasMyPeruTestEndResponses(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
            'numero_dni' => 'required|string|max:12',
            'empresario_id' => 'nullable|integer',

            'test_salida' => 'nullable|array',
            'ratings' => 'nullable|array',
        ]);

        try {
            $query = EmpresarioActividad::where('slug', $request->slug)
                ->where('numero_dni', $request->numero_dni);

            if ($request->filled('empresario_id')) {
                $query->where('empresario_id', $request->empresario_id);
            }

            $registro = $query->first();

            if (! $registro) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontró el registro del participante.',
                ], 404);
            }

            // solo salida
            // salida
            if (! empty($registro->test_salida)) {
                return response()->json([
                    'status' => 409,
                    'message' => 'El test de salida ya fue registrado anteriormente.',
                ], 409);
            }

            $registro->update([
                'test_salida' => $request->test_salida,
                'ratings' => $request->ratings,
                'fecha_ts' => now(),
            ]);

            $realizoTestEntrada = ! empty($registro->test_entrada);

            return response()->json([
                'status' => 200,
                'message' => 'Test de salida registrado correctamente.',
                'test_entrada_realizado' => $realizoTestEntrada,
                'mensaje_test_entrada' => $realizoTestEntrada ? 'si_realizo' : 'no_realizo',
            ]);
        } catch (\Exception $e) {
            Log::error('comprasMyPeruTestEndResponses: '.$e->getMessage(), $request->all());

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
