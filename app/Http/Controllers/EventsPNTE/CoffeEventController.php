<?php

namespace App\Http\Controllers\EventsPNTE;

use App\Http\Controllers\Controller;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CoffeEventController extends Controller
{
    public function showAllPhotosThumbFromCoffeeEvent()
    {
        // 1) Traer imágenes del origen 'coffe'
        $images = Image::query()
            ->where('from_origin', 'coffe')
            ->latest('id')
            ->get();

        // 2) Mapear a payload con URL pública desde /storage/thumb/{filename}
        $data = $images->map(function ($img) {
            // Si en 'url' viene un http(s), lo respetamos. Si no, asumimos que es un filename.
            $filename = $img->url ?: $img->name;
            $filename = basename($filename); // limpia rutas tipo .../thumb/archivo.jpg

            // URL pública (requiere `php artisan storage:link`)
            $publicUrl = Str::startsWith($img->url, ['http://', 'https://'])
                ? $img->url
                : Storage::disk('public')->url('thumb/' . $filename);

            return [
                'id'          => $img->id,
                'name'        => $img->name,
                'mime_type'   => $img->mime_type,
                'size'        => $img->size,
                'from_origin' => $img->from_origin,
                'id_origin'   => $img->id_origin,
                'url'         => $publicUrl,
            ];
        });

        return response()->json(['data' => $data], 200);
    }
}
