<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\SedQuestionStoreRequest;
use App\Http\Requests\StoreSedRequest;
use App\Mail\FairSedInfoMail;
use App\Models\ActividadPnte;
use App\Models\Attendance;
use App\Models\AttendanceList;
use App\Models\Empresario;
use App\Models\EmpresarioActividad;
use App\Models\EmpresarioEmprendimiento;
use App\Models\Fair;
use App\Models\Mype;
use App\Models\People;
use App\Models\SedQuestion;
use App\Models\Token;
use App\Models\UgsePostulante;
use Carbon\Carbon;
// pdf
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail; // Alias para DomPDF (probablemente registrado en config/app.php como 'PDF')
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PDF;

class PublicEventsController extends Controller
{
    public function rucConsultCompany($ruc)
    {
        try {

            $empresa = Mype::where('ruc', $ruc)->first();

            if (! $empresa) {

                $apiUrl = "https://api.decolecta.com/v1/sunat/ruc?numero={$ruc}";

                $tokens = Token::where('name', 'decolecta')
                    ->pluck('token')
                    ->toArray();

                $client = new Client;

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

                        if (! empty($responseData['numero_documento'])) {
                            break;
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                if ($responseData && ! empty($responseData['numero_documento'])) {
                    return response()->json([
                        'status' => 200,
                        'message' => 'Información obtenida',
                        'data' => [
                            'ruc' => $responseData['numero_documento'] ?? null,
                            'socialReason' => $responseData['razon_social'] ?? null,
                            'comercialName' => $responseData['razon_social'] ?? null,
                            'economicsector_id' => null,
                            'category_id' => null,
                            'comercialactivity_id' => null,
                            'city_id' => null,
                            'address' => $responseData['direccion'] ?? null,
                            'estado' => $responseData['estado'] ?? null,
                            'condicion' => $responseData['condicion'],
                            'data' => $responseData,
                        ],
                    ]);
                } else {
                    return response()->json([
                        'status' => 404,
                        'message' => 'No se pudo obtener información con los tokens disponibles 404',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 200,
                    'message' => 'Usuario',
                    'data' => [
                        'name' => $empresa->ruc ?? null,
                        'socialReason' => $empresa->socialReason ?? null,
                        'comercialName' => $empresa->comercialName ?? null,
                        'economicsector_id' => $empresa->economicsector_id,
                        'category_id' => $empresa->category_id ?? null,
                        'comercialactivity_id' => $empresa->comercialactivity_id ?? null,
                        'city_id' => $empresa->city_id ?? null,
                        'address' => $empresa->address ?? null,
                        'estado' => $empresa->estado ?? null,
                        'condicion' => $empresa->condicion ?? null,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al procesar la solicitud',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function dniConsultBusinessman($dni)
    {
        try {
            $person = People::where('documentnumber', $dni)->first();

            if (! $person) {

                $apiUrl = "https://api.decolecta.com/v1/reniec/dni?numero={$dni}";

                $tokens = Token::where('name', 'decolecta')
                    ->pluck('token')
                    ->toArray();

                $client = new Client;
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

                        if (! empty($responseData['document_number'])) {
                            break;
                        }
                    } catch (\Exception $e) {
                        // Si hay error, pasa al siguiente token
                        continue;
                    }
                }

                if ($responseData && ! empty($responseData['document_number'])) {
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
                            'email' => null,
                        ],
                    ]);
                } else {
                    return response()->json([
                        'status' => 404,
                        'message' => 'No se pudo obtener información con los tokens disponibles',
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
                        'email' => $person->email ?? null,
                    ],
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al procesar la solicitud',
                'error' => $th->getMessage(),
                'status' => 500,
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
                    'status' => 200,
                ]);
            } else {
                return response()->json([
                    'message' => 'Nuevo participante.',
                    'status' => 404,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error inesperado: '.$e->getMessage(),
                'status' => 'error',
                'code' => 500,
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
                    'status' => 200,
                ]);
            } else {
                return response()->json([
                    'message' => 'Usuario Nuevo.',
                    'status' => 404,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error inesperado: '.$e->getMessage(),
                'status' => 'error',
                'code' => 500,
            ], 500);
        }
    }

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

                if (! $ugsePostulante) {
                    return response()->json([
                        'message' => 'Error al registrar al postulante.',
                        'status' => 500,
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
                ->writer(new PngWriter)
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

            $filename = 'entrada_'.Str::random(10).'.pdf';
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
                'status' => 200,
            ], 200);
        }

        // Manejo de errores
        catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'El evento con el slug proporcionado no existe.',
                'status' => 404,
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                'status' => 422,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Unexpected error: '.$e->getMessage(),
                'status' => 500,
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
                    'user_id' => $request->user_id,
                    'question' => $request->input("question_$i"),
                    'answer' => $request->input("answer_$i"),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('questions_answers')->insert($entries);

            return response()->json([
                'message' => 'Preguntas y respuestas registradas correctamente.',
                'status' => 200,
            ]);
        } catch (\Exception $e) {
            // Registrar el error para debugging
            Log::error('Error al registrar preguntas y respuestas: '.$e->getMessage());

            return response()->json([
                'message' => 'Ocurrió un error al guardar los datos.',
                'error' => $e->getMessage(),
                'status' => 500,
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
                'message' => 'Ya existe un registro para este evento y documento.',
            ], 409); // Conflict
        }

        // Crear
        $sedQuestion = SedQuestion::create([
            'event_id' => $fair->id,
            'slug' => $request->slug,
            'documentnumber' => $request->documentnumber,
            'question_1' => $request->question_1,
            'question_2' => $request->question_2,
            'question_3' => $request->question_3,
            'question_4' => $request->question_4,
            'question_5' => $request->question_5,
        ]);

        return response()->json([
            'message' => 'Creado correctamente',
            'data' => $sedQuestion,
            'status' => 200,
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
                            'status' => 404,
                        ],
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
                        'fecha' => $fair->dates,
                        'place' => $fair->place,
                        'schedule' => $fair->hours,
                        'msgEnd' => $fair->msgEndForm,
                    ],
                    'status' => 200,
                ]);
            }

