<?php

namespace App\Http\Controllers\Pnte;

use App\Http\Controllers\Controller;
use App\Models\PpCapacitador;
use Illuminate\Http\Request;

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
}
