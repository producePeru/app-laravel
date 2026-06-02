<?php

namespace App\Http\Controllers\Cde;

use App\Http\Controllers\Controller;
use App\Models\Cde;
use App\Models\CdePnte;
use Illuminate\Http\Request;

class CdeController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'name' => $request->input('name'),
        ];

        $query = Cde::query()->with(['region', 'provincia', 'distrito']);

        // Filtrar por 'name' si está presente
        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        $data = $query->orderBy('id', 'desc')->paginate(150)->through(function ($item) {
            return $this->mapWorkshopItems($item);
        });

        return response()->json([
            'data'   => $data,
            'status' => 200
        ]);
    }

    private function mapWorkshopItems($value)
    {
        return [
            'id'                    => $value->id,
            'name'                  => strtoupper($value->name) ?? null,
            // 'city'                  => $value->city ?? null,
            // 'province'              => $value->province ?? null,
            // 'district'              => $value->district ?? null,
            'city'                  => $value->region->name ?? null,
            'province'              => $value->provincia->name ?? null,
            'district'              => $value->distrito->name ?? null,
            'address'               => $value->address ?? null,
            'cdetype_id'            => $value->cdetype_id ?? null
        ];
    }

    public function chooseCde(Request $request, $id)
    {
        $cde = Cde::find($id);

        if (!$cde) {
            return response()->json(['error' => 'CDE not found'], 404);
        }

        $value = $request->input('value');

        $cde->cdetype_id = $value;

        $cde->save();

        return response()->json([
            'message'   => 'Actualizado',
            'status' => 200
        ]);
    }

    public function addressCde(Request $request, $id)
    {
        $cde = Cde::find($id);

        if (!$cde) {
            return response()->json(['error' => 'CDE not found'], 404);
        }

        $value = $request->input('value');

        $cde->address = $value;

        $cde->save();

        return response()->json([
            'message'   => 'Actualizado',
            'status' => 200
        ]);
    }

    public function updateCde(Request $request, $id)
    {
        $cde = Cde::find($id);

        if (!$cde) {
            return response()->json(['error' => 'CDE not found'], 404);
        }

        $cde->name = $request->input('name');
        $cde->city_id = $request->input('city_id');
        $cde->province_id = $request->input('province_id');
        $cde->district_id = $request->input('district_id');
        $cde->address = $request->input('address');

        $cde->save();

        return response()->json([
            'message' => 'CDE actualizado correctamente',
            'status' => 200
        ]);
    }

    public function storeCde(Request $request)
    {
        try {
            $cde = Cde::create([
                'name'       => $request->input('name'),
                'city_id'       => $request->input('city_id'),
                'province_id'   => $request->input('province_id'),
                'district_id'   => $request->input('district_id'),
                'address'    => $request->input('address')
            ]);

            return response()->json([
                'message' => 'CDE creado con éxito',
                'status'  => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el CDE',
                'status'  => 500,
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    // NUEVO PEDIDO DE BRIGITTE

    public function registerCde(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'nullable|string|max:100',
            'region_id' => 'required|exists:cities,id',
            'provincia_id' => 'required|exists:provinces,id',
            'distrito_id' => 'required|exists:districts,id',
            'direccion' => 'nullable|string|max:100',
        ]);

        $cde = CdePnte::create($validated);

        return response()->json([
            'status' => 200,
            'message' => 'CDE registrada correctamente.',
            'data' => $cde
        ], 200);
    }

    public function listCdes(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $cdes = CdePnte::with([
            'region:id,name',
            'provincia:id,name',
            'distrito:id,name'
        ])
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $cdes
        ]);
    }

    public function update(Request $request, $id)
    {
        $cde = CdePnte::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'nullable|string|max:100',
            'region_id' => 'required|exists:cities,id',
            'provincia_id' => 'required|exists:provinces,id',
            'distrito_id' => 'required|exists:districts,id',
            'direccion' => 'nullable|string|max:100',
        ]);

        $cde->update($validated);

        return response()->json([
            'status' => 200,
            'message' => 'CDE actualizada correctamente.',
            'data' => $cde->load([
                'region:id,name',
                'provincia:id,name',
                'distrito:id,name'
            ])
        ]);
    }

    public function byRegion($regionId)
    {
        $cdes = CdePnte::with([
            'region:id,name',
            'provincia:id,name',
            'distrito:id,name'
        ])
            ->where('region_id', $regionId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cdes
        ]);
    }
}
