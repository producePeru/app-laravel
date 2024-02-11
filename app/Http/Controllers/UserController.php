<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Created;
use App\Models\Permission;
use Illuminate\Support\Facades\Crypt;

class UserController extends Controller
{
    public function listAllUsers()                                                                         
    {
        $data = User::where('status', 1)->paginate(20);
        $data->getCollection()->transform(function ($user) {
            $user->makeHidden([
                'password', 'country_code', 'is_disabled', 'updated_at', 'created_at', 'email_verified_at', 'status'
            ]);
            return $user;
        });
        return $data;
    }

    public function dataUserByDNI($dni)
    {
        $data = User::where('document_number', $dni)->first();
        if ($data) {
            $data->makeHidden([
                'password', 'is_disabled', 'updated_at', 'created_at', 'email_verified_at', 'status'
            ]);
        }
        return response()->json(['data' => $data]);
    }

    public function updateUserNoPassword($id, Request $request)
    {
        $user = User::find($id);

        if ($user) {
            $user->update($request->except('password'));
            return response()->json(['message' => 'Los datos fueron actualizados correctamente']);
        }

        return response()->json(['message' => 'User not found'], 404);
    }




    public function deleteAnUser($idUser, Request $request)
    {
        $user = User::find($idUser);

        if ($user && $user->created_by === $request->id) {
            $user->status = 0;
            $user->save(); 
            return response()->json(['message' => 'Usuario eliminado']);
        }
        return response()->json(['message' => 'No creaste este usuario, no puedes eliminarlo'], 404);
    }













    

    // public function deleteUserStatus($idAdmin, $dni)
    // {
    //     try {
    //         $id = Crypt::decryptString($idAdmin);

    //         $isAdmin = User::with('permission')->where('id', $id)->firstOrFail()->role;

    //         if ($isAdmin == 100 || $isAdmin == 1) {
    //             $user = User::where('document_number', $dni)->first();

    //             if ($user) {
    //                 $user->update(['status' => 0]);
    //                 return response()->json(['message' => 'Usuario eliminado'], 200);
    //             } else {
    //                 return response()->json(['message' => 'El usuario no existe'], 404);
    //             }
    //         }

    //         return response()->json(['message' => 'No estás habilitado para esta acción'], 403);
    //     } catch (\Exception $e) {
    //         return response()->json(['message' => 'Error al procesar la solicitud'], 500);
    //     }
    // }

}
