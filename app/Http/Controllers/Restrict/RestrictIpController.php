<?php

namespace App\Http\Controllers\Restrict;

use App\Http\Controllers\Controller;
use App\Models\RestrictIp;
use Illuminate\Http\Request;

class RestrictIpController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validar los datos de entrada
        $request->validate([
            'ip' => 'required',
            'access' => 'required',
        ]);

        // Crear el registro
        $restrictIp = RestrictIp::create([
            'ip' => $request->ip,
            'access' => $request->access,
        ]);

        return response()->json([
            'message' => 'IP agregada correctamente.',
            'data' => $restrictIp
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $restrictIp = RestrictIp::find($id);

        if (!$restrictIp) {
            return response()->json(['message' => 'IP no encontrada'], 404);
        }

        $restrictIp->delete();

        return response()->json(['message' => 'IP eliminada correctamente.'], 200);
    }
}
