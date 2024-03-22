<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Created;
use App\Models\People;
use App\Models\Permission;
use App\Models\PersonPhoto;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

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



    public function upProfilePhotoImage(Request $request, $id, $dni)
    {
        $person = People::where('number_document', $dni)->where('id', $id)->get();

        if (!$person) {
            return response()->json(['message' => 'La persona no existe', 'status' => 404]);
        }

        $photo = PersonPhoto::where('dni', $dni)->first();

        if ($photo) {
            $imageBase64 = $request->input('image');
            $imageData = explode(',', $imageBase64)[1];
            $decodedImage = base64_decode($imageData);
            $filename = 'image_' . time() . '.jpg';
            $path = 'public/photos/' . $filename;
    
            Storage::disk('local')->put($path, $decodedImage);
    
            $photo->nombre = $filename;
            $photo->ruta = $path;
            $photo->id_person = $id;
            $photo->dni = $dni;
            $photo->save();
    
            return response()->json(['message' => 'Imagen actualizada correctamente', 'status' => 200]);
        } else {
            $imageBase64 = $request->input('image');
            $imageData = explode(',', $imageBase64)[1];
            $decodedImage = base64_decode($imageData);
            $filename = 'image_' . time() . '.jpg';
            $path = 'public/photos/' . $filename;
    
            Storage::disk('local')->put($path, $decodedImage);
    
            PersonPhoto::create([
                'nombre' => $filename,
                'ruta' => $path,
                'id_person' => $id,
                'dni' => $dni
            ]);
    
            return response()->json(['message' => 'Imagen guardada correctamente', 'status' => 200]);
        }
    }

    public function showProfilePhotoImage($id, $dni) 
    {
        $person = People::where('number_document', $dni)->where('id', $id)->get();

        if (!$person) {
            return response()->json(['message' => 'La persona no existe', 'status' => 404]);
        }

        $photo = PersonPhoto::where('dni', $dni)->first();

        if (!$photo) {
            return response()->json(['message' => 'La foto no existe', 'status' => 404]);
        }

        $path = storage_path('app/' . $photo->ruta);
        return response()->file($path);
    }

    public function personalDataUser($dni)
    {
        $query = People::where('number_document', $dni)->first();

       if($query) {
        $data = [
            'id' => $query->id,
            'last_name' => $query->last_name,
            'middle_name' => $query->middle_name,
            'name' => $query->name,
            'email' => $query->email,
            'number_document' => $query->number_document
        ];

        return response()->json(['data' => $data, 'status' => 200]);
       }
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
