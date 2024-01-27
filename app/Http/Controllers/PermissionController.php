<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Permission;
use App\Http\Requests\StorePermissionRequest;
use Illuminate\Support\Facades\Crypt;

use Illuminate\Http\Request;

class PermissionController extends Controller
{   
    public function show($idUser)
    {
        $id = Crypt::decryptString($idUser);

        $permissions = Permission::find($id);

        $data = [
            'views' => json_decode($permissions->views),
            'exclusions' => json_decode($permissions->exclusions)
        ];

        try {
            return response()->json(['data' => $data], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Sin permisos'], 404);
        } 
    }

    public function store(StorePermissionRequest $request)
    {
        $user = User::where('_id', $request['created'])->first();
        $userId = User::where('_id', $request['_id'])->first();

        if (!$user || !$userId) {
            return response()->json(['error' =>'Usuario no encontrado'], 404);
        }

        $views = json_encode($request->views);
        $exclusions = json_encode($request->exclusions);

        Permission::updateOrInsert(
            ['_id' => $request->_id],
            ['created' => $user->id, 'views' => $views, 'exclusions' => $exclusions]
        );

        return response()->json(['message' =>'Accesos actualizados'], 200);
    }
}
