<?php

namespace App\Http\Controllers\Pnte;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\ActividadPnte;

class CursosPP093Controller extends Controller
{
    public function coursesPP093(Request $request)
    {
        $today = Carbon::today();

        $activities = ActividadPnte::with([
            'tainnerPp093:id,nombres_apellidos',
            'sedDescripcion:id,slug_actividad_pnte,descripcion'
        ])
            ->where('tipo_actividad_id', 6)
            ->get()
            ->filter(function ($activity) use ($today) {

                foreach ($activity->fechas ?? [] as $fecha) {

                    $date = Carbon::parse($fecha);

                    if (
                        $date->year == $today->year &&
                        $date->month == $today->month &&
                        $date->gte($today)
                    ) {
                        return true;
                    }
                }

                return false;
            })
            ->map(function ($activity) {

                $nextDate = collect($activity->fechas)
                    ->filter(fn($fecha) => Carbon::parse($fecha)->gte(now()))
                    ->sort()
                    ->first();

                return [
                    'id' => $activity->id,
                    'title' => $activity->tema,
                    'slug' => $activity->slug,
                    'componente_id' => $activity->componente_id,
                    'trainer_id' => $activity->trainer_id,
                    'trainer' => $activity->tainnerPp093?->nombres_apellidos,
                    'descripcion' => $activity->sedDescripcion?->descripcion,
                    'fecha' => $nextDate,
                    'horario' => $activity->horario,
                    'link' => $activity->link,
                ];
            })
            ->values();

        return response()->json([
            'status' => 200,
            'data' => $activities
        ]);
    }
}
