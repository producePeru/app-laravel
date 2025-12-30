<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\SedQuestionStoreRequest;
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
use App\Models\SedQuestion;
use PDF; // Alias para DomPDF (probablemente registrado en config/app.php como 'PDF')
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PublicEventsController extends Controller
{
    public function rucConsultCompany($ruc)
    {
        try {

            $empresa = Mype::where('ruc', $ruc)->first();

            if (!$empresa) {

                $apiUrl = "https://api.decolecta.com/v1/sunat/ruc?numero={$ruc}";

                $tokens = Token::where('name', 'decolecta')
                    ->pluck('token')
                    ->toArray();

                $client = new Client();

                $responseData = null;

                foreach ($tokens as $token) {

                    try {
                        $response = $client->request('GET', $apiUrl, [
                            'headers' => [
                                'Authorization' => $token,
                                'Accept' => 'application/json',
                            ],
                            'timeout' => 5,
                        ]);

                        $responseData = json_decode($response->getBody(), true);

                        if (!empty($responseData['numero_documento'])) {
                            break;
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }


                if ($responseData && !empty($responseData['numero_documento'])) {
                    return response()->json([
                        'status' => 200,
                        'message' => 'Información obtenida',
                        'data' => [
                            'ruc'                   => $responseData['numero_documento'] ?? null,
                            'socialReason'          => $responseData['razon_social'] ?? null,
                            'comercialName'         => $responseData['razon_social'] ?? null,
                            'economicsector_id'     => null,
                            'category_id'           => null,
                            'comercialactivity_id'  => null,
                            'city_id'               => null,
                            'address'               => $responseData['direccion'] ?? null,
                            'estado'                => $responseData['estado'] ?? null,
                            'condicion'             => $responseData['condicion']
                        ]
                    ]);
                } else {
                    return response()->json([
                        'status' => 404,
                        'message' => 'No se pudo obtener información con los tokens disponibles 404'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 200,
                    'message' => 'Usuario',
                    'data' => [
                        'name'                  => $empresa->ruc ?? null,
                        'socialReason'          => $empresa->socialReason ?? null,
                        'comercialName'         => $empresa->comercialName ?? null,
                        'economicsector_id'     => $empresa->economicsector_id,
                        'category_id'           => $empresa->category_id ?? null,
                        'comercialactivity_id'  => $empresa->comercialactivity_id ?? null,
                        'city_id'               => $empresa->city_id ?? null,
                        'address'               => $empresa->address ?? null,
                        'estado'                => $empresa->estado ?? null,
                        'condicion'             => $empresa->condicion ?? null
                    ]
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al procesar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function dniConsultBusinessman($dni)
    {
        try {
            $person = People::where('documentnumber', $dni)->first();

            if (!$person) {

                $apiUrl = "https://api.decolecta.com/v1/reniec/dni?numero={$dni}";

                $tokens = Token::where('name', 'decolecta')
                    ->pluck('token')
                    ->toArray();

                $client = new Client();
                $responseData = null;

                foreach ($tokens as $token) {
                    try {
                        $response = $client->request('GET', $apiUrl, [
                            'headers' => [
                                'Authorization' => $token,
                                'Accept' => 'application/json',
                            ],
                            'timeout' => 5,
                        ]);

                        $responseData = json_decode($response->getBody(), true);

                        if (!empty($responseData['document_number'])) {
                            break;
                        }
                    } catch (\Exception $e) {
                        // Si hay error, pasa al siguiente token
                        continue;
                    }
                }



                if ($responseData && !empty($responseData['document_number'])) {
                    return response()->json([
                        'status' => 200,
                        'message' => 'Información obtenida',
                        'data' => [
                            'numeroDocumento' => $responseData['document_number'],
                            'name' => $responseData['first_name'] ?? null,
                            'lastname' => $responseData['first_last_name'] ?? null,
                            'middlename' => $responseData['second_last_name'] ?? null,
                            'gender_id' => null,
                            'sick' => null,
                            'phone' => null,
                            'email' => null
                        ]
                    ]);
                } else {
                    return response()->json([
                        'status' => 404,
                        'message' => 'No se pudo obtener información con los tokens disponibles'
                    ]);
                }
            } else {
                // Si se encuentra
                return response()->json([
                    'status' => 200,
                    'message' => 'Usuario',
                    'data' => [
                        'name' => $person->name ?? null,
                        'lastname' => $person->lastname ?? null,
                        'middlename' => $person->middlename ?? null,
                        'gender_id' => $person->gender_id ?? null,
                        'sick' => $person->sick ?? null,
                        'phone' => $person->phone ?? null,
                        'email' => $person->email ?? null
                    ]
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al procesar la solicitud',
                'error' => $th->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    // public function dniConsultBusinessman($dni)
    // {
    //     try {
    //         $person = People::where('documentnumber', $dni)->first();

    //         if (!$person) {

    //             $user = auth()->user();

    //             $apiUrl = "https://api.decolecta.com/v1/reniec/dni?numero={$dni}";


    //             if ($user) {
    //                 $tokens = Token::where('user_id', $user->id)     // Tokens del usuario autenticado
    //                     ->pluck('token')
    //                     ->toArray();
    //             } else {
    //                 $tokens = Token::inRandomOrder()                // No hay sesión → usar tokens de cualquier usuario
    //                     ->pluck('token')
    //                     ->toArray();
    //             }

    //             // 3. Si no hay tokens en la base de datos
    //             if (empty($tokens)) {
    //                 return response()->json([
    //                     'status'  => 404,
    //                     'message' => 'No existen tokens registrados'
    //                 ], 404);
    //             }

    //             $client = new Client();

    //             $responseData = null;

    //             foreach ($tokens as $token) {
    //                 try {
    //                     $response = $client->request('GET', $apiUrl, [
    //                         'headers' => [
    //                             'Authorization' => $token,
    //                             'Accept' => 'application/json',
    //                         ],
    //                         'timeout' => 5,
    //                     ]);

    //                     $responseData = json_decode($response->getBody(), true);

    //                     if (!empty($responseData['document_number'])) {
    //                         break;
    //                     }
    //                 } catch (\Exception $e) {
    //                     // Si hay error, pasa al siguiente token
    //                     continue;
    //                 }
    //             }


    //             if ($responseData && !empty($responseData['document_number'])) {
    //                 return response()->json([
    //                     'status' => 200,
    //                     'message' => 'Información obtenida',
    //                     'data' => [
    //                         'numeroDocumento' => $responseData['document_number'],
    //                         'name' => $responseData['first_name'] ?? null,
    //                         'lastname' => $responseData['first_last_name'] ?? null,
    //                         'middlename' => $responseData['second_last_name'] ?? null,
    //                         'gender_id' => null,
    //                         'sick' => null,
    //                         'phone' => null,
    //                         'email' => null
    //                     ]
    //                 ]);
    //             } else {
    //                 return response()->json([
    //                     'status' => 404,
    //                     'message' => 'No se pudo obtener información con los tokens disponibles'
    //                 ]);
    //             }
    //         } else {
    //             // Si se encuentra
    //             return response()->json([
    //                 'status' => 200,
    //                 'message' => 'Usuario',
    //                 'data' => [
    //                     'name' => $person->name ?? null,
    //                     'lastname' => $person->lastname ?? null,
    //                     'middlename' => $person->middlename ?? null,
    //                     'gender_id' => $person->gender_id ?? null,
    //                     'sick' => $person->sick ?? null,
    //                     'phone' => $person->phone ?? null,
    //                     'email' => $person->email ?? null
    //                 ]
    //             ]);
    //         }
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'message' => 'Error al procesar la solicitud',
    //             'error' => $th->getMessage(),
    //             'status' => 500
    //         ], 500);
    //     }
    // }

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
                    'message' => 'Nuevo participante.',
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


    // public function participantRegistrationSed(StoreSedRequest $request)
    // {
    //     try {
    //         $fair = Fair::where('slug', $request->slug)->firstOrFail();
    //         $request->merge(['event_id' => $fair->id]);

    //         // Crear el nuevo postulante
    //         $ugsePostulante = UgsePostulante::create($request->all());

    //         if (!$ugsePostulante) {
    //             return response()->json([
    //                 'message' => 'Error al registrar al postulante.',
    //                 'status' => 500
    //             ], 500);
    //         }

    //         $mailer = $request->mailer ?? 'hostinger';

    //         // Codificar logo en base64
    //         $logoPath = public_path('images/logo/sed.png');
    //         $logoBase64 = base64_encode(file_get_contents($logoPath));
    //         $logoMime = mime_content_type($logoPath);
    //         $logoDataUri = "data:$logoMime;base64,$logoBase64";

    //         // Generar QR en base64
    //         $qrResult = Builder::create()
    //             ->writer(new PngWriter())
    //             ->data($ugsePostulante->documentnumber)
    //             ->size(200)
    //             ->margin(10)
    //             ->build();

    //         $qrBase64 = base64_encode($qrResult->getString());

    //         $qrResult = Builder::create()
    //             ->writer(new PngWriter())
    //             ->data($ugsePostulante->documentnumber)
    //             ->size(200)
    //             ->margin(10)
    //             ->build();


    //         $qrBase64 = base64_encode($qrResult->getString());

    //         // Generar PDF
    //         $pdf = PDF::loadView('pdf.ticket_entry', [
    //             'fair' => $fair,
    //             'participantName' => "{$ugsePostulante->name} {$ugsePostulante->lastname}",
    //             'qrBase64' => $qrBase64,
    //             'logoDataUri' => $logoDataUri,
    //         ]);

    //         $filename = 'entrada_' . Str::random(10) . '.pdf';
    //         $filepath = storage_path("app/public/entradas/{$filename}");
    //         Storage::makeDirectory('public/entradas');
    //         $pdf->save($filepath);

    //         $participantName = "{$ugsePostulante->name} {$ugsePostulante->lastname}";
    //         $messageContent = strip_tags($fair->msgSendEmail);

    //         Mail::mailer($mailer)
    //             ->to($ugsePostulante->email)
    //             ->send(new FairSedInfoMail(
    //                 $messageContent,
    //                 $filepath,
    //                 $participantName,
    //                 $fair
    //             ));

    //         return response()->json([
    //             'message' => 'Postulante creado correctamente y correo enviado.',
    //             'data' => $ugsePostulante,
    //             'status' => 200
    //         ], 200);
    //     } catch (ModelNotFoundException $e) {
    //         return response()->json([
    //             'message' => 'El evento con el slug proporcionado no existe.',
    //             'status' => 404
    //         ], 404);
    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'message' => 'Validation error',
    //             'errors' => $e->errors(),
    //             'status' => 422
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Unexpected error: ' . $e->getMessage(),
    //             'status' => 500
    //         ], 500);
    //     }
    // }

    public function participantRegistrationSed(StoreSedRequest $request)
    {
        try {
            // Buscar evento (Feria)
            $fair = Fair::where('slug', $request->slug)->firstOrFail();
            $request->merge(['event_id' => $fair->id]);

            // Buscar si ya existe participante con el mismo evento y documento
            $existing = UgsePostulante::where('event_id', $fair->id)
                ->where('documentnumber', $request->documentnumber)
                ->first();

            // Si ya existe, reutilizamos ese registro (no crear otro)
            if ($existing) {
                $ugsePostulante = $existing;
                $alreadyRegistered = true;
            } else {
                // Crear nuevo postulante
                $ugsePostulante = UgsePostulante::create($request->all());
                $alreadyRegistered = false;

                if (!$ugsePostulante) {
                    return response()->json([
                        'message' => 'Error al registrar al postulante.',
                        'status' => 500
                    ], 500);
                }
            }

            $mailer = $request->mailer ?? 'hostinger';

            // Codificar logo en base64
            $logoPath = public_path('images/logo/sed.png');
            $logoBase64 = base64_encode(file_get_contents($logoPath));
            $logoMime = mime_content_type($logoPath);
            $logoDataUri = "data:$logoMime;base64,$logoBase64";

            // Generar QR en base64 (documentnumber)
            $qrResult = Builder::create()
                ->writer(new PngWriter())
                ->data($ugsePostulante->documentnumber)
                ->size(200)
                ->margin(10)
                ->build();

            $qrBase64 = base64_encode($qrResult->getString());

            // Generar PDF de entrada
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

            // Enviar correo
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

            // 🧩 Mensaje adaptado según si fue nuevo o ya registrado
            $msg = $alreadyRegistered
                ? 'El participante ya estaba registrado. Se reenviaron los datos y el correo.'
                : 'Postulante creado correctamente y correo enviado.';

            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => $ugsePostulante,
                'status' => 200
            ], 200);
        }

        // Manejo de errores
        catch (ModelNotFoundException $e) {
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



    public function finallyQuestionsExtrasSed(SedQuestionStoreRequest $request)
    {
        $fair = Fair::where('slug', $request->slug)->firstOrFail();

        // Evita duplicados manualmente (solo crear)
        $exists = SedQuestion::where('event_id', $fair->id)
            ->where('documentnumber', $request->documentnumber)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ya existe un registro para este evento y documento.'
            ], 409); // Conflict
        }

        // Crear
        $sedQuestion = SedQuestion::create([
            'event_id'       => $fair->id,
            'slug'           => $request->slug,
            'documentnumber' => $request->documentnumber,
            'question_1'     => $request->question_1,
            'question_2'     => $request->question_2,
            'question_3'     => $request->question_3,
            'question_4'     => $request->question_4,
            'question_5'     => $request->question_5,
        ]);

        return response()->json([
            'message' => 'Creado correctamente',
            'data'    => $sedQuestion,
            'status'  => 200
        ], 200);
    }






    // preguntamos si el eveento existe

    public function existEvent($slug, $typeId)
    {
        try {
            $today = Carbon::now();

            $fair = Fair::where('slug', $slug)->where('fairtype_id', $typeId)->first();

            if ($fair) {
                if ($today->gt(Carbon::parse($fair->endDate)->endOfDay())) {
                    return response()->json([
                        'data' => [
                            'title' => '¡Evento Finalizado!',
                            'message' => '
                            El evento que estabas buscando ya ha caducado o no se encuentra disponible en este momento. </br>
                            Pero no te detengas 🚀, </br>
                            nuevas oportunidades están en camino.</br>
                            Sigue atento(a) a nuestros próximos talleres, capacitaciones y eventos </br>
                            para seguir fortaleciendo tu emprendimiento.
                            ',
                            'status' => 404
                        ]
                    ]);
                }

                return response()->json([
                    'data' => [
                        'slug' => $fair->slug,
                        'title' => $fair->title,
                        'subTitle' => $fair->subTitle,
                        'description' => $fair->description,
                        'modality' => $fair->modality,
                        'typeFair' => $fair->fairtype_id,
                        'fecha' => $fair->fecha,
                        'place' => $fair->place,
                        'schedule' => $fair->hours
                    ],
                    'status' => 200
                ]);
            }

            return response()->json([
                'data' => [
                    'title' => 'No se encontró el evento.',
                    'message' => 'No existe una feria con este registro.',
                    'status' => 404
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los detalles del evento.',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }
}
