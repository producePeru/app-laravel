<?php

namespace App\Http\Controllers\Pnte;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActividadPnte;
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
        ]);

        // ✅ Mes: extraer el mes de la fecha más antigua del array
        $fechaMinima = collect($validated['fechas'])
            ->map(fn($f) => Carbon::parse($f))
            ->sortBy(fn($d) => $d->timestamp)
            ->first();

        $validated['mes']          = (int) $fechaMinima->format('n'); // 1-12 sin cero
        $validated['cantidad_dias'] = count($validated['fechas']);     // bonus: setear cantidad_dias

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

        // ✅ VALIDAR: solo se puede editar hasta las 23:59 del día de creación
        $limiteEdicion = Carbon::parse($actividad->created_at)->endOfDay();

        if (Carbon::now()->gt($limiteEdicion)) { // 👈 era $limitEdicion (faltaba la 'e')
            return response()->json([
                'status'  => 403,
                'message' => 'No es posible editar esta actividad. El plazo de edición venció el ' .
                    Carbon::parse($actividad->created_at)->format('d/m/Y') . ' a las 23:59. ' .
                    'Por favor, contacte con su supervisor.',
            ], 403);
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
            'monto_gasto'          => 'nullable|string|max:255',
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
        ]);

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
            ->where('unidad', 1)

            // ✅ FILTRO: year
            ->when($request->filled('year'), function ($q) use ($request) {
                $year = $request->input('year');
                $q->where('fechas', 'LIKE', "%{$year}%");
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

            // ✅ FILTRO: tipo_actividad_id
            ->when($request->filled('tipo_actividad_id'), function ($q) use ($request) {
                $q->where('tipo_actividad_id', $request->input('tipo_actividad_id'));
            })

            ->paginate($pageSize, ['*'], 'page', $request->input('page', 1));

        // ✅ ORDENAR por la fecha más reciente dentro del JSON (en PHP post-query)
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
                'current_page'  => $actividades->currentPage(),
                'data'          => $actividades->items(),
                'first_page_url' => $actividades->url(1),
                'from'          => $actividades->firstItem(),
                'last_page'     => $actividades->lastPage(),
                'last_page_url' => $actividades->url($actividades->lastPage()),
                'links'         => $actividades->linkCollection()->toArray(),
                'next_page_url' => $actividades->nextPageUrl(),
                'path'          => $actividades->path(),
                'per_page'      => $actividades->perPage(),
                'prev_page_url' => $actividades->previousPageUrl(),
                'to'            => $actividades->lastItem(),
                'total'         => $actividades->total(),
            ],
        ]);
    }
}
