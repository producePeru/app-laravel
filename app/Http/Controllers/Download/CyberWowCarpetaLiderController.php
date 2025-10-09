<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\CyberwowBrand;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class CyberWowCarpetaLiderController extends Controller
{
    public function downloadUserFolders()
    {
        try {
            $user = Auth::user();

            // 🔹 Construir nombre base (nombre + apellido)
            $fullName = trim(($user->name ?? '') . ' ' . ($user->lastname ?? ''));
            $folderName = Str::slug($fullName ?: 'usuario', '_');
            $zipFileName = "{$folderName}_carpetas.zip";

            // 🔹 Ruta temporal del ZIP
            $zipPath = storage_path("app/public/tmp/{$zipFileName}");
            Storage::makeDirectory('public/tmp');

            // 🔹 Crear el ZIP
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                return response()->json(['message' => 'No se pudo crear el archivo ZIP.'], 500);
            }

            // Carpeta principal
            $mainFolder = "{$folderName}/";
            $empresaFolder = "{$mainFolder}empresa/";
            $productosFolder = "{$mainFolder}productos/";

            $zip->addEmptyDir($empresaFolder);
            $zip->addEmptyDir($productosFolder);

            // ==========================
            // 🔹 OBTENER MARCAS DEL USUARIO
            // ==========================
            $brands = CyberwowBrand::where('user_id', $user->id)->get();

            foreach ($brands as $brand) {
                $images = Image::whereIn('id', [$brand->logo256_id, $brand->logo160_id])
                    ->whereNotNull('url')
                    ->get();

                foreach ($images as $img) {
                    // Convertir la URL pública a una ruta física
                    $relativePath = str_replace(asset('storage') . '/', '', $img->url);
                    $path = public_path("storage/" . $relativePath);

                    if (file_exists($path)) {
                        // 🧩 Usar el nombre original del campo `name` para el archivo dentro del ZIP
                        $fileName = $img->name ?? basename($path);

                        // Agregar al ZIP con el nombre personalizado
                        $zip->addFile($path, $empresaFolder . $fileName);
                    }
                }
            }

            $zip->close();

            // 🔹 Descargar y eliminar después
            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar las carpetas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
