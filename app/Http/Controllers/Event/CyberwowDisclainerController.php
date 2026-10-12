<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Models\CyberwowDisclainer;
use App\Models\Fair;
use Illuminate\Http\Request;

class CyberwowDisclainerController extends Controller
{
    /**
     * Obtener la declaración jurada de un evento Cyber Wow.
     * GET events-ugse/cyber-wow-disclaimer/{idFair}
     */
    public function show($idFair)
    {
        $fair = Fair::findOrFail($idFair);

        $disclaimer = CyberwowDisclainer::where('id_fair', $fair->id)->first();

        return response()->json([
            'data' => $disclaimer,
            'status' => 200,
        ]);
    }

    /**
     * Crear o actualizar la declaración jurada de un evento.
     * POST events-ugse/cyber-wow-disclaimer
     * Body: { id_fair, descripcion }
     * user_id = usuario autenticado
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_fair' => 'required|exists:fairs,id',
            'descripcion' => 'nullable|string',
        ]);

        $disclaimer = CyberwowDisclainer::updateOrCreate(
            ['id_fair' => $validated['id_fair']],
            [
                'descripcion' => $validated['descripcion'],
                'user_id' => $request->user()->id,
            ]
        );

        return response()->json([
            'data' => $disclaimer,
            'message' => 'Declaración jurada guardada correctamente',
            'status' => 200,
        ]);
    }
}
