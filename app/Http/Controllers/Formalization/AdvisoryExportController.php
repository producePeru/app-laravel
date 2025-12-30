<?php

namespace App\Http\Controllers\Formalization;

use App\Exports\AdvisoriesExportService;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateAdvisoriesExport;
use App\Models\Advisory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;

class AdvisoryExportController extends Controller
{

    // Umbral para generación síncrona (ajusta según tu infra)
    protected int $syncThreshold = 5000;

    public function exportAsesories(Request $request, AdvisoriesExportService $service)
    {

        $this->authorizePermission(); // implementa getPermission si lo necesitas

        $filters = [
            'year' => $request->input('year'),
            // 'asesor' => $request->input('asesor'),
            // agrega más filtros si requiere
        ];

        $user = Auth::user();

        // Construir query base (idéntico a tu scope, pero sin ejecución todavía)
        $query = Advisory::query()->with([
            'city:id,name',
            'comercialactivity:id,name',
            'component:id,name',
            'district:id,name',
            'economicsector:id,name',
            'modality:id,name',
            'people:id,documentnumber,birthday,lastname,middlename,name,gender_id,country_id,typedocument_id,sick,hasSoon,phone,email',
            'people.gender:id,name',
            'people.pais:id,name',
            'people.typedocument:id,avr',
            'province:id,name',
            'sede',
            'theme:id,name',
            'user:id,name,lastname,middlename'
        ]);

        if ($user->rol == 1) {
            // full access
        } else if ($user->rol == 2) {
            $query->where('user_id', $user->id);
        } else {
            return response()->json(['message' => 'No tienes permiso para acceder a esta sección'], 403);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('created_at', $filters['year']);
        }

        // contar resultados rápido (sin cargar relaciones)
        $total = (clone $query)->getQuery()->getCountForPagination();

        // generar nombre de archivo
        $timestamp = now()->format('Ymd_His');
        $yearpart = $filters['year'] ?? 'all';
        $filename = "asesorias_{$yearpart}_{$timestamp}.xlsx";

        // Si el dataset es pequeño, generamos en la petición (síncrono)
        if ($total <= $this->syncThreshold) {
            $pathFile = public_path('exports/' . $filename);
            $service->generateFromQuery($query, $pathFile, 'xlsx');

            // Devolver descarga inmediata
            return response()->download($pathFile, $filename)->deleteFileAfterSend(false);
        }

        // Dataset grande -> despachar job a la cola
        GenerateAdvisoriesExport::dispatch(
            $filters,
            $filename,
            $user->id,
            'xlsx',
            $request->input('email') // ← nuevo parámetro opcional
        );

        return response()->json([
            'message' => 'Export encolado. El archivo se guardará en public/exports cuando termine.',
            'filename' => $filename,
            'download_url' => url("exports/{$filename}"), // disponible cuando job termine
            'total_rows' => $total,
            'status' => 200
        ]);
    }

    // endpoint para chequear si ya existe el archivo (opcional)
    public function statusExport(Request $request, $filename)
    {
        $path = public_path("exports/{$filename}");
        if (file_exists($path)) {
            return response()->json([
                'ready' => true,
                'download_url' => url("exports/{$filename}"),
                'size' => filesize($path)
            ]);
        }
        return response()->json(['ready' => false], 200);
    }

    protected function authorizePermission()
    {
        // Reemplaza con tu lógica real de permisos
        // throw 403 si no tiene permiso
    }
}
