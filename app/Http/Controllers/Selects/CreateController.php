<?php

namespace App\Http\Controllers\Selects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ComercialActivities;
use App\Models\Office;

class CreateController extends Controller
{
    public function postComercialActivities(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        ComercialActivities::create($data);

        return response()->json(['message' => 'Actividad creada correctamente', 'status' => 200]);
    }

    public function createOffice(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        Office::create($data);

        return response()->json(['message' => 'Creado correctamente', 'status' => 200]);
    }
}
