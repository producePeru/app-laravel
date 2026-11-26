<?php

namespace App\Http\Controllers\ComprasMyPeru;

use App\Http\Controllers\Controller;
use App\Mail\EmailParticipacionComprasMyPeruMail;
use App\Models\ActividadPnte;
use App\Models\Empresario;
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
            // ->where('tipo_actividad_id', 1)
            // ->where('nombre_actividad_id', 6)
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
            'horario_sesiones' => is_array($horarioRaw) ? $horarioRaw : null,
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
        $pagina = $request->input('pagina');

        if (! $slug || ! $correo || ! filter_var($correo, FILTER_VALIDATE_EMAIL)
            || ($pagina !== null && ! preg_match('/^[a-z0-9-]+$/i', $pagina))) {
            return response()->json([
                'status' => 422,
                'message' => 'Los parámetros slug y correo son obligatorios, el correo debe ser válido y pagina debe ser válida.',
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
            $this->enviarEmailParticipacionCooperativasUgseco($actividad, $correo, $nombres, $pagina);

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
        ?string $nombres = null,
        ?string $pagina = null
    ): void {
        if (! $correo || ! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('El correo del participante no es válido.');
        }

        Mail::mailer('hostinger3k')
            ->to($correo)
            ->send(new EmailParticipacionComprasMyPeruMail(
                $nombres ?: null,
                $this->datosCorreoCapacitacionCooperativas($actividad, $pagina)
            ));
    }

    private function datosCorreoCapacitacionCooperativas(ActividadPnte $actividad, ?string $pagina = null): array
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
            'asistencia_url' => 'https://capacitaciones.soportepnte.com/ugseco/asistencia?'.http_build_query(array_filter([
                'slug' => $actividad->slug,
                'pagina' => $pagina,
            ], static fn ($valor) => $valor !== null && $valor !== '')),
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

            $actividad = ActividadPnte::where('slug', $slug)->first();

            if (! $actividad) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontró la capacitación.',
                ], 404);
            }

            if ($error = $this->validarDisponibilidadTestSalida($actividad)) {
                return $error;
            }

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
            $actividad = ActividadPnte::where('slug', $request->slug)->first();

            if (! $actividad) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontró la capacitación.',
                ], 404);
            }

            if ($error = $this->validarDisponibilidadTestSalida($actividad)) {
                return $error;
            }

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

    public function storeEmpresarioComprasMyPeru(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'required|string',
            'ruc' => 'nullable|size:11',
            'numero_dni' => 'required|string|max:12',
            'discapacidad' => 'required|in:0,1',
            'fecha_seleccionada' => 'nullable|date',
            // 'coop_rol_institucion' => 'nullable|string|max:255',
        ]);

        try {

            DB::beginTransaction();

            $actividad = ActividadPnte::where('slug', $request->slug)
                ->firstOrFail();

            $fechasNormalizadas = $this->normalizarFechasActividad($actividad->fechas);
            $hoy = Carbon::today()->toDateString();
            $fechaSeleccionada = $request->filled('fecha_seleccionada')
                ? Carbon::parse($request->input('fecha_seleccionada'))->toDateString()
                : (in_array($hoy, $fechasNormalizadas, true)
                    ? $hoy
                    : (collect($fechasNormalizadas)->first(fn ($fecha) => $fecha >= $hoy)
                        ?? ($fechasNormalizadas[0] ?? null)));

            if (! $fechaSeleccionada || ! in_array($fechaSeleccionada, $fechasNormalizadas, true)) {
                DB::rollBack();

                return response()->json([
                    'status' => 422,
                    'message' => 'Selecciona una fecha válida para esta actividad.',
                ], 422);
            }

            $isSameDay = $fechaSeleccionada === $hoy
                && in_array($hoy, $fechasNormalizadas, true);

            $datosEmpresario = [
                'ruc' => $request->ruc,
                'numero_dni' => $request->numero_dni,
                'razon_social' => $request->razon_social,
                'nombre_comercial' => $request->nombre_comercial,
                'sector_economico_id' => $request->sector_economico_id,
                'rubro_id' => $request->rubro_id,
                'actividad_comercial_id' => $request->actividad_comercial_id,
                'region_id' => $request->region_id,
                'provincia_id' => $request->provincia_id,
                'distrito_id' => $request->distrito_id,
                'direccion' => $request->direccion,
                'pais_id' => $request->pais_id,
                'tipo_documento_id' => $request->tipo_documento_id,
                'apellido_paterno' => $request->apellido_paterno,
                'apellido_materno' => $request->apellido_materno,
                'nombres' => $request->nombres,
                'genero_id' => $request->genero_id,
                'discapacidad' => $request->discapacidad,
                'celular' => $request->celular,
                'correo_electronico' => $request->correo_electronico,
                'cargo_empresa_id' => $request->cargo_empresa_id,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'edad' => $request->edad,
                'actividad_comercial_nombre' => $request->actividad_comercial_nombre,
                'tipo_empresa_id' => $request->tipo_empresa_id,
                'f_inicio_act' => $request->f_inicio_act,
                'venta_anual' => $request->venta_anual,
                'medio_entero' => $request->medio_entero,
                'coop_ruc' => $request->coop_ruc,
                'coop_razon_social' => $request->coop_razon_social,
                'coop_rol' => $request->coop_rol,
                'coop_nombre_institucion' => $request->coop_nombre_institucion,
            ];

            // if (isset($validated['coop_rol_institucion'])) {
            //     $datosEmpresario['coop_rol_institucion'] = $validated['coop_rol_institucion'];
            // }

            $identidad = [
                'tipo_documento_id' => $request->tipo_documento_id,
                'numero_dni' => $request->numero_dni,
            ];
            $empresario = Empresario::where($identidad)->first();
            if (! $empresario) {
                // Completar también el tipo de documento de un registro que aún no lo tiene.
                $empresario = Empresario::where('numero_dni', $request->numero_dni)
                    ->whereNull('tipo_documento_id')->first()
                    ?? Empresario::firstOrCreate($identidad, $datosEmpresario);
            }

            // Serializar inscripciones concurrentes del mismo participante.
            $empresario = Empresario::whereKey($empresario->id)
                ->lockForUpdate()
                ->firstOrFail();

            /**
             * 2. VALIDAR INSCRIPCIÓN DUPLICADA EN LA MISMA FECHA
             */
            $inscripcionExistente = EmpresarioActividad::where('actividad_id', $actividad->id)
                ->where('empresario_id', $empresario->id)
                ->whereDate('fecha_seleccionada', $fechaSeleccionada)
                ->lockForUpdate()
                ->first();

            if ($inscripcionExistente) {

                DB::rollBack();

                return response()->json([
                    'status' => 422,
                    'message' => 'El participante ya está registrado en esta fecha de la actividad.',
                ], 422);
            }

            // Usar el payload únicamente para completar datos que todavía estén vacíos.
            foreach ($datosEmpresario as $campo => $valor) {
                if (blank($empresario->getAttribute($campo)) && filled($valor)) {
                    $empresario->setAttribute($campo, $valor);
                }
            }
            if ($empresario->isDirty()) {
                $empresario->save();
            }

            /**
             * 4. REGISTRAR RELACIÓN
             */
            $inscripcion = EmpresarioActividad::create([
                'actividad_id' => $actividad->id,
                'slug' => $request->slug,
                'empresario_id' => $empresario->id,
                'numero_dni' => $request->numero_dni,
                'fecha_asistencia' => null,
                'fecha_seleccionada' => $fechaSeleccionada,
            ]);

            // Marcar asistencia si el registro es el mismo día del evento
            if ($isSameDay && $actividad->switch_asistencias) {
                $asistencia = $inscripcion->asistencias()->firstOrCreate(
                    ['fecha' => $hoy],
                    [
                        'slug' => $inscripcion->slug,
                        'numero_dni' => $inscripcion->numero_dni,
                        'fecha_hora' => now(),
                    ]
                );

                $primeraAsistencia = $inscripcion->asistencias()
                    ->orderBy('fecha')
                    ->orderBy('fecha_hora')
                    ->first();

                $inscripcion->update([
                    'fecha_asistencia' => $primeraAsistencia
                        ? ($primeraAsistencia->fecha_hora ?? now())->format('d/m/Y h:i a')
                        : null,
                ]);
            }

            /**
             * 5. CONTADOR
             */
            $actividad->increment('total_participantes');

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Registro procesado correctamente.',
                'empresario_id' => $empresario->id,
                'is_same_day' => $isSameDay,
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 500,
                'message' => 'Error en el servidor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function validarDisponibilidadTestSalida(ActividadPnte $actividad): ?JsonResponse
    {
        if ((int) $actividad->switch_test !== 1) {
            return response()->json([
                'status' => 403,
                'reason' => 'test_desactivado',
                'message' => 'El registro de la prueba de salida está desactivado. Espera a que el asesor lo habilite.',
            ], 403);
        }

        return null;
    }

    private function normalizarFechasActividad($fechas): array
    {
        return collect($fechas ?? [])
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
            ->sort()
            ->values()
            ->all();
    }

    public function detalleGeneral($slug): JsonResponse
    {
        $actividad = ActividadPnte::with([
            'modalidad:id,name',
            'representante:id,name,lastname,middlename',
        ])
            ->where('slug', $slug)
            ->first();

        if (! $actividad) {
            return response()->json([
                'status' => 404,
                'message' => 'Actividad no encontrada.',
                'data' => null,
            ], 404);
        }

        $fechas = $this->normalizarFechasActividad($actividad->fechas);
        $hoy = Carbon::today()->toDateString();
        $tieneFechaVigente = collect($fechas)->contains(fn ($fecha) => $fecha >= $hoy);
        $formularioDisponible = empty($fechas) || $tieneFechaVigente;

        $data = [
            'slug' => $actividad->slug,
            'tema' => $actividad->tema,
            'fechas' => $fechas,
            'horario' => $actividad->horario,
            'modalidad_id' => $actividad->modalidad_id,
            'modalidad' => $actividad->modalidad?->name,
            'representante_id' => $actividad->representante_id,
            'representante' => $actividad->representante ? [
                'id' => $actividad->representante->id,
                'name' => $actividad->representante->name,
                'lastname' => $actividad->representante->lastname,
                'middlename' => $actividad->representante->middlename,
            ] : null,
            'asesor' => $actividad->representante
                ? mb_strtoupper(trim(implode(' ', array_filter([
                    $actividad->representante->name,
                    $actividad->representante->lastname,
                    $actividad->representante->middlename,
                ]))), 'UTF-8')
                : null,
            'formulario_disponible' => $formularioDisponible,
            'switch_institucion' => (int) $actividad->switch_institucion,
        ];

        if (! $formularioDisponible) {
            return response()->json([
                'status' => 410,
                'message' => 'El formulario de inscripción ya no está disponible.',
                'data' => $data,
            ], 410);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Información de la actividad obtenida correctamente.',
            'data' => $data,
        ]);
    }
}
