<?php

namespace App\Http\Controllers\Formalization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notary;
use Carbon\Carbon;

class NotaryController extends Controller
{
    // public function indexNotary()
    // {
    //     try {
    //         $formalization = Notary::withNotariesAndRelations();
    //         return response()->json($formalization, 200);
    //     } catch (\Exception $e) {
    //         return response()->json(['message' => 'Error al listar las notarías', 'status' => 500]);
    //     }
    // }

    public function indexNotary(Request $request)
    {

        $filters = [
            'name'      => $request->input('name'),
            'city_id'   => $request->input('city_id'),
        ];

        $query = Notary::query();

        $query->withItems($filters);

        $items = $query->paginate(150)->through(function ($item) {
			return $this->mapItems($item);
		});

        return response()->json([
			'data'   => $items,
			'status' => 200
		]);
    }

    private function mapItems($item)
	{
		return [
				'id' => $item->id,
                'name' => $item->name,
                'city_id' => $item->city->id,
                'province_id' => $item->province->id,
                'district_id' => $item->district->id,
                'addressNotary' => $item->addressNotary,
                'city_name' => $item->city->name,
                'province_name' => $item->province->name,
                'district_name' => $item->district->name,
                'gasto1' => $item->gasto1,
                'gasto1Detail' => $item->gasto1Detail,
                'gasto2' => $item->gasto2,
                'gasto2Detail' => $item->gasto2Detail,
                'gasto3' => $item->gasto3,
                'gasto3Detail' => $item->gasto3Detail,
                'gasto4' => $item->gasto4,
                'gasto4Detail' => $item->gasto4Detail,
                'gasto5' => $item->gasto5,
                'gasto5Detail' => $item->gasto5Detail,
                'gasto6' => $item->gasto6,
                'gasto6Detail' => $item->gasto6Detail,
                'testimonio' => $item->testimonio,
                'legalization' => $item->legalization,
                'biometric' => $item->biometric,
                'aclaratory' => $item->aclaratory,
                'socio' => $item->socio,
                'conditions' => $item->conditions,
                'contactName' => $item->contactName,
                'contactEmail' => $item->contactEmail,
                'contactPhone' => $item->contactPhone,
                'normalTarifa' => $item->normalTarifa,
                'status' => $item->status == 1 ? true : false,
				'created_at' => Carbon::parse($item->created_at)->format('d/m/Y')
		];
	}


    public function storeNotary(Request $request)
    {
        try {
            $data = $request->all();

            $data['user_id'] = $request->user()->id;

            Notary::create($data);

            return response()->json(['message' => 'Notaría registrada correctamente', 'status' => 200]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al registrar', 'status' => $e->getMessage()], 500);
        }
    }


    public function deleteNotary($id)
    {
        try {
            $notary = Notary::findOrFail($id);
            $notary->update(['status' => 0]);

            return response()->json(['message' => 'Notaría deshabilitada correctamente', 'status' => 200]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al deshabilitar la notaría', 'status' => 500]);
        }
    }


    public function updateNotary(Request $request, $id)
    {
        try {
            $notary = Notary::findOrFail($id);
            $data = $request->all();
            $notary->update($data);
            return response()->json(['message' => 'Notaría actualizada correctamente', 'status' => 200]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar la notaría', 'status' => $e->getMessage()], 500);
        }
    }

    // Filtros...
    public function indexNotaryById(Request $request)
    {
        try {
            $name = $request->query('name'); // Obtener el parámetro 'name'
            $cityId = $request->query('city_id'); // Obtener el parámetro 'city_id'

            // Construir la consulta base con relaciones
            $query = Notary::with(['city', 'province', 'district', 'user.profile'])->where('status', 1);;

            // Si se envía el parámetro 'name', aplicar los filtros
            if ($name) {
                $query->where('name', 'LIKE', '%' . $name . '%') // Filtrar por nombre del notario
                    ->orWhereHas('city', function ($q) use ($name) {
                        $q->where('name', 'LIKE', '%' . $name . '%'); // Filtrar por nombre de ciudad
                    })
                    ->orWhereHas('province', function ($q) use ($name) {
                        $q->where('name', 'LIKE', '%' . $name . '%'); // Filtrar por nombre de provincia
                    })
                    ->orWhereHas('district', function ($q) use ($name) {
                        $q->where('name', 'LIKE', '%' . $name . '%'); // Filtrar por nombre de distrito
                    });
            }

            // Si se envía el parámetro 'city_id', aplicar el filtro
            if ($cityId) {
                $query->where('city_id', $cityId); // Filtrar por ID de ciudad
            }

            // Obtener los resultados con paginación
            $notaries = $query->paginate(200);

            return response()->json($notaries, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar las notarías', 'status' => 500]);
        }
    }


    public function updateStatusNotary ($id)
    {
        try {
            $notary = Notary::findOrFail($id);
            $notary->update(['status' => !$notary->status]);

            return response()->json(['message' => 'Estado de la notaría actualizado correctamente', 'status' => 200]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el estado de la notaría', 'status' => 500]);
        }
    }
}
