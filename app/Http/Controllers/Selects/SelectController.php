<?php

namespace App\Http\Controllers\Selects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\City;
use App\Models\Province;
use App\Models\District;
use App\Models\Office;
use App\Models\Cde;
use App\Models\Gender;
use App\Models\Modality;
use App\Models\Typedocument;
use App\Models\Component;
use App\Models\Themecomponent;
use App\Models\Role;
use App\Models\DetailProcedure;
use App\Models\EconomicSector;
use App\Models\ComercialActivities;
use App\Models\Regime;
use App\Models\Notary;
use App\Models\Supervisor;
use App\Models\DriveFile;


class SelectController extends Controller
{
    public function getCities()
    {
        $cities = City::all();
        $data = $cities->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id,
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getProvinces($id)
    {
        $provinces = Province::where('city_id', $id)->get();

        $data = $provinces->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getDistricts($id)
    {
        $districts = District::where('province_id', $id)->get();

        $data = $districts->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getOffices()
    {
        $offices = Office::all();

        $data = $offices->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getCdes()
    {
        $cdes = Cde::all();

        $data = $cdes->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getGenders()
    {
        $genders = Gender::all();

        $data = $genders->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getModalities()
    {
        $modalities = Modality::all();

        $data = $modalities->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getTypeDocuments()
    {
        $typeDocuments = Typedocument::all();

        $data = $typeDocuments->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getComponents()
    {
        $components = Component::all();

        $data = $components->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getComponentTheme($id)
    {
        $themes = Themecomponent::where('component_id', $id)->get();

        $data = $themes->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getRoles()
    {
        $roles = Role::all();

        $data = $roles->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getProcedures()
    {
        $detailProcedures = DetailProcedure::all();

        $data = $detailProcedures->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getEconomicSectors()
    {
        $economicSectors = EconomicSector::all();

        $data = $economicSectors->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getComercialActivities()
    {
        $comercialActivities = ComercialActivities::all();

        $data = $comercialActivities->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getRegimes()
    {
        $regimes = Regime::all();

        $data = $regimes->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getNotaries()
    {
        $notaries = Notary::all();

        $data = $notaries->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getSupervisores()
    {
        $supervisors = Supervisor::with('profile')->get();

        $data = $supervisors->map(function ($item) {
            return [
                'label' => $item->profile['name'] . ' ' . $item->profile['lastname'] . ' ' . $item->profile['middlename'],
                'value' => $item->user_id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getFolders()
    {
        $files = DriveFile::all();

        $data = $files->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }
}
