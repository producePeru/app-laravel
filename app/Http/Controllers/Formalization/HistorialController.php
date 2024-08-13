<?php

namespace App\Http\Controllers\Formalization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Advisory;
use App\Models\Formalization10;
use App\Models\Formalization20;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HistorialController extends Controller
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

    public function historialAdvisories()
    {
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];

        // 1.supervisor
        if ($role_id === 1 || $user_id === 1) {
            $results = Advisory::withAllAdvisories();
            return response()->json($results, 200);
        }
        // 2.asesor
        if ($role_id === 2) {
            $results = Advisory::ByUserId($user_id)->withAllAdvisories();
            return response()->json($results, 200);
        }
    }

    public function historialFormalizations10()
    {
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];

        // 1.supervisor
        if ($role_id === 1 || $user_id === 1) {
            $results = Formalization10::withAllFomalizations10();
            return response()->json($results, 200);
        }
        // 2. asesor
        if ($role_id === 2) {
            $results = Formalization10::ByUserId($user_id)->withAllFomalizations10();
            return response()->json($results, 200);
        }
    }

    public function historialFormalizations20()
    {
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];

        // Supervisor y superandin
        if ($role_id === 1 || $user_id === 1) {
            $results = Formalization20::withAllFomalizations20();
            return response()->json($results, 200);
        }

        if ($role_id === 2) {
            $results = Formalization20::ByUserId($user_id)->withAllFomalizations20();
            return response()->json($results, 200);
        }
    }



    // TABULADOR DE ASESORIA-FORMALIZACIONES...

    public function filterHistorialAdvisoriesByDates(Request $request)
    {
        $filters = [
            'user_id' => !$request->input('user_id') ? null : explode(',', $request->input('user_id')),
            'people_id' => $request->input('people_id'),
            'dateStart' => $request->input('dateStart'),
            'dateEnd' => $request->input('dateEnd'),
            'city_id' => $request->input('city_id')
        ];

        $userRole = getUserRole();                              //🚩
        $roleIdArray = $userRole['role_id'];
        $user_id = $userRole['user_id'];

        if (in_array(1, $roleIdArray) || $user_id === 1) {
            $advisories = Advisory::withAdvisoryRangeDate($filters);
            return response()->json($advisories, 200);
        }
        if (in_array(2, $roleIdArray) || in_array(7, $roleIdArray)) {
            $results = Advisory::ByUserId($user_id)->withAdvisoryRangeDate($filters);
            return response()->json($results, 200);
        }
    }

    // 10
    public function filterHistorialFormalizations10ByDates(Request $request)
    {
        // $role_id = $this->getUserRole()['role_id'];
        // $user_id = $this->getUserRole()['user_id'];
        $filters = [
            'user_id' => !$request->input('user_id') ? null : explode(',', $request->input('user_id')),
            'people_id' => $request->input('people_id'),
            'dateStart' => $request->input('dateStart'),
            'dateEnd' => $request->input('dateEnd'),
            'city_id' => $request->input('city_id')
        ];

        $userRole = getUserRole();
        $roleIdArray = $userRole['role_id'];
        $user_id = $userRole['user_id'];

        if (in_array(1, $roleIdArray) || $user_id === 1) {
            $data = Formalization10::withFormalizationRangeDate($filters);
            return response()->json($data, 200);
        }
        if (in_array(2, $roleIdArray) || in_array(7, $roleIdArray)) {
            $results = Formalization10::ByUserId($user_id)->withFormalizationRangeDate($filters);
            return response()->json($results, 200);
        }
    }

    public function filterHistorialFormalizations20ByDates(Request $request)
    {
        $filters = [
            'user_id' => !$request->input('user_id') ? null : explode(',', $request->input('user_id')),
            'people_id' => $request->input('people_id'),
            'dateStart' => $request->input('dateStart'),
            'dateEnd' => $request->input('dateEnd'),
            'city_id' => $request->input('city_id')
        ];

        $userRole = getUserRole();
        $roleIdArray = $userRole['role_id'];
        $user_id = $userRole['user_id'];

        if (in_array(1, $roleIdArray) || $user_id === 1) {
            $data = Formalization20::withFormalizationRangeDate($filters);
            return response()->json($data, 200);
        }
        if (in_array(2, $roleIdArray) || in_array(7, $roleIdArray)) {
            $results = Formalization20::ByUserId($user_id)->withFormalizationRangeDate($filters);
            return response()->json($results, 200);
        }
    }
    // TABULADOR DE ASESORIA-FORMALIZACIONES...


    // HISTORIAL DE REGISTROS...
    public function getByPeopleIdRegisters($peopleId)
    {
        $advisories = Advisory::
        where('people_id', $peopleId)
        ->with('user.profile', 'component', 'theme','modality', 'city', 'province', 'district')
        ->get()
        ->map(function ($advisory) {
            return [
                'id' => $advisory->id,
                'createDate' => $advisory->created_at,
                'updateDate' => $advisory->updated_at,
                'asesor' => strtoupper($advisory->user->profile->name . ' ' . $advisory->user->profile->lastname . ' ' . $advisory->user->profile->middlename),
                'component' => $advisory->component->name,
                'theme' => $advisory->theme->name,
                'modality' => $advisory->modality->name,
                'city' => $advisory->city->name,
                'province' => $advisory->province->name,
                'district' => $advisory->district->name
            ];
        })->sortByDesc('created_at');


        $formalization10 = Formalization10::
        where('people_id', $peopleId)
        ->with('detailprocedure', 'modality', 'economicsector', 'comercialactivity', 'city', 'province', 'district', 'user.profile')
        ->get()
        ->map(function ($item) {
            return [
                'id' => $item->id,
                'createDate' => $item->created_at,
                'updateDate' => $item->updated_at,
                'detailprocedure' => $item->detailprocedure->name,
                'modality' => $item->modality->name,
                'economicsector' => $item->economicsector->name,
                'comercialactivity' => $item->comercialactivity->name,
                'city' => $item->city->name,
                'ruc' => $item->ruc,
                'province' => $item->province->name,
                'district' => $item->district->name,
                'asesor' => strtoupper($item->user->profile->name . ' ' . $item->user->profile->lastname . ' ' . $item->user->profile->middlename)
            ];
        })->sortByDesc('created_at');


        $formalization20 = Formalization20::
            where('people_id', $peopleId)
            ->with('economicsector', 'comercialactivity', 'regime', 'city', 'province', 'district', 'modality', 'notary', 'mype', 'user.profile')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'createDate' => $item->created_at,
                    'task' => $item->task,
                    // 'codesunarp' => $item->codesunarp ? $item->codesunarp : '-',
                    'numbernotary' => $item->numbernotary ? $item->numbernotary : '-',
                    'address' => $item->address ? $item->address : '-',
                    'economicsector' => $item->economicsector ? $item->economicsector->name : '-',
                    'comercialactivity' => $item->comercialactivity ? $item->comercialactivity->name : '-',
                    'regime' => $item->regime ? $item->regime->name : '-',
                    'city' => $item->city ? $item->city->name : '-',
                    'province' => $item->province ? $item->province->name : '-',
                    'district' => $item->district ? $item->district->name : '-',
                    'modality' => $item->modality ? $item->modality->name : '-',
                    'notary' => $item->notary ? $item->notary->name : '-',
                    'mypename' => $item->nameMype,
                    'ruc' => $item->ruc ? $item->ruc : 'EN TRÁMITE',
                    'asesor' => $item->user->profile->name . ' ' . $item->user->profile->lastname . ' ' . $item->user->profile->middlename
                ];
            })->sortByDesc('created_at');


        $data = [
            'advisories' => $advisories,
            'formalization10' => $formalization10,
            'formalization20' => $formalization20,
        ];


        return response()->json(['data' => $data, 'status' => 200]);
    }
}
