<?php

namespace App\Http\Controllers;
use App\Models\Mype;

use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function TotalMype()
    {
        $year = 2024;
        $meta = 50000;
        $avance = Mype::count();
        $brecha = $meta - $avance;

        $p_meta = 100;
        $p_avance = round(($avance / $meta) * 100);
        $p_brecha = round(($brecha / $meta) * 100);

        $data = [
            'year' => $year,
            'meta' => $meta,
            'avance' => $avance,
            'brecha' => $brecha,

            'porcMeta' => $p_meta,
            'porcAvance' => $p_avance,
            'porcBrecha' => $p_brecha
        ];

        try {
            return response()->json(['data' => $data]);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error desconocido',], 500);
        }
    }

    public function AnualProgress()
    {
        $mypes = Mype::all();

        return $mypes;
    }
}


