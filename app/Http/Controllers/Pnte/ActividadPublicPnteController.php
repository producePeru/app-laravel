<?php

namespace App\Http\Controllers\Pnte;

use App\Http\Controllers\Controller;
use App\Models\ActividadPnte;
use App\Models\Empresario;
use App\Models\EmpresarioActividad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActividadPublicPnteController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $actividad = ActividadPnte::with([
            'tipoActividad:id,name',
            'nombreActividad:id,name',
            'regionRel:id,name',
            'provinciaRel:id,name',
            'distritoRel:id,name',
            'representante:id,name,lastname,middlename',
        ])
            ->where('slug', $slug)
            ->select([
                'id',
                'slug',
                'fechas',
                'tema',
                'lugar',
                'tipo_actividad_id',
                'nombre_actividad_id',
                'region',
                'provincia',
                'distrito',
                'representante_id',
            ])
            ->firstOrFail();

        return response()->json([
            'status' => 200,
            'data' => $actividad,
        ]);
    }

    public function getByDni(string $dni): JsonResponse
    {
        $empresario = Empresario::where('numero_dni', $dni)
            ->select([
                'numero_dni',
                'apellido_paterno',
                'apellido_materno',
                'nombres',
                'genero_id',
                'discapacidad',
                'celular',
                'correo_electronico',
                'cargo_empresa_id',
                'fecha_nacimiento',
                'edad',
                'pais_id',
            ])
            ->first();

        if (! $empresario) {
            return response()->json([
                'status' => 404,
                'message' => 'Empresario no encontrado.',
            ]);
        }

        return response()->json([
            'status' => 200,
            'data' => $empresario,
        ]);
    }

    public function getByRuc(string $ruc): JsonResponse
    {
        $empresario = Empresario::where('ruc', $ruc)
            ->select([
                'ruc',
                'razon_social',
                'nombre_comercial',
                'sector_economico_id',
                'actividad_comercial_nombre',
                'rubro_id',
                'actividad_comercial_id',
                'region_id',
                'provincia_id',
                'distrito_id',
                'direccion',
            ])
            ->first();

        if (! $empresario) {
            return response()->json([
                'status' => 404,
                'message' => 'Empresa no encontrada.',
            ]);
        }

        return response()->json([
            'status' => 200,
            'data' => $empresario,
        ]);
    }

    public function storeEmpresario(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
            'ruc' => 'nullable|size:11',
            'numero_dni' => 'required|string|max:12',
        ]);

        try {

            DB::beginTransaction();

            $empresario = Empresario::where('ruc', $request->ruc)
                ->where('numero_dni', $request->numero_dni)
                ->first();

            if ($empresario) {

                // 🔄 UPDATE (NO tocar ruc ni numero_dni)
                $empresario->update([

                    'actividad_comercial_nombre' => $request->actividad_comercial_nombre,
                    'apellido_materno' => $request->apellido_materno,
                    'apellido_paterno' => $request->apellido_paterno,
                    'celular' => $request->celular,
                    'correo_electronico' => $request->correo_electronico,
                    'direccion' => $request->direccion,
                    'discapacidad' => $request->discapacidad,
                    'distrito_id' => $request->distrito_id,
                    'genero_id' => $request->genero_id,
                    'nombre_comercial' => $request->nombre_comercial,
                    'nombres' => $request->nombres,
                    'pais_id' => $request->pais_id,
                    'provincia_id' => $request->provincia_id,
                    'razon_social' => $request->razon_social,
                    'region_id' => $request->region_id,
                    'rubro_id' => $request->rubro_id,
                    'sector_economico_id' => $request->sector_economico_id,
                    'tipo_documento_id' => $request->tipo_documento_id,
                ]);
            } else {

                // ✅ CREATE
                $empresario = Empresario::create([
                    'actividad_comercial_nombre' => $request->actividad_comercial_nombre,
                    'apellido_materno' => $request->apellido_materno,
                    'apellido_paterno' => $request->apellido_paterno,
                    'celular' => $request->celular,
                    'correo_electronico' => $request->correo_electronico,
                    'direccion' => $request->direccion,
                    'discapacidad' => $request->discapacidad,
                    'distrito_id' => $request->distrito_id,
                    'genero_id' => $request->genero_id,
                    'nombre_comercial' => $request->nombre_comercial,
                    'nombres' => $request->nombres,
                    'numero_dni' => $request->numero_dni,
                    'pais_id' => $request->pais_id,
                    'provincia_id' => $request->provincia_id,
                    'razon_social' => $request->razon_social,
                    'region_id' => $request->region_id,
                    'rubro_id' => $request->rubro_id,
                    'ruc' => $request->ruc,
                    'sector_economico_id' => $request->sector_economico_id,
                    'tipo_documento_id' => $request->tipo_documento_id,

                ]);
            }

            // 🔍 Buscar actividad
            $actividad = ActividadPnte::where('slug', $request->slug)->firstOrFail();

            // ❌ Validar si ya está registrado en esa actividad
            $existsActividad = EmpresarioActividad::where('actividad_id', $actividad->id)
                ->where('numero_dni', $request->numero_dni)
                ->exists();

            if ($existsActividad) {
                DB::rollBack();

                return response()->json([
                    'status' => 422,
                    'message' => 'El participante ya está registrado en esta actividad.',
                ]);
            }

            // ✅ Registrar asistencia
            EmpresarioActividad::create([
                'actividad_id' => $actividad->id,
                'slug' => $request->slug,
                'empresario_id' => $empresario->id,
                'numero_dni' => $request->numero_dni,
                'fecha_asistencia' => null,
            ]);

            // ✅ Incrementar participantes
            $actividad->increment('total_participantes');

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => $empresario->wasRecentlyCreated
                    ? 'Empresario registrado correctamente.'
                    : 'Empresario registrado en la actividad.',
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

    public function getEventsDots(Request $request)
    {
        $yearMonth = $request->input('year_month'); // YYYY-MM
        $cityId = $request->input('city_id'); // opcional

        if (! $yearMonth) {
            return response()->json([
                ['key' => 'dot-ugo', 'dot' => 'blue', 'dates' => []],
                ['key' => 'dot-ugse', 'dot' => 'red', 'dates' => []],
                ['key' => 'dot-ugseco', 'dot' => 'green', 'dates' => []],
            ]);
        }

        [$year, $month] = explode('-', $yearMonth);

        $query = ActividadPnte::query()
            ->where('activo', 1);

        // filtro opcional por ciudad
        if (! empty($cityId)) {
            $query->where('city_id', $cityId);
        }

        $events = $query->get([
            'id',
            'unidad',
            'fechas',
        ]);

        $blueDates = [];   // UGO
        $redDates = [];    // UGSE
        $greenDates = [];  // UGSECO

        foreach ($events as $event) {

            // convertir json/string a array
            $fechas = $event->fechas;

            if (is_string($fechas)) {
                $fechas = json_decode($fechas, true);
            }

            if (! is_array($fechas)) {
                continue;
            }

            foreach ($fechas as $fecha) {

                if (empty($fecha)) {
                    continue;
                }

                // validar que pertenezca al mes solicitado
                if (
                    substr($fecha, 0, 4) != $year ||
                    substr($fecha, 5, 2) != str_pad($month, 2, '0', STR_PAD_LEFT)
                ) {
                    continue;
                }

                $dateStr = \Carbon\Carbon::parse($fecha)->toISOString();

                switch ((int) $event->unidad) {

                    case 1: // UGO
                        if (! in_array($dateStr, $blueDates)) {
                            $blueDates[] = $dateStr;
                        }
                        break;

                    case 2: // UGSE
                        if (! in_array($dateStr, $redDates)) {
                            $redDates[] = $dateStr;
                        }
                        break;

                    case 3: // UGSECO
                        if (! in_array($dateStr, $greenDates)) {
                            $greenDates[] = $dateStr;
                        }
                        break;
                }
            }
        }

        return response()->json([
            [
                'key' => 'dot-ugo',
                'dot' => 'blue',
                'dates' => $blueDates,
            ],
            [
                'key' => 'dot-ugse',
                'dot' => 'red',
                'dates' => $redDates,
            ],
            [
                'key' => 'dot-ugseco',
                'dot' => 'green',
                'dates' => $greenDates,
            ],
        ]);
    }

    public function getEventsByDate(Request $request)
    {
        $dateSelected = $request->input('dateSelected'); // "2026-05-28"
        $offices = $request->input('office', []);
        $city = $request->input('city_id');

        if (! $dateSelected) {
            return response()->json([
                'message' => 'Debe proporcionar una fecha válida.',
                'events' => [],
            ], 400);
        }

        // Mapear unidades
        $unidadMap = [
            'UGO' => 1,
            'UGSE' => 2,
            'UGSECO' => 3,
        ];

        $unidadIds = collect($offices)
            ->map(fn ($o) => $unidadMap[strtoupper(trim($o))] ?? null)
            ->filter()
            ->values()
            ->toArray();

        $actividades = ActividadPnte::with([
            'tipoActividad:id,name',
            'nombreActividad:id,name',
            'regionRel:id,name',
            'provinciaRel:id,name',
            'distritoRel:id,name',
            'representante:id,name,lastname',
        ])
            ->where('activo', 1) // 🔥 SOLO ACTIVOS
            ->whereJsonContains('fechas', $dateSelected)
            ->when(! empty($city), fn ($q) => $q->where('region', $city))
            ->when(! empty($unidadIds), fn ($q) => $q->whereIn('unidad', $unidadIds))
            ->get();

        $formatted = $actividades->map(function ($actividad) use ($dateSelected) {

            $unidad = (int) $actividad->unidad;

            $tipo = match ($unidad) {
                1 => 'UGO',
                2 => 'UGSE',
                3 => 'UGSECO',
                default => 'OTRO'
            };

            $dotColor = match ($unidad) {
                1 => 'blue',
                2 => 'red',
                3 => 'green',
                default => 'default'
            };

            return [
                'id' => $actividad->id,
                'dates' => [$dateSelected],
                'cantidad_dias' => $actividad->cantidad_dias,
                'tipo_actividad' => $actividad->tipoActividad->name ?? null,
                'nombre_actividad' => $actividad->nombreActividad->name ?? null,
                'title' => $actividad->tema,
                'region' => $actividad->regionRel->name ?? $actividad->region,
                'provincia' => $actividad->provinciaRel->name ?? $actividad->provincia,
                'distrito' => $actividad->distritoRel->name ?? $actividad->distrito,
                'direccion' => $actividad->lugar,
                'entidad_organizadora' => $actividad->entidad_organizadora,
                'entidad_aliada' => $actividad->entidad_aliada,
                'mypes_beneficiadas' => $actividad->mypes_beneficiadas,
                'total_participantes' => $actividad->total_participantes,
                'tipo' => $tipo,
                'dot' => $dotColor,
                'cancelado' => $actividad->cancelado,
                'reprogramado' => $actividad->reprogramado,
                'asesor' => $actividad->representante
                    ? mb_strtoupper(
                        trim("{$actividad->representante->name} {$actividad->representante->lastname}"),
                        'UTF-8'
                    )
                    : null,
            ];
        })->values();

        return response()->json([
            'message' => 'Eventos obtenidos correctamente.',
            'status' => 200,
            'data' => $formatted,
        ]);
    }
}
