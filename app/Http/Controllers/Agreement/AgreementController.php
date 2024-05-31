<?php

namespace App\Http\Controllers\Agreement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Agreement;
use App\Models\AgreementActions;

class AgreementController extends Controller
{
    public function index()
    {
        $data = Agreement::with(
            ['estadoOperatividad', 'estadoConvenio', 'region', 'provincia', 'distrito', 'acciones', 'archivosConvenios']
        )->join('cities', 'agreements.city_id', '=', 'cities.id')
        ->select('agreements.*')                                    // 🚩id agreements
        ->orderBy('cities.name', 'asc')
        ->paginate(20);

        $data->getCollection()->transform(function ($item) {        // 🚩decode
            $item['initials'] = json_decode($item['initials']);
            return $item;
        });

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'denomination' => 'required|string|max:100',
                'alliedEntity' => 'required|string|max:100',
                'homeOperations' => 'nullable|string|max:100',
                'address' => 'required|string|max:80',
                'reference' => 'required|string|max:100',
                'resolution' => 'required|string|max:150',
                'initials' => 'required|array',
                'startDate' => 'nullable|date',
                'endDate' => 'nullable|date',
                'city_id' => 'required|exists:cities,id',
                'province_id' => 'required|exists:provinces,id',
                'district_id' => 'required|exists:districts,id',
                'operationalstatus_id' => 'required|exists:operationalstatus,id',
                'agreementstatus_id' => 'required|exists:agreementstatus,id',               // 🚩reference
                'createdBy' => 'required|exists:users,id'
            ]);

            $validatedData['initials'] = json_encode($validatedData['initials']);


            $convenio = Agreement::create($validatedData);

            return response()->json(['message' => 'Convenio creado con éxito', 'status' => 200]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error:' => $e,'status' => 500]);
        }
        catch (QueryException $e) {
            return response()->json(['message' => 'El usuario se registró pero la relación ha fallado', 'error' => $e], 400);
        }
    }

    public function storeAction(Request $request)
    {
        try {
            $request->validate([
                'description' => 'required|string|max:250',
                'agreements_id' => 'required|exists:agreements,id',
            ]);

            AgreementActions::create($request->all());

            return response()->json(['message' => 'Acción asignada al convenio correctamente.', 'status' => 200]);

        } catch (QueryException $e) {
            return response()->json(['message' => 'El usuario se registró pero la relación ha fallado', 'error' => $e], 400);
        }
    }
}
