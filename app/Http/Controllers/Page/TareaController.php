<?php

namespace App\Http\Controllers\Page;

use App\Http\Controllers\Controller;
use App\Http\Requests\TareaRequest;
use App\Models\Tarea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TareaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tipo = $request->query('tipo', 'activas'); // activas | completadas | todas

        $tareas = match ($tipo) {
            'completadas' => Tarea::completadas()->get(),
            'todas' => Tarea::orderBy('orden')->get(),
            default => Tarea::activas()->get(),
        };

        return response()->json([
            'data' => $tareas,
            'total' => $tareas->count(),
        ]);
    }

    public function store(TareaRequest $request): JsonResponse
    {
        // El orden nuevo va al final
        $ultimoOrden = Tarea::max('orden') ?? 0;

        $tarea = Tarea::create([
            ...$request->validated(),
            'orden' => $ultimoOrden + 1,
        ]);

        return response()->json([
            'message' => 'Tarea creada correctamente.',
            'data' => $tarea,
        ], 201);
    }

    // GET /api/tareas/{id}
    public function show(Tarea $tarea): JsonResponse
    {
        return response()->json(['data' => $tarea]);
    }

    // PUT /api/tareas/{id}
    public function update(TareaRequest $request, Tarea $tarea): JsonResponse
    {
        $tarea->update($request->validated());

        return response()->json([
            'message' => 'Tarea actualizada correctamente.',
            'data' => $tarea->fresh(),
        ]);
    }

    // DELETE /api/tareas/{id}
    public function destroy(Tarea $tarea): JsonResponse
    {
        $tarea->delete();

        return response()->json([
            'message' => 'Tarea eliminada correctamente.',
        ]);
    }

    // PATCH /api/tareas/{id}/completar
    public function completar(Tarea $tarea): JsonResponse
    {
        $tarea->update(['completada' => true]);

        return response()->json([
            'message' => 'Tarea marcada como completada.',
            'data' => $tarea->fresh(),
        ]);
    }

    // PATCH /api/tareas/{id}/restaurar
    public function restaurar(Tarea $tarea): JsonResponse
    {
        $tarea->update(['completada' => false]);

        return response()->json([
            'message' => 'Tarea restaurada a activa.',
            'data' => $tarea->fresh(),
        ]);
    }

    // PATCH /api/tareas/reordenar
    public function reordenar(Request $request): JsonResponse
    {
        $request->validate([
            'orden' => 'required|array',
            'orden.*' => 'integer|exists:tareas,id',
        ]);

        foreach ($request->orden as $posicion => $id) {
            Tarea::where('id', $id)->update(['orden' => $posicion + 1]);
        }

        return response()->json(['message' => 'Orden actualizado correctamente.']);
    }
}
