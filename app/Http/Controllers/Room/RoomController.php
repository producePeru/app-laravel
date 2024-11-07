<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Models\Rooms;
use Illuminate\Http\Request;

class RoomController extends Controller
{

    public function index(Request $request)
    {
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        if (!$month || !$year) {
            return response()->json(['message' => 'El mes y el año son requeridos'], 400);
        }

        $rooms = Rooms::whereYear('startDate', $year)
            ->whereMonth('startDate', $month)
            ->with('profile:id,user_id,name,lastname,middlename')
            ->orderBy('timeStart', 'asc')
            ->get();

        $data = $rooms->map(function ($room) {
            $backgroundColor = '';
            switch ($room->room) {
                case 1:
                    $backgroundColor = '#e10f00';
                    break;
                case 2:
                    $backgroundColor = '#2196f3';
                    break;
                case 3:
                    $backgroundColor = 'blue';
                    break;
                default:
                    $backgroundColor = 'gray';
                    break;
            }

            // Devolver los campos requeridos
            return [
                'id' => $room->id,
                'title' => $room->title,
                'start' => $room->startDate,
                'timeStart' => $room->timeStart,
                'timeEnd' => $room->timeEnd,
                'description' => $room->description,
                'link' => $room->link,
                'unity' => $room->unity,
                'room' => $room->room,
                'backgroundColor' => $backgroundColor,
                '_id' => $room->profile->id,
                'registered' =>  $room->profile->name . ' ' . $room->profile->lastname . ' ' . $room->profile->middlename,
            ];
        });

        return response()->json($data);
    }



    public function store(Request $request)
    {
        $user = getUserRole();
        $user_id = $user['user_id'];

        // Obtener datos de la solicitud y asignar el user_id
        $data = $request->all();
        $data['user_id'] = $user_id;

        // Validación para evitar creación en fechas anteriores a hoy
        $today = date('Y-m-d');
        if ($data['startDate'] < $today) {
            return response()->json([
                'message' => 'No se puede reservar con fecha pasada.',
                'status' => 400
            ]);
        }

        // Verificar si ya existe una reserva en el mismo rango de tiempo y sala
        $existingRoom = Rooms::where('room', $data['room'])
            ->where('startDate', $data['startDate'])
            ->where(function ($query) use ($data) {
                $query->where(function ($q) use ($data) {
                    $q->where('timeStart', '<', $data['timeEnd'])  // Verifica que el inicio no esté antes del fin de la reserva
                        ->where('timeEnd', '>', $data['timeStart']); // Verifica que el fin no esté después del inicio de la reserva
                });
            })
            ->first();

        if ($existingRoom) {
            return response()->json([
                'message' => 'La sala ya está reservada en el rango de tiempo seleccionado.',
                'status' => 409
            ]);
        }

        // Crear la nueva reserva si no hay conflicto de horario
        Rooms::create($data);

        return response()->json([
            'message' => 'Sala reservada.',
            'status' => 200
        ]);
    }

    public function destroy($idRoom)
    {
        $user = auth()->user();

        $user_id = $user->id;

        $room = Rooms::find($idRoom);
        if (!$room) {
            return response()->json(['message' => 'No se encontró la sala', 'status' => 404]);
        }

        if ($room->user_id != $user_id) {
            return response()->json(['message' => 'No tiene permiso para eliminar esta sala', 'status' => 403]);
        }

        $room->delete();

        return response()->json(['message' => 'Sala eliminada correctamente', 'status' => 200]);
    }
}
