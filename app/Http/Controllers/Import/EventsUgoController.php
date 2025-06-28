<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Imports\EventosUgo;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EventsUgoController extends Controller
{
    public function importEventsUgo(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240' // hasta 10MB
        ]);

        try {
            Excel::import(new EventosUgo, $request->file('file'));

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
