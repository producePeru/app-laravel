<?php

namespace App\Http\Controllers\Formalization;

use App\Http\Controllers\Controller;
use App\Exports\FormalizationRuc10ExportService;
use App\Jobs\GenerateFormalizationRuc10Export;
use App\Models\Formalization10;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormalizationRuc10ExportController extends Controller
{
    protected int $syncThreshold = 5000;

    public function exportRuc10(Request $request, FormalizationRuc10ExportService $service)
    {
        $this->authorizePermission();

        $filters = [
            'year' => $request->input('year'),
        ];

        $user = Auth::user();

        $query = Formalization10::query()->with([
            'city:id,name',
            'comercialactivity:id,name',
            'detailprocedure:id,name',
            'district:id,name',
            'economicsector:id,name',
            'modality:id,name',
            'people:id,documentnumber,birthday,lastname,middlename,name,gender_id,country_id,typedocument_id,sick,hasSoon,phone,email',
            'people.gender:id,name',
            'people.pais:id,name',
            'people.typedocument:id,avr',
            'province:id,name',
            'sede',
            'user:id,name,lastname,middlename'
        ]);

        if ($user->rol == 1) {
            // acceso total
        } else if ($user->rol == 2) {
            $query->where('user_id', $user->id);
        } else {
            return response()->json(['message' => 'No tienes permiso para acceder a esta sección'], 403);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('created_at', $filters['year']);
        }

        $total = (clone $query)->getQuery()->getCountForPagination();

        $timestamp = now()->format('Ymd_His');
        $yearpart = $filters['year'] ?? 'all';
        $filename = "formalizaciones_ruc10_{$yearpart}_{$timestamp}.xlsx";

        if ($total <= $this->syncThreshold) {
            $pathFile = public_path('exports/' . $filename);
            $service->generateFromQuery($query, $pathFile, 'xlsx');

            return response()->download($pathFile, $filename)->deleteFileAfterSend(false);
        }

        GenerateFormalizationRuc10Export::dispatch(
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
