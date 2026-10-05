<?php

namespace App\Http\Controllers\Convenio;

use App\Http\Controllers\Controller;
use App\Models\Convenio;
use App\Models\ConvenioArchivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConvenioArchivoController extends Controller
{
    /**
     * Documentos relacionados al convenio.
     */
    public function index($convenioId)
    {
        $convenio = Convenio::find($convenioId);

        if (! $convenio) {
            return response()->json([
                'status' => 404,
                'message' => 'Convenio no encontrado.',
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'data' => ConvenioArchivo::where('convenio_id', $convenio->id)
                ->orderBy('id')
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'nombre' => $a->nombre_original,
                    'url' => asset($a->ruta),
                    'mime' => $a->mime,
                    'tamano' => $a->tamano,
                    'created_at' => $a->created_at?->format('d/m/Y H:i'),
                ]),
        ]);
    }

    /**
     * Carga documentos del convenio (fotos, Office, PDF, etc.).
     */
    public function store(Request $request, $convenioId)
    {
        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,ppt,pptx,pdf,txt,csv,zip|max:20480',
        ]);

        $convenio = Convenio::find($convenioId);

        if (! $convenio) {
            return response()->json([
                'status' => 404,
                'message' => 'Convenio no encontrado.',
            ], 404);
        }

        try {
            $dir = public_path('storage/convenios/archivos');
            if (! File::exists($dir)) {
                File::makeDirectory($dir, 0775, true, true);
            }

            $guardados = [];
            foreach ((array) $request->file('files') as $file) {
                // Leer todo del temporal ANTES de moverlo.
                $ext = strtolower($file->getClientOriginalExtension());
                $original = $file->getClientOriginalName();
                $mime = $file->getMimeType();
                $tamano = $file->getSize();
                $nombre = (string) Str::uuid().'.'.$ext;
                $file->move($dir, $nombre);

                $guardados[] = ConvenioArchivo::create([
                    'convenio_id' => $convenio->id,
                    'nombre_original' => $original,
                    'ruta' => 'storage/convenios/archivos/'.$nombre,
                    'mime' => $mime,
                    'tamano' => $tamano,
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Documento(s) cargado(s) correctamente.',
                'data' => $guardados,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al subir archivos del convenio: '.$e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error al subir los archivos.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $archivo = ConvenioArchivo::find($id);

        if (! $archivo) {
            return response()->json([
                'status' => 404,
                'message' => 'Archivo no encontrado.',
            ], 404);
        }

        $absoluta = public_path($archivo->ruta);
        if (File::exists($absoluta)) {
            File::delete($absoluta);
        }
        $archivo->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Archivo eliminado.',
        ]);
    }
}
