<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadOthersController extends Controller
{
    public function descargarAPKar()
    {
        $archivoPath = 'apk/producear.apk';

        return response()->download(
            storage_path('app/' . $archivoPath),
            'evento-produce.apk',
            ['Content-Type' => 'application/vnd.android.package-archive']
        );
    }
}
