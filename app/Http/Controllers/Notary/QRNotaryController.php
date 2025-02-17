<?php

namespace App\Http\Controllers\Notary;

use App\Http\Controllers\Controller;
use App\Models\QRNotaries;
use Illuminate\Http\Request;

class QRNotaryController extends Controller
{

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            QRNotaries::create($data);

            return response()->json(['message' => 'Datos enviados', 'status' => 200]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al procesar la solicitud',
                'error' => $th->getMessage(),
                'status' => 500
            ], 500);
        }
    }
}
