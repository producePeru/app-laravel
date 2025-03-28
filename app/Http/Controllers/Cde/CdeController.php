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
            'name'      => $request->input('name'),
        ];


        $userRole = getUserRole();

        $query = Cde::query();

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
            'city'                  => $value->city ?? null,
            'province'              => $value->province ?? null,
            'district'              => $value->district ?? null,
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
}
