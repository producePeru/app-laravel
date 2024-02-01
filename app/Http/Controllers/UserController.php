<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Facades\Crypt;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('status', 1)->paginate(20);

        $users->each(function ($user) {
            
            switch ($user->role) {
                case 100:
                    $user->role = "super";
                    break;
                case 1:
                    $user->role = "admin";
                    break;
                case 2:
                    $user->role = "usuario";
                    break;
                default:
                    $user->role = "invitado";
                    break;
            }

            $user->makeHidden([
                'nick_name', 'password', 'country_code', 'is_disabled',
                'created_by', 'update_by', 'updated_at', 'created_at',
                'email_verified_at', 'status', 'id'
            ]);
        });

        return $users; 
    }

    public function showUserWithViews($dni)
    {
        $user = User::with('permission')->where('document_number', $dni)->first();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $data = [
            'document_type' => $user->document_type,
            'document_number' => $user->document_number,
            'last_name' => $user->last_name,
            'middle_name' => $user->middle_name,
            'name' => $user->name,
            'country_code' => $user->country_code,
            'email' => $user->email,
            'office_code' => $user->office_code,
            'sede_code' => $user->sede_code,
            'role' => $user->role,
            'gender' => $user->gender,
        
            'permission' => json_decode($user->permission->views)
        ];

        return response()->json(['data' => $data]);
    }

    public function deleteUserStatus($idAdmin, $dni)
    {
        try {
            $id = Crypt::decryptString($idAdmin);

            $isAdmin = User::with('permission')->where('id', $id)->firstOrFail()->role;

            if ($isAdmin == 100 || $isAdmin == 1) {
                $user = User::where('document_number', $dni)->first();

                if ($user) {
                    $user->update(['status' => 0]);
                    return response()->json(['message' => 'Usuario eliminado'], 200);
                } else {
                    return response()->json(['message' => 'El usuario no existe'], 404);
                }
            }

            return response()->json(['message' => 'No estás habilitado para esta acción'], 403);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al procesar la solicitud'], 500);
        }
    }

}
