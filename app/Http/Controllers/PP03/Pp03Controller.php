<?php

namespace App\Http\Controllers\PP03;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Eventspp03;
use App\Models\City;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Pp03Controller extends Controller
{
    public function store(Request $request)
    {
        try {
            $data = $request->all();

            // Verificar existencia de ciudad
            $city = City::find($data['city_id']);
            if (!$city) {
                return response()->json(['error' => 'Ciudad no encontrada'], 404);
            }

            // Crear código de fechas si existe dateEnd
            $codigoFechas = $data['dateStart'] . $data['dateEnd'];

            // Crear slug
            $slugParts = [
                Str::slug($city->name),
                Str::slug($data['nameEvent']),
            ];

            if ($codigoFechas) {
                $slugParts[] = $codigoFechas;
            }

            $slug = implode('-', $slugParts);

            // Agregar slug al array de datos
            $data['slug'] = $slug;

            // Crear evento
            Eventspp03::create($data);

            return response()->json(['message' => 'Evento creado correctamente', 'status' => 200]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al procesar la solicitud',
                'error' => $th->getMessage(),
                'status' => 500
            ], 500);
        }
    }


    public function index(Request $request)
    {
        $filters = [
            'year'      =>  $request->input('year'),
            'dateStart' =>  $request->input('dateStart'),
            'dateEnd'   =>  $request->input('dateEnd'),
            'name'      =>  $request->input('name'),
            'orderby'   =>  $request->input('orderby'),
        ];

        $query = Eventspp03::query();

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
            'nameEvent' => $item->nameEvent,
            'slug' => $item->slug,
            'city_id' => $item->city->id,
            'city_name' => $item->city->name,
            'place' => $item->place,
            'modality_id' => $item->modality->id,
            'modality_name' => $item->modality->name,
            'dateStartFormat' => $item->dateStart ? Carbon::parse($item->dateStart)->format('d/m/Y') : null,
            'dateEndFormat' => $item->dateEnd ? Carbon::parse($item->dateEnd)->format('d/m/Y') : null,
            'dateStart' => $item->dateStart ? $item->dateStart : null,
            'dateEnd' => $item->dateEnd ? $item->dateEnd : null,
            'hours' => $item->hours,
            'description' => $item->description,
            'created_at' => Carbon::parse($item->created_at)->format('d/m/Y')
        ];
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();

            // Verificar existencia de ciudad
            $city = City::find($data['city_id']);
            if (!$city) {
                return response()->json(['error' => 'Ciudad no encontrada'], 404);
            }

            // Crear código de fechas si existe dateEnd
            $codigoFechas = $data['dateStart'] . $data['dateEnd'];

            // Crear slug
            $slugParts = [
                Str::slug($city->name),
                Str::slug($data['nameEvent']),
            ];

            if ($codigoFechas) {
                $slugParts[] = $codigoFechas;
            }

            $slug = implode('-', $slugParts);

            // Agregar slug al array de datos
            $data['slug'] = $slug;

            // Actualizar evento
            Eventspp03::where('id', $id)->update($data);

            return response()->json(['message' => 'Evento actualizado correctamente', 'status' => 200]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al procesar la solicitud',
                'error' => $th->getMessage(),
                'status' => 500
            ], 500);
        }
    }
}
