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
        // Tomamos la fecha de hoy a las 00:00:00 para evitar conflictos con las horas
        $today = Carbon::today();

        $activities = ActividadPnte::with([
            'tainnerPp093:id,nombres_apellidos',
            'sedDescripcion:id,slug_actividad_pnte,descripcion'
        ])
            ->where('tipo_actividad_id', 6)
            ->get()
            ->filter(function ($activity) use ($today) {
                // Evaluamos si el evento tiene al menos una fecha válida a partir de hoy en adelante
                foreach ($activity->fechas ?? [] as $fecha) {
                    $date = Carbon::parse($fecha)->startOfDay();

                    // 🌟 CORRECCIÓN: Trae eventos de hoy en adelante (no importa el mes o año mientras sea futuro)
                    if ($date->gte($today)) {
                        return true;
                    }
                }

                return false;
            })
            ->map(function ($activity) use ($today) {

                // 🌟 CORRECCIÓN: Filtramos las fechas usando el día completo (startOfDay) 
                // para no omitir eventos que ocurran hoy pero cuyas horas ya pasaron del 'now()'
                $nextDate = collect($activity->fechas)
                    ->filter(fn($fecha) => Carbon::parse($fecha)->startOfDay()->gte($today))
                    ->sort()
                    ->first();

                return [
                    'id'            => $activity->id,
                    'title'         => $activity->tema,
                    'slug'          => $activity->slug,
                    'componente_id' => $activity->componente_id,
                    'trainer_id'    => $activity->trainer_id,
                    'trainer'       => $activity->tainnerPp093?->nombres_apellidos,
                    'descripcion'   => $activity->sedDescripcion?->descripcion,

                    // 🌟 CORRECCIÓN: Formateamos la fecha devuelta a dia/mes/año (dd/mm/yyyy)
                    'fecha'         => $nextDate ? Carbon::parse($nextDate)->format('d/m/Y') : null,

                    'horario'       => $activity->horario,
                    'link'          => $activity->link,
                ];
            })
            ->values();

        return response()->json([
            'status' => 200,
            'data'   => $activities
        ]);
    }
}
