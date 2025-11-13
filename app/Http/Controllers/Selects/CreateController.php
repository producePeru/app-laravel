<?php

namespace App\Http\Controllers\Selects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ComercialActivities;
use App\Models\Component;
use App\Models\Office;
use App\Models\EconomicSector;
use App\Models\Themecomponent;
use App\Models\Notary;
use App\Models\Cde;
use App\Models\City;
use App\Models\Province;
use App\Models\District;
use Illuminate\Support\Facades\Log;

class CreateController extends Controller
{
    public function postComercialActivities(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        ComercialActivities::create($data);

        return response()->json(['message' => 'Actividad creada correctamente', 'status' => 200]);
    }

    public function createOffice(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        Office::create($data);

        return response()->json(['message' => 'Creado correctamente', 'status' => 200]);
    }

    public function createEconomicSector(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        EconomicSector::create($data);

        return response()->json(['message' => 'Creado correctamente', 'status' => 200]);
    }

    public function createNewComponent(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        Component::create($data);

        return response()->json(['message' => 'Creado correctamente', 'status' => 200]);
    }

    public function createNewTheme(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'component_id' => 'required',
        ]);

        Themecomponent::create($data);

        return response()->json(['message' => 'Creado correctamente', 'status' => 200]);
    }

    public function createNewEconomicSector(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        EconomicSector::create($data);

        return response()->json(['message' => 'Creado correctamente', 'status' => 200]);
    }

    public function createCdeNotary(Request $request)
    {
        $notaryId = $request->input('notary_id');

        $notary = Notary::find($notaryId);

        if (!$notary) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        $city = City::find($notary->city_id);
        $province = Province::find($notary->province_id);
        $district = District::find($notary->district_id);

        if (!$city || !$province || !$district) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        $cde = Cde::where('notary_id', $notaryId)->first();

        if ($cde) {
            $cde->update([
                'name' => $notary->name,
                'city' => $city->name,
                'province' => $province->name,
                'district' => $district->name,
                'address' => $notary->address,
                'notary_id' => $notaryId
            ]);
        } else {
            $cde = Cde::create([
                'name' => $notary->name,
                'city' => $city->name,
                'province' => $province->name,
                'district' => $district->name,
                'address' => $notary->address,
                'notary_id' => $notaryId
            ]);
        }

        return response()->json($cde->id);
    }

    public function createCde(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'city' => 'required|string',
            'province' => 'required|string',
            'district' => 'required|string',
            'address' => 'required|string',
        ]);

        $cde = Cde::create($data);

        return response()->json([
            'message' => 'Creado correctamente',
            'status' => 200,
            'id' => $cde->id,
        ]);
    }


    // geo
    public function createProvince(Request $request)
    {
        try {
            // Validar el payload recibido
            $validated = $request->validate([
                'name'    => 'required|string|max:255',
                'city_id' => 'required|integer|exists:cities,id',
            ]);

            // Crear el nuevo registro en Province
            Province::create([
                'name'    => $validated['name'],
                'city_id' => $validated['city_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Provincia creada correctamente.',
                'status'  => 200
            ]);
        } catch (\Throwable $e) {

            Log::error('Error en updateCde: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al crear la provincia.',
                'error'   => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function createDistrict(Request $request)
    {
        try {
            // Validar el payload recibido
            $validated = $request->validate([
                'name'    => 'required|string|max:255',
                'province_id' => 'required|integer|exists:provinces,id',
            ]);

            // Crear el nuevo registro en Province
            District::create([
                'name'    => $validated['name'],
                'province_id' => $validated['province_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Distrito creada correctamente.',
                'status'  => 200
            ]);
        } catch (\Throwable $e) {

            Log::error('Error en updateCde: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al crear la Distrito.',
                'error'   => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function updateCity(Request $request)
    {
        try {
            $validated = $request->validate([
                'id'   => 'required|integer|exists:cities,id',
                'name' => 'required|string|max:255',
            ]);

            // Buscar la provincia por ID
            $request = City::find($validated['id']);

            // Actualizar el nombre
            $request->name = $validated['name'];
            $request->save();

            return response()->json([
                'success' => true,
                'message' => 'Region actualizada correctamente.',
                'status' => 200
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en updateCde: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al actualizar la region.',
                'error'   => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function updateProvince(Request $request)
    {
        try {
            $validated = $request->validate([
                'id'   => 'required|integer|exists:provinces,id',
                'name' => 'required|string|max:255',
            ]);

            // Buscar la provincia por ID
            $province = \App\Models\Province::find($validated['id']);

            // Actualizar el nombre
            $province->name = $validated['name'];
            $province->save();

            return response()->json([
                'success' => true,
                'message' => 'Provincia actualizada correctamente.',
                'status'  => 200,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en updateCde: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al actualizar la provincia.',
                'error'   => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function updateDistrict(Request $request)
    {
        try {
            $validated = $request->validate([
                'id'   => 'required|integer|exists:districts,id',
                'name' => 'required|string|max:255',
            ]);

            // Buscar el distrito por ID
            $district = \App\Models\District::find($validated['id']);

            // Actualizar el nombre
            $district->name = $validated['name'];
            $district->save();

            return response()->json([
                'success' => true,
                'message' => 'Distrito actualizado correctamente.',
                'status'  => 200,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en updateCde: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al actualizar el distrito.',
                'error'   => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
