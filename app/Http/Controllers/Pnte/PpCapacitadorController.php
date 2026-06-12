<?php

namespace App\Http\Controllers\Pnte;

use App\Http\Controllers\Controller;
use App\Models\Empresario;
use App\Models\PpCapacitador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class PpCapacitadorController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $request->input('pageSize', 10);

        $data = PpCapacitador::query()

            ->when(
                $request->filled('name'),
                function ($q) use ($request) {

                    $name = trim($request->name);

                    $q->where(function ($query) use ($name) {

                        $query->where(
                            'nombres_apellidos',
                            'LIKE',
                            "%{$name}%"
                        )
                            ->orWhere(
                                'dni',
                                'LIKE',
                                "%{$name}%"
                            )
                            ->orWhere(
                                'correo',
                                'LIKE',
                                "%{$name}%"
                            );
                    });
                }
            )

            ->orderByDesc('id')
            ->paginate($pageSize);

        return response()->json([
            'status' => 200,
            'message' => 'Capacitadores obtenidos correctamente.',
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombres_apellidos' => 'required|string|max:255',
            'dni' => 'nullable|string|max:20|unique:pp_capacitadores,dni',
            'correo' => 'nullable|email|max:255'
        ]);

        $capacitador = PpCapacitador::create([
            'nombres_apellidos' => trim($request->nombres_apellidos),
            'dni' => trim($request->dni),
            'correo' => trim($request->correo)
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Capacitador registrado correctamente.',
            'data' => $capacitador
        ]);
    }

    public function update(Request $request, $id)
    {
        $capacitador = PpCapacitador::findOrFail($id);

        $request->validate([
            'nombres_apellidos' => 'required|string|max:255',
            'dni' => 'nullable|string|max:20|unique:pp_capacitadores,dni,' . $id,
            'correo' => 'nullable|email|max:255'
        ]);

        $capacitador->update([
            'nombres_apellidos' => trim($request->nombres_apellidos),
            'dni' => trim($request->dni),
            'correo' => trim($request->correo)
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Capacitador actualizado correctamente.',
            'data' => $capacitador
        ]);
    }



    public function isRegisterPlataforma($ruc, $dni)
    {
        try {
            // 🌟 Modificado para traer únicamente id, ruc, numero_dni y celular
            $empresario = \App\Models\Empresario::select([
                'id',
                'ruc',
                'numero_dni',
                'celular'
            ])
                ->where('ruc', $ruc)
                ->where('numero_dni', $dni)
                ->first();

            if (!$empresario) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Empresario no encontrado con el RUC y DNI proporcionados.',
                ]); // Nota: Si deseas consistencia de HTTP status codes, podrías agregar , 404 al final
            }

            return response()->json([
                'status' => 200,
                'data'   => $empresario
            ], 200);
        } catch (\Exception $e) { // Agregada la barra invertida '\' por si no tienes el 'use Exception;' arriba
            Log::error("Error en isRegisterPlataforma: " . $e->getMessage(), [
                'ruc'   => $ruc,
                'dni'   => $dni,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Ocurrió un error interno en el servidor al procesar la solicitud.',
            ], 500);
        }
    }
}
