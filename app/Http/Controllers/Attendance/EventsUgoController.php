<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Mype;
use App\Models\People;
use App\Models\EventUgo;
use Illuminate\Http\Request;

class EventsUgoController extends Controller
{
    public function participantsUgoEvent(Request $request)
    {
        try {
            $payload = $request->all();

            // 1. Buscar o crear Mype por RUC
            $mype = Mype::firstOrCreate(
                ['ruc' => $payload['ruc']],
                [
                    'economicsector_id' => $payload['economicsector_id'],
                    'comercialactivity_id' => $payload['comercialactivity_id'],
                    'comercialName' => $payload['comercialName'],
                    'socialReason' => $payload['socialReason'] ?? null
                ]
            );

            // 2. Buscar o crear People por documentnumber
            $person = People::firstOrCreate(
                ['documentnumber' => $payload['documentnumber']],
                [
                    'typedocument_id' => $payload['typedocument_id'],
                    'lastname' => $payload['lastname'],
                    'middlename' => $payload['middlename'],
                    'name' => $payload['name'],
                    'gender_id' => $payload['gender_id'],
                ]
            );

            // 3. Buscar el id_form desde el slug
            $attendance = Attendance::where('slug', $payload['slug'])->first();

            // 3. Registrar en EventUgo
            $eventUgo = EventUgo::create([
                'id_mype' => $mype->id,
                'id_businessman' => $person->id,
                'id_form' => $attendance->id,
                'comercialName' => $payload['comercialName'],
                'sick' => $payload['sick'],
                'phone' => $payload['phone'],
                'email' => $payload['email']
            ]);

            return response()->json([
                'status' => 200,
                'success' => true,
                'eventUgo_id' => $eventUgo->id,
                'mype_id' => $mype->id,
                'businessman_id' => $person->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Error al procesar la solicitud.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listAttendanceByAsesor()
    {
        try {
            $attendances = Attendance::where('user_id', auth()->user()->id)->get();

            return $attendances;

            return response()->json([
                'status' => 200,
                'success' => true,
                'attendances' => $attendances
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Error al procesar la solicitud.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
