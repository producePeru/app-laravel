<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advisory;
use App\Models\Cde;
use App\Models\Formalization10;
use App\Models\Formalization20;
use App\Models\Notification;
use App\Models\People;
use App\Models\Reason;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReasonController extends Controller
{
    public function index(Request $request)
    {
        Notification::query()->update(['count' => 0]);

        $query = Reason::with(['user:id,name,lastname,middlename']);

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

        // 🚨 Aquí NO usamos paginate todavía → usamos get()
        $allReasons = $query->get();

        // 👉 Mapeo de registros
        $mapped = $allReasons->map(function ($reason) {
            $relatedData = null;

            switch ($reason->table_name) {
                case 'asesoria':
                    $relatedData = Advisory::withTrashed()->find($reason->row_id);
                    break;
                case 'f10':
                    $relatedData = Formalization10::withTrashed()->find($reason->row_id);
                    break;
                case 'f20':
                    $relatedData = Formalization20::withTrashed()->find($reason->row_id);
                    break;
                case 'people':
                    $relatedData = People::withTrashed()->find($reason->row_id);
                    break;
            }

            $businessman = null;


            if ($reason->table_name == 'people') {
                $businessman = $relatedData
                    ? People::where('id', $relatedData->id)
                    ->select('name', 'lastname', 'middlename', 'documentnumber')
                    ->first()
                    : null;
            } else {
                $businessman = $relatedData
                    ? People::where('id', $relatedData->people_id)
                    ->select('name', 'lastname', 'middlename', 'documentnumber')
                    ->first()
                    : null;
            }

            $cde = $relatedData ? Cde::where('id', $relatedData->cde_id)->value('name') : null;

            return [
                'table'       => $reason->table_name,
                'row_id'      => $reason->row_id,
                'action'      => $reason->action,
                'user'        => $reason->user->name . ' ' . $reason->user->lastname . ' ' . $reason->user->middlename,
                'description' => $reason->description,
                'businessman' => $businessman,
                'cde'         => $cde,
                'ruc'         => $relatedData ? $relatedData->ruc : null,
                'date'        => $reason->created_at->format('Y-m-d'),
                'time'        => $reason->created_at->format('H:i'),
            ];
        });

        // 👉 Agrupación (table,row_id,action,user)
        $grouped = $mapped->groupBy(function ($item) {
            return $item['table'] . '_' . $item['row_id'] . '_' . $item['action'] . '_' . $item['user'];
        })->map(function ($group) {
            $first = $group->first();
            return [
                'table'        => $first['table'],
                'row_id'       => $first['row_id'],
                'action'       => $first['action'],
                'user'         => $first['user'],
                'descriptions' => $group->pluck('description')->values(),
                'businessman'  => $first['businessman'],
                'cde'          => $first['cde'],
                'ruc'          => $first['ruc'],
                'date'         => $first['date'],
                'time'         => $first['time'],
            ];
        })->values();

        // 📑 Paginación manual después de agrupar
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;

        $paginated = new LengthAwarePaginator(
            $grouped->slice($offset, $perPage)->values(),
            $grouped->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json($paginated, 200);
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

        Notification::query()->increment('count');

        return response()->json([
            'message' => 'Reason creado correctamente',
            'data'    => $reason,
            'status'  => 200
        ], 200);
    }


    // cuenta las alertas
    public function howManyAlerts()
    {
        $notification = Notification::first();
        return response()->json([
            'count' => $notification->count ?? 0
        ]);
    }
}
