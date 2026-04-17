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
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|mimes:jpg,jpeg,png,webp,gif,mp4,mov,avi|max:51200'
        ]);

        if (!$request->hasFile('files')) {
            return response()->json(['error' => 'No files'], 400);
        }

        $saved = [];

        DB::beginTransaction();

        try {

            foreach ($request->file('files') as $file) {

                if (!$file->isValid()) continue;

                $uuid = (string) Str::uuid();
                $ext  = strtolower($file->getClientOriginalExtension());
                $name = $uuid . '.' . $ext;

                // 🔥 USAR STORAGE (SOLUCIÓN AL ERROR TMP)
                $path = $file->storeAs('boda', $name, 'public');

                $type = str_starts_with($file->getMimeType(), 'video')
                    ? 'video'
                    : 'image';

                $media = Media::create([
                    'name' => $file->getClientOriginalName(),
                    'path' => 'storage/' . $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'type' => $type
                ]);

                // 🔥 devolver URL lista
                $media->url = asset($media->path);

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
