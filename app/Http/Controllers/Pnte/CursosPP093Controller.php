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
        $now   = Carbon::now();

        $activities = ActividadPnte::with([
            'tainnerPp093:id,nombres_apellidos',
            'sedDescripcion:id,slug_actividad_pnte,descripcion'
        ])
            ->where('tipo_actividad_id', 6)
            ->where('unidad', 2)
            ->get()
            ->filter(function ($activity) use ($today) {
                // ✅ REGLA 2 y 3: Mostrar si al menos UNA fecha es hoy o futura
                foreach ($activity->fechas ?? [] as $fecha) {
                    $date = Carbon::parse($fecha)->startOfDay();
                    if ($date->gte($today)) {
                        return true;
                    }
                }
                return false;
            })
            ->map(function ($activity) use ($today) {

                // ✅ Filtramos CADA fecha: solo las que son hoy o futuras
                $validFechas = collect($activity->fechas)
                    ->filter(fn($fecha) => Carbon::parse($fecha)->startOfDay()->gte($today))
                    ->sort()
                    ->values();

                // Si no quedan fechas válidas, descartamos la actividad completa
                if ($validFechas->isEmpty()) return null;

                $nextDate = $validFechas->first();

                return [
                    'id'            => $activity->id,
                    'title'         => $activity->tema,
                    'slug'          => $activity->slug,
                    'componente_id' => $activity->componente_id,
                    'trainer_id'    => $activity->trainer_id,
                    'trainer'       => $activity->tainnerPp093?->nombres_apellidos,
                    'descripcion'   => $activity->sedDescripcion?->descripcion,
                    'fecha'         => Carbon::parse($nextDate)->format('d/m/Y'),

                    // ✅ Ahora devolvemos TODAS las fechas válidas (no solo la próxima)
                    'fechas'        => $validFechas->map(fn($f) => Carbon::parse($f)->format('d/m/Y'))->values(),

                    'horario'       => $activity->horario,
                    'link'          => $activity->link,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'status' => 200,
            'data'   => $activities
        ]);
    }
}
