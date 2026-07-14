<?php

namespace App\Http\Controllers\Pnte;

use App\Http\Controllers\Controller;
use App\Models\ActividadPnte;
use App\Models\Attendance;
use App\Models\EmpresarioActividad;
use App\Models\PntTest;
use App\Models\SedDescripcion;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Support\Facades\Log;

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
        ]);


        $validated['representante_id'] =
            $validated['representante_id'] ?? Auth::id();

        // =====================================================
        // OBTENER MES DE LA FECHA MÁS ANTIGUA
        // =====================================================

        $fechaMinima = collect($validated['fechas'])
            ->map(fn($f) => Carbon::parse($f))
            ->sortBy(fn($d) => $d->timestamp)
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
            $slug = $original . '-' . $count;
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
                    'message' => 'No es posible editar esta actividad. El plazo de edición venció el ' .
                        Carbon::parse($actividad->created_at)->format('d/m/Y') .
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
            ->map(fn($f) => Carbon::parse($f))
            ->sortBy(fn($d) => $d->timestamp)
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
            'tainnerPp093:id,nombres_apellidos'
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
            ->when(!in_array($user->rol, [1, 2, 3]), function ($q) {
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
                $end     = \Carbon\Carbon::parse($to);

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

        return response()->json([
            'status'  => 200,
            'message' => 'Actividades obtenidas correctamente.',
            'data'    => [
                'current_page'    => $actividades->currentPage(),
                'data'            => $actividades->items(),
                'first_page_url'  => $actividades->url(1),
                'from'            => $actividades->firstItem(),
                'last_page'       => $actividades->lastPage(),
                'last_page_url'   => $actividades->url($actividades->lastPage()),
                'links'           => $actividades->linkCollection()->toArray(),
                'next_page_url'   => $actividades->nextPageUrl(),
                'path'            => $actividades->path(),
                'per_page'        => $actividades->perPage(),
                'prev_page_url'   => $actividades->previousPageUrl(),
                'to'              => $actividades->lastItem(),
                'total'           => $actividades->total(),
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
            ->map(fn($f) => Carbon::parse($f))
            ->sortBy(fn($d) => $d->timestamp)
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
                'fechas'
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

                    'actividad_comercial_nombre' => !empty($e?->actividadComercial?->name)
                        ? mb_strtoupper($e->actividadComercial->name, 'UTF-8')
                        : (
                            !empty($e?->actividad_comercial_nombre)
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
                        ($e?->apellido_paterno ?? '') . ' ' .
                            ($e?->apellido_materno ?? '') . ' ' .
                            ($e?->nombres ?? '')
                    ))
                        ? mb_strtoupper(
                            trim(
                                ($e?->apellido_paterno ?? '') . ' ' .
                                    ($e?->apellido_materno ?? '') . ' ' .
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
                    'coop_rol' => $e?->coop_rol
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
                    'total_participantes'   => $total,
                    'total_asesorias'       => $totalAsesorias,
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

            return response()->json([
                'status' => 200,
                'message' => 'Resumen de asistencia obtenido correctamente.',
                'data' => [
                    'slug' => $slug,
                    'total' => $total,
                    'asistieron' => $asistieron,
                    'no_asistieron' => $noAsistieron,
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

            // Se cambió el origen: ahora 'horario' es el requerido
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

        // Asignar representante por defecto si no viene
        $validated['representante_id'] = $validated['representante_id'] ?? Auth::id();

        // =====================================================
        // EXTRAER LAS FECHAS DESDE EL ARRAY DE HORARIO
        // =====================================================
        $fechasExtraidas = collect($validated['horario'])->pluck('fecha')->unique()->toArray();
        $validated['fechas'] = array_values($fechasExtraidas); // Guardar el array limpio de fechas

        // =====================================================
        // OBTENER MES DE LA FECHA MÁS ANTIGUA
        // =====================================================
        $fechaMinima = collect($validated['fechas'])
            ->map(fn($f) => Carbon::parse($f))
            ->sortBy(fn($d) => $d->timestamp)
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

            if (!$event) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Actividad no encontrada.'
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

            $ratingsLabel = [
                1 => 'Muy insatisfecho',
                2 => 'Insatisfecho',
                3 => 'Poco satisfecho',
                4 => 'Satisfecho',
                5 => 'Muy satisfecho',
            ];

            $ratingsQuestions = [
                'rating_1' => 'Se cumplió con tus expectativas personales 1',
                'rating_2' => 'Se cumplió con tus expectativas personales 2',
                'rating_3' => 'Se cumplió con tus expectativas personales 3',
                'rating_4' => 'Se cumplió con tus expectativas personales 4',
                'rating_5' => 'Se cumplió con tus expectativas personales 5',
            ];

            // 🔥 TRANSFORMAR LA COLECCIÓN (Mismo mapeo tuyo)
            $data->getCollection()->transform(function ($item) use ($pntTest, $ratingsLabel, $ratingsQuestions) {

                $e = $item->empresario;

                // Resolver Test Entrada

                $testEntrada = [];

                if (!empty($item->test_entrada) && !empty($pntTest?->test_entrada)) {

                    foreach ($item->test_entrada as $preguntaKey => $respuestaId) {

                        $numero = (int) str_replace('pregunta_', '', $preguntaKey);

                        $preguntaBD = $pntTest->test_entrada[$numero - 1] ?? null;

                        if (!$preguntaBD) {
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

                if (!empty($item->test_salida) && !empty($pntTest?->test_entrada)) {

                    foreach ($item->test_salida as $preguntaKey => $respuestaId) {

                        $numero = (int) str_replace('pregunta_', '', $preguntaKey);

                        // 👇 Mismo cambio aquí: banco de preguntas es test_entrada
                        $preguntaBD = $pntTest->test_entrada[$numero - 1] ?? null;

                        if (!$preguntaBD) {
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

                /* Resolver Ratings */

                $ratings = [];

                if (!empty($item->ratings)) {

                    foreach ($item->ratings as $key => $value) {

                        $ratings[] = [
                            'pregunta' => $ratingsQuestions[$key] ?? $key,
                            'valor' => $value,
                            'respuesta' => $ratingsLabel[$value] ?? null,
                        ];
                    }
                }

                return [

                    'id' => $item->id,
                    'actividad_id' => $item->actividad_id,
                    'slug' => $item->slug,

                    'fecha_asistencia' => $item->fecha_asistencia ? true : false,

                    'numero_dni' => $item->numero_dni,

                    // DATOS EMPRESARIO

                    'ruc' => $e?->ruc,
                    'razon_social' => !empty($e?->razon_social) ? mb_strtoupper($e->razon_social, 'UTF-8') : null,
                    'nombre_comercial' => !empty($e?->nombre_comercial) ? mb_strtoupper($e->nombre_comercial, 'UTF-8') : null,
                    'sector_economico_id' => $e?->sector_economico_id,
                    'sector_economico_nombre' => !empty($e?->sectorEconomico?->name) ? mb_strtoupper($e->sectorEconomico->name, 'UTF-8') : null,
                    'rubro_id' => $e?->rubro_id,
                    'rubro_nombre' => !empty($e?->rubro?->name) ? mb_strtoupper($e->rubro->name, 'UTF-8') : null,
                    'actividad_comercial_id' => $e?->actividad_comercial_id,
                    'actividad_comercial_nombre' => !empty($e?->actividadComercial?->name)
                        ? mb_strtoupper($e->actividadComercial->name, 'UTF-8')
                        : (!empty($e?->actividad_comercial_nombre)
                            ? mb_strtoupper($e->actividad_comercial_nombre, 'UTF-8')
                            : null),

                    'region_id' => $e?->region_id,
                    'region_nombre' => $e?->region?->name,
                    'provincia_id' => $e?->provincia_id,
                    'provincia_nombre' => $e?->provincia?->name,
                    'distrito_id' => $e?->distrito_id,
                    'distrito_nombre' => $e?->distrito?->name,

                    'direccion' => !empty($e?->direccion)
                        ? mb_strtoupper($e->direccion, 'UTF-8')
                        : null,

                    'pais_id' => $e?->pais_id,
                    'pais_nombre' => $e?->pais?->name,

                    'tipo_documento_id' => $e?->tipo_documento_id,
                    'tipo_documento_nombre' => $e?->tipoDocumento?->avr,

                    'numero_dni_empresario' => $e?->numero_dni,

                    'apellido_paterno' => !empty($e?->apellido_paterno)
                        ? mb_strtoupper($e->apellido_paterno, 'UTF-8')
                        : null,

                    'apellido_materno' => !empty($e?->apellido_materno)
                        ? mb_strtoupper($e->apellido_materno, 'UTF-8')
                        : null,

                    'nombres' => !empty($e?->nombres)
                        ? mb_strtoupper($e->nombres, 'UTF-8')
                        : null,

                    'nombre_completo' => !empty(trim(($e?->apellido_paterno ?? '') . ' ' . ($e?->apellido_materno ?? '') . ' ' . ($e?->nombres ?? '')))
                        ? mb_strtoupper(trim(($e?->apellido_paterno ?? '') . ' ' . ($e?->apellido_materno ?? '') . ' ' . ($e?->nombres ?? '')), 'UTF-8')
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
}
