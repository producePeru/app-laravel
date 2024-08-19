<?php

namespace App\Http\Controllers\Mype;
use App\Models\Mype;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Token;
use GuzzleHttp\Client;

class MypeController extends Controller
{
    public function index(Request $request)
    {
        $query = Mype::query();

        // Excluir registros donde el campo 'ruc' es null o vacío
        $query->whereNotNull('ruc')->where('ruc', '<>', '');

        if ($request->has('search')) {
            $search = $request->input('search');
            $fields = ['ruc', 'razonSocial', 'actividadEconomica', 'departamento', 'provincia', 'distrito', 'direccion'];

            $query->where(function ($q) use ($search, $fields) {
                foreach ($fields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        $mypes = $query->orderBy('id', 'desc')->paginate(100);

        return response()->json([ 'data' => $mypes, 'status' => 200 ]);
    }

    public function getApiInfo($numeroRUC)
    {
        $mype = Mype::where('ruc', $numeroRUC)->first();

        if ($mype) {
            if (empty($mype->razonSocial)) {

                $apiUrl = "https://api.apis.net.pe/v2/sunat/ruc/full?numero={$numeroRUC}";

                try {

                    $tokenRecord = Token::where('status', 1)->first();

                    if (!$tokenRecord) {
                        return response()->json(['status' => 404, 'message' => 'Token no encontrado o inactivo']);
                    }

                    $client = new Client();
                    $response = $client->request('GET', $apiUrl, [
                        'headers' => [
                            'Authorization' => $tokenRecord->token,
                            'Accept' => 'application/json',
                        ],
                    ]);

                    $resp = json_decode($response->getBody(), true);

                    $mype->razonSocial = $resp['razonSocial'];
                    $mype->estado = $resp['estado'];
                    $mype->condicion = $resp['condicion'];
                    $mype->numero = $resp['numero'];
                    $mype->ubigeo = $resp['ubigeo'];
                    $mype->actividadEconomica = $resp['actividadEconomica'];
                    $mype->direccion = $resp['direccion'];
                    $mype->lote = $resp['lote'];
                    $mype->manzana = $resp['manzana'];
                    $mype->distrito = $resp['distrito'];
                    $mype->provincia = $resp['provincia'];
                    $mype->departamento = $resp['departamento'];
                    $mype->tipo = $resp['tipo'];
                    $mype->tipoContabilidad = $resp['tipoContabilidad'];
                    $mype->numeroTrabajadores = $resp['numeroTrabajadores'];
                    $mype->tipoFacturacion = $resp['tipoFacturacion'];
                    $mype->comercioExterior = $resp['comercioExterior'];

                    $mype->save();

                    return response()->json(['status' => 200, 'message' => 'Datos actualizados']);

                } catch (\Exception $e) {
                    return response()->json([
                        'error' => 'No se pudo obtener información del MYPE',
                       'status' => 500,
                       'error' => $e
                    ], 500);
                }

            } else {
                return response()->json([
                   'message' => 'Está MYPE ya existe',
                ]);
            }
        }
    }

    public function getDataByRuc($ruc)
    {
        $mype = Mype::where('ruc', $ruc)->first();

        if ($mype) {
            return response()->json([
                'data' => $mype,
                'status' => 200
            ]);
        } else {
            return response()->json([
                'message' => 'Mype not found',
                'status' => 404
            ]);
        }
    }

    public function updateDataByRuc($id, Request $request)
    {
        $mype = Mype::where('id', $id)->first();

        if (!$mype) {
            return response()->json(['message' => 'RUC no encontrado en la tabla'], 404);
        }

        $mype->update($request->all());

        return response()->json(['message' => 'Datos actualizados con éxito', 'status' => 200]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'ruc' => 'max:11',
            'createdFrom' => 'string',
            'rasonSocial' => 'nullable|string', // Opcional
        ]);

        $user_role = getUserRole(); // 🚩 flat

        $existingMype = Mype::where('ruc', $validatedData['ruc'])->first();

        if ($existingMype) {
            // Si ya existe no lo crees
            return response()->json(['status' => 409, 'message' => 'Mype - RUC already exists']);
        }

        $mype = Mype::create([
            'razonSocial' => $request->input('rasonSocial'),
            'ruc' => $validatedData['ruc'],
            'user_id' => $user_role['user_id'],
            'createdFrom' => $validatedData['createdFrom'],
        ]);

        return response()->json(['status' => 200, 'message' => 'Mype created successfully']);
    }

}
