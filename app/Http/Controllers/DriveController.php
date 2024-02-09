<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Drive;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Response;

class DriveController extends Controller
{
    public function driveUpFiles(Request $request)
    {
        $idDecrypt = Crypt::decryptString($request->created_by);

        $user = User::where('id', $idDecrypt)->first();

        try {
            $uploadedFiles = $request->file('files');

            if ($uploadedFiles && is_array($uploadedFiles)) {
                foreach ($uploadedFiles as $uploadedFile) {
                    $path = $uploadedFile->store('drive');

                    Drive::create([
                        'created_by' => $user->id,
                        'name' => $uploadedFile->getClientOriginalName(),
                        'path' => $path,
                    ]);
                }

                return response()->json(['message' => 'Archivos subidos correctamente']);
            } else {
                
                return response()->json(['error' => 'No se proporcionaron archivos en la solicitud.'], 400);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al subir archivos: ' . $e->getMessage()], 500);
        }
    }

    public function showFilesAuthor($id)
    {
        try {
            $idUser = $id;
            $user = User::with('permission')->where('id', $idUser)->firstOrFail();
            $userData = User::with('drive')->where('id', $idUser)->first();
            $filesWithUsers = $userData->drive;

            return $this->formatFilesData($filesWithUsers, $userData);
        } catch (\Exception $e) {
            throw new \Exception('Error al procesar la solicitud');
        }
    }

    public function showFiles($id)
    {
        try {
            if ($id != 'admin') {
                $data = $this->getUserFiles($id);
            } else {
                $data = $this->getAllFilesWithUsers();
            }

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    private function getUserFiles($id)
    {
        try {
            $idUser = Crypt::decryptString($id);
            $user = User::with('permission')->where('id', $idUser)->firstOrFail();
            $userData = User::with('drive')->where('id', $idUser)->first();
            $filesWithUsers = $userData->drive;

            return $this->formatFilesData($filesWithUsers, $userData);
        } catch (\Exception $e) {
            throw new \Exception('Error al procesar la solicitud');
        }
    }

    private function getAllFilesWithUsers()
    {
        $filesWithUsers = Drive::with('user')->get();
        return $this->formatFilesData($filesWithUsers);
    }

    private function formatFilesData($filesWithUsers, $userData = null)
    {
        return $filesWithUsers->map(function ($file) use ($userData) {
    
            return [
                'idFile' => $file->id,
                'idUser' => $file->user->id,
                'filename' => $file->name,
                'path' => basename($file->path),
                'created_at' => $file->created_at,
                'status' => $file->status,
                'user' => $userData ? $userData->name . ' ' . $userData->last_name . ' ' . $userData->middle_name : $file->user->name . ' ' . $file->user->last_name . ' ' . $file->user->middle_name
            ];
        });
    }

    public function downloadFile($archivo)
    {
        $ruta = storage_path("app/drive/{$archivo}");

        if (file_exists($ruta)) {
            return response()->download($ruta);
        } else {
            abort(404, 'Archivo no encontrado');
        }
    }


    public function searchByNameFile($name, $idUser) {
        $idDecrypt = Crypt::decryptString($idUser);
        $user = User::where('id', $idDecrypt)->first();
       
        if($user && ($user->role === 100 || $user->role === 1)) {
            $data = Drive::where('name', 'like', '%' . $name . '%')->paginate(20)
            ->through(function($drive){
                return [
                    'idFile' => $drive->id,
                    'idUser' => $drive->user->id,
                    'filename' => $drive->name,
                    'path' => $drive->name,
                    'created_at' => $drive->created_at,
                    'status' => $drive->status,
                    'user' => $drive->user->name . ' ' . $drive->user->last_name . ' ' . $drive->user->middle_name
                ];
            });
        } else {
            $data = Drive::where('name', 'like', '%' . $name . '%')->where('created_by', $user->id)->paginate(20)
            ->through(function($drive){
                return [
                    'idFile' => $drive->id,
                    'idUser' => $drive->user->id,
                    'filename' => $drive->name,
                    'path' => $drive->name,
                    'created_at' => $drive->created_at,
                    'status' => $drive->status,
                    'user' => $drive->user->name . ' ' . $drive->user->last_name . ' ' . $drive->user->middle_name
                ];
            });
        }
    
        return response()->json(['data' => $data]);
    }




}
