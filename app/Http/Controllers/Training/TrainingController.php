<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
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
}
