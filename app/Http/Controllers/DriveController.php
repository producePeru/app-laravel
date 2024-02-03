<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Drive;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

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
                        'name' => $user->document_number . '_' . $uploadedFile->getClientOriginalName(),
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

}
