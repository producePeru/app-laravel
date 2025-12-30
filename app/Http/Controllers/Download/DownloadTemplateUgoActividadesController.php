<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;

class DownloadTemplateUgoActividadesController extends Controller
{
    public function downloadUgoActividades()
    {
        $path = 'plantillas/template_import_ugo_actividades.xlsx';

        if (!Storage::exists($path)) {
            return response()->json([
                'message' => 'La plantilla no fue encontrada en el servidor.'
            ], Response::HTTP_NOT_FOUND);
        }

        return Storage::download($path, 'Plantilla_Import_Ugo_Actividades.xlsx');
    }
}
