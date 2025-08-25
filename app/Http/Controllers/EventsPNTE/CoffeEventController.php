<?php

namespace App\Http\Controllers\EventsPNTE;

use App\Http\Controllers\Controller;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CoffeEventController extends Controller
{
    public function showAllPhotosFromCoffeeEvent(Request $request)
    {
        // Tipos válidos
        $allowedTypes = ['thumb', 'medium', 'original'];

        // type solicitado (default: original o lo que prefieras)
        $type = strtolower($request->query('type', 'original'));
        if (!in_array($type, $allowedTypes)) {
            return response()->json([
                'status'  => 422,
                'message' => "El parámetro 'type' debe ser: " . implode(', ', $allowedTypes),
            ], 422);
        }

        // Traer imágenes del origen 'coffe'
        $images = Image::query()
            ->where('from_origin', 'coffe')
            ->latest('id')
            ->get();

        $data = $images->map(function ($img) use ($type) {

            $filename = basename(parse_url((string) $img->url, PHP_URL_PATH));
            $publicUrl = url("storage/images/{$type}/{$filename}");

            return [
                'id'          => $img->id,
                'name'        => $img->name,
                'mime_type'   => $img->mime_type,
                'size'        => $img->size,
                'from_origin' => $img->from_origin,
                'id_origin'   => $img->id_origin,
                'url'         => $publicUrl
            ];
        });

        return response()->json(['data' => $data, 'status' => 200]);
    }

    public function removeImageFromCoffeeEvent($id)
    {
        $image = Image::query()
            ->where('id', $id)
            ->where('from_origin', 'coffe')
            ->first();

        if (!$image) {
            return response()->json([
                'status'  => 404,
                'message' => 'Imagen no encontrada'
            ], 404);
        }

        // Soft delete: marca deleted_at sin borrar físicamente
        $image->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Registro eliminado correctamente'
        ]);
    }
}
