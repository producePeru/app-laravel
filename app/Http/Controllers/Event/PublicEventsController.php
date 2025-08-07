<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSedRequest;
use App\Models\City;
use App\Models\District;
use App\Models\Empresa;
use App\Models\Empresario;
use App\Models\Fair;
use App\Models\Mype;
use App\Models\People;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Token;
use App\Models\UgsePostulante;
use GuzzleHttp\Client;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
// pdf
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Mail\FairSedInfoMail;
use App\Models\Attendance;
use App\Models\AttendanceList;
use PDF; // Alias para DomPDF (probablemente registrado en config/app.php como 'PDF')
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\DB;

class PublicEventsController extends Controller
{
    public function rucConsultCompany($ruc)
    {
        try {

            // Buscar en la base de datos local
            $empresa = Mype::where('ruc', $ruc)->first([
                'ruc',
                'socialReason',                 // razon social
                'economicsector_id',            // id sector economico
                'category_id',                  // id rubro
                'comercialactivity_id',         // id actividad comercial
                'city_id',                      // id region
                'address',                      // direccion
                'nameService',                  // estado
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
                            'socialReason' => $resp['razonSocial'],
                            'economicsector_id' => null,
                            'category_id' => null,
                            'comercialactivity_id' => null,
                            'city_id' => $region?->id,
                            'address' => $resp['direccion'],
                            'nameService' => $resp['estado'] ?? null,
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
            return response()->json([
                'status' => 500,
                'message' => 'No se encontraron datos de la empresa con el RUC proporcionado',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function dniConsultBusinessman($dni)
    {
        try {
            // Buscar empresario local
            $empresario = People::where('documentnumber', $dni)->first([
                'typedocument_id',
                'documentnumber',
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
                            'documentnumber' => $resp['numeroDocumento'],
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
                'message' => 'No se pudo verificar al empresario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function isThisUserRegistered(Request $request)
    {
        try {
            $fair = Fair::where('slug', $request->slug)->firstOrFail();

            $existingPostulanteExists = UgsePostulante::where('documentnumber', $request->documentnumber)
                ->where('event_id', $fair->id)
                ->exists();

            if ($existingPostulanteExists) {
                return response()->json([
                    'message' => 'El usuario ya está registrado en este evento.',
                    'status' => 200
                ]);
            } else {
                return response()->json([
                    'message' => 'Este usuario ya esta inscrito en este evento.',
                    'status' => 404
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error inesperado: ' . $e->getMessage(),
                'status' => 'error',
                'code' => 500
            ], 500);
        }
    }

    public function isThisUserRegisteredMercado(Request $request)
    {
        try {

            $event = Attendance::where('slug', $request->slug)->firstOrFail();

            $existingPostulanteExists = AttendanceList::where('documentnumber', $request->documentnumber)
                ->where('attendancelist_id', $event->id)
                ->exists();

            if ($existingPostulanteExists) {
                return response()->json([
                    'message' => 'El usuario ya está registrado en este evento.',
                    'status' => 200
                ]);
            } else {
                return response()->json([
                    'message' => 'Usuario Nuevo.',
                    'status' => 404
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error inesperado: ' . $e->getMessage(),
                'status' => 'error',
                'code' => 500
            ], 500);
        }
    }


    public function participantRegistrationSed(StoreSedRequest $request)
    {
        try {
            $fair = Fair::where('slug', $request->slug)->firstOrFail();
            $request->merge(['event_id' => $fair->id]);

            // Crear el nuevo postulante
            $ugsePostulante = UgsePostulante::create($request->all());

            if (!$ugsePostulante) {
                return response()->json([
                    'message' => 'Error al registrar al postulante.',
                    'status' => 500
                ], 500);
            }

            $mailer = $request->mailer ?? 'digitalization';

            // Codificar logo en base64
            $logoPath = public_path('images/logo/sed.png');
            $logoBase64 = base64_encode(file_get_contents($logoPath));
            $logoMime = mime_content_type($logoPath);
            $logoDataUri = "data:$logoMime;base64,$logoBase64";

            // Generar QR en base64
            $qrResult = Builder::create()
                ->writer(new PngWriter())
                ->data($ugsePostulante->documentnumber)
                ->size(200)
                ->margin(10)
                ->build();

            $qrBase64 = base64_encode($qrResult->getString());

            $qrResult = Builder::create()
                ->writer(new PngWriter())
                ->data($ugsePostulante->documentnumber)
                ->size(200)
                ->margin(10)
                ->build();


            $qrBase64 = base64_encode($qrResult->getString());

            // Generar PDF
            $pdf = PDF::loadView('pdf.ticket_entry', [
                'fair' => $fair,
                'participantName' => "{$ugsePostulante->name} {$ugsePostulante->lastname}",
                'qrBase64' => $qrBase64,
                'logoDataUri' => $logoDataUri,
            ]);

            $filename = 'entrada_' . Str::random(10) . '.pdf';
            $filepath = storage_path("app/public/entradas/{$filename}");
            Storage::makeDirectory('public/entradas');
            $pdf->save($filepath);

            $participantName = "{$ugsePostulante->name} {$ugsePostulante->lastname}";
            $messageContent = strip_tags($fair->msgSendEmail);

            Mail::mailer($mailer)
                ->to($ugsePostulante->email)
                ->send(new FairSedInfoMail(
                    $messageContent,
                    $filepath,
                    $participantName,
                    $fair
                ));

            return response()->json([
                'message' => 'Postulante creado correctamente y correo enviado.',
                'data' => $ugsePostulante,
                'status' => 200
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'El evento con el slug proporcionado no existe.',
                'status' => 404
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    // pregunta & respuesta de formalizacion 
    public function formalizationsQuestionsAndAnswers(Request $request)
    {
        try {
            // Validación de campos esperados
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'question_1' => 'required|string',
                'answer_1' => 'required|string',
                'question_2' => 'required|string',
                'answer_2' => 'required|string',
                'question_3' => 'required|string',
                'answer_3' => 'required|string',
                'question_4' => 'required|string',
                'answer_4' => 'required|string',
                'question_5' => 'required|string',
                'answer_5' => 'required|string',
            ]);

            $entries = [];

            for ($i = 1; $i <= 5; $i++) {
                $entries[] = [
                    'user_id'    => $request->user_id,
                    'question'   => $request->input("question_$i"),
                    'answer'     => $request->input("answer_$i"),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('questions_answers')->insert($entries);

            return response()->json([
                'message' => 'Preguntas y respuestas registradas correctamente.',
                'status'  => 200
            ]);
        } catch (\Exception $e) {
            // Registrar el error para debugging
            Log::error('Error al registrar preguntas y respuestas: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ocurrió un error al guardar los datos.',
                'error'   => $e->getMessage(),
                'status'  => 500
            ], 500);
        }
    }
}
