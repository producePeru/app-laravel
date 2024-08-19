<?php

namespace App\Http\Controllers\Formalization;

use App\Http\Controllers\Controller;
use App\Models\Advisory;
use App\Models\Formalization10;
use App\Models\Formalization20;
use App\Models\Supervisor;
use App\Models\SupervisorUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ChartController extends Controller
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


    public function index(Request $request)
    {

        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];
        $dateStart = $request->input('dateStart');
        $dateEnd = $request->input('dateEnd');

        $userRole = getUserRole();
        $roleIdArray = $userRole['role_id'];

        $asesoriesCount = [];
        $formalization10Count = [];
        $formalization20Count = [];
        $currentYear = date('Y');

        for ($i = 1; $i <= 12; $i++) {
            $asesoriesCount[$i] = 0;
            $formalization10Count[$i] = 0;
            $formalization20Count[$i] = 0;
        }

        if (in_array(1, $roleIdArray)) {
            $asesories = Advisory::descargaExcelAsesorias([
                'dateStart' => $dateStart,
                'dateEnd' => $dateEnd
            ]);
            $formalization10 = Formalization10::allFormalizations10([
                'dateStart' => $dateStart,
                'dateEnd' => $dateEnd,
            ]);
            $formalization20 = Formalization20::allFormalizations20([
                'dateStart' => $dateStart,
                'dateEnd' => $dateEnd,
            ]);
        }

        if (in_array(2, $roleIdArray) || in_array(7, $roleIdArray)) {

            $asesories = Advisory::ByUserId($user_id)->descargaExcelAsesorias([
                'dateStart' => $dateStart,
                'dateEnd' => $dateEnd
            ]);
            $formalization10 = Formalization10::ByUserId($user_id)->allFormalizations10([
                'dateStart' => $dateStart,
                'dateEnd' => $dateEnd,
            ]);
            $formalization20 = Formalization20::ByUserId($user_id)->allFormalizations20([
                'dateStart' => $dateStart,
                'dateEnd' => $dateEnd,
            ]);
        }

        $totalAsesories = count($asesories);
        $totalFormalization10 = count($formalization10);
        $totalFormalization20 = count($formalization20);

        foreach ($asesories as $asesory) {
            $month = date('n', strtotime($asesory->created_at));
            $year = date('Y', strtotime($asesory->created_at));
            if ($year == $currentYear) {
                $asesoriesCount[$month]++;
            }
        }

        foreach ($formalization10 as $formalization) {
            $month = date('n', strtotime($formalization->created_at));
            $year = date('Y', strtotime($formalization->created_at));
            if ($year == $currentYear) {
                $formalization10Count[$month]++;
            }
        }

        foreach ($formalization20 as $formalization) {
            $month = date('n', strtotime($formalization->created_at));
            $year = date('Y', strtotime($formalization->created_at));
            if ($year == $currentYear) {
                $formalization20Count[$month]++;
            }
        }

        $asesoriesCount = array_values($asesoriesCount);
        $formalization10Count = array_values($formalization10Count);
        $formalization20Count = array_values($formalization20Count);

        return [
            'asesories' => $asesoriesCount,
            'formalization10' => $formalization10Count,
            'formalization20' => $formalization20Count,

            'totalasesories' => $totalAsesories,
            'totalFormalization10' => $totalFormalization10,
            'totalFormalization20' => $totalFormalization20,
            'bar' => [$totalAsesories, $totalFormalization10, $totalFormalization20]
        ];
    }
}

