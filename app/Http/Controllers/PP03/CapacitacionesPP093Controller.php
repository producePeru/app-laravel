<?php

namespace App\Http\Controllers\PP03;

use App\Http\Controllers\Controller;
use App\Models\ActividadPnte;
use App\Models\Empresario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CapacitacionesPP093Controller extends Controller
{
    public function getCoursesByBusinessManPp093(Request $request, $empresario_id)
    {
        try {
            // 1. Validamos que el parámetro sea un número válido
            if (!is_numeric($empresario_id)) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'El ID del empresario proporcionado no es válido.',
                ], 400);
            }

            // Fijamos la fecha de hoy a las 00:00:00 para proteger los eventos del mismo día
            $today = Carbon::today();

            // 2. Consultamos ActividadPnte aplicando el tipo_actividad_id y la relación
            $capacitaciones = ActividadPnte::with([
                'tainnerPp093:id,nombres_apellidos',
                'sedDescripcion:id,slug_actividad_pnte,descripcion'
            ])
                ->where('tipo_actividad_id', 6) // 🌟 Agregado para filtrar solo tipo 6
                ->whereHas('empresariosActividad', function ($query) use ($empresario_id) {
                    $query->where('empresario_id', $empresario_id);
                })
                ->orderBy('id', 'DESC')
                ->get()
                // 3. Aplicamos el mapeo estructurado
                ->map(function ($activity) use ($today) {

                    // Filtramos usando el día completo (startOfDay) comparado con 'today'
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

                        // Mantiene el formato correcto dia/mes/año
                        'fecha'         => $nextDate ? Carbon::parse($nextDate)->format('d/m/Y') : null,

                        'horario'       => $activity->horario,
                        'link'          => $activity->link,
                    ];
                })
                ->values();

            // 4. Retornar los datos
            return response()->json([
                'status' => 200,
                'data'   => $capacitaciones
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error en getCoursesByBusinessManPp093: " . $e->getMessage(), [
                'empresario_id' => $empresario_id,
                'trace'         => $e->getTraceAsString()
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Ocurrió un problema en el servidor al recuperar tus cursos.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function getPublicDataByRucAndDni($ruc, $dni)
    {
        // 1. Definimos explícitamente los campos permitidos y seguros
        $fields = [
            'id', // Lo incluimos por si lo necesitas para relacionar en el cliente
            'ruc',
            'razon_social',
            'nombre_comercial',
            'sector_economico_id',
            'rubro_id',
            'actividad_comercial_id',
            'region_id',
            'provincia_id',
            'distrito_id',
            'direccion',

            'pais_id',
            'tipo_documento_id',
            'numero_dni',
            'apellido_paterno',
            'apellido_materno',
            'nombres',
            'genero_id',
            'discapacidad',
            'cargo_empresa_id',
            'fecha_nacimiento',
            'edad',

            'actividad_comercial_nombre',
            'tipo_empresa_id',
            'f_inicio_act',
            'venta_anual',
            'medio_entero'
        ];

        try {
            // 2. Buscamos al empresario que coincida con ambos datos
            $empresario = Empresario::select($fields)
                ->where('ruc', $ruc)
                ->where('numero_dni', $dni)
                ->first();

            // 3. Si no existe, devolvemos un 404 limpio
            if (!$empresario) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'No se encontraron registros que coincidan con los datos ingresados.'
                ], 404);
            }

            // 4. Retorno exitoso con la data filtrada
            return response()->json([
                'status' => 200,
                'data'   => $empresario
            ], 200);
        } catch (\Exception $e) {
            // En producción puedes cambiar esto por Log::error(...)
            return response()->json([
                'status'  => 500,
                'message' => 'Ocurrió un error en el servidor al recuperar la información.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
