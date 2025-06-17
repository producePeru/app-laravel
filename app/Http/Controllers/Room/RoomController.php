<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|string',
            'sala' => 'required|string|max:100',
            'inicio' => 'required|date',
            'fin' => 'required|date|after:inicio',
            'descripcion' => 'nullable|string',
            'unidad' => 'nullable|string|max:50',
        ]);

        if (isset($validated['id'])) {
            // Actualizar reserva existente
            $room = Room::findOrFail($validated['id']);
            $room->update([
                'sala' => $validated['sala'],
                'inicio' => $validated['inicio'],
                'fin' => $validated['fin'],
                'descripcion' => $validated['descripcion'] ?? null,
                'unidad' => $validated['unidad'] ?? null,
                'updated_by' => Auth::id(),  // <-- Actualizar usuario que modificó
            ]);

            return response()->json([
                'message' => 'Reserva actualizada correctamente.',
                'id' => $room->id,
                'data' => $room,
                'status' => 200
            ]);
        } else {
            // Crear nueva reserva
            $room = Room::create([
                'sala' => $validated['sala'],
                'inicio' => $validated['inicio'],
                'fin' => $validated['fin'],
                'descripcion' => $validated['descripcion'] ?? null,
                'unidad' => $validated['unidad'] ?? null,
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Reserva creada correctamente.',
                'id' => $room->id,   // <-- Devuelvo el id para que el frontend pueda asignarlo
                'data' => $room,
                'status' => 200
            ]);
        }
    }

    // public function getByMonth(Request $request)
    // {
    //     $validated = $request->validate([
    //         'month' => 'required|integer|min:1|max:12',
    //         'year' => 'required|integer|min:2000',
    //     ]);

    //     $startOfMonth = now()->setDate($validated['year'], $validated['month'], 1)->startOfMonth();
    //     $endOfMonth = $startOfMonth->copy()->endOfMonth();

    //     $events = Room::with('creator')->whereBetween('inicio', [$startOfMonth, $endOfMonth])->get();

    //     return response()->json($events);
    // }

    public function getByMonth(Request $request)
    {
        $validated = $request->validate([
            'start' => 'required|date',
            'end'   => 'required|date|after_or_equal:start',
        ]);

        $startDate = Carbon::parse($validated['start'])->startOfDay();
        $endDate   = Carbon::parse($validated['end'])->endOfDay();

        $events = Room::with('creator')
            ->whereBetween('inicio', [$startDate, $endDate])
            ->get();

        return response()->json($events);
    }


    public function updateRoomDescription(Request $request, $id)
    {
        $validated = $request->validate([
            'descripcion' => 'required|string',
            'unidad' => 'nullable|string|max:50',
        ]);

        $room = Room::findOrFail($id);
        $room->descripcion = $validated['descripcion'];
        $room->unidad = $validated['unidad'];
        $room->updated_by = Auth::id();
        $room->save();

        return response()->json([
            'message' => 'Descripción actualizada correctamente.',
            'data' => $room,
            'status' => 200
        ]);
    }

    public function destroy($id)
    {
        $evento = Room::find($id);

        if (!$evento) {
            return response()->json(['message' => 'Evento no encontrado'], 404);
        }

        $evento->delete();

        return response()->json(['message' => 'Reserva eliminada del calendario', 'status' => 200]);
    }
}
