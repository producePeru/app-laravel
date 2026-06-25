<?php

namespace App\Http\Controllers\Pnte;

use App\Http\Controllers\Controller;
use App\Models\ActividadPnte;
use App\Models\Empresario;
use App\Models\EmpresarioActividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Client;
use App\Models\Token;

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
        try {
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
                ->latest()
                ->first();

            if ($empresario) {
                return response()->json([
                    'status' => 200,
                    'source' => 'database',
                    'data' => $empresario,
                ]);
            }
            return response()->json([
                'status' => 404,
                'message' => 'Empresa no encontrada en base de datos.',
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Error al consultar RUC',
                'error' => $e->getMessage(),
            ], 500);
        }
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
                ['key' => 'dot-ugsc', 'dot' => 'green', 'dates' => []],
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
                'key' => 'dot-ugsc',
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
            'UGSC' => 3,
        ];

        $unidadIds = collect($offices)
            ->map(fn($o) => $unidadMap[strtoupper(trim($o))] ?? null)
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
            ->when(! empty($city), fn($q) => $q->where('region', $city))
            ->when(! empty($unidadIds), fn($q) => $q->whereIn('unidad', $unidadIds))
            ->get();

        $formatted = $actividades->map(function ($actividad) use ($dateSelected) {

            $unidad = (int) $actividad->unidad;

            $tipo = match ($unidad) {
                1 => 'UGO',
                2 => 'UGSE',
                3 => 'UGSC',
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




    // 🚩🚩🚩🚩🚩🚩🚩🚩🚩🚩🚩🚩🚩🚩🚩🚩🚩🚩🚩🚩🚩🚩🚩🚩🚩 

    public function registerBusinessManPP093(Request $request)
    {
        $request->validate([
            'ruc'                                => 'required|string|max:11',
            'razon_social'                       => 'required|string|max:255',
            'nombre_comercial'                   => 'nullable|string|max:255',
            'sector_economico_id'                => 'required|integer',
            'rubro_id'                           => 'required|integer',
            'actividad_comercial_nombre'         => 'nullable|string',
            'region_id'                          => 'required|integer',
            'provincia_id'                       => 'required|integer',
            'distrito_id'                        => 'required|integer',
            'direccion'                          => 'required|string|max:500',
            'tipo_documento_id'                  => 'required|integer',
            'numero_dni'                         => 'required|string|max:12',
            'apellido_paterno'                   => 'required|string|max:100',
            'apellido_materno'                   => 'required|string|max:100',
            'nombres'                            => 'required|string|max:100',
            'genero_id'                          => 'required|integer',
            'discapacidad'                       => 'required|integer|in:0,1',
            'celular'                            => 'required|string|max:15',
            'correo_electronico'                 => 'required|email|max:150',
            'pais_id'                            => 'required|integer',
            'fecha_nacimiento'                   => 'required|string',
            'venta_anual'                        => 'required|integer',
            'medio_entero'                       => 'required|integer',
            'tipo_empresa_id'                    => 'required|integer',

            // Validamos el array dinámico enviado por el Front
            'actividades'                        => 'required|array|min:1',
            'actividades.*.slug'                 => 'required|string',
            'actividades.*.fecha_seleccionada'   => 'required|date_format:Y-m-d',
            'actividades.*.horario_inicio'       => 'required|string',
            'actividades.*.horario_fin'          => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $ruc = $request->ruc;
            $numeroDni = $request->numero_dni;

            // Conversión de fecha de nacimiento a Año-Mes-Día
            try {
                $carbonFecha = Carbon::createFromFormat('d/m/Y', $request->fecha_nacimiento);
                $fechaNacimientoFormatted = $carbonFecha->format('Y-m-d');
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status'  => 422,
                    'message' => 'El formato de la fecha de nacimiento debe ser Día/Mes/Año.'
                ], 422);
            }

            // 1. Unificamos todo el payload entrante mapeado para guardar/comparar
            $payloadData = [
                'ruc'                        => $ruc,
                'numero_dni'                 => $numeroDni,
                'razon_social'               => $request->razon_social,
                'nombre_comercial'           => $request->nombre_comercial,
                'sector_economico_id'        => $request->sector_economico_id,
                'rubro_id'                   => $request->rubro_id,
                'actividad_comercial_nombre' => $request->actividad_comercial_nombre,
                'region_id'                  => $request->region_id,
                'provincia_id'               => $request->provincia_id,
                'distrito_id'                => $request->distrito_id,
                'direccion'                  => $request->direccion,
                'tipo_documento_id'          => $request->tipo_documento_id,
                'apellido_paterno'           => $request->apellido_paterno,
                'apellido_materno'           => $request->apellido_materno,
                'nombres'                    => $request->nombres,
                'genero_id'                  => $request->genero_id,
                'discapacidad'               => $request->discapacidad,
                'celular'                    => $request->celular,
                'correo_electronico'         => $request->correo_electronico,
                'pais_id'                    => $request->pais_id,
                'fecha_nacimiento'           => $fechaNacimientoFormatted,
                'edad'                       => $carbonFecha->age,
                'venta_anual'                => $request->venta_anual,
                'medio_entero'               => $request->medio_entero,
                'tipo_empresa_id'            => $request->tipo_empresa_id,
                // Agregado por si manejas la columna en BD (puedes omitirlo si no existe)
                'actividad_comercial_id'     => $request->actividad_comercial_id ?? null
            ];

            // 2. Definimos las columnas que SÍ gatillan un nuevo registro si cambian y tienen valor
            $columnasEmpresaModificables = [
                'nombre_comercial',
                'sector_economico_id',
                'rubro_id',
                'actividad_comercial_id',
                'actividad_comercial_nombre',
                'region_id',
                'provincia_id',
                'distrito_id',
                'direccion'
            ];

            $columnasPersonalesModificables = [
                'discapacidad',
                'celular',
                'correo_electronico',
                'cargo_empresa_id',
                'venta_anual',
                'medio_entero'
            ];

            // Combinamos ambas listas para el bucle de validación estructural
            $columnasDiscrepantes = array_merge($columnasEmpresaModificables, $columnasPersonalesModificables);

            // Buscar el último registro guardado de este empresario
            $empresarioExistente = Empresario::where('ruc', $ruc)
                ->where('numero_dni', $numeroDni)
                ->latest('id')
                ->first();

            $empresario = null;

            if ($empresarioExistente) {
                $forzarNuevoRegistro = false;
                $camposParaCompletar = [];

                foreach ($payloadData as $columna => $valorEntrada) {
                    $valorActual = $empresarioExistente->{$columna};

                    // Si pertenece al grupo de columnas que gatillan un nuevo historial
                    if (in_array($columna, $columnasDiscrepantes)) {
                        // Condición: Si ya tiene valor almacenado Y el nuevo valor es diferente -> Fuerza Insert
                        if (!is_null($valorActual) && $valorActual !== '' && $valorActual != $valorEntrada) {
                            $forzarNuevoRegistro = true;
                            break;
                        }
                    }

                    // Guardamos en memoria por si solo toca completar datos vacíos (en cualquier columna)
                    if (is_null($valorActual) || $valorActual === '') {
                        $camposParaCompletar[$columna] = $valorEntrada;
                    }
                }

                if ($forzarNuevoRegistro) {
                    // REGLA: Hubo cambios en variables críticas -> Creamos nueva fila histórica
                    $empresario = Empresario::create($payloadData);
                } else {
                    // REGLA: No hay discrepancias en lo que ya estaba lleno, procedemos a rellenar si habían campos NULL
                    if (!empty($camposParaCompletar)) {
                        $empresarioExistente->update($camposParaCompletar);
                    }
                    $empresario = $empresarioExistente;
                }
            } else {
                // REGLA: Es la primera vez que se registra este RUC + DNI en el sistema
                $empresario = Empresario::create($payloadData);
            }

            $empresarioId = $empresario->id;

            // Procesar matrículas a capacitaciones/actividades
            foreach ($request->actividades as $act) {

                $actividadBase = ActividadPnte::where('slug', $act['slug'])->first();

                if (!$actividadBase) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => 404,
                        'message' => "La capacitación con el código slug '{$act['slug']}' no existe en el sistema.",
                    ], 404);
                }

                // Evitamos que se duplique exactamente la misma fila en la tabla intermedia
                $alreadyRegisteredExact = EmpresarioActividad::where('slug', $act['slug'])
                    ->where('empresario_id', $empresarioId)
                    ->where('fecha_seleccionada', $act['fecha_seleccionada'])
                    ->where('horario_inicio', $act['horario_inicio'])
                    ->where('horario_fin', $act['horario_fin'])
                    ->exists();

                if ($alreadyRegisteredExact) {
                    continue;
                }

                EmpresarioActividad::create([
                    'actividad_id'       => $actividadBase->id,
                    'slug'               => $act['slug'],
                    'empresario_id'      => $empresarioId,
                    'numero_dni'         => $numeroDni,
                    'fecha_seleccionada' => $act['fecha_seleccionada'],
                    'horario_inicio'     => $act['horario_inicio'],
                    'horario_fin'        => $act['horario_fin'],
                    'fecha_asistencia'   => null,
                ]);

                $actividadBase->increment('total_participantes');
            }

            DB::commit();

            return response()->json([
                'status'     => 200,
                'message'    => 'Procesado correctamente con reglas de control de historial.',
                'id'         => $empresarioId,
                'ruc'        => $ruc,
                'numero_dni' => $numeroDni,
                'celular'    => $request->celular
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error masivo en registerBusinessManPP093: " . $e->getMessage());

            return response()->json([
                'status'  => 500,
                'message' => 'Ocurrió un error inesperado en el servidor.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function checkToCoursePp093(Request $request)
    {
        // 1. Validación del Payload Completo y su Arreglo Anidado
        $request->validate([
            'empresario_id'                      => 'required|integer',
            'numero_dni'                         => 'required|string|max:12',
            'ruc'                                => 'nullable|string|max:11',
            'actividades'                        => 'required|array|min:1',
            'actividades.*.actividad_id'         => 'required|integer',
            'actividades.*.slug'                 => 'required|string',
            'actividades.*.fecha_seleccionada'   => 'required|date_format:Y-m-d',
            'actividades.*.horario_inicio'       => 'required|string',
            'actividades.*.horario_fin'          => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $empresarioId = $request->empresario_id;
            $numeroDni    = $request->numero_dni;

            // 2. Iteramos cada una de las actividades enviadas
            foreach ($request->actividades as $act) {

                // 🔥 VALIDACIÓN ULTRA ESPECÍFICA: Comprobamos si ya existe EXACTAMENTE la misma inscripción
                $alreadyRegisteredExact = EmpresarioActividad::where('actividad_id', $act['actividad_id'])
                    ->where('empresario_id', $empresarioId)
                    ->where('numero_dni', $numeroDni)
                    ->where('fecha_seleccionada', $act['fecha_seleccionada'])
                    ->where('horario_inicio', $act['horario_inicio'])
                    ->where('horario_fin', $act['horario_fin'])
                    ->exists();

                if ($alreadyRegisteredExact) {
                    DB::rollBack();

                    // Formateamos la fecha para que el mensaje sea amigable (Ej: 10/06/2026)
                    $fechaAmigable = date('d/m/Y', strtotime($act['fecha_seleccionada']));

                    return response()->json([
                        'status'  => 422,
                        'message' => "Ya te encuentras registrado en esta capacitación para el día {$fechaAmigable} en el horario de {$act['horario_inicio']} a {$act['horario_fin']}.",
                    ]);
                }

                // Comprobamos la existencia del curso base
                $actividad = ActividadPnte::find($act['actividad_id']);

                if (!$actividad) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => 404,
                        'message' => "La capacitación con ID {$act['actividad_id']} no existe o ha sido dada de baja.",
                    ], 404);
                }

                // 3. Si no existía duplicidad exacta, el registro se crea con normalidad
                // (Incluso si repite actividad_id pero cambia la fecha o la hora)
                EmpresarioActividad::create([
                    'actividad_id'       => $act['actividad_id'],
                    'slug'               => $act['slug'],
                    'empresario_id'      => $empresarioId,
                    'numero_dni'         => $numeroDni,
                    'fecha_seleccionada' => $act['fecha_seleccionada'],
                    'horario_inicio'     => $act['horario_inicio'],
                    'horario_fin'        => $act['horario_fin'],
                    'fecha_asistencia'   => null,
                ]);

                $actividad->increment('total_participantes');
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'message' => 'Inscripciones procesadas correctamente de manera masiva y exitosa.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Error masivo en checkToCoursePp093: " . $e->getMessage(), [
                'payload' => $request->all(),
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Ocurrió un error inesperado en el servidor al procesar las matrículas.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // cooperativas ********

    public function storeEmpresarioCooperativa(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
            'ruc' => 'nullable|size:11',
            'numero_dni' => 'required|string|max:12',
        ]);

        try {

            DB::beginTransaction();

            /**
             * 1. EMPRESARIO
             * Se crea SOLO si no existe una fila idéntica
             */
            $empresario = Empresario::firstOrCreate(
                [
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
                ]
            );

            /**
             * 2. ACTIVIDAD
             */
            $actividad = ActividadPnte::where('slug', $request->slug)
                ->firstOrFail();

            /**
             * 3. VALIDAR SI YA ESTÁ REGISTRADO EN ACTIVIDAD
             */
            $existsActividad = EmpresarioActividad::where('actividad_id', $actividad->id)
                ->where('empresario_id', $empresario->id)
                ->exists();

            if ($existsActividad) {

                DB::rollBack();

                return response()->json([
                    'status' => 422,
                    'message' => 'El participante ya está registrado en esta actividad.',
                ]);
            }

            /**
             * 4. REGISTRAR RELACIÓN
             */
            EmpresarioActividad::create([
                'actividad_id' => $actividad->id,
                'slug' => $request->slug,
                'empresario_id' => $empresario->id,
                'numero_dni' => $request->numero_dni,
                'fecha_asistencia' => null,
            ]);

            /**
             * 5. CONTADOR
             */
            $actividad->increment('total_participantes');

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Registro procesado correctamente.',
                'empresario_id' => $empresario->id,
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
}
