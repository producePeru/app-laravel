<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\CyberwowParticipant;
use App\Models\Fair;
use Illuminate\Http\Request;
use Carbon\Carbon;

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
                'actividadComercial:id,name',
                'rubro:id,name',
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

            $rows = $postulantes->map(function ($item, $index) {
                return [
                    $index + 1,
                    $item->ruc,
                    $item->razonSocial,
                    $item->nombreComercial,
                    $item->region->name,
                    $item->provincia->name,
                    $item->distrito->name,
                    $item->direccion,
                    // 'web',
                    // 'facebook',
                    // 'instagram',
                    $item->sectorEconomico->name,
                    $item->rubro->name,
                    $item->actividadComercial->name,
                    $item->descripcion,

                    $item->tipoDocumento->name,
                    $item->documentnumber,
                    $item
                ];
            });


            return $rows;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al exportar: ' . $e->getMessage()
            ], 500);
        }
    }
}
