<?php

namespace App\Http\Controllers\RetroAlimentacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RetroAlimentacionController extends Controller
{
    /**
     * Supervisor de retroalimentación: SOLO users.supervisor_id == 1.
     * Ve el detalle de todos los temas y tiene todas las opciones habilitadas.
     */
    private function esSupervisor($user = null): bool
    {
        $user = $user ?: auth()->user();
        if (! $user) {
            return false;
        }

        return (int) ($user->supervisor_id ?? 0) === 1;
    }

    /** Solo el creador, el supervisor o el admin pueden gestionar un tema. */
    private function puedeGestionarTema($tema, $user = null): bool
    {
        $user = $user ?: auth()->user();

        return (int) $tema->user_id === (int) $user->id || $this->esSupervisor($user);
    }
    /**
     * GET /retroalimentacion/temas
     * Lista temas con creador + promedios + conteo de calificaciones.
     * Query: ?search=&estado=&page=&per_page=
     */
    public function index(Request $request)
    {
        try {
            $search   = $request->input('search');
            $estado   = $request->input('estado'); // 1 | 0 | null=todos
            $perPage  = (int) ($request->input('per_page', 15));
            $perPage  = $perPage > 0 && $perPage <= 100 ? $perPage : 15;

            $query = DB::table('retro_temas as t')
                ->leftJoin('users as u', 'u.id', '=', 't.user_id')
                ->leftJoin('retro_calificaciones as c', 'c.retro_tema_id', '=', 't.id')
                ->select(
                    't.id',
                    't.tema',
                    't.descripcion',
                    't.fecha',
                    't.hora',
                    't.estado',
                    't.user_id',
                    't.created_at',
                    DB::raw("CONCAT(COALESCE(u.name,''),' ',COALESCE(u.lastname,''),' ',COALESCE(u.middlename,'')) as creador_nombre"),
                    'u.email as creador_email',
                    DB::raw('COUNT(c.id) as total_calificaciones'),
                    DB::raw('ROUND(AVG(c.r1_dominio),2) as prom_r1'),
                    DB::raw('ROUND(AVG(c.r2_valor_mype),2) as prom_r2'),
                    DB::raw('ROUND(AVG(c.r3_formalizacion),2) as prom_r3'),
                    DB::raw('ROUND(AVG(c.r4_tiempo),2) as prom_r4'),
                    DB::raw('ROUND(AVG(c.r5_claridad),2) as prom_r5'),
                    DB::raw('ROUND(AVG(c.r6_ppt),2) as prom_r6'),
                    DB::raw('ROUND((AVG(c.r1_dominio)+AVG(c.r2_valor_mype)+AVG(c.r3_formalizacion)+AVG(c.r4_tiempo)+AVG(c.r5_claridad)+AVG(c.r6_ppt))/6,2) as promedio_general')
                )
                ->groupBy('t.id', 't.tema', 't.descripcion', 't.fecha', 't.hora', 't.estado', 't.user_id', 't.created_at', 'u.name', 'u.lastname', 'u.middlename', 'u.email')
                ->orderBy('t.fecha', 'desc')
                ->orderBy('t.hora', 'desc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('t.tema', 'like', "%{$search}%")
                        ->orWhere('u.name', 'like', "%{$search}%")
                        ->orWhere('u.lastname', 'like', "%{$search}%")
                        ->orWhere('u.dni', 'like', "%{$search}%");
                });
            }

            if ($estado !== null && $estado !== '') {
                $query->where('t.estado', (int) $estado);
            }

            $data = $query->paginate($perPage);

            // Indica si el usuario autenticado ya calificó cada tema
            $myIds = DB::table('retro_calificaciones')
                ->where('user_id', auth()->id())
                ->pluck('retro_tema_id')
                ->toArray();

            $data->getCollection()->transform(function ($item) use ($myIds) {
                $item->ya_calificado = in_array($item->id, $myIds);
                $item->es_mio = ((int) $item->user_id === (int) auth()->id());
                return $item;
            });

