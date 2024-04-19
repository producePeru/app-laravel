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

class ChartController extends Controller
{
    public function index()
    {
        $advisories = Advisory::count();
        $formalizations10 = Formalization10::count();
        $formalizations20 = Formalization20::count();
        $supervisor = Supervisor::count();
        $asesores = SupervisorUser::distinct('supervisado_id')->count('supervisado_id');
        $registrados = DB::table('from_people')->where('from_id', 1)->count();

        return response()->json(['data' => [
            'advisories' => $advisories,
            'formalizations10' => $formalizations10,
            'formalizations20' => $formalizations20,
            'supervisores' => $supervisor,
            'asesores' => $asesores,
            'registrados' => $registrados
        ],  'status' => 200]);
    }

    public function countAdvisoriesByAdvisors()
    {

    }
}

// crea l funcion
// hace referencia a la tabla advisories que su modelo es Advisory
// que tiene los siguientes atributos
// id, observations, component, theme y user_id
// donde el user_id hace referencia a un id de la tabla user
// sucede que tambien hay una tabla llamada people
