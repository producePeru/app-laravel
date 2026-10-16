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

    /**
     * Renombra solo el nombre visual del documento.
     * La extensión se preserva en el servidor para no romper
     * el tipo de archivo, la previsualización ni la descarga.
     * No se toca el archivo físico (ruta con uuid).
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => [
                'required',
                'string',
                'min:1',
                'max:150',
                // Sin separadores de ruta ni caracteres problemáticos en Windows/URLs.
                'regex:/^[^\/\\\\:*?"<>|]+$/',
            ],
        ], [
            'nombre.regex' => 'El nombre no puede contener / \\ : * ? " < > |',
        ]);

        $archivo = ConvenioArchivo::find($id);

        if (! $archivo) {
            return response()->json([
                'status' => 404,
                'message' => 'Archivo no encontrado.',
            ], 404);
        }

        // Extensión real: se toma del registro actual (o de la ruta física),
        // nunca de lo que escriba el usuario.
        $extension = strtolower(pathinfo((string) $archivo->nombre_original, PATHINFO_EXTENSION));
        if (! $extension) {
            $extension = strtolower(pathinfo((string) $archivo->ruta, PATHINFO_EXTENSION));
        }

        // El usuario manda solo la base: si igual manda "informe.pdf",
        // se recorta la extensión para no duplicarla.
        $base = trim((string) $request->input('nombre'));
        $baseSinExt = $extension
            ? preg_replace('/\.'.preg_quote($extension, '/').'$/i', '', $base)
            : $base;
        $baseSinExt = trim(preg_replace('/\s+/', ' ', (string) $baseSinExt));

        if ($baseSinExt === '') {
            return response()->json([
                'status' => 422,
                'message' => 'El nombre no puede quedar vacío.',
            ], 422);
        }

        $archivo->nombre_original = $extension
            ? $baseSinExt.'.'.$extension
            : $baseSinExt;
        $archivo->save();

        return response()->json([
            'status' => 200,
            'message' => 'Nombre actualizado.',
            'data' => [
                'id' => $archivo->id,
                'nombre' => $archivo->nombre_original,
                'url' => asset($archivo->ruta),
                'mime' => $archivo->mime,
                'tamano' => $archivo->tamano,
                'created_at' => $archivo->created_at?->format('d/m/Y H:i'),
            ],
        ]);
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
