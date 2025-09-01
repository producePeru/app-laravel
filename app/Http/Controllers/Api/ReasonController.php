<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reason;
use Illuminate\Http\Request;

class ReasonController extends Controller
{
    public function index(Request $request)
    {
        $query = Reason::with(['user:id,name,lastname']);

        // 🔎 Búsqueda por nombre o apellido
        if ($search = $request->get('q')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%");
            });
        }

        // 🔄 Orden (asc | desc)
        $order = $request->get('order', 'desc');
        $query->orderBy('created_at', $order);

        // 📑 Paginación (default 10 por página)
        $reasons = $query->paginate($request->get('per_page', 10));

        // Formatear respuesta
        $data = $reasons->through(function ($reason) {
            return [
                'id'          => $reason->id,
                'table_name'  => $reason->table_name,
                'row_id'      => $reason->row_id,
                'description' => $reason->description,
                'action'      => $reason->action,
                'user'        => [
                    'id'       => $reason->user->id ?? null,
                    'name'     => $reason->user->name ?? '',
                    'lastname' => $reason->user->lastname ?? '',
                ],
                'date' => $reason->created_at?->format('Y-m-d'),
                'time' => $reason->created_at?->format('H:i'),
            ];
        });

        return response()->json($data, 200);
    }


    public function indicateReasonAction(Request $request)
    {
        // Validar datos, pero sin pedir user_id
        $validated = $request->validate([
            'table_name'  => 'required|string|max:50',
            'row_id'      => 'required|integer',
            'description' => 'nullable|string',
            'action'      => 'required|in:d,c,u,imp,dow',
        ]);

        // Tomar el user_id de la sesión autenticada
        $validated['user_id'] = auth()->id(); // o $request->user()->id

        // Crear registro
        $reason = Reason::create($validated);

        return response()->json([
            'message' => 'Reason creado correctamente',
            'data'    => $reason,
            'status'  => 200
        ], 200);
    }
}
