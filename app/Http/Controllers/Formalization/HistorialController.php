<?php

namespace App\Http\Controllers\Formalization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Advisory;
use App\Models\Formalization10;
use App\Models\Formalization20;
use Illuminate\Support\Facades\DB;

class HistorialController extends Controller
{
    public function historialAdvisories($userId, $dni)
    {  
        $roleUser = DB::table('role_user')
        ->where('user_id', $userId)
        ->where('dniuser', $dni)
        ->first();
        
        if (!$roleUser) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        if ($roleUser->role_id === 1) {
            $results = Advisory::WithProfileAndRelations();
            return response()->json($results, 200);
        }

        if ($roleUser->role_id === 2) {
            $results = Advisory::ByUserId($userId)->WithProfileAndRelations();
            return response()->json($results, 200);
        }
    }

    public function historialFormalizations10($userId, $dni)
    {
        $roleUser = DB::table('role_user')
        ->where('user_id', $userId)
        ->where('dniuser', $dni)
        ->first();
        
        if (!$roleUser) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        if ($roleUser->role_id === 1) {
            $results = Formalization10::WithFormalizationAndRelations();
            return response()->json($results, 200);
        }

        if ($roleUser->role_id === 2) {
            $results = Formalization10::ByUserId($userId)->WithFormalizationAndRelations();
            return response()->json($results, 200);
        }
    }



    public function historialFormalizations20($userId, $dni)
    {

        $roleUser = DB::table('role_user')
        ->where('user_id', $userId)
        ->where('dniuser', $dni)
        ->first();
        
        if (!$roleUser) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        if ($roleUser->role_id === 1) {
            $results = Formalization20::WithFormalizationAndRelations();
            return response()->json($results, 200);
        }

        if ($roleUser->role_id === 2) {
            $results = Formalization20::ByUserId($userId)->WithFormalizationAndRelations();
            return response()->json($results, 200);
        }
    }
}
