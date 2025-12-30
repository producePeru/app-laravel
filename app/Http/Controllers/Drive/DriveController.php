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
use App\Models\DriveUser;


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
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];

        // 3.admin
        if ($role_id === 3 || $user_id === 1) {
            $files = Drive::with('profile')->orderBy('created_at', 'desc')->paginate(20);
            return response()->json(['data' => $files], 200);
        }
        // 4.user
        if ($role_id === 4) {
            $allFiles = Drive::with('profile')
                ->where('user_id', $user_id)
                ->orWhereHas('driveUsers', function ($query) use ($user_id) {
                    $query->whereJsonContains('user_ids', $user_id);
                })
                ->orderByDesc('created_at')
                ->paginate(20);

            return response()->json(['data' => $allFiles], 200);
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

    public function visibleByAll($id)
    {
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];

        if ($role_id === 3 || $user_id === 1) {
            $file = Drive::find($id);
            if (!$file) {
                return response()->json(['message' => 'No encontrado', 'status' => 404]);
            }
            $file->is_visible = !$file->is_visible;
            $file->save();
            return response()->json(['message' => 'Ahora este archivo será visible para todos', 'status' => 200]);
        }
        return response()->json(['message' => 'Unauthorized access', 'status' => 403]);
    }

    // LISTA DE LOS USUARIOS DE DRIVES
    public function usersOnlyDrivers()
    {
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];

        if ($role_id === 3 || $user_id === 1) {
            $users = DB::table('role_user')
                ->whereIn('role_id', [3, 4])
                ->join('profiles', 'role_user.user_id', '=', 'profiles.user_id')
                ->get()
                ->map(function ($user) {
                    return [
                        'label' => $user->name . ' ' . $user->lastname . ' ' . $user->middlename,
                        'value' => $user->user_id
                    ];
                });

            return $users;
        }

        return response()->json(['message' => 'Unauthorized access', 'status' => 403]);
    }

    public function storeOrUpdateDriveUsers(Request $request)
    {
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];
        $drive_id = $request->input('drive_id');
        $user_ids = $request->input('user_ids');
        $drive_user = DriveUser::where('drive_id', $drive_id)->first();

        if ($role_id === 3 || $user_id === 1) {
            if (!$drive_user) {
                $drive_user = new DriveUser();
                $drive_user->drive_id = $drive_id;
                $drive_user->user_ids = $user_ids;
                $drive_user->save();
            } else {
                $drive_user->user_ids = $user_ids;
                $drive_user->save();
            }
            return response()->json(['message' => 'Asignación procesada', 'status' => 200]);
        }

        return response()->json(['message' => 'Unauthorized access', 'status' => 403]);

    }

    // DE LA TABLA DRIVE_USER
    public function usersSelectedDrive($id)
    {
        $driveUsers = DriveUser::where('drive_id', $id)->get();
        return response()->json(['data' => $driveUsers], 200);
    }
}


