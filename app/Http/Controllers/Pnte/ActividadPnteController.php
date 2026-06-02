<?php

namespace App\Http\Controllers\Pnte;

use App\Http\Controllers\Controller;
use App\Models\ActividadPnte;
use App\Models\Attendance;
use App\Models\EmpresarioActividad;
use App\Models\SedDescripcion;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'horario' => 'nullable|string',
        ]);

        // ✅ Mes: extraer el mes de la fecha más antigua del array
        $fechaMinima = collect($validated['fechas'])
            ->map(fn($f) => Carbon::parse($f))
            ->sortBy(fn($d) => $d->timestamp)
            ->first();

        $validated['mes'] = (int) $fechaMinima->format('n'); // 1-12 sin cero
        $validated['cantidad_dias'] = count($validated['fechas']);     // bonus: setear cantidad_dias

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
                'success' => false,
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
            $limiteEdicion = Carbon::parse(
                $actividad->created_at
            )->endOfDay();

            if (Carbon::now()->gt($limiteEdicion)) {

                return response()->json([
                    'status' => 403,
                    'message' =>
                    'No es posible editar esta actividad. El plazo de edición venció el ' .
                        Carbon::parse($actividad->created_at)
                        ->format('d/m/Y') .
                        ' a las 23:59. Por favor, contacte con su supervisor.',
                ], 403);
            }
        }

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
            'horario' => 'nullable|string',
        ]);

        // ✅ obtener mes de la fecha mínima
        $fechaMinima = collect($validated['fechas'])
            ->map(fn($f) => Carbon::parse($f))
            ->sortBy(fn($d) => $d->timestamp)
            ->first();

        $validated['mes'] = (int) $fechaMinima->format('n');
        $validated['cantidad_dias'] = count($validated['fechas']);

        try {

            DB::transaction(function () use (
                $actividad,
                $validated
            ) {

                $validated['actualizado_por_id'] = Auth::id();

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
        } catch (Throwable $e) {

            return response()->json([
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
                'created_at',
            ])

            // ✅ FILTRO UNIDAD
            // si viene unidad filtra
            // si no viene lista todos
            ->when(
                $request->filled('unidad'),
                function ($q) use ($request) {

                    $q->where(
                        'unidad',
                        $request->input('unidad')
                    );
                }
            )

            // ✅ FILTRO POR ROL
            ->when($user->rol == 2, function ($q) use ($user) {

                $q->where('representante_id', $user->id);
            })

            // ✅ FILTRO: asesor
            ->when($request->filled('asesor'), function ($q) use ($request) {

                $q->where(
                    'representante_id',
                    $request->input('asesor')
                );
            })

            // ✅ FILTRO: pnte
            ->when($request->filled('pnte'), function ($q) use ($request) {

                $q->where(
                    'tipo_actividad_id',
                    $request->input('pnte')
                );
            })

            // ✅ FILTRO: tipo_actividad_id
            ->when($request->filled('tipo_actividad_id'), function ($q) use ($request) {

                $q->where(
                    'tipo_actividad_id',
                    $request->input('tipo_actividad_id')
                );
            })

            // ✅ FILTRO: year
            ->when($request->filled('year'), function ($q) use ($request) {

                $year = $request->input('year');

                $q->where(
                    'fechas',
                    'LIKE',
                    "%{$year}%"
                );
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

                        $query->orWhereJsonContains(
                            'fechas',
                            $fecha
                        );

                        $current->addDay();
                    }
                });
            })

            // ✅ FILTRO: city → region
            ->when($request->filled('city'), function ($q) use ($request) {

                $q->where(
                    'region',
                    $request->input('city')
                );
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
            'cancelado' => 'required|string|max:255',
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

    public function actualizarTotalParticipantes(Request $request): JsonResponse
    {
        // ✅ unidad puede venir: 1, 2 o 3
        $request->validate([
            'unidad' => 'nullable|integer|in:1,2,3',
        ]);

        // ✅ SI ENVÍA UNIDAD → FILTRA
        // ✅ SI NO ENVÍA → TRAE TODOS
        $actividades = ActividadPnte::when(
            $request->filled('unidad'),
            function ($q) use ($request) {

                $q->where(
                    'unidad',
                    $request->input('unidad')
                );
            }
        )->get();

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
            'message' => 'Total de participantes, asesorías y formalizaciones actualizado correctamente.',
        ]);
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

    public function attendanceSummaryBySlug($slug)
    {
        try {

            $total = EmpresarioActividad::where('slug', $slug)
                ->count();

            $asistieron = EmpresarioActividad::where('slug', $slug)
                ->whereNotNull('fecha_asistencia')
                ->count();

            $noAsistieron = EmpresarioActividad::where('slug', $slug)
                ->whereNull('fecha_asistencia')
                ->count();

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

    // ******************************************************  **************
    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    // para las migraciones
    // 1. las fechas de startDate y endDate a dates
    public function generarFechasAttendance(Request $request)
    {
        set_time_limit(0);

        $lastId = 0;
        $totalProcesados = 0;

        do {
            $registros = Attendance::where('id', '>', $lastId)
                ->orderBy('id')
                ->limit(100)
                ->get();

            if ($registros->isEmpty()) {
                break;
            }

            foreach ($registros as $row) {

                if (! $row->startDate || ! $row->endDate) {
                    continue;
                }

                $start = Carbon::parse($row->startDate);
                $end = Carbon::parse($row->endDate);

                $fechas = [];

                while ($start->lte($end)) {
                    $fechas[] = $start->format('Y-m-d');
                    $start->addDay();
                }

                $row->dates = $fechas;
                $row->save();

                $lastId = $row->id;
                $totalProcesados++;
            }
        } while (true);

        return response()->json([
            'message' => 'Proceso completado ✅',
            'total_procesados' => $totalProcesados,
            'ultimo_id' => $lastId,
        ]);
    }

    // 2.
    //     SELECT DISTINCT eventsoffice_id
    // FROM attendancelist;

    // 1 = 3
    // 2 = 1
    // 3 = 2
    // 5 = 1
    // 8 = 4
    // 12 = 1
    // 13 = 4
    // 14 = 2
    // 15 = 3

    //     UPDATE attendancelist
    // SET tipo_actividad_id = CASE eventsoffice_id
    //     WHEN 1 THEN 3
    //     WHEN 2 THEN 1
    //     WHEN 3 THEN 2
    //     WHEN 5 THEN 1
    //     WHEN 8 THEN 4
    //     WHEN 12 THEN 1
    //     WHEN 13 THEN 4
    //     WHEN 14 THEN 2
    //     WHEN 15 THEN 3
    // END
    // WHERE eventsoffice_id IN (1,2,3,5,8,12,13,14,15);

    // 3.
    // SELECT DISTINCT `title` FROM attendancelist;

    public function setNombreActividad(Request $request)
    {
        // 1. Definimos el listado maestro (puedes mover esto a un config/actividades.php)
        $mappingOriginal = [
            'expo puno' => 9,
            'campaña despega tu empresa y produce-lima' => 8,
            'potencia tu empresa - digitalización' => 3,
            'potencia tu empresa - gestion empresarial' => 2,
            'feria de emprendimiento albarracino' => 9,
            'potencia tu empresa - formalización' => 1,
            'potencia tu empresa - financiamiento' => 2,
            'potencia tu empresa - gestion financiera' => 2,
            'potencia tu empresa - gestion comercial' => 2,
            'potencia tu empresa - acceso al financiamiento' => 5,
            'potencia tu empresa-formalización' => 1,
            'difusión de los servicios del pnte en el mercado modelo de neshuya' => 9,
            'difusión de los servicios del pnte' => 9,
            'potencia tu eampresa - acceso al financiamiento' => 5,
            'potencia tu empresa- formalización' => 1,
            'difusión de los servicios del pnte en la feria: "i feria municipal de servicios empresariales' => 9,
            'fortalece tu mercado' => 7,
            'despega tu empresa' => 8,
            'difusión de los servicios del pnte en la feria "campaña de formalizacion"' => 9,
            'difusión de los servicios del pnte en la campaña de formalización “despega tu empresa y produce”,' => 8,
            'difusión de los servicios del pnte en la feria "campaña formalizados ganamos todos"' => 9,
            'difucion de los servicios del pnte' => 9,
            'difusión de los servicios del pnte en la acción cívica: “te escucho, te incluyo, te represento”' => 9,
            'evento de difusion de los servicios de pnte' => 9,
            'feria laboral chiclayo' => 9,
            'potencia tu empresa - desarrollo productivo' => 4,
            'potencia tu empresa - difusion' => 9,
            'capacitación en gestión empresarial' => 2,
            'potencia tu empresa  - digitalizacion' => 3,
            'potencia  tu empresa - gestión empresarial' => 2,
            'potencia tu empresa - desarrollo empresarial / digitalizacion' => 4,
            'brindar difusión de los servicios de los cinco componentes del pnte en la campaña de formalización' => 9,
            'potencia tu empresa - formalización.' => 1,
            'primera jornada por la cooper-acción' => 9,
            'feria koshi nomabo' => 9,
            'campaña de difusión de los servicios del pnte' => 9,
            'potencia tu empresa - acceso al financiamiento.' => 5,
            'potencia tu empresa - comite artesanos' => 2,
            'caravana multisectorial' => 9,
            'potencia tu empresa - formalización"' => 1,
            'potencia tu empresa - sectores priorizados' => 4,
            'difusión de los servicios del pnte en el congreso por el día del artesano: unidos por el arte – impu' => 9,
            'difusión de los servicios del pnte en la sesión de concertación' => 9,
            'impulsamos la formalizacion en gamarra' => 1,
            'difusión de los servicios del pnte en la feria informativa socio laboral y empresarial de curahuasi' => 9,
            'difusión de los servicios del pnte en el congreso por el día del artesano' => 9,
            'capacitacion' => 1,
            'mi negocio en facebook - digitalización' => 3,
            'campaña formalizate ya' => 9,
            'difusion de los servicios que brinda el pnte en la feria nacional la semana del empleo' => 9,
            'campaña de formalización' => 9,
            'difusión de los servicios del pnte en la feria juntos por la formalización' => 9,
            'difusión de los servicios del pnte en la feria informativa por el dia de la mype' => 9,
            'potencia tu empresa- digitalización' => 3,
            'difusión de los servicios del pnte en la ruta de formalidad/licencia en una hora' => 9,
            'potencia tu empresa- gestión empresarial' => 2,
            'despega tu empresa y produce' => 8,
            'potencia tu empresa-digitalización' => 3,
            'potencia tu empresa - sectores priorizados - gestiòn empresarial' => 2,
            'brindar difusión de los servicios de los cinco componentes del pnte' => 9,
            'potencia tu mercado' => 7,
            'potencia tu empresa - financiamiento - sectores priorizados' => 5,
            'potencia tu empresa - digitalizaciòn - sectores priorizados' => 5,
            'potencia tu empresa - gestiòn empresarial - sectores priorizados' => 5,
            'difusión de los servicios del pnte en la feria regional de emprendimiento' => 9,
            'difusión de los servicios del pnte en la feria jaèn emprende' => 9,
            'capacitacion en formalización y habilidades  personales' => 1,
            'difusión de los servicios del pnte.' => 9,
            'difusión de los servicios del pnte en la feria de las plantas' => 9,
            'inauguración cde huánuco' => 9,
            'potencia tu empresa' => 1,
            'difusión de los servicios del pnte en la caravana institucional - midis' => 9,
            'feria empresarial y de empleo' => 9,
            'difucion de servicios de pnte' => 9,
            'prueba' => 1,
            'difusión de los servicios del pnte en la feria san juan pampa' => 9,
            'potencia tu empreaa - formalización' => 1,
            'gestión empresarial - calidad y atencion del cliente - sector turismo' => 2,
            'formalización - formalización empresarial' => 1,
            'formalización - formalización y tributación de mype.' => 1,
            'formalización - capacitación en formalización' => 1,
            'gestión empresarial - atención al cliente' => 2,
            'digitalización - digitalizacion' => 3,
            'acceso al financiamiento - punto de equilibrio' => 5,
            'formalización - diferencias entre pp.nn y pp.jj' => 1,
            'digitalización - monederos digitales' => 3,
            'digitalización - marketing y ventas digitales' => 3,
            'gestión empresarial - modelamiento canvas' => 2,
            'gestión empresarial - liderazgo empresaria y habilidades gerenciales' => 2,
            'formalización - beneficios de la formalización' => 1,
            'digitalización - digitalizando tu empresa' => 3,
            'formalización - remype (normatividad e inscripción de trabajadores)' => 1,
            'formalización - benefcios de la formalizacion' => 1,
            'digitalización - digitalizacion empresarial' => 3,
            'gestión empresarial - emprendimiento' => 2,
            'digitalización - herramientas digitales' => 3,
            'digitalización - whatsapp business y billeteras digitales' => 3,
            'acceso al financiamiento - aprende a diseñar tu presupuesto de efectivo' => 5,
            'gestión empresarial - gestion empresarial' => 2,
            'formalización - formalizacion empresarial y tributacion' => 1,
            'formalización - formalizate artesano' => 1,
            'gestión empresarial - idea de negocio- modelo canvas' => 2,
            'gestión empresarial - como iniciar mi idea de negocio' => 2,
            'digitalización - seguridad con las billeteras digitales' => 3,
            'gestión empresarial - gestion empresarial para mype' => 2,
            'formalización - formalización empresarial y registro mype plataforma compras my peru' => 1,
            'formalización - emprende y formaliza tu empresa' => 1,
            'gestión empresarial - complementación de competencias laborales' => 2,
            'formalización - formalizacion como pp.nn' => 1,
            'formalización - pasos para formalizar mi emprendimiento (grupo n°02)' => 1,
            'digitalización - digitalizacion empresarial para mype' => 3,
            'formalización - creacion, formalizacion y desarrollo de las micro y pequeñas empresas' => 1,
            'formalización - guia para la formalizacion' => 1,
            'formalización - tributacion' => 1,
            'gestión empresarial - registro mype plataforma compras my peru' => 2,
            'formalización - formalización y registratare en la plataforma compras my peru' => 1,
            'formalización - formalizacion y trubutacion de empresas' => 1,
            'gestión empresarial - generacion de ideas de negocios' => 2,
            'gestión empresarial - compras a mi peru' => 2,
            'gestión empresarial - compras a myperu (grupo 01)' => 2,
            'formalización - asociatividad' => 1,
            'gestión empresarial - comercializacion e innovacion para abrir nuevas lineas de negocio' => 2,
            'formalización - emprende y crea tu empresa' => 1,
            'formalización - proceso de formalizacion empresarial' => 1,
            'formalización - emprende sacs' => 1,
            'formalización - emisíon de boletas y facturación electronica' => 1,
            'gestión empresarial - guia para el registro  mype a compras myperú' => 2,
            'gestión empresarial - emprendimiento empresarial' => 2,
            'digitalización - marketing digital aplicada a las empresas de servicios de hospedaje' => 3,
            'gestión empresarial - tecnicas de venta para negocios de artesanias' => 2,
            'digitalización - gestion de redes sociales para emprendimientos de atractivos turisticos' => 3,
            'gestión empresarial - marketing estrategico' => 2,
            'acceso al financiamiento - finanzas del negocio' => 5,
            'digitalización - marketing digital y estrategia de ventas - facebook ads' => 3,
            'gestión empresarial - nuevas tendencias empresariales' => 2,
            'digitalización - marketing digital' => 3,
            'gestión empresarial - tecnicas de ventas' => 2,
            'gestión empresarial - ideas de negocios' => 2,
            'gestión empresarial - generacionde ideasde negocios' => 2,
            'formalización - formalizacion y regimenes tributarios' => 1,
            'digitalización - medios de pago digitales' => 3,
            'gestión empresarial - articulando mi negocio' => 2,
            'gestión empresarial - liderazgo empresarial y habilidades gerenciales' => 2,
            'formalización - ventajas y oportunidades de la formalizacion' => 1,
            'acceso al financiamiento - alternativas de financiamiento' => 5,
            'gestión empresarial - emprendemimiento e innovacion' => 2,
            'digitalización - marketing digital y estrategia de ventas - whatsapp business' => 3,
            'formalización - pasos para constituir  una pncn y ppjj' => 1,
            'gestión empresarial - innovación empresarial con desígn thinking' => 2,
            'gestión empresarial - habilidades blandas' => 2,
            'digitalización - pago de salarios digitales' => 3,
            'gestión empresarial - economia circular' => 2,
            'gestión empresarial - modelo de negocio- metodo canvas' => 2,
            'formalización - formalizacion tributaria' => 1,
            'gestión empresarial - lanzamiento de emprendiemientos' => 2,
            'formalización - formalización empresarial y tributaria a productores agropecuarios' => 1,
            'formalización - formalización empresarial y tributaria a artesanos' => 1,
            'formalización - formalizacion empresarial y regimenes tributarios' => 1,
            'acceso al financiamiento - estructura de costos' => 5,
            'gestión empresarial -  de modelo canvas' => 2,
            'gestión empresarial - liderazgo empresarial' => 2,
            'acceso al financiamiento - plan financiero para el negocio' => 5,
            'acceso al financiamiento - caja rápida' => 5,
            'digitalización - marketing digital y gestion de redes sociales' => 3,
            'gestión empresarial - cómo desarrollar y potenciar negocios exitosos' => 2,
            'acceso al financiamiento - flujo de caja' => 5,
            'digitalización - diseño gráfico para redes sociales canva' => 3,
            'gestión empresarial - destaca y vende más' => 2,
            'acceso al financiamiento - accede a los fondos concursables' => 5,
            'gestión empresarial - emprendimiento e idea de negocios' => 2,
            'formalización - beneficios de la formalización empresarial' => 1,
            'desarrollo productivo - bpm' => 4,
            'digitalización - crea tu catálogo digital con whatsapp business' => 3,
            'gestión empresarial - gestión empresarial exitosa' => 2,
            'acceso al financiamiento - tips para obtener un prestamo financiero' => 5,
            'digitalización - herramientas digitales para mercados' => 3,
            'digitalización - digitaliza tus canales de comunicación correo y whatsapp business' => 3,
            'gestión empresarial -  estudia tu mercado e identifica nuevas tendencias' => 2,
            'gestión empresarial - registro mype' => 2,
            'gestión empresarial - atencion al cliente y tecnicas de venta' => 2,
            'formalización - formalizacion ruc 10 y nrus' => 1,
            'gestión empresarial - técnicas en venta' => 2,
            'formalización - inscríbete al remype' => 1,
            'gestión empresarial - herramientas para la sostenibilidad del emprendimiento' => 2,
            'acceso al financiamiento - comprobantes de pago y caja rápida' => 5,
            'gestión empresarial -  liderazgo empresarial y habilidades gerenciales' => 2,
            'gestión empresarial - registro de marca y atencion al cliente' => 2,
            'desarrollo productivo - optimiza tus procesos y mejora tu mype' => 4,
            'formalización - importancia de la formalizacion' => 1,
            'acceso al financiamiento - analiza tu capacidad de pago' => 5,
            'gestión empresarial - de economia circular' => 2,
            'formalización - regimenes tributarios' => 1,
            'gestión empresarial - conociendo mi negocio' => 2,
            'acceso al financiamiento - acceso al financiamiento' => 5,
            'gestión empresarial - estudia tu mercado e identifica nuevas tendencias' => 2,
            'gestión empresarial - beneficios del neuromarketing' => 2,
            'acceso al financiamiento - mejora tus decisíones financieras' => 5,
            'formalización - formalización e inscríbete al remype' => 1,
            'digitalización - crea contenido y posíciona tu mype' => 3,
            'formalización - formalización empresarial y digitalización' => 3,
            'digitalización - fortalece tu negocio con whastapp de negocios' => 3,
            'gestión empresarial - definicion de procesos para mejorar la productividad' => 2,
            'gestión empresarial - marketing para pymes' => 2,
            'gestión empresarial - fidelización de clientes internos y externos' => 2,
            'desarrollo productivo - manúal de la 5 s' => 4,
            'gestión empresarial - economia circular como estrategia de crecimiento para mype' => 2,
            'desarrollo productivo - cuida la seguridad y salud en tu mype' => 4,
            'formalización - beneficios de la formalización de negocios' => 1,
            'formalización - proceso de formalizacion ppnn y sus ventajas' => 1,
            'gestión empresarial - idea de negocio y emprendimiento' => 2,
            'formalización - regimen tributario y beneficios de la formalización' => 1,
            'formalización - formalización empresarial e inscripción al remype' => 1,
            'formalización - mitos y verdades de la formalización empresarial' => 1,
            'digitalización - digitalización para bodegueros' => 3,
            'formalización - constitución de personas jurídicas' => 1,
            'formalización -  mitos y verdades de la formalizacion' => 1,
            'digitalización - whatsapp de negocios crea tu catalogo digital' => 3,
            'digitalización - camino a la digitalizacion' => 3,
            'gestión empresarial - conoce las estrategias y tácticas para vender más' => 2,
            'formalización - regímenes tributarios y contables y sus obligaciones' => 1,
            'gestión empresarial - idea de negocios' => 2,
            'gestión empresarial - modelo canvas' => 2,
            'gestión empresarial - ideas de negocio' => 2,
            'formalización - como formalizar y potenciar mi emprendimiento' => 1,
            'formalización - beneficios de formalizacion de las mype y uso de medios digitales' => 1,
            'formalización - proceso de constitución empresarial' => 1,
            'digitalización -  digitaliza tus canales de comunicación crea contenido y posíciona tu mype' => 3,
            'formalización - beneficios de formalizar mi emprendimiento y modalidades societarias para procompite' => 1,
            'formalización - consejos legales para emprendedores' => 1,
            'formalización - formalizacion regimenes tributarios ycaja rápida' => 1,
            'gestión empresarial - ¿cómo generamos mas oportunidades en nuevos mercados? - modelo canvas' => 2,
            'digitalización - whatsapp business' => 3,
            'acceso al financiamiento - gestión de riesgos financieros en una mype' => 5,
            'acceso al financiamiento - productos financieros para tu negocio' => 5,
            'formalización - beneficios de formalizar mi emprendimiento' => 1,
            'gestión empresarial - técnicas de venta para negocios de artesania y textil' => 2,
            'gestión empresarial - diseño y propuesta de marca' => 2,
            'formalización - formalizacion  y regimenes tributarios' => 1,
            'digitalización - medios de pagos digitales y whatsapp business' => 3,
            'formalización -  formalizacion empresarial y tributaria en el encuetro de jovenes unh 2024' => 1,
            'formalización - formalizacion empresarial y regimen tributario' => 1,
            'formalización - formalización empresarial registro mype plataforma compras my peru' => 1,
            'gestión empresarial - generacion de ideas, modelo de negocios canvas y prototipos de negocios' => 2,
            'gestión empresarial - idea de negocio' => 2,
            'formalización - formalización empreasarial' => 1,
            'digitalización - digitaliza tus canales de comunicacion' => 3,
            'gestión empresarial - estudio de mercado y nuevas tendecias' => 2,
            'gestión empresarial -  como desarrollar y potenciar negocios exitosos' => 2,
            'gestión empresarial - atencion al cliente y asociatividad' => 2,
            'gestión empresarial - ¿cómo elaborar un plan de negocios?' => 2,
            'formalización - modelo canvas y formalizando mi emprendimiento' => 1,
            'formalización - pasos y beneficios de la formalización' => 1,
            'digitalización - atencion al cliente y gestion de redes sociales' => 3,
            'gestión empresarial - gestión de atención al cliente' => 2,
            'digitalización - digitalizando mi negocio' => 3,
            'gestión empresarial - catalogo digital y destaca y vende mas' => 2,
            'gestión empresarial - modelamiento de negocios' => 2,
            'formalización - facilidades y beneficios de la formalización, a los productores alpaqueros' => 1,
            'gestión empresarial - atencion al cliente  y dijitalizacion' => 2,
            'gestión empresarial - registro de marcas' => 2,
            'formalización - mitos de la formalización' => 1,
            'digitalización - abc digital - redes sociales.' => 3,
            'gestión empresarial - segmento de mercado' => 2,
            'gestión empresarial - uso de billeteras digitales y crédito mype' => 2,
            'desarrollo productivo - gestion de inventarios y control de calidad' => 4,
            'gestión empresarial - como generar mi modelo de negocio' => 2,
            'formalización - cómo formalizar mi emprendimiento' => 1,
            'gestión empresarial - ideas y modelamiento de negocios' => 2,
            'gestión empresarial -  estrategias y tacticas para vender mas' => 2,
            'formalización - diferencia entre personas naturales y jurídicas' => 1,
            'digitalización - contenido digital para tu negocio' => 3,
            'gestión empresarial - estrategias y tácticas para vender más' => 2,
            'formalización - beneficios y facilidades de la formalización empresarial' => 1,
            'gestión empresarial -  habilidades blandas, modelamiento de negocios (modelo canva)' => 2,
            'formalización - flujo de  caja' => 2,
            'formalización - cómo generar una idea de negocio y beneficios de la formalización' => 4,
            'gestión empresarial - ¿como elaborar un plan de negocio?' => 2,
            'formalización - inscríbete en el remype' => 1,
            'gestión empresarial - estudio de mercado e identificación de nuevas tendencias' => 2,
            'formalización - formalizando mi emprendimiento' => 1,
            'gestión empresarial - modelamiento de negocios - aplicación práctica' => 2,
            'formalización - estudia tu mercado e identifica nuevas tendencias' => 1,
            'desarrollo productivo - desarrollo productivo para emprendedores' => 4,
            'formalización - proceso de constitución de empresas' => 1,
            'gestión empresarial - idea y modelo de negocio' => 2,
            'gestión empresarial - servicio de atención al cliente' => 2,
            'digitalización - administración de grupos y comunidades por whatsapp' => 3,
            'formalización - pautas para constituir una empresa, de emprendedor a empresario' => 1,
            'digitalización - ventas digitales con whatsapp business' => 3,
            'gestión empresarial - gestión comercial' => 2,
            'gestión empresarial - marketing para emprendedores' => 2,
            'gestión empresarial - estudio de mercados y nuevas tendencias' => 2,
            'formalización - formalización y constitución del negocio' => 1,
            'gestión empresarial - flujo de caja y whatsapp business' => 2,
            'gestión empresarial - obten mayores ganancias con el flujo de caja' => 2,
            'formalización - tributación, comprobantes electrónicos y medios de pago' => 1,
            'formalización - mitos y verdades de la formalización' => 1,
            'gestión empresarial - optimiza tus procesos y mejora tu mype' => 2,
            'digitalización -  crea contenido de valor para redes sociales e incrementa tus ventas' => 3,
            'formalización - formalización a emprendedores y regímenes tributarios' => 1,
            'formalización - formalización empresarial y comprobantes electrónicos' => 1,
            'formalización - registro mype' => 1,
            'formalización - creación y beneficios de la formalizar una empresa' => 1,
            'gestión empresarial - mejora tus decisíones financieras' => 2,
            'gestión empresarial - generando ideas de negocio' => 2,
            'formalización - beneficios de  formalización' => 1,
            'formalización - creación de empresa, régimen tributario y acceso al acceso a financiamiento' => 1,
            'digitalización - redes sociales de alto impacto' => 3,
            'gestión empresarial - investigación de mercado' => 2,
            'formalización - formalización de empresas y regimenes tributarios' => 1,
            'acceso al financiamiento - evita ingresar a infocorp y mejora tu historial crediticio' => 5,
            'digitalización - plataformas digitales para tu emprendimiento' => 3,
            'digitalización - digitalización para mype' => 3,
            'digitalización - incrementa tus ventas con herramientas digitales' => 3,
            'formalización - emprendimiento y formalizacion' => 1,
            'formalización - formalización empresarial y regímenes tributarios.' => 1,
            'formalización - conoce el igv aplicable a tu mype y cómo obtener tu ruc' => 1,
            'gestión empresarial - inscribete en el remype' => 2,
            'formalización - importancia de la formalización empresarial' => 1,
            'formalización - modelo de negocios canvas' => 1,
            'gestión empresarial - ténicas de venta y escaparatismo' => 2,
            'gestión empresarial - planes de negocio' => 2,
            'gestión empresarial - gestión empresarial exitosa, empleando el modelo canvas.' => 2,
            'formalización - emprende y formaliza para crecer' => 1,
            'formalización - cómo crear tu empresa.' => 1,
            'digitalización - plataformas digitales para tu negocio' => 3,
            'formalización - infórmate sobre tus obligaciones tributarias' => 1,
            'formalización - emprende joven y formaliza tu futuro' => 1,
            'formalización - pasos para la formalización personas naturales y jurídicas' => 1,
            'digitalización - marketing y marketing digital' => 3,
            'difusion de los servicios del pnte en la feria festival del emprendimiento moqueguano 2025' => 9,
            'difusión de los servicios del pnte en la feria  multisectorial de inclusión social y oportunidades' => 9,
            'potencias tu empresa - formalización' => 1,
            'difusión de los servicios del pnte en la campaña informativa gratuita "el notario en tu barrio"' => 9,
            'difusión de los servicios del pnte en la feria' => 9,
            'emoliente bandera' => 9,
            'potencia tu empesa - gestión empresarial' => 2,
            'potencia tu empresa-gestion empresarial' => 2,
            'potencia tu empresa-formalizate ahora' => 1,
            'difusión de los servicios del pnte - feria de la ruta de la formalización' => 9,
            'inauguracion cde ancon' => 9,
            'potencia tu empresa - formalzación' => 1,
            'difusión de los servicios del pnte en la feria ii megaferia promocional mypes' => 9,
            'feria de emprendedores - sedapal' => 9,
            'feria laboral aeropuertuaria' => 9,
            'potencia tu empresa - gestión empresarial - mypes priorizadas' => 2,
            'potencia tu empresa - formalización - mypes priorizadas' => 1,
            'potencia tu empresa - desarrollo productivo - mypes priorizadas' => 4,
            'feria de formalización y crecimiento' => 1,
            'potencia tu empresa -formalizacion' => 1,
            'potencia tu empresa - acceso a financiamiento' => 5,
            'potencia tu  empresa - formalzación' => 1,
            'difusión de los servicios del pnte"' => 9,
            'potencia tu mpresa - gestión empresarial' => 2,
            'difusion de los servicios pnte' => 9,
            'aprende sobre formalización' => 1,
            'mypes priorizadas' => 1,
            'potencia tu empresa - gestipon empresarial' => 2,
            'potencia tu empresa pasco' => 2,
            'i encuentro de jóvenes por la formalización laboral' => 1,
            'difusión de los servicios del pnte en la expoferia artesanal' => 9,
            'potencia tu empresa - marketing digital' => 3,
            'compras públicas' => 6,
            'campaña multisectorial de servicios' => 9,
            'pontencia tu empresa - formalización' => 1,
            'potencia tu empresa - gestión empresarial.' => 2,
            'feria de servicios' => 9,
            'pasacalle renovacion gamarra' => 9,
            'potencia tu empresa - f' => 1,
            'potencia tu empresa - publicidad en redes' => 3,
            'potencia tu empresa - habilidades' => 3,
            'brindar difusión de los servicios de los cinco componentes del pnte en la feria formalizate y crece' => 9,
            'difusion  de los servicios del pnte' => 9,
            'difusión de los servicios del pnte - difusion' => 9,
            'potencia tu empresa - gestión y digitalización' => 3,
            'hackathon regional lambayeque' => 9,
            'formalizate ahora - ambo 2025' => 1,
            'pontencia tu empresa - gestión empresarial' => 2,
            'potencia tu empresa - gestión de inventarios' => 2,
            'difusion de los servicios del pnte en la minicaravana' => 9,
            'formalización' => 1,
            'potencia tu empresa -digitalización' => 3,
            'potencia tu  empresa -  formalizacion' => 1,
            'difusion de los servicios' => 9,
            'difusión de los servicios de pnte' => 9,
            'potencia tu empresa - formalización1' => 1,
            'brindar difusión de los servicios del pnte' => 9,
            'potencia tu empresa - liderazgo empresarial y habilidades gerenciales' => 2,
            'potencia tu empresa -f' => 1,
            'potencia tu empresa- formalización3' => 1,
            'difusion  de los servicion del pnte' => 9,
            'potencia tu empresa - formalización4' => 1,
            'ciclo de capacitaciones' => 9,
            'potencia tu empresa - desarollo productivo' => 4,
            'compras - sector económico' => 6,
            'compras -sector económico priorizado' => 6,
            'campaña despega tu empresa' => 8,
            'POTENCIA TU EMPRESA - DIGITALIZACIÓN' => 3,
            'POTENCIA TU EMPRESA - FORMALIZACIÓN' => 1,
            'CAMPAÑA DESPEGA TU EMPRESA Y PRODUCE-LIMA' => 8,
            'Potencia Tu Empresa - Formalizacion' => 1,
            'Potencia Tu Empresa - Digitalizacion' => 3,
            'DIFUSIóN DE LOS SERVICIOS DEL PNTE EN LA FERIA "CAMPAÑA DE FORMALIZACION"' => 9,
            'Difusión de los servicios del PNTE en la feria "CAMPAÑA FORMALIZADOS GANAMOS TODOS"' => 9,
            'POTENCIA TU EMPRESA - FORMALIZACION' => 1,
            'PRIMERA JORNADA POR LA COOPER-ACCIÓN' => 9,
            'CAMPAÑA DE DIFUSION DE LOS SERVICIOS DEL PNTE' => 9,
            'Potencia tu empresa - formalizacion' => 1,
            'Difusion de los servicios del PNTE' => 9,
            'CAMPAÑA FORMALIZATE YA' => 8,
            'difusion de los servicios del PNTE' => 9,
            'Potencia Tu Empresa - Formalizaciòn' => 1,
            'Difusión de los servicios del PNTE en la feria Jaen emprende' => 9,
            'GESTIÓN EMPRESARIAL - calidad y atencion del cliente - sector turismo' => 2,
            'FORMALIZACIÓN - Formalización empresarial' => 1,
            'FORMALIZACIÓN - formalización y tributación de MYPE.' => 1,
            'FORMALIZACIÓN - capacitación en formalización' => 1,
            'GESTIÓN EMPRESARIAL - atención al cliente' => 2,
            'DIGITALIZACIÓN - digitalizacion' => 3,
            'FORMALIZACIÓN - formalizacion empresarial' => 1,
            'FORMALIZACIÓN - diferencias entre pp.nn y pp.jj' => 1,
            'DIGITALIZACIÓN - monederos digitales' => 3,
            'DIGITALIZACIÓN - marketing y ventas digitales' => 3,
            'GESTIÓN EMPRESARIAL - MODELAMIENTO CANVAS' => 2,
            'GESTIÓN EMPRESARIAL - liderazgo empresaria y habilidades gerenciales' => 2,
            'FORMALIZACIÓN - Beneficios de la formalización' => 1,
            'DIGITALIZACIÓN - digitalizando tu empresa' => 3,
            'FORMALIZACIÓN - reMYPE (normatividad e inscripción de trabajadores)' => 1,
            'FORMALIZACIÓN - benefcios de la formalizacion' => 1,
            'DIGITALIZACIÓN - digitalizacion empresarial' => 3,
            'GESTIÓN EMPRESARIAL - emprendimiento' => 2,
            'DIGITALIZACIÓN - herramientas digitales' => 3,
            'DIGITALIZACIÓN - whatsapp business y billeteras digitales' => 3,
            'GESTIÓN EMPRESARIAL - gestion empresarial' => 2,
            'FORMALIZACIÓN - formalizacion empresarial y tributacion' => 1,
            'FORMALIZACIÓN - formalizate artesano' => 1,
            'GESTIÓN EMPRESARIAL - idea de negocio- Modelo canvas' => 2,
            'GESTIÓN EMPRESARIAL - como iniciar mi idea de negocio' => 2,
            'DIGITALIZACIÓN - seguridad con las billeteras digitales' => 3,
            'GESTIÓN EMPRESARIAL - gestion empresarial para MYPE' => 2,
            'FORMALIZACIÓN - Formalización empresarial y registro MYPE plataforma compras my peru' => 1,
            'FORMALIZACIÓN - emprende y formaliza tu empresa' => 1,
            'GESTIÓN EMPRESARIAL - complementación de competencias laborales' => 2,
            'FORMALIZACIÓN - formalizacion como pp.nn' => 1,
            'FORMALIZACIÓN - pasos para formalizar mi emprendimiento (grupo n°02)' => 1,
            'DIGITALIZACIÓN - digitalizacion empresarial para MYPE' => 3,
            'FORMALIZACIÓN - creacion, formalizacion y desarrollo de las micro y pequeñas empresas' => 1,
            'FORMALIZACIÓN - guia para la formalizacion' => 1,
            'FORMALIZACIÓN - tributacion' => 1,
            'GESTIÓN EMPRESARIAL - registro MYPE plataforma compras my peru' => 2,
            'FORMALIZACIÓN - formalización y registratare en la plataforma compras my peru' => 1,
            'FORMALIZACIÓN - formalizacion y trubutacion de empresas' => 1,
            'GESTIÓN EMPRESARIAL - generacion de ideas de negocios' => 2,
            'GESTIÓN EMPRESARIAL - compras a mi peru' => 2,
            'GESTIÓN EMPRESARIAL - compras a MYPEru (grupo 01)' => 2,
            'DIGITALIZACIÓN - digitalización' => 3,
            'FORMALIZACIÓN - asociatividad' => 1,
            'GESTIÓN EMPRESARIAL - comercializacion e innovacion para abrir nuevas lineas de negocio' => 2,
            'FORMALIZACIÓN - emprende y crea tu empresa' => 1,
            'FORMALIZACIÓN - proceso de formalizacion empresarial' => 1,
            'FORMALIZACIÓN - emprende sacs' => 1,
            'FORMALIZACIÓN - emisíon de boletas y facturación electronica' => 1,
            'GESTIÓN EMPRESARIAL - guia para el registro  MYPE a compras MYPErú' => 2,
            'GESTIÓN EMPRESARIAL - emprendimiento empresarial' => 2,
            'DIGITALIZACIÓN - marketing digital aplicada a las empresas de servicios de hospedaje' => 3,
            'GESTIÓN EMPRESARIAL - tecnicas de venta para negocios de artesanias' => 2,
            'DIGITALIZACIÓN - gestion de redes sociales para emprendimientos de atractivos turisticos' => 3,
            'GESTIÓN EMPRESARIAL - marketing estrategico' => 2,
            'DIGITALIZACIÓN - marketing digital y estrategia de ventas - facebook ads' => 3,
            'GESTIÓN EMPRESARIAL - gestión empresarial' => 2,
            'GESTIÓN EMPRESARIAL - nuevas tendencias empresariales' => 2,
            'DIGITALIZACIÓN - marketing digital' => 3,
            'GESTIÓN EMPRESARIAL - tecnicas de ventas' => 2,
            'GESTIÓN EMPRESARIAL - ideas de negocios' => 2,
            'GESTIÓN EMPRESARIAL - generacionde ideasde negocios' => 2,
            'DIGITALIZACIÓN - medios de pago digitales' => 3,
            'GESTIÓN EMPRESARIAL - articulando mi negocio' => 2,
            'GESTIÓN EMPRESARIAL - liderazgo empresarial y habilidades gerenciales' => 2,
            'FORMALIZACIÓN - proceso de Formalización empresarial' => 1,
            'FORMALIZACIÓN - ventajas y oportunidades de la formalizacion' => 1,
            'GESTIÓN EMPRESARIAL - emprendemimiento e innovacion' => 2,
            'DIGITALIZACIÓN - marketing digital y estrategia de ventas - whatsapp business' => 3,
            'FORMALIZACIÓN - pasos para constituir  una pncn y ppjj' => 1,
            'GESTIÓN EMPRESARIAL - innovación empresarial con desígn thinking' => 2,
            'GESTIÓN EMPRESARIAL - habilidades blandas' => 2,
            'DIGITALIZACIÓN - pago de salarios digitales' => 3,
            'GESTIÓN EMPRESARIAL - economia circular' => 2,
            'GESTIÓN EMPRESARIAL - Modelo de negocio- metodo canvas' => 2,
            'FORMALIZACIÓN - formalizacion tributaria' => 1,
            'GESTIÓN EMPRESARIAL - lanzamiento de emprendiemientos' => 2,
            'FORMALIZACIÓN - Formalización empresarial y tributaria a productores agropecuarios' => 1,
            'FORMALIZACIÓN - Formalización empresarial y tributaria a artesanos' => 1,
            'FORMALIZACIÓN - formalizacion empresarial y regimenes tributarios' => 1,
            'GESTIÓN EMPRESARIAL -  de Modelo canvas' => 2,
            'GESTIÓN EMPRESARIAL - liderazgo empresarial' => 2,
            'DIGITALIZACIÓN - marketing digital y gestion de redes sociales' => 3,
            'FORMALIZACIÓN - Formalizacion empresarial' => 1,
            'GESTIÓN EMPRESARIAL - cómo desarrollar y potenciar negocios exitosos' => 2,
            'DIGITALIZACIÓN - Diseño gráfico para redes sociales canva' => 3,
            'GESTIÓN EMPRESARIAL - atencion al cliente' => 2,
            'GESTIÓN EMPRESARIAL - destaca y vende más' => 2,
            'GESTIÓN EMPRESARIAL - emprendimiento e idea de negocios' => 2,
            'FORMALIZACIÓN - Beneficios de la formalización empresarial' => 1,
            'DIGITALIZACIÓN - crea tu catálogo digital con whatsapp business' => 3,
            'GESTIÓN EMPRESARIAL - gestión empresarial exitosa' => 2,
            'DIGITALIZACIÓN - Herramientas digitales para mercados' => 3,
            'DIGITALIZACIÓN - digitaliza tus canales de comunicación correo y whatsapp business' => 3,
            'GESTIÓN EMPRESARIAL - Generacion de ideas de negocios' => 2,
            'GESTIÓN EMPRESARIAL -  estudia tu mercado e identifica nuevas tendencias' => 2,
            'GESTIÓN EMPRESARIAL - registro MYPE' => 2,
            'GESTIÓN EMPRESARIAL - atencion al cliente y tecnicas de venta' => 2,
            'FORMALIZACIÓN - Formalizacion ruc 10 y nrus' => 1,
            'GESTIÓN EMPRESARIAL - técnicas en venta' => 2,
            'FORMALIZACIÓN - inscríbete al reMYPE' => 1,
            'GESTIÓN EMPRESARIAL - Herramientas para la sostenibilidad del emprendimiento' => 2,
            'GESTIÓN EMPRESARIAL -  liderazgo empresarial y habilidades gerenciales' => 2,
            'GESTIÓN EMPRESARIAL - registro de marca y atencion al cliente' => 2,
            'FORMALIZACIÓN - Importancia de la formalizacion' => 1,
            'GESTIÓN EMPRESARIAL - de economia circular' => 2,
            'FORMALIZACIÓN - Regimenes tributarios' => 1,
            'GESTIÓN EMPRESARIAL - Conociendo mi negocio' => 2,
            'GESTIÓN EMPRESARIAL - estudia tu mercado e identifica nuevas tendencias' => 2,
            'GESTIÓN EMPRESARIAL - beneficios del neuromarketing' => 2,
            'FORMALIZACIÓN - formalización e inscríbete al reMYPE' => 1,
            'DIGITALIZACIÓN - crea contenido y posíciona tu MYPE' => 3,
            'FORMALIZACIÓN - Formalización empresarial y digitalización' => 1,
            'DIGITALIZACIÓN - Fortalece tu negocio con whastapp de negocios' => 3,
            'GESTIÓN EMPRESARIAL - Definicion de procesos para mejorar la productividad' => 2,
            'GESTIÓN EMPRESARIAL - marketing para pymes' => 2,
            'GESTIÓN EMPRESARIAL - fidelización de clientes internos y externos' => 2,
            'GESTIÓN EMPRESARIAL - Economia circular como estrategia de crecimiento para MYPE' => 2,
            'FORMALIZACIÓN - Beneficios de la formalización de negocios' => 1,
            'FORMALIZACIÓN - Proceso de formalizacion ppnn y sus ventajas' => 1,
            'GESTIÓN EMPRESARIAL - idea de negocio y emprendimiento' => 2,
            'FORMALIZACIÓN - Regimen tributario y Beneficios de la formalización' => 1,
            'FORMALIZACIÓN - Formalización empresarial e inscripción al REMYPE' => 1,
            'FORMALIZACIÓN - mitos y verdades de la Formalización empresarial' => 1,
            'DIGITALIZACIÓN - digitalización para bodegueros' => 3,
            'FORMALIZACIÓN - constitución de personas jurídicas' => 1,
            'FORMALIZACIÓN -  mitos y verdades de la formalizacion' => 1,
            'DIGITALIZACIÓN - Whatsapp de negocios crea tu catalogo digital' => 3,
            'DIGITALIZACIÓN - Camino a la digitalizacion' => 3,
            'GESTIÓN EMPRESARIAL - conoce las estrategias y tácticas para vender más' => 2,
            'FORMALIZACIÓN - regímenes tributarios y contables y sus obligaciones' => 1,
            'GESTIÓN EMPRESARIAL - Idea de negocios' => 2,
            'GESTIÓN EMPRESARIAL - Modelo canvas' => 2,
            'FORMALIZACIÓN - como formalizar y potenciar mi emprendimiento' => 1,
            'FORMALIZACIÓN - Beneficios de formalizacion de las MYPE y uso de medios digitales' => 1,
            'FORMALIZACIÓN - Proceso de constitución empresarial' => 1,
            'DIGITALIZACIÓN -  digitaliza tus canales de comunicación crea contenido y posíciona tu MYPE' => 3,
            'FORMALIZACIÓN - Beneficios de formalizar mi emprendimiento y modalidades societarias para procompite' => 1,
            'FORMALIZACIÓN - consejos legales para emprendedores' => 1,
            'GESTIÓN EMPRESARIAL - Destaca y vende más' => 2,
            'FORMALIZACIÓN - formalizacion regimenes tributarios yCaja rápida' => 1,
            'GESTIÓN EMPRESARIAL - ¿cómo generamos mas oportunidades en nuevos mercados? - Modelo canvas' => 2,
            'DIGITALIZACIÓN - whatsapp business' => 3,
            'FORMALIZACIÓN - inscribete al reMYPE' => 1,
            'FORMALIZACIÓN - Beneficios de formalizar mi emprendimiento' => 1,
            'FORMALIZACIÓN - proceso de constitución empresarial' => 1,
            'GESTIÓN EMPRESARIAL - técnicas de venta para negocios de artesania y textil' => 2,
            'GESTIÓN EMPRESARIAL - diseño y propuesta de marca' => 2,
            'FORMALIZACIÓN - formalizacion  y regimenes tributarios' => 1,
            'DIGITALIZACIÓN - medios de pagos digitales y whatsapp business' => 3,
            'FORMALIZACIÓN -  formalizacion empresarial y tributaria en el encuetro de jovenes unh 2024' => 1,
            'GESTIÓN EMPRESARIAL - destaca y vende mas' => 2,
            'FORMALIZACIÓN - formalizacion empresarial y regimen tributario' => 1,
            'FORMALIZACIÓN - Formalización empresarial registro MYPE plataforma compras my peru' => 1,
            'GESTIÓN EMPRESARIAL - generacion de ideas, Modelo de negocios canvas y prototipos de negocios' => 2,
            'FORMALIZACIÓN - Formalización empreasarial' => 1,
            'DIGITALIZACIÓN - digitaliza tus canales de comunicacion' => 3,
            'GESTIÓN EMPRESARIAL - estudio de mercado y nuevas tendecias' => 2,
            'GESTIÓN EMPRESARIAL -  como desarrollar y potenciar negocios exitosos' => 2,
            'GESTIÓN EMPRESARIAL - atencion al cliente y asociatividad' => 2,
            'GESTIÓN EMPRESARIAL - ¿cómo elaborar un plan de negocios?' => 2,
            'FORMALIZACIÓN - Modelo canvas y formalizando mi emprendimiento' => 1,
            'FORMALIZACIÓN - Pasos y Beneficios de la formalización' => 1,
            'DIGITALIZACIÓN - atencion al cliente y gestion de redes sociales' => 3,
            'GESTIÓN EMPRESARIAL - gestión de atención al cliente' => 2,
            'DIGITALIZACIÓN - digitalizando mi negocio' => 3,
            'GESTIÓN EMPRESARIAL - catalogo digital y destaca y vende mas' => 2,
            'GESTIÓN EMPRESARIAL - modelamiento de negocios' => 2,
            'FORMALIZACIÓN - facilidades y Beneficios de la formalización, a los productores alpaqueros' => 1,
            'GESTIÓN EMPRESARIAL - atencion al cliente  y dijitalizacion' => 2,
            'GESTIÓN EMPRESARIAL - Registro de marcas' => 2,
            'FORMALIZACIÓN - Mitos de la formalización' => 1,
            'DIGITALIZACIÓN - abc digital - redes sociales.' => 3,
            'GESTIÓN EMPRESARIAL - segmento de mercado' => 2,
            'GESTIÓN EMPRESARIAL - uso de billeteras digitales y crédito MYPE' => 2,
            'GESTIÓN EMPRESARIAL - como generar mi Modelo de negocio' => 2,
            'FORMALIZACIÓN - cómo formalizar mi emprendimiento' => 1,
            'GESTIÓN EMPRESARIAL - ideas y modelamiento de negocios' => 2,
            'GESTIÓN EMPRESARIAL -  estrategias y tacticas para vender mas' => 2,
            'FORMALIZACIÓN - diferencia entre personas naturales y jurídicas' => 1,
            'FORMALIZACIÓN - formalizacion empresarial y régimen tributario' => 1,
            'FORMALIZACIÓN - regímenes tributarios' => 1,
            'DIGITALIZACIÓN - contenido digital para tu negocio' => 3,
            'GESTIÓN EMPRESARIAL - estrategias y tácticas para vender más' => 2,
            'FORMALIZACIÓN - beneficios y facilidades de la Formalización empresarial' => 1,
            'GESTIÓN EMPRESARIAL -  habilidades blandas, modelamiento de negocios (Modelo canva)' => 2,
            'FORMALIZACIÓN - flujo de  caja' => 1,
            'FORMALIZACIÓN - cómo generar una idea de negocio y Beneficios de la formalización' => 1,
            'GESTIÓN EMPRESARIAL - ¿como elaborar un plan de negocio?' => 2,
            'FORMALIZACIÓN - inscríbete en el reMYPE' => 1,
            'GESTIÓN EMPRESARIAL - estudio de mercado e identificación de nuevas tendencias' => 2,
            'FORMALIZACIÓN - formalizando mi emprendimiento' => 1,
            'GESTIÓN EMPRESARIAL - modelamiento de negocios - aplicación práctica' => 2,
            'GESTIÓN EMPRESARIAL - Estudio de mercado e identificación de nuevas tendencias' => 2,
            'FORMALIZACIÓN - Estudia tu Mercado e identifica nuevas tendencias' => 1,
            'FORMALIZACIÓN - proceso de constitución de empresas' => 1,
            'GESTIÓN EMPRESARIAL - idea y Modelo de negocio' => 2,
            'GESTIÓN EMPRESARIAL - Cómo desarrollar y potenciar negocios exitosos' => 2,
            'GESTIÓN EMPRESARIAL - servicio de atención al cliente' => 2,
            'DIGITALIZACIÓN - Administración de grupos y comunidades por Whatsapp' => 3,
            'FORMALIZACIÓN - Formalización empresarial y régimen tributario' => 1,
            'GESTIÓN EMPRESARIAL - generación de ideas, Modelo de negocios canvas y prototipos de negocios' => 2,
            'FORMALIZACIÓN - pautas para constituir una empresa, de emprendedor a empresario' => 1,
            'DIGITALIZACIÓN - ventas digitales con whatsapp business' => 3,
            'GESTIÓN EMPRESARIAL - gestión comercial' => 2,
            'GESTIÓN EMPRESARIAL - Marketing para emprendedores' => 2,
            'GESTIÓN EMPRESARIAL - estudio de mercados y nuevas tendencias' => 2,
            'FORMALIZACIÓN - Formalización y constitución del negocio' => 1,
            'GESTIÓN EMPRESARIAL - flujo de caja y whatsapp business' => 2,
            'GESTIÓN EMPRESARIAL - Registro MYPE' => 2,
            'GESTIÓN EMPRESARIAL - Obten mayores ganancias con el flujo de caja' => 2,
            'FORMALIZACIÓN - tributación, comprobantes electrónicos y medios de pago' => 1,
            'FORMALIZACIÓN - mitos y verdades de la formalización' => 1,
            'GESTIÓN EMPRESARIAL - Optimiza tus procesos y mejora tu MYPE' => 2,
            'DIGITALIZACIÓN -  crea contenido de valor para redes sociales e incrementa tus ventas' => 3,
            'FORMALIZACIÓN - Cómo formalizar mi emprendimiento' => 1,
            'FORMALIZACIÓN - Formalización a emprendedores y regímenes tributarios' => 1,
            'FORMALIZACIÓN - Formalización empresarial y comprobantes electrónicos' => 1,
            'FORMALIZACIÓN - Registro MYPE' => 1,
            'FORMALIZACIÓN - beneficios de la formalizaciòn' => 1,
            'FORMALIZACIÓN - creación y beneficios de la formalizar una empresa' => 1,
            'GESTIÓN EMPRESARIAL - Mejora tus deciSíones financieras' => 2,
            'GESTIÓN EMPRESARIAL - generando ideas de negocio' => 2,
            'FORMALIZACIÓN - beneficios de  formalización' => 1,
            'GESTIÓN EMPRESARIAL - Gestión empresarial' => 2,
            'FORMALIZACIÓN - creación de empresa, régimen tributario y acceso al Acceso a financiamiento' => 5,
            'DIGITALIZACIÓN - redes sociales de alto impacto' => 3,
            'GESTIÓN EMPRESARIAL - Investigación de mercado' => 2,
            'FORMALIZACIÓN - formalización de empresas y regimenes tributarios' => 1,
            'DIGITALIZACIÓN - Herramientas digitales' => 3,
            'GESTIÓN EMPRESARIAL - Fidelización de clientes internos y externos' => 2,
            'FORMALIZACIÓN - Creación de empresa, regimen tributario y acceso al Acceso a financiamiento' => 5,
            'GESTIÓN EMPRESARIAL - Atencion al cliente' => 2,
            'DIGITALIZACIÓN - Plataformas digitales para tu emprendimiento' => 3,
            'DIGITALIZACIÓN - Digitalización para MYPE' => 3,
            'DIGITALIZACIÓN - Incrementa tus ventas con herramientas digitales' => 3,
            'FORMALIZACIÓN - Emprendimiento y Formalizacion' => 1,
            'FORMALIZACIÓN - Formalización empresarial y Regímenes Tributarios.' => 1,
            'GESTIÓN EMPRESARIAL - Estudio de mercados y nuevas tendencias' => 2,
            'FORMALIZACIÓN - Conoce el IGV aplicable a tu MYPE y cómo obtener tu RUC' => 1,
            'GESTIÓN EMPRESARIAL - Inscribete en el REMYPE' => 2,
            'FORMALIZACIÓN - Diferencia entre personas naturales y jurídicas' => 1,
            'FORMALIZACIÓN - Importancia de la Formalización empresarial' => 1,
            'DIGITALIZACIÓN - Marketing digital' => 3,
            'FORMALIZACIÓN - Formalizacion y regimenes tributarios' => 1,
            'FORMALIZACIÓN - Beneficios de la Formalizaciòn' => 1,
            'FORMALIZACIÓN - Modelo de negocios canvas' => 1,
            'DIGITALIZACIÓN - Digitaliza tus canales de comunicación' => 3,
            'GESTIÓN EMPRESARIAL - Ténicas de venta y escaparatismo' => 2,
            'GESTIÓN EMPRESARIAL - Planes de negocio' => 2,
            'GESTIÓN EMPRESARIAL - Gestión Empresarial Exitosa, empleando el Modelo CANVAS.' => 2,
            'FORMALIZACIÓN - Emprende y formaliza para crecer' => 1,
            'FORMALIZACIÓN - Cómo crear tu empresa.' => 1,
            'FORMALIZACIÓN - Formalización empresarial y regímenes tributarios' => 1,
            'DIGITALIZACIÓN - Plataformas digitales para tu negocio' => 3,
            'FORMALIZACIÓN - Infórmate sobre tus obligaciones tributarias' => 1,
            'FORMALIZACIÓN - Emprende Joven y Formaliza tu futuro' => 1,
            'FORMALIZACIÓN - Pasos para la Formalización Personas Naturales y Jurídicas' => 1,
            'FORMALIZACIÓN - Regímenes Tributarios' => 1,
            'DIGITALIZACIÓN - Crea contenido y posiciona tu Mype' => 3,
            'DIGITALIZACIÓN - Marketing y marketing digital' => 3,
            'DIGITALIZACIÓN - Digitaliza tus canales de comunicacion' => 3,
            'POTENCIA TU EMPRESA - FORMALZACIÓN' => 1,
            'POTENCIA TU MPRESA - GESTIÓN EMPRESARIAL' => 2,
            'I ENCUENTRO DE JÓVENES POR LA FORMALIZACIÓN LABORAL' => 1,
            'Potencia Tu Empresa- Gestion Empresarial' => 2,
            'Potencia Tu Empresa - Formalizacion.' => 1,
            'DIFUSIÓN DE LOS SERVICIOS DEL PNTE' => 9,
            'COMPRAS -SECTOR ECONÓMICO PRIORIZADO' => 6,
            'CAMPAÑA DESPEGA TU EMPRESA' => 8,
        ];

        // Normalizar las claves del mapping
        $mapping = [];

        foreach ($mappingOriginal as $key => $value) {
            $keyNormalizada = $this->eliminarAcentos(
                mb_strtolower(trim($key), 'UTF-8')
            );

            $mapping[$keyNormalizada] = $value;
        }

        $registros = DB::table('attendancelist')->get();

        $contador = 0;

        foreach ($registros as $item) {

            $tituloLimpio = $this->eliminarAcentos(
                mb_strtolower(trim($item->title), 'UTF-8')
            );

            if (array_key_exists($tituloLimpio, $mapping)) {

                DB::table('attendancelist')
                    ->where('id', $item->id)
                    ->update([
                        'nombre_actividad_id' => $mapping[$tituloLimpio],
                    ]);

                $contador++;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Se actualizaron $contador registros correctamente.",
        ]);
    }

    private function eliminarAcentos($cadena)
    {
        return strtr($cadena, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'ñ' => 'n',
            'Ñ' => 'N',
        ]);
    }

    // 5
    //     SELECT DISTINCT `modality` FROM attendancelist;

    //     UPDATE attendancelist
    // SET modality = 'v'
    // WHERE modality = 'VIRTUAL';
}
