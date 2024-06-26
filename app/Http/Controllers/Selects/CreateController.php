<?php

namespace App\Http\Controllers\Selects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ComercialActivities;
use App\Models\Component;
use App\Models\Office;
use App\Models\EconomicSector;
use App\Models\Themecomponent;

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

    public function createEconomicSector(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        EconomicSector::create($data);

        return response()->json(['message' => 'Creado correctamente', 'status' => 200]);
    }

    public function createNewComponent(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        Component::create($data);

        return response()->json(['message' => 'Creado correctamente', 'status' => 200]);
    }

    public function createNewTheme(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'component_id' => 'required',
        ]);

        Themecomponent::create($data);

        return response()->json(['message' => 'Creado correctamente', 'status' => 200]);
    }

    public function createNewEconomicSector(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        EconomicSector::create($data);

        return response()->json(['message' => 'Creado correctamente', 'status' => 200]);
    }
}
