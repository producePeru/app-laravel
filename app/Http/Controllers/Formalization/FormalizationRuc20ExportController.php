<?php

namespace App\Http\Controllers\Formalization;

use App\Http\Controllers\Controller;
use App\Exports\FormalizationRuc20ExportService;
use App\Jobs\GenerateFormalizationRuc20Export;
use App\Models\Formalization20;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class FormalizationRuc20ExportController extends Controller
{
    protected int $syncThreshold = 5000;

    public function exportRuc20(Request $request, FormalizationRuc20ExportService $service)
    {
        $this->authorizePermission();

        $filters = [
            'year' => $request->input('year'),
        ];

        $user = Auth::user();

        $query = Formalization20::query()->with([
            'city:id,name',
            'province:id,name',
            'district:id,name',
            'modality:id,name',
            'comercialactivity:id,name',
            'regime:id,name',
            'notary:id,name',
            'economicsector:id,name',
            'typecapital:id,name',
            'mype:id,name,ruc',
            'people:id,documentnumber,birthday,lastname,middlename,name,gender_id,country_id,typedocument_id,sick,hasSoon,phone,email',
            'people.gender:id,name',
            'people.typedocument:id,avr',
            'sede',
            'user:id,name,lastname,middlename',
            'userupdater:id,name,lastname,middlename'
        ]);

        // Control de acceso
        if ($user->rol == 1) {
            // acceso total
        } elseif ($user->rol == 2) {
            $query->where('user_id', $user->id);
        } else {
            return response()->json(['message' => 'No tienes permiso para acceder a esta sección'], 403);
        }

        // Filtros
        if (!empty($filters['year'])) {
            $query->whereYear('created_at', $filters['year']);
        }

        $total = (clone $query)->getQuery()->getCountForPagination();

        // Nombre del archivo
        $timestamp = now()->format('Ymd_His');
        $yearpart = $filters['year'] ?? 'all';
        $filename = "formalizaciones_ruc20_{$yearpart}_{$timestamp}.xlsx";

        if ($total <= $this->syncThreshold) {
            $pathFile = public_path('exports/' . $filename);
            $service->generateFromQuery($query, $pathFile, 'xlsx');

            return response()->download($pathFile, $filename)->deleteFileAfterSend(false);
        }

        // Encolar exportación
        GenerateFormalizationRuc20Export::dispatch(
            $filters,
            $filename,
            $user->id,
            'xlsx',
            $request->input('email')
        );

        return response()->json([
            'message' => 'Export encolado. El archivo se guardará en public/exports cuando termine.',
            'filename' => $filename,
            'download_url' => url("exports/{$filename}"),
            'total_rows' => $total,
            'status' => 200
        ]);
    }

    protected function authorizePermission()
    {
        // lógica real de permisos
    }
}
