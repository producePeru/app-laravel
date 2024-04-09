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

    public function indexNotaryById($cityId) {
        try {
            $formalization = Notary::withNotariesById($cityId);
            return response()->json($formalization, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar las notarías', 'status' => 500]);
        }
    }
}
