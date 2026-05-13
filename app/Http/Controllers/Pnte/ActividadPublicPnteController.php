<?php

namespace App\Http\Controllers\Pnte;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ActividadPnte;
use App\Models\Empresario;
use App\Models\EmpresarioActividad;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ActividadPublicPnteController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $actividad = ActividadPnte::with([
            'tipoActividad:id,name',
            'nombreActividad:id,name',
            'regionRel:id,name',
            'provinciaRel:id,name',
            'distritoRel:id,name',
            'representante:id,name,lastname,middlename'
        ])
            ->where('slug', $slug)
            ->select([
                'id',
                'slug',
                'fechas',
                'tema',
                'lugar',
                'tipo_actividad_id',
                'nombre_actividad_id',
                'region',
                'provincia',
                'distrito',
                'representante_id'
            ])
            ->firstOrFail();

        return response()->json([
            'status' => 200,
            'data'   => $actividad,
        ]);
    }

    public function getByDni(string $dni): JsonResponse
    {
        $empresario = Empresario::where('numero_dni', $dni)
            ->select([
                'numero_dni',
                'apellido_paterno',
                'apellido_materno',
                'nombres',
                'genero_id',
                'discapacidad',
                'celular',
                'correo_electronico',
                'cargo_empresa_id',
                'fecha_nacimiento',
                'edad',
                'pais_id'
            ])
            ->first();

        if (!$empresario) {
            return response()->json([
                'status'  => 404,
                'message' => 'Empresario no encontrado.',
            ]);
        }

        return response()->json([
            'status' => 200,
            'data'   => $empresario,
        ]);
    }

    public function getByRuc(string $ruc): JsonResponse
    {
        $empresario = Empresario::where('ruc', $ruc)
            ->select([
                'ruc',
                'razon_social',
                'nombre_comercial',
                'sector_economico_id',
                'actividad_comercial_nombre',
                'rubro_id',
                'actividad_comercial_id',
                'region_id',
                'provincia_id',
                'distrito_id',
                'direccion'
            ])
            ->first();

        if (!$empresario) {
            return response()->json([
                'status'  => 404,
                'message' => 'Empresa no encontrada.',
            ]);
        }

        return response()->json([
            'status' => 200,
            'data'   => $empresario,
        ]);
    }

    public function storeEmpresario(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
            'ruc' => 'nullable|size:11',
            'numero_dni' => 'required|string|max:12',
        ]);

        try {

            DB::beginTransaction();

            $empresario = Empresario::where('ruc', $request->ruc)
                ->where('numero_dni', $request->numero_dni)
                ->first();

            if ($empresario) {

                // 🔄 UPDATE (NO tocar ruc ni numero_dni)
                $empresario->update([

                    'actividad_comercial_nombre' => $request->actividad_comercial_nombre,
                    'apellido_materno' => $request->apellido_materno,
                    'apellido_paterno' => $request->apellido_paterno,
                    'celular' => $request->celular,
                    'correo_electronico' => $request->correo_electronico,
                    'direccion' => $request->direccion,
                    'discapacidad' => $request->discapacidad,
                    'distrito_id' => $request->distrito_id,
                    'genero_id' => $request->genero_id,
                    'nombre_comercial' => $request->nombre_comercial,
                    'nombres' => $request->nombres,
                    'pais_id' => $request->pais_id,
                    'provincia_id' => $request->provincia_id,
                    'razon_social' => $request->razon_social,
                    'region_id' => $request->region_id,
                    'rubro_id' => $request->rubro_id,
                    'sector_economico_id' => $request->sector_economico_id,
                    'tipo_documento_id' => $request->tipo_documento_id,
                ]);
            } else {

                // ✅ CREATE
                $empresario = Empresario::create([
                    'actividad_comercial_nombre' => $request->actividad_comercial_nombre,
                    'apellido_materno' => $request->apellido_materno,
                    'apellido_paterno' => $request->apellido_paterno,
                    'celular' => $request->celular,
                    'correo_electronico' => $request->correo_electronico,
                    'direccion' => $request->direccion,
                    'discapacidad' => $request->discapacidad,
                    'distrito_id' => $request->distrito_id,
                    'genero_id' => $request->genero_id,
                    'nombre_comercial' => $request->nombre_comercial,
                    'nombres' => $request->nombres,
                    'numero_dni' => $request->numero_dni,
                    'pais_id' => $request->pais_id,
                    'provincia_id' => $request->provincia_id,
                    'razon_social' => $request->razon_social,
                    'region_id' => $request->region_id,
                    'rubro_id' => $request->rubro_id,
                    'ruc' => $request->ruc,
                    'sector_economico_id' => $request->sector_economico_id,
                    'tipo_documento_id' => $request->tipo_documento_id,

                ]);
            }

            // 🔍 Buscar actividad
            $actividad = ActividadPnte::where('slug', $request->slug)->firstOrFail();

            // ❌ Validar si ya está registrado en esa actividad
            $existsActividad = EmpresarioActividad::where('actividad_id', $actividad->id)
                ->where('numero_dni', $request->numero_dni)
                ->exists();

            if ($existsActividad) {
                DB::rollBack();
                return response()->json([
                    'status' => 422,
                    'message' => 'El participante ya está registrado en esta actividad.'
                ]);
            }

            // ✅ Registrar asistencia
            EmpresarioActividad::create([
                'actividad_id' => $actividad->id,
                'slug' => $request->slug,
                'empresario_id' => $empresario->id,
                'numero_dni' => $request->numero_dni,
                'fecha_asistencia' => null,
            ]);

            // ✅ Incrementar participantes
            $actividad->increment('total_participantes');

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => $empresario->wasRecentlyCreated
                    ? 'Empresario registrado correctamente.'
                    : 'Empresario registrado en la actividad.'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 500,
                'message' => 'Error en el servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
