<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\CyberwowParticipant;
use App\Models\Fair;
use Illuminate\Http\Request;

class CyberWowParticipantesController extends Controller
{
    public function exportList(Request $request, $slug)
    {
        try {

            $fair = Fair::where('slug', $slug)->firstOrFail();

            $query = CyberwowParticipant::with([
                'region',
                'provincia',
                'distrito',
                'sectorEconomico',
                'actividadComercial',
                'rubro',
                'tipoDocumento',
                'genero',
                'pais',
                'medioEntero'
            ])->where('event_id', $fair->id)
                ->orderBy('created_at', 'desc');

            if ($request->filled('search')) {
                $search = $request->input('search');

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('lastname', 'LIKE', "%{$search}%")
                        ->orWhere('middlename', 'LIKE', "%{$search}%")
                        ->orWhere('documentnumber', 'LIKE', "%{$search}%")
                        ->orWhere('ruc', 'LIKE', "%{$search}%")
                        ->orWhere('razonSocial', 'LIKE', "%{$search}%")
                        ->orWhere('nombreComercial', 'LIKE', "%{$search}%");
                });
            }


            $postulantes = $query->get();



            return $postulantes;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al exportar: ' . $e->getMessage()
            ], 500);
        }
    }
}
