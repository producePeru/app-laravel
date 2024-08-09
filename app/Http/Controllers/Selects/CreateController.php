<?php

namespace App\Http\Controllers\Selects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ComercialActivities;
use App\Models\Component;
use App\Models\Office;
use App\Models\EconomicSector;
use App\Models\Themecomponent;
use App\Models\Notary;
use App\Models\Cde;
use App\Models\City;
use App\Models\Province;
use App\Models\District;

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

    public function createCdeNotary(Request $request)
    {
        $notaryId = $request->input('notary_id');

        $notary = Notary::find($notaryId);

        if (!$notary) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        $city = City::find($notary->city_id);
        $province = Province::find($notary->province_id);
        $district = District::find($notary->district_id);

        if (!$city || !$province || !$district) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        $cde = Cde::where('notary_id', $notaryId)->first();

        if ($cde) {
            $cde->update([
                'name' => $notary->name,
                'city' => $city->name,
                'province' => $province->name,
                'district' => $district->name,
                'address' => $notary->address,
                'notary_id' => $notaryId
            ]);
        } else {
            $cde = Cde::create([
                'name' => $notary->name,
                'city' => $city->name,
                'province' => $province->name,
                'district' => $district->name,
                'address' => $notary->address,
                'notary_id' => $notaryId
            ]);
        }

        return response()->json($cde->id);

    }

}
