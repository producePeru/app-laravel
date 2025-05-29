<?php

namespace App\Http\Controllers\Image;

use App\Http\Controllers\Controller;
use App\Models\Image;
use Intervention\Image\ImageManager;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function upload(Request $request)
    {
        // Validación
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'from_origin' => 'required|string|max:50',
        ]);

        // Obtener archivo
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;

        // Crear instancia del ImageManager
        $manager = ImageManager::gd();

        // Rutas
        $originalPath = "images/original/{$filename}";
        $mediumPath = "images/medium/{$filename}";
        $thumbPath = "images/thumb/{$filename}";

        // Asegurar que las carpetas existan
        Storage::disk('public')->makeDirectory('images/original');
        Storage::disk('public')->makeDirectory('images/medium');
        Storage::disk('public')->makeDirectory('images/thumb');

        // Guardar original sin modificar
        Storage::disk('public')->put($originalPath, file_get_contents($file));

        // Procesar mediana (800x600 máximo, calidad 75)
        $manager->read($file->getRealPath())
            ->resizeDown(800, 600)
            ->toJpeg(75)
            ->save(storage_path('app/public/') . $mediumPath);

        // Procesar thumbnail (120x120, calidad 60)
        $manager->read($file->getRealPath())
            ->resizeDown(120, 120)
            ->toJpeg(60)
            ->save(storage_path('app/public/') . $thumbPath);

        // Guardar datos en base de datos
        $imageModel = Image::create([
            'name' => $originalName,
            'url' => Storage::url($originalPath),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'from_origin' => $request->input('from_origin'),
        ]);

        return response()->json([
            'data' => $imageModel,
            'status' => 200,
            // 'message' => 'Imagen cargada y optimizada correctamente',
            // 'original' => Storage::url($originalPath),
            // 'medium' => Storage::url($mediumPath),
            'thumb' => Storage::url($thumbPath),
        ]);
    }

    public function setOriginImage(Request $request, $id)
    {
        try {
            $request->validate([
                'id_origin' => 'required|integer',
            ]);

            $image = Image::findOrFail($id);
            $image->id_origin = $request->input('id_origin');
            $image->save();

            return response()->json([
                'message' => 'Imagen actualizada con éxito',
                'image' => $image,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Imagen no encontrada',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error inesperado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
