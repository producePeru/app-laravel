<?php

namespace App\Http\Controllers;

use App\Models\Mype;
use Illuminate\Http\Request;
use App\Http\Requests\StoreMypeRequest;
use App\Http\Requests\UpdateMypeRequest;
use App\Http\Resources\MypeCollection;
use App\Http\Resources\MypeResource;
use App\Filters\MypeFilter;
use Illuminate\Database\QueryException;
use GuzzleHttp\Client;

class MypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $mype = Mype::paginate(50);
        return new MypeCollection($mype);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMypeRequest $request)
    {
        try {
            $mype = Mype::create($request->all());
            return response()->json(['message' => 'Mype creada correctamente', 'data' => $mype], 201);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error al crear la Mype. Por favor, inténtalo de nuevo.', $e], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al crear la Mype.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Mype $mype)
    {
        //
    }

    public function dataMypeRuc($ruc)
    {
        $mype = Mype::where('ruc', $ruc)->first();

        if (!$mype) {
            return response()->json(['message' => 'not found', 'status' => 404], 404);
        }

        return response()->json(['data' => $mype]);
    }

    public function getDataFromExternalApi(Request $request, $ruc)
    {
        $apiUrl = "https://api.apis.net.pe/v2/sunat/ruc?numero={$ruc}";

        try {
            $client = new Client();
            $response = $client->request('GET', $apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer apis-token-6688.nekxM8GmGEHYD9qosrnbDWNxQlNOzaT5', 
                    'Accept' => 'application/json',
                ],
                
            ]);

            $data = json_decode($response->getBody(), true);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mype $mype)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMypeRequest $request, Mype $mype)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mype $mype)
    {
        //
    }
}
