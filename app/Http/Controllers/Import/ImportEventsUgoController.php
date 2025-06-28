<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Imports\EventosUgoImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportEventsUgoController extends Controller
{
    public function importEventsUgo(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240' // hasta 10MB
        ]);

        try {
            Excel::import(new EventosUgoImport, $request->file('file'));

            return response()->json([
                'message' => 'Importación completada correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error durante la importación.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
