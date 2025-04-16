<?php

namespace App\Http\Controllers\Cde;

use App\Http\Controllers\Controller;
use App\Models\Cde;
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
}
