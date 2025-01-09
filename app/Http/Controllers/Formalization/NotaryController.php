<?php

namespace App\Http\Controllers\Formalization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notary;

class NotaryController extends Controller
{
    public function indexNotary()
    {
        try {
            $formalization = Notary::withNotariesAndRelations();
            return response()->json($formalization, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar las notarías', 'status' => 500]);
        }
    }

    public function storeNotary(Request $request)
    {
        try {
            $data = $request->all();
            Notary::create($data);

            return response()->json(['message' => 'Notaría registrada correctamente', 'status' => 200]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al registrar la notaría', 'status' => $e]);
        }
    }

    public function deleteNotary($id)
    {
        try {
            $notary = Notary::findOrFail($id);
            $notary->delete();

            return response()->json(['message' => 'Notaría eliminada correctamente', 'status' => 200]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar la notaría', 'status' => 500]);
        }
    }

    public function updateNotary(Request $request, $id)
    {
        try {
            $notary = Notary::findOrFail($id);
            $notary->update($request->all());

            return response()->json(['message' => 'Notaría actualizada correctamente', 'status' => 200]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar la notaría', 'status' => 500]);
        }
    }

    // Filtros...
    public function indexNotaryById(Request $request)
    {
        try {
            $name = $request->query('name'); // Obtener el parámetro 'name'
            $cityId = $request->query('city_id'); // Obtener el parámetro 'city_id'

            // Construir la consulta base con relaciones
            $query = Notary::with(['city', 'province', 'district', 'user.profile']);

            // Si se envía el parámetro 'name', aplicar los filtros
            if ($name) {
                $query->where('name', 'LIKE', '%' . $name . '%') // Filtrar por nombre del notario
                    ->orWhereHas('city', function ($q) use ($name) {
                        $q->where('name', 'LIKE', '%' . $name . '%'); // Filtrar por nombre de ciudad
                    })
                    ->orWhereHas('province', function ($q) use ($name) {
                        $q->where('name', 'LIKE', '%' . $name . '%'); // Filtrar por nombre de provincia
                    })
                    ->orWhereHas('district', function ($q) use ($name) {
                        $q->where('name', 'LIKE', '%' . $name . '%'); // Filtrar por nombre de distrito
                    });
            }

            // Si se envía el parámetro 'city_id', aplicar el filtro
            if ($cityId) {
                $query->where('city_id', $cityId); // Filtrar por ID de ciudad
            }

            // Obtener los resultados con paginación
            $notaries = $query->paginate(200);

            return response()->json($notaries, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar las notarías', 'status' => 500]);
        }
    }
}
