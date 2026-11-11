<?php

namespace App\Http\Controllers\ComprasMyPeru;

use App\Http\Controllers\Controller;
use App\Models\ActividadPnte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReactivaSectorTextilController extends Controller
{
    public function eventsCalendarReactivaSectores(Request $request): JsonResponse
    {
        $user = auth()->user();

        $start = $request->input('start');
        $end = $request->input('end');

        $query = ActividadPnte::with([
            'tipoActividad:id,name',
            'nombreActividad:id,name',
            'regionRel:id,name',
            'provinciaRel:id,name',
            'distritoRel:id,name',
            'representante:id,name,lastname,middlename',
            'modalidad:id,name',
            'tainnerPp093:id,nombres_apellidos',
        ])
            ->select([
                'id',
                'unidad',
                'mes',
                'fechas',
                'cantidad_dias',
                'tipo_actividad_id',
                'nombre_actividad_id',
                'tema',
                'region',
                'provincia',
                'distrito',
                'lugar',
                'descripcion',
                'entidad_organizadora',
                'entidad_aliada',
                'representante_id',
                'requiere_pasaje',
                'monto_gasto',
                'mypes_beneficiadas',
                'modalidad_id',
                'total_participantes',
                'total_asesorias',
                'total_formalizaciones',
                'slug',
                'cancelado',
                'cancelado_por_id',
                'reprogramado',
                'reprogramado_por_id',
                'registrado_por_id',
                'actualizado_por_id',
                'horario',
                'activo',
                'link',
                'componente_id',
                'trainer_id',
                'tipo_mercado',
                'tipo_gestion',
                'eliminar',
                'prendido',
                'created_at',
            ])

            // ✅ FILTRO POR ROL
            ->when(in_array($user->rol, [1, 3]), function ($q) {
                // sin restricción
            })
            ->when($user->rol == 2, function ($q) use ($user) {
                $q->where('representante_id', $user->id);
            })
            ->when(! in_array($user->rol, [1, 2, 3]), function ($q) {
                $q->whereRaw('1 = 0');
            })

            // ✅ FILTROS FIJOS PARA CALENDARIO COMPRAS MYPERÚ
            ->where('unidad', 1)
            ->where('tipo_actividad_id', 1)
            ->whereIn('nombre_actividad_id', [25, 26, 27, 28, 29]);

        // ✅ OBTENER TODOS Y FILTRAR POR RANGO EN PHP
        $startDate = $start ? \Carbon\Carbon::parse($start)->startOfDay() : null;
        $endDate = $end ? \Carbon\Carbon::parse($end)->endOfDay() : null;

        $actividades = $query->get()->filter(function ($actividad) use ($startDate, $endDate) {
            if (! $startDate || ! $endDate) {
                return true;
            }

            $fechasRaw = $actividad->fechas;
            $fechas = is_string($fechasRaw) ? (json_decode($fechasRaw, true) ?? []) : (is_array($fechasRaw) ? $fechasRaw : []);

            foreach ($fechas as $f) {
                try {
                    $fecha = \Carbon\Carbon::parse($f);
                    if ($fecha->gte($startDate) && $fecha->lte($endDate)) {
                        return true;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            return false;
        })->values();

        // Convertir eliminar y prendido: 1/0 → true/false
        $actividades->each(function ($actividad) {
            $actividad->eliminar = $actividad->eliminar === 1;
            $actividad->formulario_registro = $actividad->prendido == 1;
        });

        return response()->json([
            'status' => 200,
            'message' => 'Eventos de calendario Compras MYPERÚ obtenidos correctamente.',
            'data' => $actividades,
        ]);
    }
}
