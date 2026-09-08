<?php

namespace App\Http\Controllers\Pnte;

use App\Http\Controllers\Controller;
use App\Jobs\SendRecordatorioPP093DesdeAdminJob;
use App\Models\ActividadPnte;
use App\Models\Empresario;
use App\Models\EmpresarioActividad;
use App\Models\EmpresarioEmprendimiento;
use App\Models\PntTest;
use App\Models\SedDescripcion;
use App\Models\sedQuestionAnswer;
use App\Services\GoogleMeetCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ActividadPnteController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unidad' => 'required|integer|in:1,2,3,4,5',
            'fechas' => 'required|array|min:1',
            'fechas.*' => 'required|date_format:Y-m-d',

            'tipo_actividad_id' => 'required|exists:tipo_actividad,id',
            'nombre_actividad_id' => 'required|exists:nombre_actividad,id',

            'tema' => 'nullable|string',

            'region' => 'required|exists:cities,id',
            'provincia' => 'required|exists:provinces,id',
            'distrito' => 'required|exists:districts,id',

            'lugar' => 'nullable|string|max:255',

            'entidad_organizadora' => 'nullable|string|max:255',
            'entidad_aliada' => 'nullable|string|max:255',

            'representante_id' => 'nullable|exists:users,id',

            'requiere_pasaje' => 'required|boolean',
            'monto_gasto' => 'nullable|max:255',

            'mypes_beneficiadas' => 'nullable|integer|min:0',

            'modalidad_id' => 'nullable|exists:modalities,id',

            'horario' => 'nullable|string',

            'link' => 'nullable|string',

            'componente_id' => 'nullable',
            'trainer_id' => 'nullable|exists:pp_capacitadores,id',

            'tipo_mercado' => 'nullable',
            'tipo_gestion' => 'nullable',
        ]);

        $validated['representante_id'] =
            $validated['representante_id'] ?? Auth::id();

        // =====================================================
        // OBTENER MES DE LA FECHA MÁS ANTIGUA
        // =====================================================

        $fechaMinima = collect($validated['fechas'])
            ->map(fn ($f) => Carbon::parse($f))
            ->sortBy(fn ($d) => $d->timestamp)
            ->first();

        $validated['mes'] = (int) $fechaMinima->format('n');

        // =====================================================
        // CANTIDAD DE DÍAS
        // =====================================================

        $validated['cantidad_dias'] = count($validated['fechas']);

        try {

            $actividad = DB::transaction(function () use ($validated) {

                $validated['slug'] = $this->generateUniqueSlug($validated);

                $validated['registrado_por_id'] = Auth::id();

                return ActividadPnte::create($validated);
            });

            return response()->json([
                'status' => 200,
                'message' => 'Actividad registrada correctamente.',
                'data' => $actividad->load([
                    'tipoActividad',
                    'nombreActividad',
                    'regionRel',
                    'provinciaRel',
                    'distritoRel',
                    'representante',
                    'modalidad',
                ]),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al registrar la actividad.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ─── SLUG ÚNICO ───────────────────────────────────────────────

    private function generateUniqueSlug(array $data): string
    {
        // Base: tipo_actividad_id + primera fecha + random
        $base = implode('-', [
            'act',
            $data['tipo_actividad_id'],
            $data['fechas'][0],        // primera fecha del array
            Str::random(6),
        ]);

        $slug = Str::slug($base);

        // Garantizar unicidad
        $count = 0;
        $original = $slug;

        while (ActividadPnte::where('slug', $slug)->exists()) {
            $count++;
            $slug = $original.'-'.$count;
        }

        return $slug;
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $actividad = ActividadPnte::findOrFail($id);
        $user = Auth::user();

        // ✅ SOLO EL ROL 1 PUEDE EDITAR EN CUALQUIER MOMENTO
        if ($user->rol != 1) {
            // límite hasta las 23:59 del día de creación
            $limiteEdicion = Carbon::parse($actividad->created_at)->endOfDay();

            if (Carbon::now()->gt($limiteEdicion)) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No es posible editar esta actividad. El plazo de edición venció el '.
                        Carbon::parse($actividad->created_at)->format('d/m/Y').
                        ' a las 23:59. Por favor, contacte con su supervisor.',
                ], 403);
            }
        }

        // 🛠️ VALIDACIÓN CORREGIDA Y ACTUALIZADA
        $validated = $request->validate([
            'unidad' => 'required|integer|in:1,2,3,4,5',
            'fechas' => 'required|array|min:1',
            'fechas.*' => 'required|date_format:Y-m-d',
            'tipo_actividad_id' => 'required|exists:tipo_actividad,id',
            'nombre_actividad_id' => 'required|exists:nombre_actividad,id',
            'tema' => 'nullable|string|max:255',
            'region' => 'required|exists:cities,id',
            'provincia' => 'required|exists:provinces,id',
            'distrito' => 'required|exists:districts,id',
            'lugar' => 'nullable|string|max:255',
            'entidad_organizadora' => 'nullable|string|max:255',
            'entidad_aliada' => 'nullable|string|max:255',
            'representante_id' => 'nullable|exists:users,id',
            'requiere_pasaje' => 'required|boolean',
            'monto_gasto' => 'nullable|max:255',
            'mypes_beneficiadas' => 'nullable|integer|min:0',
            'modalidad_id' => 'nullable|exists:modalities,id',
            'total_participantes' => 'nullable|integer|min:0',
            'total_asesorias' => 'nullable|integer|min:0',
            'total_formalizaciones' => 'nullable|integer|min:0',

            // ✨ Cambiado de string a array para soportar tu estructura estructurada
            'horario' => 'nullable',
            'link' => 'nullable|string',
            'componente_id' => 'nullable|integer',
            'trainer_id' => 'nullable|exists:pp_capacitadores,id',
        ]);

        // ✅ Obtener mes de la fecha mínima de forma segura
        $fechaMinima = collect($validated['fechas'])
            ->map(fn ($f) => Carbon::parse($f))
            ->sortBy(fn ($d) => $d->timestamp)
            ->first();

        $validated['mes'] = (int) $fechaMinima->format('n');
        $validated['cantidad_dias'] = count($validated['fechas']);

        try {
            DB::transaction(function () use ($actividad, $validated) {
                $validated['actualizado_por_id'] = Auth::id();

                // 🌟 CONTROL EXTRA: Si el modelo ActividadPnte NO tiene 'horario' en el $casts como array/json,
                // lo convertimos a string JSON manualmente antes de persistir para no romper la BD.
                if (isset($validated['horario']) && is_array($validated['horario'])) {
                    // Solo activa esta línea si tu modelo ActividadPnte no tiene protegido el cast: protected $casts = ['horario' => 'array'];
                    // $validated['horario'] = json_encode($validated['horario']);
                }

                $actividad->update($validated);
            });

            return response()->json([
                'status' => 200,
                'message' => 'Actividad actualizada correctamente.',
                'data' => $actividad->fresh()->load([
                    'tipoActividad',
                    'nombreActividad',
                    'regionRel',
                    'provinciaRel',
                    'distritoRel',
                    'representante',
                    'modalidad',
                ]),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500, // Homologado con tu estándar de respuestas
                'success' => false,
                'message' => 'Error al actualizar la actividad.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'year' => 'nullable|integer|digits:4',
            'rangeDate' => 'nullable|array|size:2',
            'rangeDate.*' => 'required|date_format:Y-m-d',
            'city' => 'nullable|integer|exists:cities,id',

            // ✅ FILTROS
            'tipo_actividad_id' => 'nullable|integer|exists:tipo_actividad,id',
            'asesor' => 'nullable|integer',
            'pnte' => 'nullable|integer',

            // ✅ NUEVO
            'unidad' => 'nullable|integer',
            'name' => 'nullable|string',
        ]);

        $pageSize = $request->input('pageSize', 10);

        $user = auth()->user();

        $actividades = ActividadPnte::with([
            'tipoActividad:id,name',
            'nombreActividad:id,name',
            'regionRel:id,name',
            'provinciaRel:id,name',
            'distritoRel:id,name',
            'representante:id,name,lastname,middlename',
            'modalidad:id,name',
            'tainnerPp093:id,nombres_apellidos',
        ])
            ->select([
                'id',
                'unidad',
                'mes',
                'fechas',
                'cantidad_dias',
                'tipo_actividad_id',
                'nombre_actividad_id',
                'tema',
                'region',
                'provincia',
                'distrito',
                'lugar',
                'entidad_organizadora',
                'entidad_aliada',
                'representante_id',
                'requiere_pasaje',
                'monto_gasto',
                'mypes_beneficiadas',
                'modalidad_id',
                'total_participantes',
                'total_asesorias',
                'total_formalizaciones',
                'slug',
                'cancelado',
                'cancelado_por_id',
                'reprogramado',
                'reprogramado_por_id',
                'registrado_por_id',
                'actualizado_por_id',
                'horario',
                'activo',
                'link',
                'componente_id',
                'trainer_id',
                'tipo_mercado',
                'tipo_gestion',
                'eliminar',
                'prendido',
                'created_at',
            ])

            // ✅ FILTRO POR ROL
            // rol 1 y 3 → ven todo
            // rol 2     → solo sus actividades
            // cualquier otro → no ve nada
            ->when(in_array($user->rol, [1, 3]), function ($q) {
                // sin restricción
            })
            ->when($user->rol == 2, function ($q) use ($user) {
                $q->where('representante_id', $user->id);
            })
            ->when(! in_array($user->rol, [1, 2, 3]), function ($q) {
                $q->whereRaw('1 = 0');
            })

            // ✅ FILTRO UNIDAD
            ->when($request->filled('unidad'), function ($q) use ($request) {
                $q->where('unidad', $request->input('unidad'));
            })

            // ✅ FILTRO: asesor — solo aplica si NO es rol 2
            ->when($request->filled('asesor') && $user->rol != 2, function ($q) use ($request) {
                $q->where('representante_id', $request->input('asesor'));
            })

            // ✅ FILTRO: pnte
            ->when($request->filled('pnte'), function ($q) use ($request) {
                $q->where('tipo_actividad_id', $request->input('pnte'));
            })

            // ✅ FILTRO: tipo_actividad_id
            ->when($request->filled('tipo_actividad_id'), function ($q) use ($request) {
                $q->where('tipo_actividad_id', $request->input('tipo_actividad_id'));
            })

            // ✅ FILTRO: year
            ->when($request->filled('year'), function ($q) use ($request) {
                $year = $request->input('year');
                $q->where('fechas', 'LIKE', "%{$year}%");
            })

            // ✅ FILTRO: tema
            ->when($request->filled('name'), function ($q) use ($request) {
                $name = trim($request->input('name'));
                $q->where('tema', 'LIKE', "%{$name}%");
            })

            // ✅ FILTRO: rangeDate
            ->when($request->filled('rangeDate'), function ($q) use ($request) {
                [$from, $to] = $request->input('rangeDate');

                $current = \Carbon\Carbon::parse($from);
                $end = \Carbon\Carbon::parse($to);

                $q->where(function ($query) use ($current, $end) {
                    while ($current->lte($end)) {
                        $fecha = $current->format('Y-m-d');
                        $query->orWhereJsonContains('fechas', $fecha);
                        $current->addDay();
                    }
                });
            })

            // ✅ FILTRO: city → region
            ->when($request->filled('city'), function ($q) use ($request) {
                $q->where('region', $request->input('city'));
            })

            // ✅ ORDENAR POR FECHA MÁS RECIENTE
            ->orderByRaw("
            JSON_UNQUOTE(
                JSON_EXTRACT(
                    fechas,
                    CONCAT('$[', JSON_LENGTH(fechas) - 1, ']')
                )
            ) DESC
        ")

            ->paginate(
                $pageSize,
                ['*'],
                'page',
                $request->input('page', 1)
            );

        // Convertir eliminar y prendido: 1/0 → true/false
        $actividades->getCollection()->transform(function ($actividad) {
            $actividad->eliminar = $actividad->eliminar === 1;
            $actividad->formulario_registro = $actividad->prendido == 1;

            return $actividad;
        });

        return response()->json([
            'status' => 200,
            'message' => 'Actividades obtenidas correctamente.',
            'data' => [
                'current_page' => $actividades->currentPage(),
                'data' => $actividades->items(),
                'first_page_url' => $actividades->url(1),
                'from' => $actividades->firstItem(),
                'last_page' => $actividades->lastPage(),
                'last_page_url' => $actividades->url($actividades->lastPage()),
                'links' => $actividades->linkCollection()->toArray(),
                'next_page_url' => $actividades->nextPageUrl(),
                'path' => $actividades->path(),
                'per_page' => $actividades->perPage(),
                'prev_page_url' => $actividades->previousPageUrl(),
                'to' => $actividades->lastItem(),
                'total' => $actividades->total(),
            ],
        ]);
    }

    public function reprogramar(Request $request, int $id): JsonResponse
    {
        // ✅ Solo rol 1
        if (Auth::user()->rol != 1) {
            return response()->json([
                'status' => 403,
                'message' => 'No tienes permisos para reprogramar actividades.',
            ]);
        }

        $actividad = ActividadPnte::findOrFail($id);

        $validated = $request->validate([
            'fechas' => 'required|array|min:1',
            'fechas.*' => 'required|date_format:Y-m-d',
            'reprogramado' => 'required|string|max:255',
        ]);

        $fechaMinima = collect($validated['fechas'])
            ->map(fn ($f) => Carbon::parse($f))
            ->sortBy(fn ($d) => $d->timestamp)
            ->first();

        try {
            DB::transaction(function () use ($actividad, $validated, $fechaMinima) {
                $actividad->update([
                    'fechas' => $validated['fechas'],
                    'mes' => (int) $fechaMinima->format('n'),
                    'cantidad_dias' => count($validated['fechas']),
                    'reprogramado' => $validated['reprogramado'],
                    'reprogramado_por_id' => Auth::id(),
                ]);
            });

            return response()->json([
                'status' => 200,
                'message' => 'Actividad reprogramada correctamente.',
                'data' => $actividad->fresh()->load([
                    'tipoActividad',
                    'nombreActividad',
                    'regionRel',
                    'provinciaRel',
                    'distritoRel',
                    'representante',
                    'modalidad',
                ]),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reprogramar la actividad.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancelar(Request $request, int $id): JsonResponse
    {
        // ✅ Solo rol 1
        if (Auth::user()->rol != 1) {
            return response()->json([
                'status' => 403,
                'message' => 'No tienes permisos para cancelar actividades.',
            ]);
        }

        $actividad = ActividadPnte::findOrFail($id);

        $validated = $request->validate([
            'cancelado' => 'required|string',
        ]);

        try {
            DB::transaction(function () use ($actividad, $validated) {
                $actividad->update([
                    'cancelado' => $validated['cancelado'],
                    'cancelado_por_id' => Auth::id(),
                ]);
            });

            return response()->json([
                'status' => 200,
                'message' => 'Actividad cancelada correctamente.',
                'data' => $actividad->fresh()->load([
                    'tipoActividad',
                    'nombreActividad',
                    'regionRel',
                    'provinciaRel',
                    'distritoRel',
                    'representante',
                    'modalidad',
                ]),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la actividad.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function inscritosPorSlug(Request $request, $slug)
    {
        try {

            $perPage = $request->input('pageSize', 10);
            $search = trim($request->input('name', ''));

            $event = ActividadPnte::select(
                'id',
                'slug',
                'tema',
                'fechas',
                'nombre_actividad_id'
            )
                ->where('slug', $slug)
                ->first();

            $query = EmpresarioActividad::with([
                'empresario',
                'empresario.pais',
                'empresario.region',
                'empresario.provincia',
                'empresario.distrito',
                'empresario.actividadComercial',
                'empresario.sectorEconomico',
                'empresario.rubro',
                'empresario.tipoDocumento',
                'empresario.genero',
            ])

                ->where('slug', $slug)

                // 🔥 BUSCADOR
                ->when($search, function ($q) use ($search) {

                    $q->whereHas('empresario', function ($emp) use ($search) {

                        $emp->where('ruc', 'LIKE', "%{$search}%")

                            ->orWhere('numero_dni', 'LIKE', "%{$search}%")

                            ->orWhereRaw("
                            CONCAT(
                                COALESCE(apellido_paterno, ''),
                                ' ',
                                COALESCE(apellido_materno, ''),
                                ' ',
                                COALESCE(nombres, '')
                            ) LIKE ?
                        ", ["%{$search}%"]);
                    });
                })

                ->orderBy('created_at', 'desc');

            $data = $query->paginate($perPage);

            // 🔥 TRANSFORMAR
            $data->getCollection()->transform(function ($item) {

                $e = $item->empresario;

                return [
                    'id' => $item->id,
                    'actividad_id' => $item->actividad_id,
                    'slug' => $item->slug,
                    'fecha_asistencia' => $item->fecha_asistencia ? true : false,
                    'asistire' => $item->asistire,
                    'numero_dni' => $item->numero_dni,

                    // 🔥 DATOS EMPRESARIO
                    'ruc' => $e?->ruc,

                    'razon_social' => ! empty($e?->razon_social)
                        ? mb_strtoupper($e->razon_social, 'UTF-8')
                        : null,

                    'nombre_comercial' => ! empty($e?->nombre_comercial)
                        ? mb_strtoupper($e->nombre_comercial, 'UTF-8')
                        : null,

                    'sector_economico_id' => $e?->sector_economico_id,

                    'sector_economico_nombre' => ! empty($e?->sectorEconomico?->name)
                        ? mb_strtoupper($e->sectorEconomico->name, 'UTF-8')
                        : null,

                    'rubro_id' => $e?->rubro_id,

                    'rubro_nombre' => ! empty($e?->rubro?->name)
                        ? mb_strtoupper($e->rubro->name, 'UTF-8')
                        : null,

                    'actividad_comercial_id' => $e?->actividad_comercial_id,

                    'actividad_comercial_nombre' => ! empty($e?->actividadComercial?->name)
                        ? mb_strtoupper($e->actividadComercial->name, 'UTF-8')
                        : (
                            ! empty($e?->actividad_comercial_nombre)
                            ? mb_strtoupper($e->actividad_comercial_nombre, 'UTF-8')
                            : null
                        ),

                    'region_id' => $e?->region_id,
                    'region_nombre' => $e?->region?->name,

                    'provincia_id' => $e?->provincia_id,
                    'provincia_nombre' => $e?->provincia?->name,

                    'distrito_id' => $e?->distrito_id,
                    'distrito_nombre' => $e?->distrito?->name,

                    'direccion' => ! empty($e?->direccion)
                        ? mb_strtoupper($e->direccion, 'UTF-8')
                        : null,

                    'pais_id' => $e?->pais_id,
                    'pais_nombre' => $e?->pais?->name,

                    'tipo_documento_id' => $e?->tipo_documento_id,

                    'tipo_documento_nombre' => $e?->tipoDocumento?->avr,

                    'numero_dni_empresario' => $e?->numero_dni,

                    'apellido_paterno' => ! empty($e?->apellido_paterno)
                        ? mb_strtoupper($e->apellido_paterno, 'UTF-8')
                        : null,

                    'apellido_materno' => ! empty($e?->apellido_materno)
                        ? mb_strtoupper($e->apellido_materno, 'UTF-8')
                        : null,

                    'nombres' => ! empty($e?->nombres)
                        ? mb_strtoupper($e->nombres, 'UTF-8')
                        : null,

                    'nombre_completo' => ! empty(trim(
                        ($e?->apellido_paterno ?? '').' '.
                            ($e?->apellido_materno ?? '').' '.
                            ($e?->nombres ?? '')
                    ))
                        ? mb_strtoupper(
                            trim(
                                ($e?->apellido_paterno ?? '').' '.
                                    ($e?->apellido_materno ?? '').' '.
                                    ($e?->nombres ?? '')
                            ),
                            'UTF-8'
                        )
                        : null,

                    'genero_id' => $e?->genero_id,
                    'genero_avr' => $e?->genero?->avr,

                    'discapacidad' => $e?->discapacidad,

                    'discapacidad_nombre' => isset($e?->discapacidad)
                        ? ($e->discapacidad ? 'SI' : 'NO')
                        : null,

                    'celular' => $e?->celular,

                    'correo_electronico' => $e?->correo_electronico,

                    'cargo_empresa_id' => $e?->cargo_empresa_id,

                    'fecha_nacimiento' => $e?->fecha_nacimiento,

                    'edad' => $e?->edad,

                    'como_entero' => $e?->como_entero,

                    'personal_asesoria' => $item->personal_asesoria,

                    'personal_formalizacion' => $item->personal_formalizacion,

                    'coop_ruc' => $e?->coop_ruc,
                    'coop_razon_social' => $e?->coop_razon_social,
                    'coop_rol' => $e?->coop_rol,

                    'nombre_mercado' => $e?->nombre_mercado,
                ];
            });

            return response()->json([
                'status' => 200,
                'data' => $data,
                'event' => $event,
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener inscritos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateValuesSelect(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
            'rowId' => 'required|integer',
            'column' => 'required|string|in:personal_asesoria,personal_formalizacion',
            'value' => 'required|in:0,1',
        ]);

        try {

            // 1️⃣ Buscar actividad
            $actividad = ActividadPnte::where('slug', $request->slug)->first();

            if (! $actividad) {
                return response()->json([
                    'message' => 'Actividad no encontrada',
                ], 404);
            }

            // 2️⃣ Buscar registro
            $row = EmpresarioActividad::where('id', $request->rowId)
                ->where('slug', $request->slug)
                ->first();

            if (! $row) {
                return response()->json([
                    'message' => 'Registro no encontrado',
                ], 404);
            }

            // 3️⃣ Actualizar columna dinámica
            $column = $request->column;

            $row->{$column} = (int) $request->value;
            $row->save();

            // 4️⃣ Recalcular totales
            $totalAsesorias = EmpresarioActividad::where('slug', $request->slug)
                ->where('personal_asesoria', 1)
                ->count();

            $totalFormalizaciones = EmpresarioActividad::where('slug', $request->slug)
                ->where('personal_formalizacion', 1)
                ->count();

            // 5️⃣ Actualizar actividad
            $actividad->total_asesorias = $totalAsesorias;
            $actividad->total_formalizaciones = $totalFormalizaciones;
            $actividad->save();

            return response()->json([
                'status' => 200,
                'message' => 'Actualizado correctamente',
                'data' => [
                    'id' => $row->id,
                    'column' => $column,
                    'value' => $row->{$column},
                    'total_asesorias' => $totalAsesorias,
                    'total_formalizaciones' => $totalFormalizaciones,
                ],
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Error al actualizar',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteInscritos(Request $request): JsonResponse
    {
        $request->validate([
            'slug' => 'required|string',
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer',
        ]);

        try {
            $actividad = ActividadPnte::where('slug', $request->slug)->first();

            if (! $actividad) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Actividad no encontrada',
                ], 404);
            }

            $inscritos = EmpresarioActividad::where('slug', $request->slug)
                ->whereIn('id', $request->ids)
                ->get();

            if ($inscritos->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontraron inscritos para eliminar',
                ], 404);
            }

            $inicioDeHoy = now()->startOfDay();

            $tieneRegistrosDeDiasAnteriores = $inscritos->contains(
                fn ($inscrito) => $inscrito->created_at->lt($inicioDeHoy)
            );

            if ($tieneRegistrosDeDiasAnteriores && ! $actividad->eliminar) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No puede eliminar inscritos de días anteriores al registro. Comuníquese con su administrador.',
                ]);
            }

            $deleted = EmpresarioActividad::where('slug', $request->slug)
                ->whereIn('id', $request->ids)
                ->delete();

            $totalAsesorias = EmpresarioActividad::where('slug', $request->slug)
                ->where('personal_asesoria', 1)
                ->count();

            $totalFormalizaciones = EmpresarioActividad::where('slug', $request->slug)
                ->where('personal_formalizacion', 1)
                ->count();

            $actividad->total_asesorias = $totalAsesorias;
            $actividad->total_formalizaciones = $totalFormalizaciones;
            $actividad->save();

            return response()->json([
                'status' => 200,
                'message' => "{$deleted} inscrito(s) eliminado(s) correctamente",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al eliminar inscritos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function descargarPantillaInscritos()
    {
        $path = storage_path('app/plantillas/plantilla_importar_inscritos.xlsx');

        if (! file_exists($path)) {
            return response()->json([
                'message' => 'Archivo no encontrado',
            ], 404);
        }

        return response()->download(
            $path,
            'plantilla_importar_inscritos.xlsx'
        );
    }

    // UGSE INSCRITOS
    public function descargarPantillaInscritosUgsc()
    {
        $path = storage_path('app/plantillas/plantilla_ugsc_upload.xlsx');

        if (! file_exists($path)) {
            return response()->json([
                'message' => 'Archivo no encontrado',
            ], 404);
        }

        return response()->download(
            $path,
            'plantilla_ugsc_upload.xlsx'
        );
    }

    public function actualizarTotalParticipantes(Request $request): JsonResponse
    {
        try {

            $request->validate([
                'unidad' => 'nullable|integer|in:1,2,3',
            ]);

            $user = auth()->user();

            $actividades = ActividadPnte::when(
                $request->filled('unidad'),
                function ($q) use ($request) {
                    $q->where('unidad', $request->input('unidad'));
                }
            )
                ->when(
                    $user->rol == 2,
                    function ($q) use ($user) {
                        $q->where('representante_id', $user->id);
                    }
                )
                ->orderByDesc('id')
                ->limit(200)
                ->get();

            foreach ($actividades as $actividad) {

                $total = EmpresarioActividad::where(
                    'slug',
                    $actividad->slug
                )->count();

                $totalAsesorias = EmpresarioActividad::where(
                    'slug',
                    $actividad->slug
                )
                    ->where('personal_asesoria', 1)
                    ->count();

                $totalFormalizaciones = EmpresarioActividad::where(
                    'slug',
                    $actividad->slug
                )
                    ->where('personal_formalizacion', 1)
                    ->count();

                $actividad->update([
                    'total_participantes' => $total,
                    'total_asesorias' => $totalAsesorias,
                    'total_formalizaciones' => $totalFormalizaciones,
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Totales actualizados correctamente.',
                'procesados' => $actividades->count(),
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Error al actualizar los totales.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeOrUpdateDescripcion(Request $request)
    {
        try {

            $request->validate([
                'slug_actividad_pnte' => 'required|string',
                'descripcion' => 'nullable|string',
                'mensaje_finalizacion' => 'nullable|string',
                'mensaje_correo' => 'nullable|string',
                'mensaje_recordatorio' => 'nullable|string',
            ]);

            $descripcion = SedDescripcion::updateOrCreate(

                [
                    'slug_actividad_pnte' => $request->slug_actividad_pnte,
                ],

                [
                    'descripcion' => $request->descripcion,
                    'mensaje_finalizacion' => $request->mensaje_finalizacion,
                    'mensaje_correo' => $request->mensaje_correo,
                    'mensaje_recordatorio' => $request->mensaje_recordatorio,
                ]
            );

            return response()->json([
                'status' => 200,
                'message' => 'Registro guardado correctamente.',
                'data' => $descripcion,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error al guardar el registro.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getDescripcionBySlug($slug)
    {
        try {

            $descripcion = SedDescripcion::where('slug_actividad_pnte', $slug)
                ->first();

            if (! $descripcion) {

                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontró información para este slug.',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Información obtenida correctamente.',
                'data' => $descripcion,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error al obtener la información.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateAsistenciaFecha(Request $request)
    {
        try {

            $request->validate([
                'slug' => 'required|string',
                'numero_dni' => 'required|string',
                'check' => 'required|boolean',
                'date' => 'nullable|string',
            ]);

            $registro = EmpresarioActividad::where('slug', $request->slug)
                ->where('numero_dni', $request->numero_dni)
                ->first();

            if (! $registro) {

                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontró el registro.',
                ], 404);
            }

            $registro->update([
                'fecha_asistencia' => $request->check
                    ? $request->date
                    : null,
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Asistencia actualizada correctamente.',
                'data' => $registro,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error al actualizar la asistencia.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function registrarAccesoToEmail(Request $request)
    {
        $registro = EmpresarioActividad::where('slug', $request->slug)
            ->where('numero_dni', $request->dni)
            ->firstOrFail();

        $this->updateAsistenciaFecha(new Request([
            'slug' => $request->slug,
            'numero_dni' => $request->dni,
            'check' => true,
            'date' => now()->format('Y-m-d H:i:s'),
        ]));

        return redirect()->away($registro->link);
    }

    public function attendanceSummaryBySlug(Request $request, $slug)
    {
        try {

            $baseQuery = EmpresarioActividad::where('slug', $slug);

            // ✅ FILTRO: year
            if ($request->filled('year')) {
                $baseQuery->whereYear('fecha_seleccionada', $request->input('year'));
            }

            // ✅ FILTRO: dateEvent
            if ($request->filled('dateEvent')) {
                $baseQuery->whereDate('fecha_seleccionada', $request->input('dateEvent'));
            }

            $total = $baseQuery->count();

            $asistieron = (clone $baseQuery)->whereNotNull('fecha_asistencia')->count();

            $noAsistieron = (clone $baseQuery)->whereNull('fecha_asistencia')->count();

            $pruebasEntrada = (clone $baseQuery)->whereRaw('JSON_LENGTH(test_entrada) > 0')->count();

            $pruebasSalida = (clone $baseQuery)->whereRaw('JSON_LENGTH(test_salida) > 0')->count();

            $confirmaron = (clone $baseQuery)->where('asistire', 1)->count();

            $noAsistiran = (clone $baseQuery)->where('asistire', 0)->count();

            $encuestasSatisfaccion = sedQuestionAnswer::where('slug_sed', $slug)
                ->distinct('dni')
                ->count('dni');

            return response()->json([
                'status' => 200,
                'message' => 'Resumen de asistencia obtenido correctamente.',
                'data' => [
                    'slug' => $slug,
                    'total' => $total,
                    'asistieron' => $asistieron,
                    'no_asistieron' => $noAsistieron,
                    'pruebas_entrada' => $pruebasEntrada,
                    'pruebas_salida' => $pruebasSalida,
                    'confirmaron' => $confirmaron,
                    'no_asistiran' => $noAsistiran,
                    'encuestas_satisfaccion' => $encuestasSatisfaccion,
                ],
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error al obtener el resumen.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function aprobarEvento($id)
    {
        try {

            $actividad = ActividadPnte::find($id);

            if (! $actividad) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Evento no encontrado',
                ], 404);
            }

            // Cambia entre 1 y 0
            $actividad->activo = $actividad->activo == 1 ? 0 : 1;

            $actividad->save();

            return response()->json([
                'status' => 200,
                'message' => $actividad->activo == 1
                    ? 'Evento aprobado correctamente'
                    : 'Evento desactivado correctamente',
                'data' => $actividad,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al aprobar el evento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteEvent($id)
    {
        try {

            $event = ActividadPnte::findOrFail($id);

            $event->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Evento eliminado correctamente.',
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Error al eliminar el evento.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // create para los pp093

    public function pp093Store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unidad' => 'required|integer|in:1,2,3,4,5',

            'horario' => 'required|array|min:1',
            'horario.*.id' => 'required',
            'horario.*.fecha' => 'required|date_format:Y-m-d',
            'horario.*.horaInicio' => 'required|string',
            'horario.*.horaFin' => 'required|string',

            'tipo_actividad_id' => 'required|exists:tipo_actividad,id',
            'nombre_actividad_id' => 'required|exists:nombre_actividad,id',

            'tema' => 'nullable|string|max:255',

            'region' => 'required|exists:cities,id',
            'provincia' => 'required|exists:provinces,id',
            'distrito' => 'required|exists:districts,id',

            'lugar' => 'nullable|string|max:255',

            'entidad_organizadora' => 'nullable|string|max:255',
            'entidad_aliada' => 'nullable|string|max:255',

            'representante_id' => 'nullable|exists:users,id',

            'requiere_pasaje' => 'required|boolean',
            'monto_gasto' => 'nullable|max:255',

            'mypes_beneficiadas' => 'nullable|integer|min:0',

            'modalidad_id' => 'nullable|exists:modalities,id',
            'link' => 'nullable|string',

            'componente_id' => 'nullable',
            'trainer_id' => 'nullable|exists:pp_capacitadores,id',
        ]);

        $validated['representante_id'] = $validated['representante_id'] ?? Auth::id();

        $fechasExtraidas = collect($validated['horario'])->pluck('fecha')->unique()->toArray();
        $validated['fechas'] = array_values($fechasExtraidas);

        $fechaMinima = collect($validated['fechas'])
            ->map(fn ($f) => Carbon::parse($f))
            ->sortBy(fn ($d) => $d->timestamp)
            ->first();

        $validated['mes'] = (int) $fechaMinima->format('n');
        $validated['cantidad_dias'] = count($validated['fechas']);

        try {
            $actividad = DB::transaction(function () use ($validated) {
                $validated['slug'] = $this->generateUniqueSlug($validated);
                $validated['registrado_por_id'] = Auth::id();

                return ActividadPnte::create($validated);
            });

            $actividad->load('representante');

            try {
                $resultado = (new GoogleMeetCalendarService)->crearEventosParaActividad(
                    $actividad,
                    $validated['horario'],
                    $validated['tema'] ?? null
                );

                // Guardamos el enlace de Meet y el array de horarios con los IDs de Google Calendar
                $actividad->link = $resultado['meetLink'];
                $actividad->horario = $resultado['horarioActualizado'];
                $actividad->save();
            } catch (Throwable $eCalendar) {
                Log::error('Error al agendar en Google Calendar: '.$eCalendar->getMessage());
            }

            return response()->json([
                'status' => 200,
                'message' => 'Actividad registrada correctamente.',
                'data' => $actividad->fresh()->load([
                    'tipoActividad',
                    'nombreActividad',
                    'regionRel',
                    'provinciaRel',
                    'distritoRel',
                    'representante',
                    'modalidad',
                ]),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al registrar la actividad.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // LISTA DE INSCRITOS MODIFICADA PARA PP093

    public function inscritosPP093PorSlug(Request $request, $slug)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $search = trim($request->input('name', ''));

            $year = $request->input('year');
            $dateEvent = $request->input('dateEvent');

            $event = ActividadPnte::select('id', 'slug', 'tema', 'fechas')
                ->where('slug', $slug)
                ->first();

            if (! $event) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Actividad no encontrada.',
                ], 404);
            }

            // 👥 2. CONSULTA DE INSCRITOS
            $query = EmpresarioActividad::with([
                'empresario',
                'empresario.pais',
                'empresario.region',
                'empresario.provincia',
                'empresario.distrito',
                'empresario.actividadComercial',
                'empresario.sectorEconomico',
                'empresario.rubro',
                'empresario.tipoDocumento',
                'empresario.genero',
            ])
                ->where('actividad_id', $event->id)
                ->where('slug', $slug);

            if ($dateEvent) {
                $query->where('fecha_seleccionada', $dateEvent);
            }

            // 🔍 BUSCADOR (RUC, DNI, Nombres)
            $query->when($search, function ($q) use ($search) {
                $q->whereHas('empresario', function ($emp) use ($search) {
                    $emp->where('ruc', 'LIKE', "%{$search}%")
                        ->orWhere('numero_dni', 'LIKE', "%{$search}%")
                        ->orWhereRaw("
                        CONCAT(
                            COALESCE(apellido_paterno, ''),
                            ' ',
                            COALESCE(apellido_materno, ''),
                            ' ',
                            COALESCE(nombres, '')
                        ) LIKE ?
                    ", ["%{$search}%"]);
                });
            })
                ->orderBy('created_at', 'desc');

            $data = $query->paginate($perPage);

            // NUEVO
            $pntTest = PntTest::where('slug', $slug)->first();

            // 🔥 TRANSFORMAR LA COLECCIÓN (Mismo mapeo tuyo)
            $data->getCollection()->transform(function ($item) use ($pntTest) {

                $e = $item->empresario;

                // Resolver Test Entrada

                $testEntrada = [];

                if (! empty($item->test_entrada) && ! empty($pntTest?->test_entrada)) {

                    foreach ($item->test_entrada as $preguntaKey => $respuestaId) {

                        $numero = (int) str_replace('pregunta_', '', $preguntaKey);

                        $preguntaBD = $pntTest->test_entrada[$numero - 1] ?? null;

                        if (! $preguntaBD) {
                            continue;
                        }

                        $respuestaTexto = null;

                        foreach ($preguntaBD['opciones'] as $opcion) {
                            if ($opcion['id'] == $respuestaId) {
                                $respuestaTexto = $opcion['texto'];
                                break;
                            }
                        }

                        $testEntrada[] = [
                            'pregunta' => $preguntaBD['texto'],
                            'respuesta' => $respuestaTexto,
                            'respuesta_id' => $respuestaId,
                            'correcta' => $preguntaBD['correctaId'] == $respuestaId,
                        ];
                    }
                }

                // Resolver Test Salida

                $testSalida = [];

                if (! empty($item->test_salida) && ! empty($pntTest?->test_entrada)) {

                    foreach ($item->test_salida as $preguntaKey => $respuestaId) {

                        $numero = (int) str_replace('pregunta_', '', $preguntaKey);

                        // 👇 Mismo cambio aquí: banco de preguntas es test_entrada
                        $preguntaBD = $pntTest->test_entrada[$numero - 1] ?? null;

                        if (! $preguntaBD) {
                            continue;
                        }

                        $respuestaTexto = null;

                        foreach ($preguntaBD['opciones'] as $opcion) {
                            if ($opcion['id'] == $respuestaId) {
                                $respuestaTexto = $opcion['texto'];
                                break;
                            }
                        }

                        $testSalida[] = [
                            'pregunta' => $preguntaBD['texto'],
                            'respuesta' => $respuestaTexto,
                            'respuesta_id' => $respuestaId,
                            'correcta' => $preguntaBD['correctaId'] == $respuestaId,
                        ];
                    }
                }

                /* Resolver Ratings dinámicamente desde test_salida */

                $ratings = [];

                if (! empty($item->ratings) && ! empty($pntTest?->test_salida)) {

                    foreach ($item->ratings as $key => $value) {

                        // rating_id_1 => id_1
                        $ratingId = str_replace('rating_', '', $key);

                        // Buscar la pregunta en test_salida por id
                        $preguntaBD = collect($pntTest->test_salida)->firstWhere('id', $ratingId);

                        $textoPregunta = $preguntaBD['texto'] ?? $key;

                        // value es el índice de la opción (1-based)
                        $respuestaLabel = null;
                        if ($preguntaBD && isset($preguntaBD['opciones'][$value - 1])) {
                            $respuestaLabel = $preguntaBD['opciones'][$value - 1]['label'];
                        }

                        $ratings[] = [
                            'pregunta' => $textoPregunta,
                            'valor' => $value,
                            'respuesta' => $respuestaLabel,
                        ];
                    }
                }

                return [

                    'id' => $item->id,
                    'actividad_id' => $item->actividad_id,
                    'slug' => $item->slug,

                    'fecha_asistencia' => $item->fecha_asistencia ? true : false,
                    'horario_asistencia' => $item->fecha_asistencia,

                    'numero_dni' => $item->numero_dni,

                    // DATOS EMPRESARIO

                    'ruc' => $e?->ruc,
                    'razon_social' => ! empty($e?->razon_social) ? mb_strtoupper($e->razon_social, 'UTF-8') : null,
                    'nombre_comercial' => ! empty($e?->nombre_comercial) ? mb_strtoupper($e->nombre_comercial, 'UTF-8') : null,
                    'sector_economico_id' => $e?->sector_economico_id,
                    'sector_economico_nombre' => ! empty($e?->sectorEconomico?->name) ? mb_strtoupper($e->sectorEconomico->name, 'UTF-8') : null,
                    'rubro_id' => $e?->rubro_id,
                    'rubro_nombre' => ! empty($e?->rubro?->name) ? mb_strtoupper($e->rubro->name, 'UTF-8') : null,
                    'actividad_comercial_id' => $e?->actividad_comercial_id,
                    'actividad_comercial_nombre' => ! empty($e?->actividadComercial?->name)
                        ? mb_strtoupper($e->actividadComercial->name, 'UTF-8')
                        : (! empty($e?->actividad_comercial_nombre)
                            ? mb_strtoupper($e->actividad_comercial_nombre, 'UTF-8')
                            : null),

                    'region_id' => $e?->region_id,
                    'region_nombre' => $e?->region?->name,
                    'provincia_id' => $e?->provincia_id,
                    'provincia_nombre' => $e?->provincia?->name,
                    'distrito_id' => $e?->distrito_id,
                    'distrito_nombre' => $e?->distrito?->name,

                    'direccion' => ! empty($e?->direccion)
                        ? mb_strtoupper($e->direccion, 'UTF-8')
                        : null,

                    'pais_id' => $e?->pais_id,
                    'pais_nombre' => $e?->pais?->name,

                    'tipo_documento_id' => $e?->tipo_documento_id,
                    'tipo_documento_nombre' => $e?->tipoDocumento?->avr,

                    'numero_dni_empresario' => $e?->numero_dni,

                    'apellido_paterno' => ! empty($e?->apellido_paterno)
                        ? mb_strtoupper($e->apellido_paterno, 'UTF-8')
                        : null,

                    'apellido_materno' => ! empty($e?->apellido_materno)
                        ? mb_strtoupper($e->apellido_materno, 'UTF-8')
                        : null,

                    'nombres' => ! empty($e?->nombres)
                        ? mb_strtoupper($e->nombres, 'UTF-8')
                        : null,

                    'nombre_completo' => ! empty(trim(($e?->apellido_paterno ?? '').' '.($e?->apellido_materno ?? '').' '.($e?->nombres ?? '')))
                        ? mb_strtoupper(trim(($e?->apellido_paterno ?? '').' '.($e?->apellido_materno ?? '').' '.($e?->nombres ?? '')), 'UTF-8')
                        : null,

                    'genero_id' => $e?->genero_id,
                    'genero_avr' => $e?->genero?->avr,

                    'discapacidad' => $e?->discapacidad,
                    'discapacidad_nombre' => isset($e?->discapacidad)
                        ? ($e->discapacidad ? 'SI' : 'NO')
                        : null,

                    'celular' => $e?->celular,
                    'correo_electronico' => $e?->correo_electronico,

                    'cargo_empresa_id' => $e?->cargo_empresa_id,
                    'fecha_nacimiento' => $e?->fecha_nacimiento,
                    'edad' => $e?->edad,
                    'como_entero' => $e?->como_entero,

                    'personal_asesoria' => $item->personal_asesoria,
                    'personal_formalizacion' => $item->personal_formalizacion,

                    'fecha_seleccionada' => $item->fecha_seleccionada,
                    'horario_inicio' => $item->horario_inicio,
                    'horario_fin' => $item->horario_fin,

                    // NUEVOS CAMPOS

                    'c_constancia' => (bool) $item->c_constancia,

                    'fecha_te' => $item->fecha_te,
                    'fecha_ts' => $item->fecha_ts,

                    'test_entrada' => $testEntrada,

                    'test_salida' => $testSalida,

                    'caso_practico' => $item->caso_practico,

                    'ratings' => $ratings,

                    'sugerencias' => $item->sugerencias,
                ];
            });

            return response()->json([
                'status' => 200,
                'data' => $data,
                'event' => $event,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener inscritos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function permissionToDelete($slug)
    {
        $actividad = ActividadPnte::where('slug', $slug)->firstOrFail();

        $actividad->eliminar = $actividad->eliminar === 1 ? null : 1;

        $actividad->save();

        return response()->json([
            'status' => 200,
            'message' => 'Permiso de eliminación actualizado correctamente.',
        ]);
    }

    public function toggleFormularioRegistro($slug)
    {

        $actividad = ActividadPnte::where('slug', $slug)->firstOrFail();

        $actividad->prendido = $actividad->prendido === 1 ? 0 : 1;

        $actividad->save();

        return response()->json([
            'status' => 200,
            'message' => 'Formulario de registro actualizado.',
            'prendido' => $actividad->prendido,
        ]);
    }

    public function enviaEmailRecordatoriosPP093(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string',
            'dateEvent' => 'required|date_format:Y-m-d',
        ]);

        try {
            $actividad = ActividadPnte::where('slug', $validated['slug'])->first();

            if (! $actividad) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Actividad no encontrada.',
                ], 404);
            }

            $inscritos = EmpresarioActividad::where('slug', $validated['slug'])
                ->where('fecha_seleccionada', $validated['dateEvent'])
                ->with('empresario')
                ->get();

            if ($inscritos->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No hay inscritos para la fecha indicada.',
                ], 404);
            }

            $mailer = 'hostinger3k';
            $enviados = 0;
            $sinCorreo = 0;

            foreach ($inscritos as $inscrito) {
                $empresario = $inscrito->empresario;
                $correo = $empresario?->correo_electronico;

                if (empty($correo)) {
                    $sinCorreo++;

                    continue;
                }

                $nombreCompleto = trim(
                    ($empresario->apellido_paterno ?? '').' '.
                    ($empresario->apellido_materno ?? '').' '.
                    ($empresario->nombres ?? '')
                );

                $dataUsuario = [
                    'nombres' => ! empty($nombreCompleto) ? $nombreCompleto : 'Usuario',
                    'correo_electronico' => $correo,
                ];

                $actividadItem = [
                    'id' => $actividad->id,
                    'slug' => $actividad->slug,
                    'tema' => $actividad->tema,
                    'entidad_organizadora' => $actividad->entidad_organizadora ?? 'Plataforma PNTE',
                    'lugar' => $actividad->lugar ?? 'Virtual',
                    'link_meet' => $actividad->link,
                    'link_test' => 'https://inscripcion.soporte-pnte.com/pp093-test-entrada/'
                        .$actividad->slug
                        .'?'
                        .http_build_query([
                            'id' => $actividad->id,
                            'date' => $inscrito->fecha_seleccionada,
                            'hourStart' => $inscrito->horario_inicio,
                            'hourEnd' => $inscrito->horario_fin,
                        ]),
                    'fecha_seleccionada' => date('d/m/Y', strtotime($inscrito->fecha_seleccionada)),
                    'horario_inicio' => $inscrito->horario_inicio,
                    'horario_fin' => $inscrito->horario_fin,
                ];

                SendRecordatorioPP093DesdeAdminJob::dispatch(
                    $correo,
                    $dataUsuario,
                    $actividadItem,
                    $mailer
                );

                $enviados++;
            }

            return response()->json([
                'status' => 200,
                'message' => "Se programaron {$enviados} correo(s) de recordatorio.",
                'data' => [
                    'total_inscritos' => $inscritos->count(),
                    'emails_programados' => $enviados,
                    'inscritos_sin_correo' => $sinCorreo,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Error al enviar recordatorios PP093: '.$e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error al enviar los recordatorios.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // IMPORTAR PARA LAS FERIAS EN FORMATO JSON

    public function importEmpresariosJson(Request $request, $slug): JsonResponse
    {
        $payload = $request->input('data');

        if (! is_array($payload)) {
            return response()->json([
                'success' => false,
                'message' => 'El campo data debe ser un array.',
            ], 422);
        }

        $actividad = ActividadPnte::where('slug', $slug)->firstOrFail();

        $importados = 0;
        $errores = [];

        DB::transaction(function () use ($payload, $actividad, $slug, &$importados, &$errores) {
            foreach ($payload as $indice => $item) {
                $item = is_array($item) ? $item : (array) $item;

                $numeroPosicion = $indice + 1;

                try {
                    $documento = $item['num_doc'] ?? $item['numero_dni'] ?? null;

                    if ($documento === null || trim((string) $documento) === '') {
                        throw new \Exception('No se encontró num_doc en el registro.');
                    }

                    $dataEmpresario = [
                        'ruc' => $item['ruc'] ?? null,
                        'razon_social' => $item['razon_social'] ?? null,
                        'nombre_comercial' => $item['nombre_comercial'] ?? null,
                        'sector_economico_id' => $item['sector'] ?? null,
                        'rubro_id' => $item['rubro'] ?? null,
                        'actividad_comercial_id' => $item['actividad_comercial'] ?: null,
                        'region_id' => $item['departamento'] ?? null,
                        'provincia_id' => $item['provincia'] ?? null,
                        'distrito_id' => $item['distrito'] ?? null,
                        'direccion' => $item['domicilio'] ?? null,
                        'pais_id' => $item['pais'] ?? null,
                        'tipo_documento_id' => $item['tipo_doc'] ?? null,
                        'numero_dni' => $documento,
                        'apellido_paterno' => $item['apellido_pat'] ?? null,
                        'apellido_materno' => $item['apellido_mat'] ?? null,
                        'nombres' => $item['nombres'] ?? null,
                        'genero_id' => (isset($item['genero']) && Str::lower(trim($item['genero'])) === 'masculino') ? 1 : 2,
                        'discapacidad' => (isset($item['discapacidad']) && (int) $item['discapacidad'] === 1) ? 1 : 0,
                        'celular' => $this->extraerNueveDigitos($item['telefono'] ?? null),
                        'correo_electronico' => $item['correo'] ?? null,
                        'fecha_nacimiento' => $item['fecha_nacimiento'] ?? null,
                        'edad' => $item['edad'] ?? null,
                    ];

                    $empresario = Empresario::create($dataEmpresario);

                    $actividadId = $actividad->id;

                    EmpresarioActividad::create([
                        'actividad_id' => $actividadId,
                        'slug' => $slug,
                        'empresario_id' => $empresario->id,
                        'numero_dni' => $documento,
                    ]);

                    EmpresarioEmprendimiento::create([
                        'empresario_id' => $empresario->id,
                        'actividad_id' => $actividadId,
                        'redes_sociales' => $this->construitRedesSociales($item),
                        'pertenece_gremio' => $item['pertenece_gremio'] ?? '0',
                        'nombre_gremio' => $item['nombre_gremio'] ?? null,
                        'cap_prod_mensual' => $item['capacidad_producccion_mensual'] ?? null,
                        'porc_prod_planta' => $item['porcentaje_produccion_planta_propia'] ?? null,
                        'porc_prod_maquila' => $item['porcentaje_produccion_maquila'] ?? null,
                        'tiene_puntos_venta' => $item['cuenta_punto_venta'] ?? '0',
                        'num_puntos_ventas' => $item['numeros_punto_venta'] ?? null,
                        'desc_negocio' => isset($item['explicacion']) ? trim($item['explicacion']) : null,
                        'pos' => $this->siNoToBool($item['cuenta_pos'] ?? null),
                        'yape_plim' => $this->siNoToBool($item['cuenta_pagos'] ?? null),
                        'tiene_tiendas' => $this->siNoToBool($item['tienda_virtual'] ?? null),
                        'nombre_tienda' => $item['tienda_virtual_otro'] ?? null,
                        'tiene_delivery' => $this->siNoToBool($item['delivery'] ?? null),
                        'factura_electronica' => $this->siNoToBool($item['emite_factura'] ?? null),
                        'participado_produce' => $this->siNoToBool($item['servicio_produce'] ?? null),
                        'nombre_servicio' => $item['servicio_produce_otro'] ?? null,
                        'participado_feria' => $this->siNoToBool($item['participado_feria'] ?? null),
                        'nombre_feria' => $item['participado_otro'] ?? null,
                        'formalizado_produce' => $this->siNoToBool($item['formalizado'] ?? null),
                        'indecopi' => $item['marca_registrada_indecopi'] ?? '0',
                        'logros_empresa' => isset($item['logros']) ? trim($item['logros']) : null,
                        'terminos_condiciones' => $item['accept_terms'] ?? '0',
                    ]);

                    $importados++;
                } catch (Throwable $e) {
                    $errores[] = [
                        'indice' => $numeroPosicion,
                        'numero_dni' => $item['num_doc'] ?? $item['numero_dni'] ?? null,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        return response()->json([
            'success' => count($errores) === 0,
            'message' => count($errores) === 0
                ? 'Empresarios importados correctamente.'
                : 'Se importaron algunos empresarios, pero hubo errores.',
            'total_recibido' => count($payload),
            'importados' => $importados,
            'fallidos' => count($errores),
            'errores' => $errores,
        ]);
    }

    private function siNoToBool($valor): int
    {
        if ($valor === null) {
            return 0;
        }

        return Str::upper(trim((string) $valor)) === 'SI' ? 1 : 0;
    }

    private function construitRedesSociales(array $item): array
    {
        $redes = [];

        $mapa = [
            'pagina' => 'Web',
            'facebook' => 'Facebook',
            'imstagram' => 'Instagram',
        ];

        foreach ($mapa as $campo => $nombre) {
            $link = isset($item[$campo]) ? trim((string) $item[$campo]) : '';

            if ($link === '') {
                continue;
            }

            $link = str_replace(['\\/', '\\'], '', $link);

            $redes[] = [
                'name' => $nombre,
                'link' => $link,
            ];
        }

        return $redes;
    }

    private function extraerNueveDigitos($telefono): ?string
    {
        if ($telefono === null) {
            return null;
        }

        $telefono = trim((string) $telefono);

        if ($telefono === '') {
            return null;
        }

        $digitos = preg_replace('/\D/', '', $telefono);

        if ($digitos === null || $digitos === '') {
            return null;
        }

        return substr($digitos, -9);
    }

    // FERIAS + DETALLE DE LAS PREGUNTAS

    public function inscritosFeriaPorSlug(Request $request, $slug)
    {
        try {

            $perPage = $request->input('pageSize', 10);
            $search = trim($request->input('name', ''));

            $event = ActividadPnte::select(
                'id',
                'slug',
                'tema',
                'fechas',
                'nombre_actividad_id'
            )
                ->where('slug', $slug)
                ->first();

            $query = EmpresarioActividad::with([
                'emprendimiento',
                'empresario',
                'empresario.pais',
                'empresario.region',
                'empresario.provincia',
                'empresario.distrito',
                'empresario.actividadComercial',
                'empresario.sectorEconomico',
                'empresario.rubro',
                'empresario.tipoDocumento',
                'empresario.genero',
            ])

                ->where('slug', $slug)

                // 🔥 BUSCADOR
                ->when($search, function ($q) use ($search) {

                    $q->whereHas('empresario', function ($emp) use ($search) {

                        $emp->where('ruc', 'LIKE', "%{$search}%")

                            ->orWhere('numero_dni', 'LIKE', "%{$search}%")

                            ->orWhereRaw("
                            CONCAT(
                                COALESCE(apellido_paterno, ''),
                                ' ',
                                COALESCE(apellido_materno, ''),
                                ' ',
                                COALESCE(nombres, '')
                            ) LIKE ?
                        ", ["%{$search}%"]);
                    });
                })

                ->orderBy('created_at', 'desc');

            $data = $query->paginate($perPage);

            // 🔥 TRANSFORMAR
            $data->getCollection()->transform(function ($item) {

                $e = $item->empresario;

                return [
                    'id' => $item->id,
                    'actividad_id' => $item->actividad_id,
                    'slug' => $item->slug,
                    'fecha_asistencia' => $item->fecha_asistencia ? true : false,
                    'asistire' => $item->asistire,
                    'numero_dni' => $item->numero_dni,

                    // 🔥 DATOS EMPRESARIO
                    'ruc' => $e?->ruc,

                    'razon_social' => ! empty($e?->razon_social)
                        ? mb_strtoupper($e->razon_social, 'UTF-8')
                        : null,

                    'nombre_comercial' => ! empty($e?->nombre_comercial)
                        ? mb_strtoupper($e->nombre_comercial, 'UTF-8')
                        : null,

                    'sector_economico_id' => $e?->sector_economico_id,

                    'sector_economico_nombre' => ! empty($e?->sectorEconomico?->name)
                        ? mb_strtoupper($e->sectorEconomico->name, 'UTF-8')
                        : null,

                    'rubro_id' => $e?->rubro_id,

                    'rubro_nombre' => ! empty($e?->rubro?->name)
                        ? mb_strtoupper($e->rubro->name, 'UTF-8')
                        : null,

                    'actividad_comercial_id' => $e?->actividad_comercial_id,

                    'actividad_comercial_nombre' => ! empty($e?->actividadComercial?->name)
                        ? mb_strtoupper($e->actividadComercial->name, 'UTF-8')
                        : (
                            ! empty($e?->actividad_comercial_nombre)
                            ? mb_strtoupper($e->actividad_comercial_nombre, 'UTF-8')
                            : null
                        ),

                    'region_id' => $e?->region_id,
                    'region_nombre' => $e?->region?->name,

                    'provincia_id' => $e?->provincia_id,
                    'provincia_nombre' => $e?->provincia?->name,

                    'distrito_id' => $e?->distrito_id,
                    'distrito_nombre' => $e?->distrito?->name,

                    'direccion' => ! empty($e?->direccion)
                        ? mb_strtoupper($e->direccion, 'UTF-8')
                        : null,

                    'pais_id' => $e?->pais_id,
                    'pais_nombre' => $e?->pais?->name,

                    'tipo_documento_id' => $e?->tipo_documento_id,

                    'tipo_documento_nombre' => $e?->tipoDocumento?->avr,

                    'numero_dni_empresario' => $e?->numero_dni,

                    'apellido_paterno' => ! empty($e?->apellido_paterno)
                        ? mb_strtoupper($e->apellido_paterno, 'UTF-8')
                        : null,

                    'apellido_materno' => ! empty($e?->apellido_materno)
                        ? mb_strtoupper($e->apellido_materno, 'UTF-8')
                        : null,

                    'nombres' => ! empty($e?->nombres)
                        ? mb_strtoupper($e->nombres, 'UTF-8')
                        : null,

                    'nombre_completo' => ! empty(trim(
                        ($e?->apellido_paterno ?? '').' '.
                            ($e?->apellido_materno ?? '').' '.
                            ($e?->nombres ?? '')
                    ))
                        ? mb_strtoupper(
                            trim(
                                ($e?->apellido_paterno ?? '').' '.
                                    ($e?->apellido_materno ?? '').' '.
                                    ($e?->nombres ?? '')
                            ),
                            'UTF-8'
                        )
                        : null,

                    'genero_id' => $e?->genero_id,
                    'genero_avr' => $e?->genero?->avr,

                    'discapacidad' => $e?->discapacidad,

                    'discapacidad_nombre' => isset($e?->discapacidad)
                        ? ($e->discapacidad ? 'SI' : 'NO')
                        : null,

                    'celular' => $e?->celular,

                    'correo_electronico' => $e?->correo_electronico,

                    'cargo_empresa_id' => $e?->cargo_empresa_id,

                    'fecha_nacimiento' => $e?->fecha_nacimiento,

                    'edad' => $e?->edad,

                    'como_entero' => $e?->como_entero,

                    'personal_asesoria' => $item->personal_asesoria,

                    'personal_formalizacion' => $item->personal_formalizacion,

                    'coop_ruc' => $e?->coop_ruc,
                    'coop_razon_social' => $e?->coop_razon_social,
                    'coop_rol' => $e?->coop_rol,

                    'nombre_mercado' => $e?->nombre_mercado,

                    'emprendimiento' => ($emp = $item->emprendimiento) ? [
                        'empresario_id' => $emp->empresario_id,
                        'actividad_id' => $emp->actividad_id,
                        'redes_sociales' => $emp->redes_sociales,
                        'pertenece_gremio' => $this->sino($emp->pertenece_gremio),
                        'nombre_gremio' => $emp->nombre_gremio,
                        'cap_prod_mensual' => $emp->cap_prod_mensual,
                        'porc_prod_planta' => $emp->porc_prod_planta,
                        'porc_prod_maquila' => $emp->porc_prod_maquila,
                        'tiene_puntos_venta' => $this->sino($emp->tiene_puntos_venta),
                        'num_puntos_ventas' => $emp->num_puntos_ventas,
                        'desc_negocio' => $emp->desc_negocio,
                        'pos' => $this->sino($emp->pos),
                        'yape_plim' => $this->sino($emp->yape_plim),
                        'tiene_tiendas' => $this->sino($emp->tiene_tiendas),
                        'nombre_tienda' => $emp->nombre_tienda,
                        'tiene_delivery' => $this->sino($emp->tiene_delivery),
                        'factura_electronica' => $this->sino($emp->factura_electronica),
                        'participado_produce' => $this->sino($emp->participado_produce),
                        'nombre_servicio' => $emp->nombre_servicio,
                        'participado_feria' => $this->sino($emp->participado_feria),
                        'nombre_feria' => $emp->nombre_feria,
                        'formalizado_produce' => $this->sino($emp->formalizado_produce),
                        'indecopi' => $this->sino($emp->indecopi),
                        'logros_empresa' => $emp->logros_empresa,
                        'terminos_condiciones' => $this->sino($emp->terminos_condiciones),
                    ] : null,
                ];
            });

            return response()->json([
                'status' => 200,
                'data' => $data,
                'event' => $event,
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener inscritos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function sino($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return $value ? 'SI' : 'NO';
    }
}
