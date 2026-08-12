<?php

namespace App\Http\Controllers\Pnte;

use App\Exports\TiendaContactosExport;
use App\Http\Controllers\Controller;
use App\Mail\TiendaContactoMail;
use App\Models\Tienda;
use App\Models\TiendaContacto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class TiendaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $pageSize = $request->input('pageSize', 10);

        $data = Tienda::with(['image'])
            ->when($request->filled('name'), function ($q) use ($request) {
                $name = trim($request->name);
                $q->where(function ($query) use ($name) {
                    $query->where('nombre', 'LIKE', "%{$name}%")
                        ->orWhere('ruc', 'LIKE', "%{$name}%")
                        ->orWhere('correo', 'LIKE', "%{$name}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($pageSize);

        // ✅ Tamaños disponibles de imagen
        $sizes = ['thumb', 'medium', 'original'];

        $data->getCollection()->transform(function ($tienda) use ($sizes) {

            if ($tienda->image) {
                $filename = basename(parse_url((string) $tienda->image->url, PHP_URL_PATH));

                $urls = [];
                foreach ($sizes as $size) {
                    $urls[$size] = url("storage/images/{$size}/{$filename}");
                }

                $tienda->image->setAttribute('urls', $urls);
                $tienda->image->makeHidden('url');
            }

            return $tienda;
        });

        return response()->json([
            'status' => 200,
            'message' => 'Tiendas obtenidas correctamente.',
            'data' => $data,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'ruc' => 'required|max:11|unique:tiendas,ruc',
            'envio_id' => 'nullable|integer',
            'celular' => 'nullable|max:9',
            'correo' => 'nullable|email|max:255',
            'categoria' => 'nullable|string',
            'image_id' => 'nullable|integer|exists:images,id',
            'socials' => 'nullable|array',
            'socials.*.name' => 'nullable|string|max:100',
            'socials.*.link' => 'nullable|string|max:255',
        ]);

        $tienda = Tienda::create([
            'nombre' => trim($request->nombre),
            'descripcion' => $request->descripcion,
            'ruc' => trim($request->ruc),
            'envio_id' => $request->envio_id,
            'celular' => $request->celular ? trim($request->celular) : null,
            'correo' => $request->correo ? trim($request->correo) : null,
            'categoria' => $request->categoria,
            'image_id' => $request->image_id,
            'socials' => $request->socials ?? [],
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Tienda registrada correctamente.',
            // 'data' => $tienda->load(['image', 'envio']),
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $tienda = Tienda::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'ruc' => 'required|string|max:20|unique:tiendas,ruc,'.$id,
            'envio_id' => 'nullable|integer',
            'celular' => 'nullable|max:20',
            'categoria' => 'nullable|string',
            'correo' => 'nullable|email|max:255',
            'image_id' => 'nullable|integer|exists:images,id',
            'socials' => 'nullable|array',
            'socials.*.name' => 'nullable|string|max:100',
            'socials.*.link' => 'nullable|string|max:255',
        ]);

        $data = [
            'nombre' => trim($request->nombre),
            'descripcion' => $request->descripcion,
            'ruc' => trim($request->ruc),
            'envio_id' => $request->envio_id,
            'celular' => $request->celular ? trim($request->celular) : null,
            'categoria' => $request->categoria,
            'correo' => $request->correo ? trim($request->correo) : null,
            'socials' => $request->has('socials')
                ? $request->socials
                : $tienda->socials,
        ];

        // Si image_id es null, mantiene la imagen actual.
        // Si viene un ID, actualiza la imagen.
        if ($request->image_id !== null) {
            $data['image_id'] = $request->image_id;
        }

        $tienda->update($data);

        return response()->json([
            'status' => 200,
            'message' => 'Tienda actualizada correctamente.',
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $tienda = Tienda::findOrFail($id);
        $tienda->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Tienda eliminada correctamente.',
        ]);
    }

    public function exportContactos()
    {
        return Excel::download(
            new TiendaContactosExport,
            'tiendas_contactos.xlsx'
        );
    }

    // MODO PUBLICO
    /**
     * Listado público de tiendas (solo id, nombre e imagen thumb).
     * Pensado para vistas tipo catálogo/grid, sin exponer datos sensibles.
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $pageSize = 50; // fijo, no configurable desde el público

        $data = Tienda::with(['image'])
            ->select(['id', 'nombre', 'image_id'])
            ->when($request->filled('name'), function ($q) use ($request) {
                $q->where('nombre', 'LIKE', '%'.trim($request->name).'%');
            })
            ->inRandomOrder()
            ->paginate($pageSize);

        $data->getCollection()->transform(function ($tienda) {

            $thumbUrl = null;

            if ($tienda->image) {
                $filename = basename(
                    parse_url((string) $tienda->image->url, PHP_URL_PATH)
                );

                $thumbUrl = url("storage/images/original/{$filename}");
            }

            return [
                'id' => $tienda->id,
                'nombre' => $tienda->nombre,
                'imagen' => $thumbUrl,
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'Tiendas obtenidas correctamente.',
            'data' => $data,
        ]);
    }

    public function show($id): JsonResponse
    {
        $tienda = Tienda::with(['image'])->findOrFail($id);

        return response()->json([
            'status' => 200,
            'message' => 'Tienda obtenida correctamente.',
            'data' => $tienda,
        ]);
    }

    public function storeContactanos(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => 'string|max:255',
            'celular' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:255',
            'productos' => 'nullable|string',
            'id_empresa' => 'required|integer|exists:tiendas,id',
        ]);

        $contacto = TiendaContacto::create($data);

        if (! empty($contacto->correo)) {

            $tienda = Tienda::findOrFail($contacto->id_empresa);

            $mailer = 'capacitaciones';

            Mail::mailer($mailer)
                ->to($contacto->correo)
                ->send(new TiendaContactoMail(
                    $contacto,
                    $tienda
                ));
        }

        return response()->json([
            'message' => 'Contacto creado correctamente',
            'data' => $contacto,
            'status' => 200,
        ]);
    }
}
