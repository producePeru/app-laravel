<?php

namespace App\Http\Controllers\Mype;

use App\Models\Mype;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Token;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;

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

        return response()->json(['data' => $mypes, 'status' => 200]);
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



    // registrar una mype para que participe en ferias si existe la editas sino la creas

    public function registerMype(Request $request)
    {
        $mype = Mype::where('ruc', $request->ruc)->firstOrNew(['ruc' => $request->ruc]);

        $mype->fill($request->all());

        $storagePath = 'public/mypes';

        // Procesar y guardar filePDF
        if ($request->hasFile('filePDF')) {
            $filePDF = $request->file('filePDF');
            $filePDFName = $filePDF->getClientOriginalName();
            $filePDFPath = $filePDF->storeAs($storagePath, $filePDFName);
            $mype->filePDF_name = $filePDFName;
            $mype->filePDF_path = $filePDFPath;
        }

        // Procesar y guardar logo
        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $logoName = $logo->getClientOriginalName();
            $logoPath = $logo->storeAs($storagePath, $logoName);
            $mype->logo_name = $logoName;
            $mype->logo_path = $logoPath;
        }

        // Procesar y guardar img1
        if ($request->hasFile('img1')) {
            $img1 = $request->file('img1');
            $img1Name = $img1->getClientOriginalName();
            $img1Path = $img1->storeAs($storagePath, $img1Name);
            $mype->img1_name = $img1Name;
            $mype->img1_path = $img1Path;
        }

        // Procesar y guardar img2
        if ($request->hasFile('img2')) {
            $img2 = $request->file('img2');
            $img2Name = $img2->getClientOriginalName();
            $img2Path = $img2->storeAs($storagePath, $img2Name);
            $mype->img2_name = $img2Name;
            $mype->img2_path = $img2Path;
        }

        // Procesar y guardar img3
        if ($request->hasFile('img3')) {
            $img3 = $request->file('img3');
            $img3Name = $img3->getClientOriginalName();
            $img3Path = $img3->storeAs($storagePath, $img3Name);
            $mype->img3_name = $img3Name;
            $mype->img3_path = $img3Path;
        }

        $mype->save();

        return response()->json(['message' => 'Mype registrado/actualizado exitosamente.', 'id_mype' => $mype->id, 'status' => 200]);
    }

    // SI EXISTE LO EDITAS SINO LO EDITAS
    public function apiRUC($numeroRUC)
    {

        $mype = Mype::where('ruc', $numeroRUC)->first();

        if (!$mype) {
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

                $responseData = json_decode($response->getBody(), true);

                return response()->json([
                    'status' => 200,
                    'message' => 'Información de MYPE obtenida',
                    'data' => [
                        'comercialName' => null,
                        'socialReason' => $responseData['razonSocial'] ?? null,
                        'web' => null,
                        'facebook' => null,
                        'instagram' => null,
                        'description' => null,
                        'address' => $responseData['direccion'] ?? null,
                    ]
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'No se pudo obtener información del MYPE',
                    'status' => 500,
                    'error' => $e->getMessage()
                ], 500);
            }
        } else {
            // Si se encuentra la MYPE
            return response()->json([
                'status' => 200,
                'message' => 'La MYPE ya existe',
                'data' => [
                    'comercialName' => $mype->comercialName ?? null,
                    'socialReason' => $mype->socialReason ?? null,
                    'web' => $mype->web ?? null,
                    'facebook' => $mype->facebook ?? null,
                    'instagram' => $mype->instagram ?? null,
                    'description' => $mype->description ?? null,
                    'address' => $mype->address ?? null,
                ]
            ]);
        }
    }
}
