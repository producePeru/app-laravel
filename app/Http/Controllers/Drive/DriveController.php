<?php

namespace App\Http\Controllers\Drive;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Drive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;

class DriveController extends Controller
{
    public function index($userId,$dni)
    {
        $roleUser = DB::table('role_user')
                ->where('user_id', $userId)
                ->where('dniuser', $dni)
                ->first();

        if (!$roleUser) {
            return response()->json(['error' => 'El rol y/o DNI no existen.'], 404);
        }

        if($roleUser->role_id === 3) {
            $files = Drive::with('profile')->paginate(20);
            return response()->json(['data' => $files], 200);
        }

        if($roleUser->role_id === 4) {
            $files = Drive::with('profile')->where('user_id', $userId)->paginate(20);
            return response()->json(['data' => $files], 200);
        }

    }

    public function store(Request $request)
    {
        try {
            $uploadedFiles = $request->file('files');

            if ($uploadedFiles && is_array($uploadedFiles)) {
                foreach ($uploadedFiles as $uploadedFile) {
                    $path = $uploadedFile->store('drive');

                    Drive::create([
                        'user_id' => $request->user_id,
                        'profile_id' => $request->profile_id,
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

    public function downloadFile($path)
    {
        $ruta = storage_path("app/{$path}");

        if (file_exists($ruta)) {
            return response()->download($ruta);
        } else {
            return response()->json(['message' => 'Archivo no encontrado', 'status' => 404]);
        }
    }

    public function deleteFile($id)
    {
        DB::table('drives')->where('id', $id)->update(['deleted_at' => now()]);
        return response()->json(['message' => 'Archivo eliminado correctamente'], 200);
    }
}
