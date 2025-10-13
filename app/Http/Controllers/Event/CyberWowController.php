<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Models\CyberwowBrand;
use App\Models\CyberwowParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class CyberWowController extends Controller
{
    public function cyberwowBackStep1($idParticipante)
    {
        try {
            // Buscar el participante por ID
            $participant = CyberwowParticipant::findOrFail($idParticipante);

            // Actualizar los campos paso1 y paso2 a null
            $participant->update([
                'paso1' => null,
                'paso2' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Los pasos fueron reiniciados correctamente.',
                'data' => $participant,
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reiniciar los pasos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cyberwowBackStep2($idParticipante)
    {
        try {
            $participant = CyberwowParticipant::findOrFail($idParticipante);

            $participant->paso2 = null;
            $participant->paso3 = null;
            $participant->save();

            return response()->json([
                'success' => true,
                'message' => 'Los pasos fueron reiniciados correctamente go 2',
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reiniciar los pasos: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function cyberwowDataStep2($idEvent, $idParticipante)
    {
        try {
            // 1️⃣ Buscar el participante
            $participante = CyberwowParticipant::find($idParticipante);

            if (!$participante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Participante no encontrado.'
                ], 404);
            }

            // 2️⃣ Buscar la marca asociada al evento, usuario autenticado y participante
            $brand = CyberwowBrand::with(['logo256', 'logo160'])
                ->where('wow_id', $idEvent)
                ->where('user_id', Auth::id())
                ->where('company_id', $participante->id)
                ->first();

            if (!$brand) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró información de la marca para este participante.',
                    'status' => 404
                ]);
            }

            // 3️⃣ Armar los datos con las relaciones
            $data = [
                'isService'    => $brand->isService,
                'description'  => $brand->description,
                'url'          => $brand->url,
                'logo256_url'  => $brand->logo256?->url,
                'logo160_url'  => $brand->logo160?->url,
                'logo256_id'  => $brand->logo256?->id,
                'logo160_id'  => $brand->logo160?->id,
                'wow_id'       => $brand->wow_id,
                'user_id'      => $brand->user_id,
                'company_id'   => $brand->company_id,
                'participante' => [
                    'id'          => $participante->id,
                    'name'        => $participante->name ?? null,
                    'company_id'  => $participante->company_id,
                ],
            ];

            // 4️⃣ Retornar respuesta
            return response()->json([
                'success' => true,
                'data'    => $data,
                'status'  => 200
            ], 200);
        } catch (\Exception $e) {
            // 5️⃣ Manejo de errores genérico
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la información.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
