<?php

namespace App\Http\Controllers\Drive;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Drive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\DriveFile;

class DriveController extends Controller
{

    private function getUserRole()
    {
        $user_id = Auth::user()->id;

        $roleUser = DB::table('role_user')
        ->where('user_id', $user_id)
        ->first();

        if ($user_id != $roleUser->user_id) {
            return response()->json(['message' => 'Este rol no es correcto', 'status' => 404]);
        }

        return [
            "role_id" => $roleUser->role_id,
            'user_id' => $user_id
        ];
    }


    public function index()
    {
        $user_id = Auth::user()->id;

        $roleUser = DB::table('role_user')
        ->where('user_id', $user_id)
        ->first();

        if ($user_id != $roleUser->user_id) {
            return response()->json(['message' => 'Este rol no es correcto', 'status' => 404]);
        }

        // 3.admin
        if ($roleUser->role_id === 3 || $user_id === 1) {
            $files = Drive::with('profile')->paginate(20);
            return response()->json(['data' => $files], 200);
        }
        // 4.user
        if ($roleUser->role_id === 4) {
            $files = Drive::with('profile')->where('user_id', $user_id)->paginate(20);
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
                        'file_id' => $request->file_id
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

        $user_id = Auth::user()->id;

        $roleUser = DB::table('role_user')
        ->where('user_id', $user_id)
        ->first();

        if ($user_id != $roleUser->user_id) {
            return response()->json(['message' => 'Este rol no es correcto', 'status' => 404]);
        }

        // 4.user
        if ($roleUser->role_id === 4) {
            return response()->json(['message' => 'La eliminación de este archivo no es posible. Por favor, busca orientación de tu administrador.', 'status' => 500]);
        }

        DB::table('drives')->where('id', $id)->update(['deleted_at' => now()]);
        return response()->json(['message' => 'Archivo eliminado correctamente'], 200);
    }

    public function createFile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'color' => 'required|string|max:15',
        ]);

        $user_id = Auth::user()->id;

        $roleUser = DB::table('role_user')
        ->where('user_id', $user_id)
        ->first();

        if ($user_id != $roleUser->user_id) {
            return response()->json(['message' => 'Este rol no es correcto', 'status' => 404]);
        }

        if ($roleUser->role_id === 3 || $user_id === 1) {
            $driveFile = new DriveFile();
            $driveFile->name = $request->input('name');
            $driveFile->color = $request->input('color');
            $driveFile->save();
            return response()->json(['message' => 'Carpeta creada con éxito', 'status' => 200]);
        }

        return response()->json(['message' => 'Sin acceso', 'status' => 500]);
    }

    public function updateFile(Request $request, $id)
    {
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];

        if ($role_id === 3 || $user_id === 1) {
            $file = DriveFile::find($id);

            if (!$file) {
                return response()->json(['message' => 'No encontrado', 'status' => 404]);
            }

            $file->update($request->all());

            return response()->json(['message' => 'Se actualizó la carpeta', 'status' => 200]);
        }

        return response()->json(['message' => 'Sin acceso', 'status' => 500]);
    }

    public function allFiles()
    {
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];

        if ($role_id === 3 || $user_id === 1) {
            $files = DriveFile::get();
            return response()->json(['data' => $files, 'status' => 200]);
        }

        return response()->json(['message' => 'Sin acceso', 'status' => 500]);
    }

    public function dataByIdFile($fileId)
    {
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];

        if ($role_id === 3 || $user_id === 1) {
            $data = Drive::where('file_id', $fileId)->with('profile')->paginate(20);
            return response()->json(['data' => $data, 'status' => 200]);
        }

        return response()->json(['message' => 'Sin acceso', 'status' => 500]);
    }
}


