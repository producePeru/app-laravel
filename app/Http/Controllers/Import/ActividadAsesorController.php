<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Imports\ActividadAsesorImport;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceList;
use Maatwebsite\Excel\Facades\Excel;

class ActividadAsesorController extends Controller
{
    public function importExcel(Request $request, $slug)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,xls',
        ]);

        // Buscar lista por slug
        $attendance = Attendance::where('slug', $slug)->first();
        if (!$attendance) {
            return response()->json(['message' => 'No se encontró la lista de asistencia.'], 404);
        }

        try {
            $import = new ActividadAsesorImport($attendance->id, $slug);
            Excel::import($import, $request->file('file'));

            return response()->json([
                'message' => "Importación completada ({$import->getTotalRows()} registros)."
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al importar: ' . $e->getMessage(),
            ], 500);
        }
    }
}
