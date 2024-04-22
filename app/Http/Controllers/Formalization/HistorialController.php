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
}
