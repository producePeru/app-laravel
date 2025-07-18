<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSedRequest;
use App\Models\City;
use App\Models\District;
use App\Models\Empresa;
use App\Models\Empresario;
use App\Models\Fair;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Token;
use App\Models\UgsePostulante;
use GuzzleHttp\Client;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PublicEventsController extends Controller
{
    public function rucConsultCompany($ruc)
    {
        try {
            // Buscar en la base de datos local
            $empresa = Empresa::where('ruc', $ruc)->first([
                'ruc',
                'razonSocial',
                'sectorEconomico_id',
                'rubro_id',
                'actividadComercial_id',
                'region_id',
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

                        $empresaData = [
                            'ruc' => $resp['numeroDocumento'],
                            'razonSocial' => $resp['razonSocial'],
                            'sectorEconomico_id' => null,
                            'rubro_id' => null,
                            'actividadComercial_id' => null,
                            'region_id' => $region?->id,
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



    public function participantRegistrationSed(StoreSedRequest $request)
    {
        try {
            // Verificar si el 'slug' existe en el modelo 'Fair'
            $fair = Fair::where('slug', $request->slug)->firstOrFail();

            // Aquí asignamos el 'event_id' con el ID del evento encontrado
            $request->merge(['event_id' => $fair->id]);

            // Verificar si ya existe un postulante con el mismo 'event_id' y 'documentnumber'
            $existingPostulante = UgsePostulante::where('event_id', $request->event_id)
                ->where('documentnumber', $request->documentnumber)
                ->first();

            if ($existingPostulante) {
                // Si el postulante ya existe, se actualizan los datos
                $existingPostulante->update($request->all());  // Usa all() para actualizar con todos los datos

                return response()->json([
                    'message' => 'Postulante actualizado correctamente',
                    'data' => $existingPostulante,
                    'status' => 200
                ], 200);
            } else {
                // Si no existe, creamos el nuevo postulante
                $ugsePostulante = UgsePostulante::create($request->all());

                return response()->json([
                    'message' => 'Postulante creado correctamente',
                    'data' => $ugsePostulante,
                    'status' => 200
                ], 200);
            }
        } catch (ModelNotFoundException $e) {
            // Si el slug no se encuentra en la tabla 'Fair', devuelve un error
            return response()->json([
                'message' => 'El evento con el slug proporcionado no existe.',
                'status' => 404
            ], 404);
        } catch (ValidationException $e) {
            // Si ocurre un error de validación (por ejemplo, datos incorrectos o faltantes)
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(), // Los errores de validación
                'status' => 422 // Código HTTP para errores de validación
            ], 422);
        } catch (\Exception $e) {
            // Si ocurre cualquier otro tipo de error
            return response()->json([
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }
}
