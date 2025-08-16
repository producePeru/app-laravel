<?php

namespace App\Http\Controllers\Image;

use App\Http\Controllers\Controller;
use App\Models\Image;
use Intervention\Image\ImageManager;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ImageController extends Controller
{
    public function upload(Request $request)
    {
        // Validación
        $request->validate([
            'file'        => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:5120', // 5 MB
            'from_origin' => 'required|string|max:50',
        ]);

        $file         = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extOriginal  = strtolower($file->getClientOriginalExtension());

        // Rutas base en public/ (sin symlink)
        $basePublicStorage = public_path('storage');
        $dirOriginal = $basePublicStorage . '/images/original';
        $dirMedium   = $basePublicStorage . '/images/medium';
        $dirThumb    = $basePublicStorage . '/images/thumb';

        // Asegurar carpetas (775 si tu hosting lo permite; 755 si no)
        foreach ([$dirOriginal, $dirMedium, $dirThumb] as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0775, true, true);
            }
        }

        // Nombre para el original: preserva extensión original
        $uuid                 = (string) Str::uuid();
        $filenameOriginal     = $uuid . '.' . $extOriginal;
        $pathOriginalAbsolute = $dirOriginal . '/' . $filenameOriginal;

        // Mover el archivo original directo a public/storage/images/original
        $file->move($dirOriginal, $filenameOriginal);

        // Manager de imágenes (Intervention Image v3)
        $manager = ImageManager::gd();

        // Nombres y rutas para derivados (siempre JPG)
        $filenameJpg          = $uuid . '.jpg';
        $pathMediumAbsolute   = $dirMedium . '/' . $filenameJpg;
        $pathThumbAbsolute    = $dirThumb . '/' . $filenameJpg;

        // Fuente para procesar: usa el original ya movido
        $imageSource = $pathOriginalAbsolute;

        // Procesar mediana (máx 800x600, calidad 75) → JPG
        $manager->read($imageSource)
            ->resizeDown(800, 600)
            ->toJpeg(75)
            ->save($pathMediumAbsolute);

        // Procesar thumbnail (120x120, calidad 60) → JPG
        $manager->read($imageSource)
            ->resizeDown(120, 120)
            ->toJpeg(60)
            ->save($pathThumbAbsolute);

        // URLs públicas
        $urlOriginal = asset('storage/images/original/' . $filenameOriginal);
        $urlMedium   = asset('storage/images/medium/' . $filenameJpg);
        $urlThumb    = asset('storage/images/thumb/' . $filenameJpg);

        // Guardar en BD (URL del original; puedes agregar campos para medium/thumb si tu tabla los tiene)
        $imageModel = Image::create([
            'name'        => $originalName,
            'url'         => 'storage/images/original/' . $filenameOriginal, // ruta pública relativa
            'mime_type'   => $file->getClientMimeType(),
            'size'        => filesize($pathOriginalAbsolute),
            'from_origin' => $request->input('from_origin'),
        ]);

        return response()->json([
            'data'       => $imageModel,
            'status'     => 200,
            'message'    => 'Imagen cargada',
            'original'   => $urlOriginal,
            'medium'     => $urlMedium,
            'thumb'      => $urlThumb,
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
