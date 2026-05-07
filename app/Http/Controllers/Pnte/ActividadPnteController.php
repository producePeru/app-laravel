<?php

namespace App\Http\Controllers\Pnte;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActividadPnte;
use App\Models\EmpresarioActividad;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Throwable;

class ActividadPnteController extends Controller
{

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'unidad'               => 'required|integer|in:1,2,3,4,5',
            'fechas'               => 'required|array|min:1',
            'fechas.*'             => 'required|date_format:Y-m-d',
            'tipo_actividad_id'    => 'required|exists:tipo_actividad,id',
            'nombre_actividad_id'  => 'required|exists:nombre_actividad,id',
            'tema'                 => 'nullable|string|max:255',
            'region'               => 'required|exists:cities,id',
            'provincia'            => 'required|exists:provinces,id',
            'distrito'             => 'required|exists:districts,id',
            'lugar'                => 'nullable|string|max:255',
            'entidad_organizadora' => 'nullable|string|max:255',
            'entidad_aliada'       => 'nullable|string|max:255',
            'representante_id'     => $user->rol == 1 ? 'nullable|exists:users,id' : 'sometimes',
            'requiere_pasaje'      => 'required|boolean',
            'monto_gasto'          => 'nullable|max:255',
            'mypes_beneficiadas'   => 'nullable|integer|min:0',
            'modalidad_id'         => 'nullable|exists:modalities,id',
        ]);

        // ✅ Si rol == 2 → ignorar lo que venga y forzar su propio ID
        $validated['representante_id'] = $user->rol == 1
            ? ($validated['representante_id'] ?? null)
            : $user->id;

        // ✅ Mes: extraer el mes de la fecha más antigua del array
        $fechaMinima = collect($validated['fechas'])
            ->map(fn($f) => Carbon::parse($f))
            ->sortBy(fn($d) => $d->timestamp)
            ->first();

        $validated['mes']           = (int) $fechaMinima->format('n');
        $validated['cantidad_dias'] = count($validated['fechas']);

        try {
            $actividad = DB::transaction(function () use ($validated) {
                $validated['slug']              = $this->generateUniqueSlug($validated);
                $validated['registrado_por_id'] = Auth::id();

                return ActividadPnte::create($validated);
            });

            return response()->json([
                'status'  => 200,
                'message' => 'Actividad registrada correctamente.',
                'data'    => $actividad->load([
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
                'error'   => $e->getMessage(),
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

        // ✅ Verificar rol del usuario autenticado
        $userRol = Auth::user()->rol; // ajusta según tu campo de rol

        // 🔒 Solo rol 2 tiene restricción de tiempo
        if ($userRol == 2) {
            $limiteEdicion = Carbon::parse($actividad->created_at)->endOfDay();

            if (Carbon::now()->gt($limiteEdicion)) {
                return response()->json([
                    'status'  => 403,
                    'message' => 'No es posible editar esta actividad. El plazo de edición venció el ' .
                        Carbon::parse($actividad->created_at)->format('d/m/Y') . ' a las 23:59. ' .
                        'Por favor, contacte con su supervisor.',
                ]);
            }
        }

        $validated = $request->validate([
            'unidad'               => 'required|integer|in:1,2,3,4,5',
            'fechas'               => 'required|array|min:1',
            'fechas.*'             => 'required|date_format:Y-m-d',
            'tipo_actividad_id'    => 'required|exists:tipo_actividad,id',
            'nombre_actividad_id'  => 'required|exists:nombre_actividad,id',
            'tema'                 => 'nullable|string|max:255',
            'region'               => 'required|exists:cities,id',
            'provincia'            => 'required|exists:provinces,id',
            'distrito'             => 'required|exists:districts,id',
            'lugar'                => 'nullable|string|max:255',
            'entidad_organizadora' => 'nullable|string|max:255',
            'entidad_aliada'       => 'nullable|string|max:255',
            'representante_id'     => 'nullable|exists:users,id',
            'requiere_pasaje'      => 'required|boolean',
            'monto_gasto'          => 'nullable|max:255',
            'mypes_beneficiadas'   => 'nullable|integer|min:0',
            'modalidad_id'         => 'nullable|exists:modalities,id',
            'total_participantes'  => 'nullable|integer|min:0',
            'total_asesorias'      => 'nullable|integer|min:0',
            'total_formalizaciones' => 'nullable|integer|min:0',
        ]);

        // ✅ Mes: extraer el mes de la fecha más antigua del array
        $fechaMinima = collect($validated['fechas'])
            ->map(fn($f) => Carbon::parse($f))
            ->sortBy(fn($d) => $d->timestamp)
            ->first();

        $validated['mes']           = (int) $fechaMinima->format('n');
        $validated['cantidad_dias'] = count($validated['fechas']);

        try {
            DB::transaction(function () use ($actividad, $validated) {
                $validated['actualizado_por_id'] = Auth::id();
                $actividad->update($validated);
            });

            return response()->json([
                'status'  => 200,
                'message' => 'Actividad actualizada correctamente.',
                'data'    => $actividad->fresh()->load([
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
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'page'              => 'nullable|integer|min:1',
            'pageSize'          => 'nullable|integer|min:1|max:100',
            'year'              => 'nullable|integer|digits:4',
            'rangeDate'         => 'nullable|array|size:2',
            'rangeDate.*'       => 'required|date_format:Y-m-d',
            'city'              => 'nullable|integer|exists:cities,id',
            'tipo_actividad_id' => 'nullable|integer|exists:tipo_actividad,id',
            'asesor'            => 'nullable|integer|exists:users,id'
        ]);

        $user     = Auth::user();
        $pageSize = $request->input('pageSize', 10);

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
                'created_at',
            ])

            // ✅ DESPUÉS DEL select
            ->addSelect([
                'inscritos' => EmpresarioActividad::selectRaw('COUNT(*)')
                    ->whereColumn(
                        'empresario_actividad.slug',
                        'actividades_pnte.slug'
                    )
            ])

            ->where('unidad', 1)

            // ✅ ROL: si es rol 2 solo ve sus propias actividades
            ->when($user->rol == 2, function ($q) use ($user) {
                $q->where('representante_id', $user->id);
            })

            // ✅ FILTRO: year
            ->when($request->filled('year'), function ($q) use ($request) {

                $year = $request->input('year');

                $q->where('fechas', 'LIKE', "%{$year}%");
            })

            // ✅ FILTRO: rangeDate
            ->when($request->filled('rangeDate'), function ($q) use ($request) {

                [$from, $to] = $request->input('rangeDate');

                $current = Carbon::parse($from);
                $end     = Carbon::parse($to);

                $q->where(function ($query) use ($current, $end) {

                    while ($current->lte($end)) {

                        $fecha = $current->format('Y-m-d');

                        $query->orWhere(
                            'fechas',
                            'LIKE',
                            "%{$fecha}%"
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

            // ✅ FILTRO: tipo_actividad_id
            ->when($request->filled('tipo_actividad_id'), function ($q) use ($request) {

                $q->where(
                    'tipo_actividad_id',
                    $request->input('tipo_actividad_id')
                );
            })

            // ✅ FILTRO: asesor
            ->when(
                $request->filled('asesor') && $user->rol == 1,
                function ($q) use ($request) {

                    $q->where(
                        'representante_id',
                        $request->input('asesor')
                    );
                }
            )

            ->paginate(
                $pageSize,
                ['*'],
                'page',
                $request->input('page', 1)
            );

        // ✅ ORDENAR por fecha más reciente
        $actividades->setCollection(
            $actividades->getCollection()
                ->sortByDesc(function ($actividad) {

                    $fechas = is_array($actividad->fechas)
                        ? $actividad->fechas
                        : json_decode($actividad->fechas, true);

                    return collect($fechas)->max();
                })
                ->values()
        );

        return response()->json([
            'status'  => 200,
            'message' => 'Actividades obtenidas correctamente.',
            'data'    => [
                'current_page'   => $actividades->currentPage(),
                'data'           => $actividades->items(),
                'first_page_url' => $actividades->url(1),
                'from'           => $actividades->firstItem(),
                'last_page'      => $actividades->lastPage(),
                'last_page_url'  => $actividades->url($actividades->lastPage()),
                'links'          => $actividades->linkCollection()->toArray(),
                'next_page_url'  => $actividades->nextPageUrl(),
                'path'           => $actividades->path(),
                'per_page'       => $actividades->perPage(),
                'prev_page_url'  => $actividades->previousPageUrl(),
                'to'             => $actividades->lastItem(),
                'total'          => $actividades->total(),
            ],
        ]);
    }

    public function reprogramar(Request $request, int $id): JsonResponse
    {
        $actividad = ActividadPnte::findOrFail($id);

        $validated = $request->validate([
            'fechas'       => 'required|array|min:1',
            'fechas.*'     => 'required|date_format:Y-m-d',
            'reprogramado' => 'required|string|max:255',
        ]);

        // ✅ Mes: extraer el mes de la fecha más antigua
        $fechaMinima = collect($validated['fechas'])
            ->map(fn($f) => Carbon::parse($f))
            ->sortBy(fn($d) => $d->timestamp)
            ->first();

        try {
            DB::transaction(function () use ($actividad, $validated, $fechaMinima) {
                $actividad->update([
                    'fechas'               => $validated['fechas'],
                    'mes'                  => (int) $fechaMinima->format('n'),
                    'cantidad_dias'        => count($validated['fechas']),
                    'reprogramado'         => $validated['reprogramado'],
                    'reprogramado_por_id'  => Auth::id(),
                ]);
            });

            return response()->json([
                'status'  => 200,
                'message' => 'Actividad reprogramada correctamente.',
                'data'    => $actividad->fresh()->load([
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
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function cancelar(Request $request, int $id): JsonResponse
    {
        $actividad = ActividadPnte::findOrFail($id);

        $validated = $request->validate([
            'cancelado' => 'required|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($actividad, $validated) {
                $actividad->update([
                    'cancelado'        => $validated['cancelado'],
                    'cancelado_por_id' => Auth::id(),
                ]);
            });

            return response()->json([
                'status'  => 200,
                'message' => 'Actividad cancelada correctamente.',
                'data'    => $actividad->fresh()->load([
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
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function inscritosPorSlug(Request $request, $slug)
    {
        try {

            $perPage = $request->input('pageSize', 10);
            $search  = trim($request->input('name', ''));

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
                'empresario.sectorEconomico',
                'empresario.rubro',
                'empresario.tipoDocumento',
                'empresario.genero'
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
                    'fecha_asistencia' => $item->fecha_asistencia,
                    'numero_dni' => $item->numero_dni,

                    // 🔥 DATOS EMPRESARIO
                    'ruc' => $e->ruc,

                    'razon_social' => mb_strtoupper(
                        $e->razon_social ?? '',
                        'UTF-8'
                    ),

                    'nombre_comercial' => mb_strtoupper(
                        $e->nombre_comercial ?? '',
                        'UTF-8'
                    ),

                    'sector_economico_id' => $e->sector_economico_id,

                    'sector_economico_nombre' => mb_strtoupper(
                        $e->sectorEconomico?->name ?? '',
                        'UTF-8'
                    ),

                    'rubro_id' => $e->rubro_id,

                    'rubro_nombre' => mb_strtoupper(
                        $e->rubro?->name ?? '',
                        'UTF-8'
                    ),

                    'actividad_comercial_id' => $e->actividad_comercial_id,

                    'actividad_comercial_nombre' => mb_strtoupper(
                        $e->actividad_comercial_nombre ?? '',
                        'UTF-8'
                    ),

                    'region_id' => $e->region_id,
                    'region_nombre' => $e->region?->name,

                    'provincia_id' => $e->provincia_id,
                    'provincia_nombre' => $e->provincia?->name,

                    'distrito_id' => $e->distrito_id,
                    'distrito_nombre' => $e->distrito?->name,

                    'direccion' => mb_strtoupper(
                        $e->direccion ?? '',
                        'UTF-8'
                    ),

                    'pais_id' => $e->pais_id,
                    'pais_nombre' => $e->pais?->name,

                    'tipo_documento_id' => $e->tipo_documento_id,

                    'tipo_documento_nombre' => $e->tipoDocumento?->avr,

                    'numero_dni_empresario' => $e->numero_dni,

                    'apellido_paterno' => mb_strtoupper(
                        $e->apellido_paterno ?? '',
                        'UTF-8'
                    ),

                    'apellido_materno' => mb_strtoupper(
                        $e->apellido_materno ?? '',
                        'UTF-8'
                    ),

                    'nombres' => mb_strtoupper(
                        $e->nombres ?? '',
                        'UTF-8'
                    ),

                    'nombre_completo' => mb_strtoupper(
                        trim(
                            ($e->apellido_paterno ?? '') . ' ' .
                                ($e->apellido_materno ?? '') . ' ' .
                                ($e->nombres ?? '')
                        ),
                        'UTF-8'
                    ),

                    'genero_id' => $e->genero_id,
                    'genero_avr' => $e->genero?->avr,

                    'discapacidad' => $e->discapacidad,

                    'discapacidad_nombre' => $e->discapacidad
                        ? 'SI'
                        : 'NO',

                    'celular' => $e->celular,

                    'correo_electronico' => $e->correo_electronico,

                    'cargo_empresa_id' => $e->cargo_empresa_id,

                    'fecha_nacimiento' => $e->fecha_nacimiento,

                    'edad' => $e->edad,

                    'como_entero' => $e->como_entero,

                    'personal_asesoria' => $item->personal_asesoria,

                    'personal_formalizacion' => $item->personal_formalizacion,
                ];
            });

            return response()->json([
                'status' => 200,
                'data' => $data,
                'event' => $event
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener inscritos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateValuesSelect(Request $request)
    {
        $request->validate([
            'slug'   => 'required|string',
            'rowId'  => 'required|integer',
            'column' => 'required|string|in:personal_asesoria,personal_formalizacion',
            'value'  => 'required|in:0,1',
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
                'status'  => 200,
                'message' => 'Actualizado correctamente',
                'data'    => [
                    'id'                       => $row->id,
                    'column'                   => $column,
                    'value'                    => $row->{$column},
                    'total_asesorias'          => $totalAsesorias,
                    'total_formalizaciones'    => $totalFormalizaciones,
                ],
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 500,
                'message' => 'Error al actualizar',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
