<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Departament;
use App\Models\Province;
use App\Models\District;
use Illuminate\Http\Request;

class StaticController extends Controller
{
    public function getDataCountries()
    {
        $countries = Country::all();
        $formattedCountries = $countries->map(function ($country) {
            return [
                'id' => $country->id,
                'label' => $country->name,
                'value' => $country->id,
            ];
        });
        return response()->json(['data' => $formattedCountries]);
    }

    public function getDataDepartaments()
    {
        $departaments = Departament::all();
        $formattedDepartaments = $departaments->map(function ($departament) {
            return [
                'id' => $departament->idDepartamento,
                'label' => $departament->descripcion,
                'value' => $departament->descripcion,
            ];
        });
        return response()->json(['data' => $formattedDepartaments]);
    }

    public function getDataProvinces($departmentId)
    {
        $provinces = Province::where('idDepartamento', $departmentId)
            ->select('idProvincia', 'descripcion')
            ->get();

        $formattedProvinces = $provinces->map(function ($province) {
            return [
                'id' => $province->idProvincia,
                'label' => $province->descripcion,
                'value' => $province->descripcion
            ];
        });

        return response()->json(['data' => $formattedProvinces]);
    }

    public function getDataDistricts($provinceId)
    {
        $districs = District::where('idProvincia', $provinceId)
            ->select('idDistrito', 'descripcion')
            ->get();

        $formattedDistricts = $districs->map(function ($distric) {
            return [
                'id' => $distric->idDistrito,
                'label' => $distric->descripcion,
                'value' => $distric->descripcion,
            ];
        });

        return response()->json(['data' => $formattedDistricts]);
    }

}


