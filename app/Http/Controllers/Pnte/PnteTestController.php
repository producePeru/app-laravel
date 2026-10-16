<?php

namespace App\Http\Controllers\Pnte;

use App\Http\Controllers\Controller;
use App\Models\ActividadPnte;
use App\Models\Empresario;
use App\Models\EmpresarioActividad;
use App\Models\PntTest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PnteTestController extends Controller
{
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
                'data' => $test->test_entrada,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getTestSalida($slug)
    {
        try {

            $test = PntTest::where('slug', $slug)->first();

            if (! $test) {
                return response()->json([
                    'status' => 404,
                    'message' => "No existe un test con el slug '{$slug}'.",
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Test obtenido correctamente.',
                'data' => $test->caso_practico,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getPublicTest(Request $request, $slug)
    {
        try {

            $request->validate([
                'date' => 'required|date',
                'hourStart' => 'required|date_format:H:i',
                'hourEnd' => 'required|date_format:H:i',
                'type' => 'required|in:entrada',
            ]);

            $now = Carbon::now();

            // Fecha y hora límite = date + hourEnd
            $limit = Carbon::createFromFormat(
                'Y-m-d H:i',
                "{$request->date} {$request->hourEnd}"
            );

            if ($now->greaterThan($limit)) {
                return response()->json([
                    'status' => 409,
                    'message' => 'El tiempo para realizar el test de entrada ha finalizado.',
                ]);
            }

            $test = PntTest::where('slug', $slug)->first();

            if (! $test) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No existe un test para el slug indicado.',
                ]);
            }

            $actividad = ActividadPnte::select('tema', 'link', 'horario')
                ->where('slug', $slug)
                ->first();

            $fields = collect($test->test_entrada)->map(function ($pregunta, $index) {
                return [
                    'type' => 'radio',
                    'label' => $pregunta['texto'],
                    'model' => 'pregunta_'.($index + 1),
                    'required' => true,
                    'md' => 12,
                    'visible' => true,
                    'options' => collect($pregunta['opciones'])->map(function ($opcion) {
                        return [
                            'label' => $opcion['texto'],
                            'value' => $opcion['id'],
                        ];
                    })->values()->toArray(),
                ];
            })->values();

            return response()->json([
                'status' => 200,
                'message' => 'Información obtenida correctamente.',
                'data' => [
                    'slug' => $slug,
                    'tema' => optional($actividad)->tema,
                    'link' => $this->resolverMeetLinkPorSesion(
                        $actividad,
                        $request->date,
                        $request->hourStart,
                        $request->hourEnd
                    ),
                    'fields' => $fields,
                ],
            ]);
        } catch (\Exception $e) {

            Log::error('getPublicTest: '.$e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getPublicTestEnd(Request $request, $slug)
    {
        try {

            $request->validate([
                'date' => 'required|date',
                'hourStart' => 'required|date_format:H:i',
                'hourEnd' => 'required|date_format:H:i',
                'type' => 'required|in:salida',
            ]);

            $test = PntTest::where('slug', $slug)->first();

            if (! $test) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No existe un test para el slug indicado.',
                ], 404);
            }

            // Validar que el test de salida no haya vencido
            $now = Carbon::now();
            $limit = Carbon::parse($request->date)->endOfDay();

            if ($now->greaterThan($limit)) {
                return response()->json([
                    'status' => 409,
                    'message' => 'El tiempo para realizar el test de salida ha finalizado.',
                ]);
            }

            $actividad = ActividadPnte::select('tema', 'link', 'horario')
                ->where('slug', $slug)
                ->first();

            // Preguntas dinámicas del test
            $fields = collect($test->test_entrada)->map(function ($pregunta, $index) {

                return [
                    'type' => 'radio',
                    'label' => $pregunta['texto'],
                    'model' => 'pregunta_'.($index + 1),
                    'required' => true,
                    'md' => 12,
                    'visible' => true,
                    'options' => collect($pregunta['opciones'])->map(function ($opcion) {
                        return [
                            'label' => $opcion['texto'],
                            'value' => $opcion['id'],
                        ];
                    })->values()->toArray(),
                ];
            });

            // Campos adicionales del test de salida
            $fields = $fields->concat([

                [
                    'type' => 'title',
                    'label' => 'CASO PRÁCTICO',
                    'md' => 12,
                ],

                [
                    'type' => 'description',
                    'label' => $test->caso_practico ?? '',
                    'model' => 'caso_practico_title',
                    'md' => 12,
                    'visible' => true,
                ],

                [
                    'type' => 'textarea',
                    'label' => 'Respuesta del caso práctico',
                    'model' => 'caso_practico',
                    'required' => true,
                    'md' => 12,
                    'rows' => 7,
                ],

                [
                    'type' => 'title',
                    'label' => 'LEE CON ATENCIÓN CADA ENUNCIADO Y MARCA',
                    'md' => 12,
                ],

                ...collect($test->test_salida ?? [])->map(function ($pregunta, $index) {
                    return [
                        'type' => 'rating2',
                        'label' => $pregunta['texto'],
                        'model' => 'rating_'.$pregunta['id'],
                        'visible' => true,
                        'md' => 12,
                        'required' => true,
                        'options' => collect($pregunta['opciones'] ?? [])->map(function ($opcion, $i) {
                            return [
                                'label' => $opcion['label'],
                                'value' => $i + 1,
                            ];
                        })->values()->toArray(),
                    ];
                })->values()->toArray(),

                [
                    'type' => 'title',
                    'label' => 'SUGERENCIAS',
                    'md' => 12,
                ],

                [
                    'type' => 'textarea',
                    'label' => 'Apreciaciones y/o sugerencias de mejora',
                    'model' => 'sugerencias',
                    'required' => true,
                    'md' => 12,
                    'rows' => 3,
                ],

            ])->values();

            return response()->json([
                'status' => 200,
                'message' => 'Información obtenida correctamente.',
                'data' => [
                    'slug' => $slug,
                    'tema' => optional($actividad)->tema,
                    'link' => $this->resolverMeetLinkPorSesion(
                        $actividad,
                        $request->date,
                        $request->hourStart,
                        $request->hourEnd
                    ),
                    'fields' => $fields,
                ],
            ]);
        } catch (\Exception $e) {

            Log::error('getPublicTestEnd: '.$e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function validatePublicTest(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
            'ruc' => 'required|string|max:11',
            'dni' => 'required|string|max:12',
            'date' => 'required|date',
            'hourStart' => 'nullable|date_format:H:i',
            'hourEnd' => 'nullable|date_format:H:i',
        ]);

        try {

            $query = EmpresarioActividad::with([
                'empresario',
                'actividadPnte:id,slug,link,tema,horario',
            ])
                ->where('slug', $request->slug)
                ->whereDate('fecha_seleccionada', $request->date)
                ->whereHas('empresario', function ($query) use ($request) {
                    $query->where('ruc', $request->ruc)
                        ->where('numero_dni', $request->dni);
                });

            // Si viene el horario, filtramos la sesión exacta (un mismo día puede tener varias salas)
            if ($request->filled('hourStart')) {
                $query->where('horario_inicio', substr(trim((string) $request->hourStart), 0, 5));
            }
            if ($request->filled('hourEnd')) {
                $query->where('horario_fin', substr(trim((string) $request->hourEnd), 0, 5));
            }

            $registro = $query->first();

            if (! $registro) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontró un participante registrado con los datos proporcionados para la fecha indicada.',
                ]);
            }

            // Sala correcta según fecha + hora de la inscripción (meetLink de la sesión)
            $linkSala = $this->resolverMeetLinkPorSesion(
                $registro->actividadPnte,
                $registro->fecha_seleccionada,
                $registro->horario_inicio,
                $registro->horario_fin
            );

            // Validar estado del test de entrada
            if (is_null($registro->test_entrada)) {
                return response()->json([
                    'status' => 204,
                    'aplicado' => false,
                    'message' => 'El test de entrada aún no ha sido aplicado.',
                    'data' => [
                        'empresario_id' => $registro->empresario->id,
                        'slug' => $registro->slug,
                        'tema' => optional($registro->actividadPnte)->tema,
                        'fecha_seleccionada' => $registro->fecha_seleccionada,
                        'horario_fin' => $registro->horario_fin,
                        'horario_inicio' => $registro->horario_inicio,
                        'ruc' => $registro->empresario->ruc,
                        'dni' => $registro->empresario->numero_dni,
                        'email' => $registro->empresario->correo_electronico,
                        'link' => $linkSala,
                        'nombres' => trim(
                            $registro->empresario->nombres.' '.
                                $registro->empresario->apellido_paterno.' '.
                                $registro->empresario->apellido_materno
                        ),
                    ],
                ]);
            }

            return response()->json([
                'status' => 200,
                'aplicado' => true,
                'message' => 'El participante ya completó el test de entrada.',
                'data' => [
                    'empresario_id' => $registro->empresario->id,
                    'slug' => $registro->slug,
                    'tema' => optional($registro->actividadPnte)->tema,
                    'fecha_seleccionada' => $registro->fecha_seleccionada,
                    'horario_fin' => $registro->horario_fin,
                    'horario_inicio' => $registro->horario_inicio,
                    'ruc' => $registro->empresario->ruc,
                    'dni' => $registro->empresario->numero_dni,
                    'email' => $registro->empresario->correo_electronico,
                    'link' => $linkSala,
                    'nombres' => trim(
                        $registro->empresario->nombres.' '.
                            $registro->empresario->apellido_paterno.' '.
                            $registro->empresario->apellido_materno
                    ),
                ],
            ]);
        } catch (\Exception $e) {

            Log::error('validatePublicTest: '.$e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getEventInfo($slug)
    {
        try {

            $actividad = ActividadPnte::select('tema', 'fechas')
                ->where('slug', $slug)
                ->first();

            if (! $actividad) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontró una actividad con el slug indicado.',
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Información obtenida correctamente.',
                'data' => [
                    'tema' => $actividad->tema,
                    'fechas' => $actividad->fechas,
                ],
            ], 200);
        } catch (\Exception $e) {

            Log::error('getEventInfo: '.$e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // del formulario public guardamos las respuestas

    public function savePublicTest(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
            'numero_dni' => 'required|string|max:12',
            'empresario_id' => 'required|integer',
            'date' => 'required|date',
            'horario_inicio' => 'required',
            'horario_fin' => 'required',
            'type' => 'required|in:entrada,salida',

            'test_entrada' => 'nullable|array',
            'test_salida' => 'nullable|array',
            'caso_practico' => 'nullable|string',
            'ratings' => 'nullable|array',
            'sugerencias' => 'nullable|string',
        ]);

        try {

            $registro = EmpresarioActividad::where('slug', $request->slug)
                ->where('numero_dni', $request->numero_dni)
                ->where('empresario_id', $request->empresario_id)
                ->whereDate('fecha_seleccionada', $request->date)
                ->where('horario_inicio', $request->horario_inicio)
                ->where('horario_fin', $request->horario_fin)
                ->first();

            if (! $registro) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontró el registro del participante.',
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | TEST DE ENTRADA
        |--------------------------------------------------------------------------
        */

            if ($request->type === 'entrada') {

                if (! empty($registro->test_entrada)) {
                    return response()->json([
                        'status' => 409,
                        'message' => 'El test de entrada ya fue registrado anteriormente.',
                    ]);
                }

                $registro->update([
                    'test_entrada' => $request->test_entrada,
                    'fecha_te' => now(),
                ]);

                // Calcular nota sobre 20 (misma fórmula que inscritosPP093PorSlug)
                $banco = PntTest::where('slug', $request->slug)->first()?->test_entrada ?? [];
                $correctas = 0;
                $respondidas = 0;
                $detalle = [];
                foreach (($request->test_entrada ?? []) as $preguntaKey => $respuestaId) {
                    $numero = (int) str_replace('pregunta_', '', $preguntaKey);
                    $preguntaBD = $banco[$numero - 1] ?? null;
                    if (! $preguntaBD) {
                        continue;
                    }
                    $respondidas++;
                    $esCorrecta = ($preguntaBD['correctaId'] ?? null) == $respuestaId;
                    if ($esCorrecta) {
                        $correctas++;
                    }
                    $textoRespuesta = null;
                    $textoCorrecta = null;
                    foreach (($preguntaBD['opciones'] ?? []) as $opcion) {
                        if (($opcion['id'] ?? null) == $respuestaId) {
                            $textoRespuesta = $opcion['texto'] ?? null;
                        }
                        if (($opcion['id'] ?? null) == ($preguntaBD['correctaId'] ?? null)) {
                            $textoCorrecta = $opcion['texto'] ?? null;
                        }
                    }
                    $detalle[] = [
                        'pregunta' => $preguntaBD['texto'] ?? $preguntaKey,
                        'tu_respuesta' => $textoRespuesta,
                        'respuesta_correcta' => $textoCorrecta,
                        'es_correcta' => $esCorrecta,
                    ];
                }
                $nota = $respondidas > 0 ? round(($correctas / $respondidas) * 20, 2) : 0;

                return response()->json([
                    'status' => 200,
                    'message' => 'Test de entrada registrado correctamente.',
                    'nota' => $nota,
                    'detalle' => $detalle,
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | TEST DE SALIDA
        |--------------------------------------------------------------------------
        */

            if (! empty($registro->test_salida)) {
                return response()->json([
                    'status' => 409,
                    'message' => 'El test de salida ya fue registrado anteriormente.',
                ]);
            }

            $registro->update([
                'test_salida' => $request->test_salida,
                'caso_practico' => $request->caso_practico,
                'ratings' => $request->ratings,
                'sugerencias' => $request->sugerencias,
                'fecha_ts' => now(),
            ]);

            $realizoTestEntrada = ! empty($registro->test_entrada);

            return response()->json([
                'status' => 200,
                'message' => 'Test de salida registrado correctamente.',
                'test_entrada_realizado' => $realizoTestEntrada,
                'mensaje_test_entrada' => $realizoTestEntrada
                    ? 'si_realizo'
                    : 'no_realizo',
            ]);
        } catch (\Exception $e) {

            Log::error('savePublicTest: '.$e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function iWantMyCertificate(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
            'email' => 'required|email',
            'idempresario' => 'required|integer',
            'c_constancia' => 'required|boolean',
        ]);

        try {

            DB::beginTransaction();

            // Buscar el registro de la actividad
            $registro = EmpresarioActividad::where('slug', $request->slug)
                ->where('empresario_id', $request->idempresario)
                ->first();

            if (! $registro) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontró el registro de la actividad.',
                ], 404);
            }

            // Actualizar si desea constancia
            $registro->update([
                'c_constancia' => $request->c_constancia ? 1 : 0,
            ]);

            // Actualizar correo del empresario
            Empresario::where('id', $request->idempresario)
                ->update([
                    'correo_electronico' => $request->email,
                ]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'La información se actualizó correctamente.',
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('iWantMyCertificate: '.$e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function checkAsistencia(Request $request)
    {
        $request->validate([
            'empresario_id' => 'required|integer|exists:empresarios,id',
            'slug' => 'required|string|exists:empresario_actividad,slug',
            'date' => 'required|date_format:Y-m-d',
            'hourStart' => 'required|date_format:H:i',
            'hourEnd' => 'required|date_format:H:i',
        ]);

        try {
            $registro = EmpresarioActividad::where('slug', $request->slug)
                ->where('empresario_id', $request->empresario_id)
                ->where('fecha_seleccionada', $request->date)
                ->where('horario_inicio', $request->hourStart)
                ->where('horario_fin', $request->hourEnd)
                ->first();

            if (! $registro) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontró un registro de inscripción que coincida con los datos proporcionados.',
                ]);
            }

            if ($registro->fecha_asistencia) {
                return response()->json([
                    'status' => 409, // Conflict
                    'message' => 'La asistencia para esta actividad ya fue registrada el '.$registro->fecha_asistencia,
                ]);
            }

            // Guardar la fecha y hora actual en el formato solicitado
            $registro->fecha_asistencia = now()->format('d/m/Y h:i a');
            $registro->save();

            return response()->json([
                'status' => 200,
                'message' => 'Asistencia registrada correctamente.',
                'data' => [
                    'id' => $registro->id,
                    'fecha_asistencia' => $registro->fecha_asistencia,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado al registrar la asistencia.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resuelve la sala Meet correcta para una fecha + horario.
     *
     * Cada sesión del cronograma (ActividadPnte.horario[]) tiene su PROPIO
     * meetLink. El campo ActividadPnte.link es solo compatibilidad con la
     * primera sesión y NO debe usarse cuando hay varias fechas/horarios.
     *
     * Prioridad: 1. fecha + inicio + fin / 2. fecha + inicio / 3. solo fecha / 4. link global.
     */
    private function resolverMeetLinkPorSesion(
        $actividad,
        $fechaSeleccionada,
        $horaInicio = null,
        $horaFin = null
    ): ?string {
        $normalizarHora = fn ($h) => $h ? substr(trim((string) $h), 0, 5) : null;

        try {
            $fecha = Carbon::parse($fechaSeleccionada)->format('Y-m-d');
        } catch (\Throwable $e) {
            $fecha = is_string($fechaSeleccionada) ? substr($fechaSeleccionada, 0, 10) : null;
        }

        if (! $actividad || ! $fecha) {
            return $actividad->link ?? null;
        }

        $inicio = $normalizarHora($horaInicio);
        $fin = $normalizarHora($horaFin);

        $horario = is_array($actividad->horario) ? $actividad->horario : [];

        // 1. Coincidencia exacta fecha + inicio + fin
        foreach ($horario as $sesion) {
            if (empty($sesion['meetLink'])) {
                continue;
            }
            $f = isset($sesion['fecha']) ? substr((string) $sesion['fecha'], 0, 10) : null;
            if ($f !== $fecha) {
                continue;
            }
            if ($normalizarHora($sesion['horaInicio'] ?? null) === $inicio
                && $normalizarHora($sesion['horaFin'] ?? null) === $fin) {
                return $sesion['meetLink'];
            }
        }

        // 2. Coincidencia fecha + hora de inicio
        foreach ($horario as $sesion) {
            if (empty($sesion['meetLink'])) {
                continue;
            }
            $f = isset($sesion['fecha']) ? substr((string) $sesion['fecha'], 0, 10) : null;
            if ($f === $fecha && $normalizarHora($sesion['horaInicio'] ?? null) === $inicio) {
                return $sesion['meetLink'];
            }
        }

        // 3. Primera sesión de ese día con sala
        foreach ($horario as $sesion) {
            if (empty($sesion['meetLink'])) {
                continue;
            }
            $f = isset($sesion['fecha']) ? substr((string) $sesion['fecha'], 0, 10) : null;
            if ($f === $fecha) {
                return $sesion['meetLink'];
            }
        }

        // 4. Fallback global (compatibilidad)
        return $actividad->link;
    }
}
