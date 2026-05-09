<?php

namespace App\Http\Controllers\Pnte;

use App\Http\Controllers\Controller;
use App\Models\ActividadPnte;
use App\Models\Attendance;
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
        ]);

        // ✅ Mes: extraer el mes de la fecha más antigua del array
        $fechaMinima = collect($validated['fechas'])
            ->map(fn ($f) => Carbon::parse($f))
            ->sortBy(fn ($d) => $d->timestamp)
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
            $slug = $original.'-'.$count;
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
                'status' => 403,
                'message' => 'No es posible editar esta actividad. El plazo de edición venció el '.
                    Carbon::parse($actividad->created_at)->format('d/m/Y').' a las 23:59. '.
                    'Por favor, contacte con su supervisor.',
            ], 403);
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
            'monto_gasto' => 'nullable|string|max:255',
            'mypes_beneficiadas' => 'nullable|integer|min:0',
            'modalidad_id' => 'nullable|exists:modalities,id',
            'total_participantes' => 'nullable|integer|min:0',
            'total_asesorias' => 'nullable|integer|min:0',
            'total_formalizaciones' => 'nullable|integer|min:0',
        ]);

        // ✅ Mes: extraer el mes de la fecha más antigua del array
        $fechaMinima = collect($validated['fechas'])
            ->map(fn ($f) => Carbon::parse($f))
            ->sortBy(fn ($d) => $d->timestamp)
            ->first();

        $validated['mes'] = (int) $fechaMinima->format('n');
        $validated['cantidad_dias'] = count($validated['fechas']);

        try {
            DB::transaction(function () use ($actividad, $validated) {
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
        $mapping = [
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
        ];

        $registros = DB::table('attendancelist')->get();
        $contador = 0;

        foreach ($registros as $item) {
            // NORMALIZACIÓN AGRESIVA:
            // a. Pasamos a minúsculas correctamente (mb_strtolower)
            // b. Quitamos acentos para que "DIFUSIÓN" coincida con "difusion"
            $tituloLimpio = $this->eliminarAcentos(mb_strtolower(trim($item->title), 'UTF-8'));

            if (array_key_exists($tituloLimpio, $mapping)) {
                DB::table('attendancelist')
                    ->where('id', $item->id)
                    ->update(['nombre_actividad_id' => $mapping[$tituloLimpio]]);

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
        $buscar = ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'];
        $reemplazar = ['a', 'e', 'i', 'o', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'n'];

        return str_replace($buscar, $reemplazar, $cadena);
    }

    // 5
    //     SELECT DISTINCT `modality` FROM attendancelist;

    //     UPDATE attendancelist
    // SET modality = 'v'
    // WHERE modality = 'VIRTUAL';
}
