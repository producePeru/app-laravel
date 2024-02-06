<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Permission;
use App\Http\Requests\StorePermissionRequest;
use Illuminate\Support\Facades\Crypt;

use Illuminate\Http\Request;

class PermissionController extends Controller
{   
    public function asignedViews(Request $request)
    {
        try {
            $rol = Crypt::decryptString($request->created_by);
            $user = User::where('id', $rol)->first();

            if ($user && $user->role == 100) {
                
                $views = json_encode($request->views);
                $exclusions = json_encode($request->exclusions); 

                
                $permissionData = [
                    'created_by' => $user->id,
                    'views' => $views,
                    'exclusions' => $exclusions,
                ];

                Permission::updateOrInsert(
                    ['id_user' => $request->id_user],
                    $permissionData
                );

                return response()->json(['message' => "Los datos han sido registrados"]);

            } else {
                return "No tienes permisos";
            }
        } catch (\Exception $e) {

            return "Error al procesar la solicitud: " . $e->getMessage();
            
        }
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
