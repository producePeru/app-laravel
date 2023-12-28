<?php

namespace App\Http\Controllers;
use App\Models\Mype;
use Illuminate\Http\Request;
use Carbon\Carbon;

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
    
        $countByYear = [];
    
        foreach ($mypes as $mype) {
            try {
                $registrationDate = Carbon::createFromFormat('d/m/Y H:i', $mype->registration_date);
            } catch (\Exception $e) {
                continue;
            }
    
            if (!$registrationDate) {
                continue;
            }
    
            $year = $registrationDate->year;
    
            if (array_key_exists($year, $countByYear)) {
                $countByYear[$year]++;
            } else {
                $countByYear[$year] = 1;
            }
        }
    
        $categories = array_keys($countByYear);
        $data = array_values($countByYear);
    
        $response = [
            'categories' => $categories,
            'data' => $data,
        ];
    
        return response()->json($response);
    }

    public function MonthProgress()
    {
        $mypes = Mype::all();

        $countByYear = [];

        foreach ($mypes as $mype) {
            try {
                $registrationDate = Carbon::createFromFormat('d/m/Y H:i', $mype->registration_date);
            } catch (\Exception $e) {
                continue;
            }

            if (!$registrationDate) {
                continue;
            }

            $year = $registrationDate->year;
            $month = $registrationDate->month;

            if (!array_key_exists($year, $countByYear)) {
                $countByYear[$year] = array_fill(1, 12, 0);
            }

            $countByYear[$year][$month]++; 
        }

        $formattedData = [];

        foreach ($countByYear as $year => $monthlyCounts) {
            $formattedData[$year] = [
                'categories' => [
                    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
                ],
                'data' => array_values($monthlyCounts), 
            ];
        }

        return response()->json($formattedData);
    }
}




