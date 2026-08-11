<?php

namespace App\Http\Controllers\Pnte;

use App\Http\Controllers\Controller;
use App\Models\Tienda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'envio_id' => 'nullable|integer|exists:envios,id',
            'celular' => 'nullable|max:20',
            'correo' => 'nullable|email|max:255',
            'image_id' => 'nullable|integer|exists:images,id',
            'socials' => 'nullable|array',
            'socials.*.name' => 'nullable|string|max:100',
            'socials.*.link' => 'nullable|string|max:255',
        ]);

        $tienda->update([
            'nombre' => trim($request->nombre),
            'descripcion' => $request->descripcion,
            'ruc' => trim($request->ruc),
            'envio_id' => $request->envio_id,
            'celular' => $request->celular ? trim($request->celular) : null,
            'correo' => $request->correo ? trim($request->correo) : null,
            'image_id' => $request->image_id,
            'socials' => $request->has('socials') ? $request->socials : $tienda->socials,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Tienda actualizada correctamente.',
            'data' => $tienda->fresh()->load(['image', 'envio']),
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

    // MODO PUBLICO
    /**
     * Listado público de tiendas (solo id, nombre e imagen thumb).
     * Pensado para vistas tipo catálogo/grid, sin exponer datos sensibles.
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $pageSize = 50; // ✅ fijo, no configurable desde el público

        $data = Tienda::with(['image'])
            ->select(['id', 'nombre', 'image_id']) // ✅ solo lo necesario
            ->when($request->filled('name'), function ($q) use ($request) {
                $q->where('nombre', 'LIKE', '%'.trim($request->name).'%');
            })
            ->orderBy('nombre')
            ->paginate($pageSize);

        $data->getCollection()->transform(function ($tienda) {

            $thumbUrl = null;

            if ($tienda->image) {
                $filename = basename(parse_url((string) $tienda->image->url, PHP_URL_PATH));
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
}
