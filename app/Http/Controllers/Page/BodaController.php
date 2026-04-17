<?php

namespace App\Http\Controllers\Page;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Media;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BodaController extends Controller
{
    public function index()
    {
        $media = Media::latest()->get()->map(function ($item) {

            $item->url = asset($item->path);

            return $item;
        });

        return response()->json($media);
    }


    public function upload(Request $request)
{
    // 🔥 FORZAR TMP (Hostinger)
    $tmp = storage_path('app/tmp');
    if (!file_exists($tmp)) {
        mkdir($tmp, 0777, true);
    }
    ini_set('upload_tmp_dir', $tmp);

    // 🔥 TOMAR ARCHIVOS DESDE $_FILES (evita error tmp)
    if (!isset($_FILES['files'])) {
        return response()->json(['error' => 'No files'], 400);
    }

    $saved = [];

    DB::beginTransaction();

    try {

        foreach ($_FILES['files']['tmp_name'] as $i => $tmpName) {

            if (!file_exists($tmpName)) continue;

            $originalName = $_FILES['files']['name'][$i];
            $size = $_FILES['files']['size'][$i];
            $typeMime = mime_content_type($tmpName);

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $name = (string) Str::uuid() . '.' . $ext;

            $destination = public_path('storage/boda');

            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }

            // 🔥 MOVER DIRECTO DESDE TMP
            move_uploaded_file($tmpName, $destination . '/' . $name);

            $path = 'storage/boda/' . $name;

            $type = str_starts_with($typeMime, 'video') ? 'video' : 'image';

            $media = Media::create([
                'name' => $originalName,
                'path' => $path,
                'mime_type' => $typeMime,
                'size' => $size,
                'type' => $type
            ]);

            $media->url = asset($path);

            $saved[] = $media;
        }

        DB::commit();

        return response()->json([
            'status' => true,
            'data' => $saved
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'status' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function download($id)
    {
        $media = Media::findOrFail($id);

        return response()->download(public_path($media->path));
    }

    public function destroy($id)
    {
        $media = Media::findOrFail($id);

        $media->delete(); // 🔥 soft delete

        return response()->json(['status' => true]);
    }
}
