<?php

namespace App\Http\Controllers\Formalization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Advisory;
use App\Models\Formalization10;
use App\Models\Formalization20;


class HistorialController extends Controller
{
    public function historialAdvisories($id)
    {
        $results = Advisory::ByUserId($id)->WithProfileAndRelations();

        return response()->json($results, 200);
    }

    public function historialFormalizations10($id)
    {
        $results = Formalization10::ByUserId($id)->WithFormalizationAndRelations();

        return response()->json($results, 200);
    }

    public function historialFormalizations20($id)
    {
        $results = Formalization20::ByUserId($id)->WithFormalizationAndRelations();

        return response()->json($results, 200);
    }
}
