<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\CyberwowBrand;
use App\Models\CyberwowOffer;
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

            // 🔹 Cambia esta variable para incluir o no las imágenes FULL
            $includeImageFull = true; // 👈 TRUE = incluir imageFull, FALSE = no incluir

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

            // Carpetas principales
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
                // 1️⃣ Agregar logos
                $images = Image::whereIn('id', [$brand->logo256_id, $brand->logo160_id])
                    ->whereNotNull('url')
                    ->get();

                $baseFileName = Str::slug($brand->name ?? 'marca', '_');

                foreach ($images as $img) {
                    $relativePath = str_replace(asset('storage') . '/', '', $img->url);
                    $path = public_path("storage/" . $relativePath);

                    if (file_exists($path)) {
                        $fileName = $img->name ?? basename($path);
                        $zip->addFile($path, $empresaFolder . $fileName);
                        $baseFileName = pathinfo($fileName, PATHINFO_FILENAME);
                    }
                }

                // 2️⃣ Agregar ofertas una sola vez por marca
                //    Solo cargamos la relación imageFull si $includeImageFull es true
                $relations = ['imagePhone'];
                if ($includeImageFull) {
                    $relations[] = 'imageFull';
                }

                $offers = CyberwowOffer::where('company_id', $brand->company_id)
                    ->with($relations)
                    ->get();

                foreach ($offers as $index => $offer) {
                    $offerPrefix = "oferta" . ($index + 1) . "_";

                    // 🔸 Imagen principal (imageFull)
                    if ($includeImageFull && $offer->imageFull && $offer->imageFull->url) {
                        $offerPath = public_path('storage/' . str_replace(asset('storage') . '/', '', $offer->imageFull->url));
                        if (file_exists($offerPath)) {
                            $zip->addFile(
                                $offerPath,
                                $productosFolder . $offerPrefix . ($offer->imageFull->name ?? basename($offerPath))
                            );
                        }
                    }

                    // 🔸 Imagen secundaria (imagePhone)
                    if ($offer->imagePhone && $offer->imagePhone->url) {
                        $offerPath = public_path('storage/' . str_replace(asset('storage') . '/', '', $offer->imagePhone->url));
                        if (file_exists($offerPath)) {
                            $zip->addFile(
                                $offerPath,
                                $productosFolder . $offerPrefix . ($offer->imagePhone->name ?? basename($offerPath))
                            );
                        }
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
