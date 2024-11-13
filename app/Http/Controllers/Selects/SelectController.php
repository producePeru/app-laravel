<?php

namespace App\Http\Controllers\Selects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Country;
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
use App\Models\Profile;
use App\Models\AgreementOperationalStatus;
use App\Models\AgreementStatus;
use App\Models\Typecapital;

class SelectController extends Controller
{
    private function getUserRole()
    {
        $user_id = Auth::user()->id;

        $roleUser = DB::table('role_user')
            ->where('user_id', $user_id)
            ->first();

        if ($user_id != $roleUser->user_id) {
            return response()->json(['message' => 'Este rol no es correcto', 'status' => 404]);
        }

        return [
            "role_id" => $roleUser->role_id,
            'user_id' => $user_id
        ];
    }

    public function getCountries()
    {
        $cities = Country::all();

        $data = $cities->sortBy('name')->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function getCities()
    {
        $cities = City::all();

        $data = $cities->sortBy('name')->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function getProvinces($id)
    {
        $provinces = Province::where('city_id', $id)->get();

        $data = $provinces->sortBy('name')->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function getDistricts($id)
    {
        $districts = District::where('province_id', $id)->get();

        $data = $districts->sortBy('name')->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        })->values();

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

        $data = $cdes->sortBy('name')->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        })->values();
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
        $themes = Themecomponent::where('component_id', $id)
            ->whereNotIn('id', [3, 25]) // Excluir los IDs 3 y 25
            ->get();

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

    public function getAsesores()
    {
        $asesores = DB::table('role_user')->where('role_id', 2)->get();

        $data = collect();

        foreach ($asesores as $asesor) {
            $profile = Profile::find($asesor->user_id);
            if ($profile) {
                $label = strtoupper($profile->name . ' ' . $profile->lastname . ' ' . $profile->middlename);
                $data->push([
                    'label' => $label,
                    'value' => $profile->id
                ]);
            }
        }

        // Ordena por label
        $data = $data->sortBy('label')->values();

        return response()->json(['data' => $data]);
    }

    public function getOperationalStatus()
    {
        $files = AgreementOperationalStatus::all();

        $data = $files->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getAgreementStatus()
    {
        $files = AgreementStatus::all();

        $data = $files->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getTypeCapital()
    {
        $typeDocuments = Typecapital::all();

        $data = $typeDocuments->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }
}
