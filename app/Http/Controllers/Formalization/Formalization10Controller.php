<?php

namespace App\Http\Controllers\Formalization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Formalization10;

class Formalization10Controller extends Controller
{
    public function indexRuc10()
    {
        $formalization = Formalization10::withFormalizationAndRelations();

        return response()->json($formalization, 200);
    }

    public function storeRuc10(Request $request)
    {
        try {
            $data = $request->validate([
                'detailprocedure_id' => 'required|integer',
                'modality_id' => 'required|integer',
                'economicsector_id' => 'required|integer',
                'comercialactivity_id' => 'required|integer',
                'city_id' => 'required|integer',
                'province_id' => 'required|integer',
                'district_id' => 'required|integer',
                'people_id' => 'required|integer',
                'user_id' => 'required|integer'
            ]);

            Formalization10::create($data);

            return response()->json(['message' => 'Formalización creada correctamente', 'status' => 200]);
        } catch (QueryException $e) {
            return response()->json(['message' => 'La validación ha fallado', 'errors' => $e->errors()], 400);
        }
    }
}
