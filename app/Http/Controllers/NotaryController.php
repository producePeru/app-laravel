<?php

namespace App\Http\Controllers;
use App\Models\Notary;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

use Illuminate\Http\Request;

class NotaryController extends Controller
{
    public function index()
    {
        $notary = Notary::with([
            'departament' => function ($query) { $query->select('idDepartamento', 'descripcion'); },
            'province' => function ($query) { $query->select('idProvincia', 'descripcion'); },
            'district' => function ($query) { $query->select('idDistrito', 'descripcion'); }
        ])->where('status', 1)->paginate(20);
        return $notary;
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $data['created_by'] = Crypt::decryptString($request->created_by);
    
            if (!$data['created_by']) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }
    
            Notary::create($data);
    
            return response()->json(['message' => 'Creado correctamente'], 200);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error al crear. Por favor, inténtalo de nuevo.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al crear. ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $notary = Notary::find($id);
        try {
            return response()->json(['data' => $notary], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'No encontrado'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $notary = Notary::find($id);

        if (!$notary) {
            return response()->json(['message' => 'Notaria no encontrada'], 404);
        }

        $requestData = $request->except('created_by');

        $notary->update($requestData);

        return response()->json(['message' => 'Se actualizó correctamente']);
    }

    public function deleteNotary($id) {
        try {

            $notary = Notary::find($id);

            if (!$notary) {
                return response()->json(['error' => 'No encontrado'], 404);
            }

            if ($notary->status == 1) {
                $notary->update(['status' => 0]);
                $data = ['success' => true, 'message' => 'Registro eliminado correctamente'];
            } else {
                $data = ['success' => false, 'message' => 'No se puede eliminar este registro'];
            }

            return response()->json($data);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }
}