            return response()->json([
                'data' => [
                    'title' => 'No se encontró el evento.',
                    'message' => 'No existe una feria con este registro.',
                    'status' => 404,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los detalles del evento.',
                'error' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    // FERIAS EMPRESARIALES

    public function fairRegisterMype(Request $request)
    {
        try {
            $data = $request->all();

            // ─── 1. CREAR/OBTENER EMPRESARIO ─────────────────────────
            $data['fecha_nacimiento'] = \Carbon\Carbon::createFromFormat('d/m/Y', $data['fecha_nacimiento'])->format('Y-m-d');

            $empresario = Empresario::where('numero_dni', $data['numero_dni'])->first();

            if ($empresario) {
                $empresario->update($data);
            } else {
                $empresario = Empresario::create([
                    'ruc' => $data['ruc'],
                    'razon_social' => $data['razon_social'],
                    'nombre_comercial' => $data['nombre_comercial'],
                    'sector_economico_id' => $data['sector_economico_id'],
                    'rubro_id' => $data['rubro_id'],
                    'actividad_comercial_id' => $data['actividad_comercial_id'],
                    'region_id' => $data['region_id'],
                    'provincia_id' => $data['provincia_id'],
                    'distrito_id' => $data['distrito_id'],
                    'direccion' => $data['direccion'],
                    'tipo_documento_id' => $data['tipo_documento_id'],
                    'numero_dni' => $data['numero_dni'],
                    'apellido_paterno' => $data['apellido_paterno'],
                    'apellido_materno' => $data['apellido_materno'],
                    'nombres' => $data['nombres'],
                    'genero_id' => $data['genero_id'],
                    'discapacidad' => $data['discapacidad'],
                    'celular' => $data['celular'],
                    'correo_electronico' => $data['correo_electronico'],
                    'cargo_empresa_id' => $data['cargo_empresa_id'],
                    'fecha_nacimiento' => $data['fecha_nacimiento'],
                    'edad' => $data['edad'],
                    'pais_id' => $data['pais_nacimiento_id'] ?? null,
                ]);
            }

            // ─── 2. BUSCAR ACTIVIDAD POR SLUG ────────────────────────
            $actividad = ActividadPnte::where('slug', $data['slug'])->first();

            if (! $actividad) {
                return response()->json([
                    'message' => 'No se encontró la actividad con el slug proporcionado.',
                    'status' => 404,
                ], 404);
            }

            // ─── 3. REGISTRAR EMPRESARIO ACTIVIDAD ───────────────────
            EmpresarioActividad::firstOrCreate(
                [
                    'empresario_id' => $empresario->id,
                    'slug' => $data['slug'],
                ],
                [
                    'actividad_id' => $actividad->id,
                    'numero_dni' => $data['numero_dni'],
                ]
            );

            // ─── 4. REGISTRAR EMPRESARIO EMPRENDIMIENTO ──────────────
            $emprendimiento = EmpresarioEmprendimiento::firstOrCreate(
                [
                    'empresario_id' => $empresario->id,
                    'actividad_id' => $actividad->id,
                ],
                [
                    'redes_sociales' => $data['redes_sociales'] ?? null,
                    'pertenece_gremio' => $data['pertenece_gremio'] ?? false,
                    'nombre_gremio' => $data['nombre_gremio'] ?? null,
                    'cap_prod_mensual' => $data['cap_prod_mensual'] ?? null,
                    'porc_prod_planta' => $data['porc_prod_planta'] ?? null,
                    'porc_prod_maquila' => $data['porc_prod_maquila'] ?? null,
                    'tiene_puntos_venta' => $data['tiene_puntos_venta'] ?? false,
                    'num_puntos_ventas' => $data['num_puntos_ventas'] ?? null,
                    'desc_negocio' => $data['desc_negocio'] ?? null,
                    'pos' => $data['pos'] ?? false,
                    'yape_plim' => $data['yape_plim'] ?? false,
                    'tiene_tiendas' => $data['tiene_tiendas'] ?? false,
                    'nombre_tienda' => $data['nombre_tienda'] ?? null,
                    'tiene_delivery' => $data['tiene_delivery'] ?? false,
                    'factura_electronica' => $data['factura_electronica'] ?? false,
                    'participado_produce' => $data['participado_produce'] ?? false,
                    'nombre_servicio' => $data['nombre_servicio'] ?? null,
                    'participado_feria' => $data['participado_feria'] ?? false,
                    'nombre_feria' => $data['nombre_feria'] ?? null,
                    'formalizado_produce' => $data['formalizado_produce'] ?? false,
                    'indecopi' => $data['indecopi'] ?? false,
                    'logros_empresa' => $data['logros_empresa'] ?? null,
                    'terminos_condiciones' => $data['terminos_condiciones'] ?? false,
                ]
            );

            return response()->json([
                'message' => 'Mype registrado exitosamente en la feria.',
                'data' => [
                    'empresario' => $empresario,
                    'emprendimiento' => $emprendimiento,
                ],
                'status' => 200,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al registrar Mype en feria: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Ocurrió un error al registrar la Mype.',
                'error' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}