            return response()->json(['status' => 200, 'data' => $data, 'es_supervisor' => $this->esSupervisor()]);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Error al listar temas', 'error' => $th->getMessage(), 'status' => 500], 500);
        }
    }

    /**
     * POST /retroalimentacion/temas
     * Crea un tema. El creador se toma del usuario logueado (auth).
     * Body: { tema, descripcion?, fecha (Y-m-d), hora (H:i) }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tema'        => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
            'fecha'       => 'required|date_format:Y-m-d',
            'hora'        => 'required|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors(), 'status' => 422], 422);
        }

        try {
            $id = DB::table('retro_temas')->insertGetId([
                'tema'        => $request->tema,
                'descripcion' => $request->descripcion,
                'fecha'       => $request->fecha,
                'hora'        => $request->hora,
                'user_id'     => auth()->id(),
                'estado'      => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return response()->json(['message' => 'Tema de capacitación creado', 'status' => 200, 'id' => $id]);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Error al crear tema', 'error' => $th->getMessage(), 'status' => 500], 500);
        }
    }

    /**
     * GET /retroalimentacion/temas/{id}
     * Detalle + promedios + lista de calificaciones con su autor.
     */
    public function show($id)
    {
        try {
            $tema = DB::table('retro_temas as t')
                ->leftJoin('users as u', 'u.id', '=', 't.user_id')
                ->select(
                    't.*',
                    DB::raw("CONCAT(COALESCE(u.name,''),' ',COALESCE(u.lastname,''),' ',COALESCE(u.middlename,'')) as creador_nombre"),
                    'u.email as creador_email',
                    'u.dni as creador_dni'
                )
                ->where('t.id', $id)
                ->first();

            if (! $tema) {
                return response()->json(['message' => 'Tema no encontrado', 'status' => 404], 404);
            }

            $resumen = DB::table('retro_calificaciones')
                ->where('retro_tema_id', $id)
                ->select(
                    DB::raw('COUNT(*) as total'),
                    DB::raw('ROUND(AVG(r1_dominio),2) as prom_r1'),
                    DB::raw('ROUND(AVG(r2_valor_mype),2) as prom_r2'),
                    DB::raw('ROUND(AVG(r3_formalizacion),2) as prom_r3'),
                    DB::raw('ROUND(AVG(r4_tiempo),2) as prom_r4'),
                    DB::raw('ROUND(AVG(r5_claridad),2) as prom_r5'),
                    DB::raw('ROUND(AVG(r6_ppt),2) as prom_r6'),
                    DB::raw('ROUND((AVG(r1_dominio)+AVG(r2_valor_mype)+AVG(r3_formalizacion)+AVG(r4_tiempo)+AVG(r5_claridad)+AVG(r6_ppt))/6,2) as promedio_general'),
                    DB::raw('ROUND((AVG(r1_dominio)+AVG(r2_valor_mype)+AVG(r3_formalizacion))/3,2) as promedio_tecnico'),
                    DB::raw('ROUND((AVG(r4_tiempo)+AVG(r5_claridad)+AVG(r6_ppt))/3,2) as promedio_didactico')
                )
                ->first();

            $calificaciones = DB::table('retro_calificaciones as c')
                ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
                ->select(
                    'c.*',
                    DB::raw("CONCAT(COALESCE(u.name,''),' ',COALESCE(u.lastname,''),' ',COALESCE(u.middlename,'')) as calificador_nombre"),
                    'u.email as calificador_email'
                )
                ->where('c.retro_tema_id', $id)
                ->orderBy('c.created_at', 'desc')
                ->get();

            $miCalificacion = DB::table('retro_calificaciones')
                ->where('retro_tema_id', $id)
                ->where('user_id', auth()->id())
                ->first();

            return response()->json([
                'status' => 200,
                'tema' => $tema,
                'resumen' => $resumen,
                'calificaciones' => $calificaciones,
                'mi_calificacion' => $miCalificacion,
                'es_supervisor' => $this->esSupervisor(),
                'es_mio' => ((int) $tema->user_id === (int) auth()->id()),
            ]);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Error al obtener detalle', 'error' => $th->getMessage(), 'status' => 500], 500);
        }
    }

    /**
     * PUT /retroalimentacion/temas/{id}
     * Solo el creador o el supervisor (users.supervisor_id == 1) puede editar.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tema'        => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
            'fecha'       => 'sometimes|required|date_format:Y-m-d',
            'hora'        => 'sometimes|required|date_format:H:i',
            'estado'      => 'sometimes|required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors(), 'status' => 422], 422);
        }

        try {
            $tema = DB::table('retro_temas')->where('id', $id)->first();
            if (! $tema) {
                return response()->json(['message' => 'Tema no encontrado', 'status' => 404], 404);
            }

            $me = auth()->user();
            if (! $this->puedeGestionarTema($tema, $me)) {
                return response()->json(['message' => 'Solo el creador puede editar este tema', 'status' => 403], 403);
            }

            $data = $request->only(['tema', 'descripcion', 'fecha', 'hora', 'estado']);
            $data['updated_at'] = now();

            DB::table('retro_temas')->where('id', $id)->update($data);

            return response()->json(['message' => 'Tema actualizado', 'status' => 200]);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Error al actualizar', 'error' => $th->getMessage(), 'status' => 500], 500);
        }
    }

    /**
     * DELETE /retroalimentacion/temas/{id}
     */
    public function destroy($id)
    {
        try {
            $tema = DB::table('retro_temas')->where('id', $id)->first();
            if (! $tema) {
                return response()->json(['message' => 'Tema no encontrado', 'status' => 404], 404);
            }

            $me = auth()->user();
            if (! $this->puedeGestionarTema($tema, $me)) {
                return response()->json(['message' => 'Solo el creador puede eliminar este tema', 'status' => 403], 403);
            }

            DB::table('retro_temas')->where('id', $id)->delete(); // cascade a calificaciones

            return response()->json(['message' => 'Tema eliminado', 'status' => 200]);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Error al eliminar', 'error' => $th->getMessage(), 'status' => 500], 500);
        }
    }

    /**
     * PUT /retroalimentacion/temas/{id}/estado
     * Abrir (1) / cerrar (0) recepción de calificaciones.
     */
    public function toggleEstado(Request $request, $id)
    {
        try {
            $tema = DB::table('retro_temas')->where('id', $id)->first();
            if (! $tema) {
                return response()->json(['message' => 'Tema no encontrado', 'status' => 404], 404);
            }

            $nuevo = $request->input('estado', $tema->estado == 1 ? 0 : 1);
            DB::table('retro_temas')->where('id', $id)->update(['estado' => (int) $nuevo, 'updated_at' => now()]);

            return response()->json(['message' => $nuevo ? 'Tema abierto' : 'Tema cerrado', 'status' => 200, 'estado' => (int) $nuevo]);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Error al cambiar estado', 'error' => $th->getMessage(), 'status' => 500], 500);
        }
    }

    /**
     * PUT /retroalimentacion/temas/{id}/compromiso
     * El asesor creador del tema registra su compromiso de mejora personal
     * luego de leer los comentarios de sus colegas. Solo el creador puede
     * escribirlo (el super admin únicamente lo lee).
     * Body: { compromisos? }
     */
    public function guardarCompromiso(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'compromisos' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors(), 'status' => 422], 422);
        }

        try {
            $tema = DB::table('retro_temas')->where('id', $id)->first();
            if (! $tema) {
                return response()->json(['message' => 'Tema no encontrado', 'status' => 404], 404);
            }

            if ((int) $tema->user_id !== (int) auth()->id()) {
                return response()->json(['message' => 'Solo el creador del tema puede registrar su compromiso', 'status' => 403], 403);
            }

            DB::table('retro_temas')->where('id', $id)->update([
                'compromisos' => $request->compromisos,
                'updated_at'  => now(),
            ]);

            return response()->json(['message' => 'Compromiso guardado', 'status' => 200]);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Error al guardar compromiso', 'error' => $th->getMessage(), 'status' => 500], 500);
        }
    }

    /**
     * POST /retroalimentacion/temas/{id}/calificar
     * Un colega logueado califica (crea o actualiza su calificación).
     * Body: { r1_dominio, r2_valor_mype, r3_formalizacion,
     *         r4_tiempo, r5_claridad, r6_ppt (1-5),
     *         detener?, continuar?, empezar? }
     */
    public function calificar(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'r1_dominio'       => 'required|integer|min:1|max:5',
            'r2_valor_mype'    => 'required|integer|min:1|max:5',
            'r3_formalizacion' => 'required|integer|min:1|max:5',
            'r4_tiempo'        => 'required|integer|min:1|max:5',
            'r5_claridad'      => 'required|integer|min:1|max:5',
            'r6_ppt'           => 'required|integer|min:1|max:5',
            'detener'          => 'nullable|string|max:2000',
            'continuar'        => 'nullable|string|max:2000',
            'empezar'          => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Calificación inválida (ratings 1-5)', 'errors' => $validator->errors(), 'status' => 422], 422);
        }

        try {
            $tema = DB::table('retro_temas')->where('id', $id)->first();
            if (! $tema) {
                return response()->json(['message' => 'Tema no encontrado', 'status' => 404], 404);
            }

            if ((int) $tema->estado !== 1) {
                return response()->json(['message' => 'Este tema está cerrado y ya no recibe calificaciones', 'status' => 403], 403);
            }

            if ((int) $tema->user_id === (int) auth()->id() && ! $this->esSupervisor()) {
                return response()->json(['message' => 'No puedes calificar tu propio tema (es retroalimentación de colegas)', 'status' => 403], 403);
            }

            $payload = [
                'retro_tema_id'    => $id,
                'user_id'          => auth()->id(),
                'r1_dominio'       => $request->r1_dominio,
                'r2_valor_mype'    => $request->r2_valor_mype,
                'r3_formalizacion' => $request->r3_formalizacion,
                'r4_tiempo'        => $request->r4_tiempo,
                'r5_claridad'      => $request->r5_claridad,
                'r6_ppt'           => $request->r6_ppt,
                'detener'          => $request->detener,
                'continuar'        => $request->continuar,
                'empezar'          => $request->empezar,
                'updated_at'       => now(),
            ];

            $exists = DB::table('retro_calificaciones')
                ->where('retro_tema_id', $id)
                ->where('user_id', auth()->id())
                ->first();

            if ($exists) {
                DB::table('retro_calificaciones')->where('id', $exists->id)->update($payload);
                return response()->json(['message' => 'Calificación actualizada ¡Gracias por tu retroalimentación!', 'status' => 200]);
            }

            $payload['created_at'] = now();
            $newId = DB::table('retro_calificaciones')->insertGetId($payload);

            return response()->json(['message' => 'Calificación registrada ¡Gracias por tu retroalimentación!', 'status' => 200, 'id' => $newId]);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Error al calificar', 'error' => $th->getMessage(), 'status' => 500], 500);
        }
    }

    /**
     * DELETE /retroalimentacion/calificaciones/{id}
     * Elimina una calificación (dueño o admin).
     */
    public function destroyCalificacion($id)
    {
        try {
            $cal = DB::table('retro_calificaciones')->where('id', $id)->first();
            if (! $cal) {
                return response()->json(['message' => 'Calificación no encontrada', 'status' => 404], 404);
            }

            $me = auth()->user();
            if ((int) $cal->user_id !== (int) $me->id && ! $this->esSupervisor($me)) {
                return response()->json(['message' => 'No autorizado', 'status' => 403], 403);
            }

            DB::table('retro_calificaciones')->where('id', $id)->delete();

            return response()->json(['message' => 'Calificación eliminada', 'status' => 200]);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Error al eliminar calificación', 'error' => $th->getMessage(), 'status' => 500], 500);
        }
    }
}
