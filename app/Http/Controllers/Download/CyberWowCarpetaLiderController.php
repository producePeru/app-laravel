<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\CyberwowBrand;
use App\Models\Fair;
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

    public function cyberWowFolderAllBrands($slug)
    {
        try {
            // 1️⃣ Buscar la feria por slug
            $fair = Fair::where('slug', $slug)->first();

            if (!$fair) {
                return response()->json(['message' => 'Feria no encontrada.'], 404);
            }

            // 2️⃣ Nombre del archivo ZIP global
            $zipFileName = "cyberwow_{$fair->slug}_carpetas.zip";
            $zipPath = storage_path("app/public/tmp/{$zipFileName}");
            Storage::makeDirectory('public/tmp');

            // 3️⃣ Crear el ZIP
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                return response()->json(['message' => 'No se pudo crear el archivo ZIP.'], 500);
            }

            // 4️⃣ Obtener las marcas de esta feria con sus relaciones
            $brands = CyberwowBrand::with(['user', 'logo256', 'logo160'])
                ->where('wow_id', $fair->id)
                ->get();

            if ($brands->isEmpty()) {
                return response()->json(['message' => 'No se encontraron marcas para esta feria.'], 404);
            }

            foreach ($brands as $brand) {
                // Crear carpeta por marca o usuario
                $userName = trim(($brand->user->name ?? '') . ' ' . ($brand->user->lastname ?? ''));
                $brandFolder = 'Marcas';

                // Str::slug($userName ?: 'usuario_' . $brand->user_id, '_')

                // Carpeta principal
                $mainFolder = "{$brandFolder}/";
                $empresaFolder = "{$mainFolder}empresa/";

                $zip->addEmptyDir($empresaFolder);

                // 5️⃣ Obtener imágenes de logo (256 y 160)
                $images = Image::whereIn('id', [$brand->logo256_id, $brand->logo160_id])
                    ->whereNotNull('url')
                    ->get();

                foreach ($images as $img) {
                    // Ruta física
                    $relativePath = str_replace(asset('storage') . '/', '', $img->url);
                    $path = public_path("storage/" . $relativePath);

                    if (file_exists($path)) {
                        $fileName = $img->name ?? basename($path);
                        $zip->addFile($path, $empresaFolder . $fileName);
                    }
                }
            }

            $zip->close();

            // 6️⃣ Descargar y eliminar después
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
