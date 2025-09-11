<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Training;
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
    public function breakdownByMonth($year)
    {
        // Inicializamos un array con los 12 meses
        $months = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];

        $result = [];

        for ($i = 1; $i <= 12; $i++) {
            // Filtramos por mes y año
            $capacitaciones = DB::table('trainings')
                ->whereYear('fecha', $year)
                ->whereMonth('fecha', $i)
                ->count();

            $participantes = DB::table('trainings')
                ->whereYear('fecha', $year)
                ->whereMonth('fecha', $i)
                ->sum('participantes');

            $empresas = DB::table('trainings')
                ->whereYear('fecha', $year)
                ->whereMonth('fecha', $i)
                ->sum('empresas');

            $result[] = [
                'mes' => $months[$i],
                'capacitaciones' => $capacitaciones,
                'participantes' => $participantes,
                'empresas' => $empresas
            ];
        }

        return response()->json($result);
    }



    public function annualSummary($year)
    {
        // Nombres de los meses
        $months = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];

        // Consulta única agrupada por mes
        $data = DB::table('trainings')
            ->select(
                DB::raw('MONTH(fecha) as mes_num'),
                DB::raw('COUNT(*) as capacitaciones'),
                DB::raw('SUM(participantes) as participantes'),
                DB::raw('SUM(empresas) as empresas')
            )
            ->whereYear('fecha', $year)
            ->groupBy(DB::raw('MONTH(fecha)'))
            ->get()
            ->keyBy('mes_num');

        $result = [];

        $totalCap = 0;
        $totalPart = 0;
        $totalEmp = 0;

        for ($i = 1; $i <= 12; $i++) {
            $cap = $data[$i]->capacitaciones ?? 0;
            $part = $data[$i]->participantes ?? 0;
            $emp = $data[$i]->empresas ?? 0;

            $totalCap += $cap;
            $totalPart += $part;
            $totalEmp += $emp;

            $result[] = [
                'mes' => $months[$i],
                'capacitaciones' => $cap,
                'participantes' => $part,
                'empresas' => $emp
            ];
        }

        // Mejor mes
        $mejorMes = collect($result)->sortByDesc('capacitaciones')->first();

        // Porcentaje anual de meta (suponiendo 300 capacitaciones como ejemplo)
        $progAnual = round(($totalCap / 300) * 100);
        $promedioMensual = round($totalPart / 12);

        return response()->json([
            'totales' => [
                'capacitaciones' => $totalCap,
                'participantes' => $totalPart,
                'empresas' => $totalEmp,
                'mejorMes' => $mejorMes['mes'],
                'capacitacionesMejorMes' => $mejorMes['capacitaciones'],
                'progresoAnual' => $progAnual,
                'promedioMensual' => $promedioMensual,
                'cumplimientoMetaAnual' => $progAnual
            ]
        ]);
    }


    public function meetingMonthlyGoals($year, $month)
    {


        // trae todos los que son fecha y mes sean de acuerdo a $year y $month

        // $meta = TrainingMeta::whereYear('month', $year)
        // ->whereMonth('month', $month)
        // ->first();


        // return $meta;














        // Buscar la meta correspondiente al año y mes
        $meta = DB::table('trainingmetas')
            ->whereYear('month', $year)
            ->whereMonth('month', $month)
            ->first();

        // Datos reales de trainings
        $real = DB::table('trainings')
            ->select(
                DB::raw('COUNT(*) as capacitaciones'),
                DB::raw('SUM(participantes) as participantes'),
                DB::raw('SUM(empresas) as empresas')
            )
            ->whereYear('fecha', $year)
            ->whereMonth('fecha', $month)
            ->first();

        // Opcional: nombres de los meses
        $months = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];

        return response()->json([
            'anio' => $year,
            'mes' => $month,
            'nombre_mes' => $months[intval($month)],
            'reales' => [
                'capacitaciones' => $real->capacitaciones ?? 0,
                'participantes' => $real->participantes ?? 0,
                'empresas' => $real->empresas ?? 0,
            ],
            'metas' => [
                'capacitaciones' => $meta->capacitaciones ?? 0,
                'participantes' => $meta->participantes ?? 0,
                'empresas' => $meta->empresas ?? 0,
            ]
        ]);
    }
}
