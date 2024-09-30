<?php

namespace App\Http\Controllers\Agreement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Commitment;

class CommitmentsController extends Controller
{
    public function index($id_agreement, $type)
    {
        if (!in_array($type, ['tuempresa', 'aliado'])) {
            return response()->json(['error' => 'Tipo no válido'], 400);
        }

        $commitments = Commitment::where('id_agreement', $id_agreement)
        ->where('type', $type)
        ->get();

        $data = $commitments->map(function ($item) {
            return [
                'id' => $item->id,
                'commitment' => $item->commitment,
                'fulfilled' => (bool) $item->fulfilled,
                'type' => $item->type,
                'id_agreement' => $item->id_agreement,
                'created_at' => $item->created_at,
            ];
        });

        return response()->json(['data' => $data]);
    }


    public function store(Request $request)
    {
        try {

            Commitment::create($request->all());

            return response()->json(['message' => 'Compromiso creado correctamente.', 'status' => 200]);

        } catch (QueryException $e) {
            return response()->json(['message' => 'El registro ha fallado', 'error' => $e], 400);
        }
    }

    public function updateFulfilled($id)
    {
        $commitment = Commitment::find($id);

        if (!$commitment) {
            return response()->json(['error' => 'Compromiso no encontrado'], 404);
        }

        $commitment->fulfilled = !$commitment->fulfilled;

        $commitment->save();

        return response()->json(['message' => 'Compromiso completado.', 'status' => 200]);

    }
}
