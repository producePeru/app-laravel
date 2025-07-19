<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\District;
use App\Models\Empresa;
use App\Models\Empresario;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Token;
use GuzzleHttp\Client;

class PublicEventsController extends Controller
{
    public function rucConsultCompany($ruc)
    {
        try {
            // Buscar en la base de datos local
            $empresa = Empresa::where('ruc', $ruc)->first([
                'ruc',
                'razonSocial',
                'tipoContribuyente_id',
                'sectorEconomico_id',
                'rubro_id',
                'actividadComercial_id',
                'region_id',
                'provincia_id',
                'distrito_id',
                'direccion',
                'estado',
                'condicion'
            ]);

            if ($empresa) {
                return response()->json(['status' => 200, 'data' => $empresa]);
            }

            $apiUrl = "https://api.apis.net.pe/v2/sunat/ruc/full?numero={$ruc}";
            $client = new Client();

            // Obtener todos los tokens ordenados por ID
            $tokens = Token::orderBy('id')->get();

            foreach ($tokens as $tokenRecord) {
                try {
                    $response = $client->request('GET', $apiUrl, [
                        'headers' => [
                            'Authorization' => $tokenRecord->token,
                            'Accept' => 'application/json',
                        ],
                        'timeout' => 5,
                    ]);

                    $resp = json_decode($response->getBody(), true);

                    if (isset($resp['numeroDocumento'])) {
                        // Activar este token y desactivar los demás
                        Token::where('id', '!=', $tokenRecord->id)->update(['status' => 0]);
                        $tokenRecord->update(['status' => 1]);

                        $region = City::where('name', $resp['departamento'])->first();
                        $provincia = Province::where('name', $resp['provincia'])->first();
                        $distrito = District::where('name', $resp['distrito'])->first();

                        $empresaData = [
                            'ruc' => $resp['numeroDocumento'],
                            'razonSocial' => $resp['razonSocial'],
                            'tipoContribuyente_id' => null,
                            'sectorEconomico_id' => null,
                            'rubro_id' => null,
                            'actividadComercial_id' => null,
                            'region_id' => $region?->id,
                            'provincia_id' => null,
                            'distrito_id' => null,
                            'direccion' => $resp['direccion'],
                            'estado' => $resp['estado'] ?? null,
                            'condicion' => $resp['condicion'] ?? null
                        ];

                        return response()->json(['status' => 200, 'data' => $empresaData]);
                    }

                    break; // Salimos del bucle si no hay datos válidos pero sin error
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    $statusCode = $e->getResponse()->getStatusCode();

                    if ($statusCode == 429) {
                        // Token alcanzó su límite → desactivar
                        $tokenRecord->update(['status' => 0]);
                        continue; // Probar siguiente token
                    } else {
                        throw $e; // Otro error, relanzar
                    }
                }
            }

            return response()->json(['status' => 404, 'message' => 'Empresa no encontrada o sin tokens válidos']);
        } catch (\Exception $e) {
            Log::error('Error en rucConsultCompany: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Error al buscar la empresa',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function dniConsultBusinessman($dni)
    {
        try {
            // Buscar empresario local
            $empresario = Empresario::where('dni', $dni)->first([
                'typedocument_id',
                'dni',
                'name',
                'lastname',
                'middlename',
                'gender_id',
                'birthday'
            ]);

            if ($empresario) {
                return response()->json(['status' => 200, 'data' => $empresario]);
            }

            $apiUrl = "https://api.apis.net.pe/v2/reniec/dni?numero={$dni}";
            $client = new Client();

            // Obtener todos los tokens (orden ascendente por id para tener prioridad)
            $tokens = Token::orderBy('id')->get();

            foreach ($tokens as $tokenRecord) {
                try {
                    $response = $client->request('GET', $apiUrl, [
                        'headers' => [
                            'Authorization' => $tokenRecord->token,
                            'Accept' => 'application/json',
                        ],
                        'timeout' => 5,
                    ]);

                    $resp = json_decode($response->getBody(), true);

                    if (isset($resp['numeroDocumento'])) {
                        // Marcar todos los demás tokens como inactivos
                        Token::where('id', '!=', $tokenRecord->id)->update(['status' => 0]);

                        // Marcar este token como activo
                        $tokenRecord->update(['status' => 1]);

                        // Datos del empresario
                        $businessmanData = [
                            'typedocument_id' => 1,
                            'dni' => $resp['numeroDocumento'],
                            'name' => $resp['nombres'],
                            'lastname' => $resp['apellidoPaterno'],
                            'middlename' => $resp['apellidoMaterno'],
                            'gender_id' => null,
                            'birthday' => null
                        ];

                        return response()->json(['status' => 200, 'data' => $businessmanData]);
                    }

                    break; // Si obtuvo datos pero no válidos, salir
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    $statusCode = $e->getResponse()->getStatusCode();

                    if ($statusCode == 429) {
                        // Token superó su límite → lo desactivamos
                        $tokenRecord->update(['status' => 0]);
                        continue; // Probar siguiente token
                    } else {
                        throw $e;
                    }
                }
            }

            return response()->json(['status' => 404, 'message' => 'DNI no encontrado o sin tokens válidos']);
        } catch (\Exception $e) {
            Log::error('Error en dniConsultBusinessman: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Error al buscar el empresario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
