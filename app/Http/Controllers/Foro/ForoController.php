<?php

namespace App\Http\Controllers\Foro;

use App\Http\Controllers\Controller;
use App\Models\UgsePostulante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ForoController extends Controller
{
    public function isRegistered(Request $request)
    {
        try {
            // Validar que el payload tenga los campos necesarios
            $validated = $request->validate([
                'column' => 'required|string',
                'number' => 'required'
            ]);

            $column = $validated['column'];
            $number = $validated['number'];

            // Verificar que la columna exista en la tabla antes de usarla
            if (!Schema::hasColumn('ugse_postulantes', $column)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La columna especificada no es válida.'
                ], 400);
            }

            // Buscar dinámicamente en el modelo UgsePostulante
            $postulante = UgsePostulante::where($column, $number)->first();

            if ($postulante) {
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $postulante
                ]);
            }

            return response()->json([
                'success' => true,
                'status' => 404,
                'message' => 'No se encontró ningún postulante con esos datos.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en isRegistered: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar la solicitud.',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }
}
