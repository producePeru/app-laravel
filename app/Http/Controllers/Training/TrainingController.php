<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Training;
use App\Models\TrainingMeta;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TrainingController extends Controller
{

    public function index(Request $request)
    {
        $query = Training::with(['meta', 'especialista', 'dimension']);

        $items = $query->paginate(100);

        // 🔹 transform aplica sobre los elementos de la colección
        $items->getCollection()->transform(function ($item) {
            return $this->mapItems($item);
        });

        return response()->json([
            'data'   => $items,
            'status' => 200
        ]);
    }

    private function mapItems($item)
    {
        return [
            'id'                => $item->id,
            'fecha'             => $item->fecha,
            'horaInicio'        => $item->horaInicio,
            'horaFin'           => $item->horaFin,
            'tema'              => $item->tema,
            'modalidad'         => $item->modalidad,
            'estado'            => $item->estado,
            'participantes'     => $item->participantes,
            'empresas'          => $item->empresas,
            'coordinador'       => $item->coordinador,
            'observaciones'     => $item->observaciones,
            'lugar'             => $item->lugar,

            // relaciones
            'especialista'      => $item->especialista ? $item->especialista->name : null,
            'colorEspecialista' => $item->especialista ? $item->especialista->color : null,
            'dimension'         => $item->dimension ? $item->dimension->name : null,
            'meta_mes'          => $item->meta ? $item->meta->month : null,

            // ids
            'especialista_id'   => $item->especialista ? $item->especialista->id : null,
            'dimension_id'      => $item->dimension ? $item->dimension->id : null,
            'meta_id'           => $item->meta ? $item->meta->id : null,
        ];
    }


    public function store(Request $request)
    {
        $request->validate([
            'meta_id' => 'required|exists:trainingMetas,id',
            'especialista_id' => 'required|exists:trainingSpecialists,id',
            'dimension_id' => 'required|exists:trainingDimensions,id',
            'fecha' => 'required|date',
            'horaInicio' => 'required',
            'horaFin' => 'required|after:horaInicio',
            'modalidad' => 'required|in:1,2,3',
            'tema' => 'required|string|max:255',
            'lugar' => 'nullable|string|max:255',
            'participantes' => 'nullable|integer|min:0|max:99999',
            'empresas' => 'nullable|integer|min:0|max:99999',
            'estado' => 'required|in:1,2,3,4',
            'coordinador' => 'nullable|string|max:150',
            'observaciones' => 'nullable|string',
        ]);

        // ✅ Convertir ISO -> HH:mm:ss
        $data = $request->all();
        if (!empty($data['horaInicio'])) {
            $data['horaInicio'] = Carbon::parse($data['horaInicio'])->format('H:i:s');
        }
        if (!empty($data['horaFin'])) {
            $data['horaFin'] = Carbon::parse($data['horaFin'])->format('H:i:s');
        }

        $training = Training::create($data);

        return response()->json([
            'message' => 'Capacitación registrada correctamente',
            'data' => $training,
            'status' => 200
        ]);
    }

    public function update(Request $request, $id)
    {
        $training = Training::find($id);
        if (!$training) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $request->validate([
            'meta_id' => 'sometimes|exists:trainingMetas,id',
            'especialista_id' => 'sometimes|exists:trainingSpecialists,id',
            'dimension_id' => 'sometimes|exists:trainingDimensions,id',
            'fecha' => 'sometimes|date',
            'hora_inicio' => 'sometimes|date_format:H:i',
            'hora_fin' => 'sometimes|date_format:H:i|after:hora_inicio',
            'modalidad' => 'sometimes|in:1,2,3',
            'tema' => 'sometimes|string|max:255',
            'lugar' => 'nullable|string|max:255',
            'participantes' => 'nullable|integer|min:0|max:99999',
            'empresas' => 'nullable|integer|min:0|max:99999',
            'estado' => 'sometimes|in:1,2,3,4',
            'coordinador' => 'nullable|string|max:150',
            'observaciones' => 'nullable|string',
        ]);

        $training->update($request->all());

        return response()->json([
            'message' => 'Capacitación actualizada con éxito',
            'data' => $training,
            'status' => 200
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $training = Training::findOrFail($id);
        $training->estado = $request->estado;
        $training->save();

        return response()->json([
            'message' => 'Estado actualizado correctamente',
            'estado' => $training->estado
        ]);
    }



    // DASHBOARD
    public function meetingMonthlyGoals($year, $month)
    {
        // Buscar la meta para ese mes y año
        $meta = TrainingMeta::whereYear('month', $year)
            ->whereMonth('month', $month)
            ->first();

        if (!$meta) {
            // Si no hay meta, devolver estructura con ceros
            return response()->json([
                'meta' => [
                    'month'          => "$year-$month-01",
                    'capacitaciones' => 0,
                    'participantes'  => 0,
                    'empresas'       => 0,
                ],
                'totales' => [
                    'capacitaciones' => 0,
                    'participantes'  => 0,
                    'empresas'       => 0,
                    'especialistas'  => 0,
                ],
                'porcentajes' => [
                    'capacitaciones' => 0,
                    'participantes'  => 0,
                    'empresas'       => 0,
                ]
            ]);
        }

        // Obtener todos los trainings de esa meta
        $trainings = Training::where('meta_id', $meta->id)->get();

        // Hacer las sumatorias
        $totalCapacitaciones = $trainings->count();
        $totalParticipantes  = $trainings->sum('participantes');
        $totalEmpresas       = $trainings->sum('empresas');
        $totalEspecialistas  = $trainings->pluck('especialista_id')->unique()->count();

        // Calcular % de avance contra la meta
        $porcCapacitaciones = $meta->capacitaciones > 0
            ? round(($totalCapacitaciones / $meta->capacitaciones) * 100, 1)
            : 0;

        $porcParticipantes = $meta->participantes > 0
            ? round(($totalParticipantes / $meta->participantes) * 100, 1)
            : 0;

        $porcEmpresas = $meta->empresas > 0
            ? round(($totalEmpresas / $meta->empresas) * 100, 1)
            : 0;

        return response()->json([
            'meta' => $meta,
            'totales' => [
                'capacitaciones'   => $totalCapacitaciones,
                'participantes'    => $totalParticipantes,
                'empresas'         => $totalEmpresas,
                'especialistas'    => $totalEspecialistas,
            ],
            'porcentajes' => [
                'capacitaciones'   => $porcCapacitaciones,
                'participantes'    => $porcParticipantes,
                'empresas'         => $porcEmpresas,
            ]
        ]);
    }




    public function annualSummary($year)
    {
        // Metas del año (puede estar vacío)
        $metas = TrainingMeta::whereYear('month', $year)->get();

        // Sumas de metas anuales
        $metaCap = $metas->sum('capacitaciones');
        $metaPart = $metas->sum('participantes');
        $metaEmp  = $metas->sum('empresas');

        // Trainings del año
        $trainings = Training::whereYear('fecha', $year)->get();

        // Totales reales del año
        $totalCapacitaciones = $trainings->count();
        $totalParticipantes  = $trainings->sum('participantes');
        $totalEmpresas       = $trainings->sum('empresas');
        $totalEspecialistas  = $trainings->pluck('especialista_id')->unique()->count();

        // Porcentajes contra la meta anual (evita división por cero)
        $porcCap = $metaCap > 0 ? round(($totalCapacitaciones / $metaCap) * 100, 1) : 0;
        $porcPart = $metaPart > 0 ? round(($totalParticipantes / $metaPart) * 100, 1) : 0;
        $porcEmp = $metaEmp > 0 ? round(($totalEmpresas / $metaEmp) * 100, 1) : 0;

        // Construir array de 12 meses con totales por mes (útil para grid)
        $meses = [];
        for ($m = 1; $m <= 12; $m++) {
            $key = str_pad($m, 2, '0', STR_PAD_LEFT);
            $group = $trainings->filter(function ($t) use ($key) {
                return Carbon::parse($t->fecha)->format('m') === $key;
            });

            // Nombre del mes en español (fallback a inglés si locale no disponible)
            try {
                $mesNombre = Carbon::createFromDate($year, $m, 1)->locale('es')->isoFormat('MMMM');
            } catch (\Exception $e) {
                $mesNombre = Carbon::createFromDate($year, $m, 1)->format('F');
            }

            $meses[] = [
                'mes_num'        => $m,
                'mes_nombre'     => ucfirst($mesNombre),
                'capacitaciones' => $group->count(),
                'participantes'  => $group->sum('participantes'),
                'empresas'       => $group->sum('empresas'),
            ];
        }

        // Mejor mes según capacitaciones
        $best = collect($meses)->sortByDesc('capacitaciones')->first();
        $mejorMes = ($best && $best['capacitaciones'] > 0) ? $best : null;

        return response()->json([
            'metaAnual' => [
                'capacitaciones' => (int)$metaCap,
                'participantes'  => (int)$metaPart,
                'empresas'       => (int)$metaEmp,
            ],
            'totales' => [
                'capacitaciones' => (int)$totalCapacitaciones,
                'participantes'  => (int)$totalParticipantes,
                'empresas'       => (int)$totalEmpresas,
                'especialistas'  => (int)$totalEspecialistas,
            ],
            'porcentajes' => [
                'capacitaciones' => $porcCap,
                'participantes'  => $porcPart,
                'empresas'       => $porcEmp,
            ],
            'mejorMes' => $mejorMes,   // null o { mes_num, mes_nombre, capacitaciones, ... }
            'meses'    => $meses       // array con los 12 meses (útil para grid)
        ]);
    }


    public function breakdownByMonth($year)
    {

        $trainings = Training::whereYear('fecha', $year)->get();

        $meses = [];
        for ($m = 1; $m <= 12; $m++) {
            $key = str_pad($m, 2, '0', STR_PAD_LEFT);
            $group = $trainings->filter(function ($t) use ($key) {
                return Carbon::parse($t->fecha)->format('m') === $key;
            });

            $mesNombre = ucfirst(
                Carbon::createFromDate($year, $m, 1)->locale('es')->isoFormat('MMMM')
            );

            $meses[] = [
                'mes'            => $mesNombre,
                'capacitaciones' => $group->count(),
                'participantes'  => $group->sum('participantes'),
                'empresas'       => $group->sum('empresas'),
            ];
        }

        return response()->json($meses);
    }


    // CALENDARIO 
    public function calendarEvents(Request $request)
    {
        $year = $request->input('year');
        $month = $request->input('month');

        if (!$year || !$month) {
            return response()->json(['error' => 'Parámetros year y month son requeridos'], 400);
        }

        // Buscar capacitaciones del mes solicitado
        $trainings = Training::with('especialista') // asumiendo relación Training -> especialista
            ->whereYear('fecha', $year)
            ->whereMonth('fecha', $month)
            ->get();

        // Formatear salida
        $data = $trainings->map(function ($t) {
            return [
                'id'            => $t->id,
                'fecha'         => \Carbon\Carbon::parse($t->fecha)->format('Y-m-d'),
                'hora'          => Carbon::parse($t->horaInicio)->format('H:i') . ' - ' . Carbon::parse($t->horaFin)->format('H:i'),
                'tema'          => $t->tema,
                'especialista'  => $t->especialista?->name,
                'color'         => $t->especialista?->color ?? '#666666',
            ];
        });

        return response()->json($data);
    }

    public function topEspecialistas()
    {
        $top = DB::table('trainings')
            ->select(
                'especialista_id',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('especialista_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $ids = $top->pluck('especialista_id')->toArray();

        $especialistas = \App\Models\TrainingSpecialist::whereIn('id', $ids)->get();

        $result = $top->map(function ($item) use ($especialistas) {
            $especialista = $especialistas->firstWhere('id', $item->especialista_id);

            return [
                'id'    => $item->especialista_id,
                'name' => $especialista
                    ? ucwords(strtolower($especialista->name))
                    : 'Desconocido',
                'color' => $especialista?->color ?? '#666666',
                'total' => $item->total,
            ];
        });

        return response()->json([
            'topEspecialistas' => $result
        ]);
    }

    public function estadisticasDimensiones()
    {
        $result = Training::select('dimension_id', DB::raw('COUNT(*) as total'))
            ->groupBy('dimension_id')
            ->with('dimension:id,name') // relación en el modelo Training
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                return [
                    'id'   => $item->dimension_id,
                    'name' => $item->dimension ? strtolower($item->dimension->name) : 'Desconocido',
                    'total' => $item->total,
                ];
            });

        return response()->json($result);
    }


    public function estadisticasModalidad(Request $request)
    {
        $result = Training::select('modalidad', DB::raw('COUNT(*) as total'))
            ->groupBy('modalidad')
            ->pluck('total', 'modalidad'); // Devuelve en formato [modalidad => total]

        return response()->json([
            'presencial' => $result[1] ?? 0,
            'virtual'    => $result[2] ?? 0,
            'mixta'      => $result[3] ?? 0,
        ]);
    }

    public function estadisticasTrainings()
    {
        $estadisticas = Training::select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');


        // return $estadisticas;

        $data = [
            'programadas' => $estadisticas[1] ?? 0,
            'en_curso'    => $estadisticas[2] ?? 0,
            'completadas' => $estadisticas[3] ?? 0,
            'canceladas'  => $estadisticas[4] ?? 0,
        ];

        return response()->json($data);
    }
}
