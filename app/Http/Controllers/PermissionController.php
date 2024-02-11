<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Permission;
use App\Models\View;
use App\Models\ViewUser;
use App\Http\Requests\StorePermissionRequest;
use Illuminate\Support\Facades\Crypt;

use Illuminate\Http\Request;

class PermissionController extends Controller
{   
    public function asignedViews(Request $request)
    {

        $request->validate([
            'id_user' => 'required|exists:users,id',
            'views' => 'required|array',
            'views.*' => 'integer',
        ]);
        
        foreach ($request->views as $view) {
            $existingEntry = ViewUser::where('id_user', $request->id_user)->where('id_view', $view)->first();
    
            if ($existingEntry) {
                $existingEntry->update(['id_view' => null]);
            } else {
                ViewUser::create([
                    'id_user' => $request->id_user,
                    'id_view' => $view,
                ]);
            }
        }
    
        return response()->json(['message' => 'Datos guardados correctamente']);
           
    }




    public function viewsByUsers($id)
    {

        $user = User::find($id);

        if($user->role === 100)
        {
            $views = View::pluck('name')->toArray();
            return response()->json(['data' => $views]);
        } 


        if($user->role === 10)                                              //Nataly Karina = 10
        {
            $views = ['drive', 'drive-subir-archivo', 'drive-mis-archivos', 'usuarios', 'usuarios-nuevo', 'usuarios-actualizar', 'usuarios-lista'];
            return response()->json(['data' => $views]);
        } 
        if($user->role === 2)                                              //Nataly Karina = users 
        {
            $views = ['drive', 'drive-subir-archivo', 'drive-mis-archivos'];
            return response()->json(['data' => $views]);
        } 


        $views = [];
        return response()->json(['data' => $views]);


        return response()->json(['message' => 'No eres administrador']);

    }




    public function assignedViews(Request $request)
    {
        $created_by = Crypt::decryptString($request->created_by);
        

        $userView = Permission::updateOrCreate(
            ['id_user' => $request->id_user], 
            [
                'created_by' => $created_by,
                'views' => $request->views,
                'exclusions' => $request->exclusions
            ] 
        );
        return response()->json(['message' => 'Asignado correctamente']);
    }










    public function showPermissions($dni)
    {
        $user = User::where('document_number', $dni)->first()->id;

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $permission = Permission::where('id_user', $user)->first();

        $views = json_decode($permission->views, true);
        $keys = array_keys($views);
        $values = array_values($views);

        if(count($values) == 0) {
            return response()->json(['data' => []]);
        }

        if($values[0] === '***') {
            $result = [
                'registrar-usuario',
                'actualizar-persona',
                'patrimonios',
                'asesorias',
                'solicitantes',
                'notarias',
                'supervisores',
                'asesores',
                'convenios',
                'nuevo-convenio',
                'compromisos',
                'lista-convenios',
                'convenio-detalles',
                'rutadigital',
                'reportes',
                'mype',
                'crear-cuestionario',
                'cuestionarios',
                'talleres',
                'test-entrada',
                'editar-test-entrada',
                'test-salida',
                'editar-test-salida',
                'taller-descripcion',
                'calendario',
                'expositores',
                'usuarios',
                'nuevo-usuario',
                'lista',
                'drive',
                'subir-archivo',
                'mis-archivos'
            ];
        } else {
            $convertedKeys = array_map(function ($key) {
                return strtolower(str_replace(' ', '-', $key));
            }, $keys);
    
            $convertedValues = array_map(function ($value) {
                return array_map(function ($item) {
                    return strtolower(str_replace(' ', '-', $item));
                }, $value);
            }, $values);
         
            $result = array_merge($convertedKeys, ...$convertedValues);
        }
  
        
        return response()->json(['data' => $result]);
    }
}
