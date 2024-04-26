<?php

namespace App\Http\Controllers\Advisory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Advisory;

class AdvisoryController extends Controller
{
    public function index()
    {

        $advisory = Advisory::withProfileAndRelations();

        return response()->json($advisory, 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'observations' => 'nullable|string',
            'user_id' => 'required|integer',
            'people_id' => 'required|integer',
            'component_id' => 'required|integer',
            'theme_id' => 'required|integer',
            'modality_id' => 'required|integer',
            'city_id' => 'required|integer',
            'province_id' => 'required|integer',
            'district_id' => 'required|integer'
        ]);

        Advisory::create($data);

        return response()->json(['message' => 'Asesoría creada correctamente', 'status' => 200]);
    }

    public function destroy($id)
    {
        $item = Advisory::find($id);

        if (!$item) {
            return response()->json(['message' => 'No se encontró la asesoría'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Asesoría eliminada correctamente'], 200);
    }

    // public function findByData($date1, $date2)
    // {
    //     $advisory = Advisory::withAdvisoryRangeDate($date1, $date2);
    //     return response()->json($advisory, 200);
    // }
}
