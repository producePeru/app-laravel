<?php

namespace App\Http\Controllers\Page;

use App\Http\Controllers\Controller;
use App\Models\Invitado;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class InvitadoController extends Controller
{
  public function store(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'nombre'   => ['required', 'string', 'max:150'],
      'telefono' => ['required', 'string', 'max:20'],
    ]);

    $invitado = Invitado::create([
      'nombre'   => $validated['nombre'],
      'telefono' => $validated['telefono'],
      'asistira' => 'pendiente',
      'slug'     => Invitado::generateUniqueSlug($validated['nombre']),
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Invitado creado correctamente',
      'data'    => $invitado,
    ], 201);
  }

  public function index(Request $request): JsonResponse
  {
    $query = Invitado::query();

    // ✅ búsqueda por nombre o slug
    if ($request->filled('search')) {
      $query->where(function ($q) use ($request) {
        $q->where('nombre', 'like', '%' . $request->search . '%')
          ->orWhere('slug',  'like', '%' . $request->search . '%');
      });
    }

    // ✅ filtro por estado
    if ($request->filled('asistira')) {
      $query->where('asistira', $request->asistira);
    }

    $invitados = $query
      ->orderBy('created_at', 'desc')
      ->get();

    return response()->json([
      'success' => true,
      'data'    => $invitados,
      'stats'   => [
        'total'       => $invitados->count(),
        'confirmados' => $invitados->where('asistira', 'si')->count(),
        'pendientes'  => $invitados->where('asistira', 'pendiente')->count(),
        'no_asisten'  => $invitados->where('asistira', 'no')->count(),
      ],
    ]);
  }

  // ✅ show por slug — para la invitación personalizada
  public function show(string $slug): JsonResponse
  {
    $invitado = Invitado::where('slug', $slug)->firstOrFail();

    return response()->json([
      'success' => true,
      'data'    => $invitado,
    ]);
  }

  public function update(Request $request, Invitado $invitado): JsonResponse
  {
    $validated = $request->validate([
      'nombre'   => ['sometimes', 'string', 'max:150'],
      'telefono' => ['sometimes', 'string', 'max:20'],
      'asistira' => ['sometimes', Rule::in(['si', 'no', 'pendiente'])],
    ]);

    $invitado->update($validated);

    return response()->json([
      'success' => true,
      'message' => 'Invitado actualizado correctamente',
      'data'    => $invitado->fresh(),
    ]);
  }

  public function destroy(Invitado $invitado): JsonResponse
  {
    $invitado->delete();

    return response()->json([
      'success' => true,
      'message' => 'Invitado eliminado correctamente',
      'data'    => null,
    ]);
  }

  public function confirmar(Invitado $invitado): JsonResponse
  {
    $invitado->update(['asistira' => 'si']);

    return response()->json([
      'success' => true,
      'message' => 'Asistencia confirmada',
      'data'    => $invitado->fresh(),
    ]);
  }
}
