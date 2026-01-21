<?php

namespace App\Http\Controllers\MujerProduce;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\MPDiagnostico;
use App\Models\MPParticipant;
use Illuminate\Http\Request;
use App\Models\MPAttendance;
use App\Models\MPDiagnosticoResponse;
use App\Models\MPEvent;
use GuzzleHttp\Client;
use App\Models\Token;
use Carbon\Carbon;

class FormularioPublicoController extends Controller
{
    public function checkRucNumber($ruc)
    {
        try {

            // 1. Buscar RUC en tabla
            $participant = MPParticipant::where('ruc', $ruc)->first();

            if ($participant) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Participante encontrado',
                    'data' => [
                        'ruc'                   => $participant->ruc,
                        'social_reason'         => $participant->social_reason,
                        'economic_sector_id'    => $participant->economic_sector_id,
                        'rubro_id'              => $participant->rubro_id,
                        'comercial_activity_id' => $participant->comercial_activity_id,
                        'city_id'               => $participant->city_id,
                        'province_id'           => $participant->province_id,
                        'district_id'           => $participant->district_id,
                    ]
                ], 200);
            }

            // 2. Si no existe, consultar API externa (modelo simplificado)
            return $this->consultExternalRuc($ruc);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'message' => 'Error interno al procesar la solicitud',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    private function consultExternalRuc($ruc)
    {
        try {

            $apiUrl = "https://api.decolecta.com/v1/sunat/ruc?numero={$ruc}";

            $tokens = Token::where('name', 'mp')->pluck('token')->toArray();

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

                    // Verificar si la API devolvió datos válidos
                    if (!empty($responseData['numero_documento'])) {
                        break;
                    }
                } catch (\Exception $e) {
                    // Si el token falla, intentamos el siguiente
                    continue;
                }
            }

            // Si API respondió correctamente
            if ($responseData && !empty($responseData['numero_documento'])) {

                return response()->json([
                    'status' => 200,
                    'message' => 'Información obtenida de la API externa',
                    'data' => [
                        'ruc'                   => $responseData['numero_documento'] ?? null,
                        'social_reason'         => $responseData['razon_social'] ?? null,
                        'economic_sector_id'    => null,
                        'rubro_id'              => null,
                        'comercial_activity_id' => null,
                        'city_id'               => null,
                        'province_id'           => null,
                        'district_id'           => null,
                    ]
                ], 200);
            }

            // API falló o no devolvió datos válidos
            return response()->json([
                'status' => 409,
                'message' => 'fallo la api',
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'fallo la api',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    private function hideLastFour($phone)
    {
        if (!$phone) {
            return null;
        }

        $len = strlen($phone);

        if ($len <= 4) {
            return null; // o return $phone; si quieres mostrarlo
        }

        return substr($phone, 0, $len - 4);
    }



    public function checkDniNumber($dni)
    {
        try {

            // 1. Buscar RUC en tabla
            $participant = MPParticipant::where('doc_number', $dni)->first();

            if ($participant) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Participante encontrado',
                    'data' => [
                        'names'                 => $participant->names,
                        'last_name'             => $participant->last_name,
                        'middle_name'           => $participant->middle_name,
                        'civil_status_id'       => $participant->civil_status_id,
                        'num_soons'             => $participant->num_soons,
                        'gender_id'             => $participant->gender_id,
                        'sick'                  => $participant->sick,
                        'academicdegree_id'     => $participant->academicdegree_id,
                        'phone'                 => $this->hideLastFour($participant->phone),
                        'email'                 => $participant->email,
                        'role_company_id'       => $participant->role_company_id,
                        'date_of_birth'         => $participant->date_of_birth,
                        'country_id'            => $participant->country_id
                    ]
                ], 200);
            }

            // 2. Si no existe, consultar API externa (modelo simplificado)
            return $this->consultExternalDni($dni);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'message' => 'Error interno al procesar la solicitud',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    private function consultExternalDni($dni)
    {
        try {

            // VALIDACIÓN DEL DNI (8 caracteres)
            if (strlen($dni) !== 8 || !ctype_digit($dni)) {
                return response()->json([
                    'status'  => 409,
                    'message' => 'dni inválido'
                ]);
            }

            $apiUrl = "https://api.decolecta.com/v1/reniec/dni?numero={$dni}";

            $tokens = Token::where('name', 'mp')->pluck('token')->toArray();

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

                    // Verificar si la API devolvió datos válidos
                    if (!empty($responseData['document_number'])) {
                        break;
                    }
                } catch (\Exception $e) {
                    // Intentar siguiente token
                    continue;
                }
            }

            // Si API respondió correctamente
            if ($responseData && !empty($responseData['document_number'])) {

                return response()->json([
                    'status' => 201,
                    'message' => 'Información obtenida de la API externa',
                    'data' => [
                        'names'                 => $responseData['first_name'] ?? null,
                        'last_name'             => $responseData['first_last_name'] ?? null,
                        'middle_name'           => $responseData['second_last_name'] ?? null,
                        'civil_status_id'       => null,
                        'num_soons'             => null,
                        'gender_id'             => null,
                        'sick'                  => null,
                        'academicdegree_id'     => null,
                        'phone'                 => null,
                        'email'                 => null,
                        'role_company_id'       => null,
                        'date_of_birth'         => null,
                        'country_id'            => null
                    ]
                ]);
            }

            // API falló o no devolvió datos válidos
            return response()->json([
                'status'  => 409,
                'message' => 'fallo la api'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'message' => 'fallo la api',
                'error'   => $e->getMessage()
            ], 500);
        }
    }



    // Registramos a un participante de mujer produce
    public function registerParticipant(Request $request)
    {
        try {

            // =======================================================
            // 1. VALIDACIÓN
            // =======================================================
            $request->validate([
                'ruc'                   => 'nullable|string|max:11',
                'social_reason'         => 'nullable|string|max:255',
                'economic_sector_id'    => 'nullable|exists:economicsectors,id',
                'rubro_id'              => 'nullable|exists:categories,id',
                'comercial_activity_id' => 'nullable|exists:activities,id',
                'city_id'               => 'nullable|exists:cities,id',
                'province_id'           => 'nullable|exists:provinces,id',
                'district_id'           => 'nullable|exists:districts,id',

                't_doc_id'              => 'nullable|exists:typedocuments,id',
                'doc_number'            => 'required|string|max:12',
                'country_id'            => 'nullable|exists:countries,id',
                'date_of_birth'         => 'nullable|date_format:d/m/Y',
                'names'                 => 'nullable|string|max:100',
                'last_name'             => 'nullable|string|max:100',
                'middle_name'           => 'nullable|string|max:100',
                'civil_status_id'       => 'nullable|exists:civilstatus,id',
                'num_soons'             => 'nullable|max:3',
                'gender_id'             => 'nullable|exists:genders,id',
                'sick'                  => 'nullable|string|max:10',
                'academicdegree_id'     => 'nullable|exists:academicdegree,id',
                'phone'                 => 'nullable|max:9',
                'email'                 => 'nullable|string|max:200',
                'role_company_id'       => 'nullable|exists:role_company,id',

                'obs_ruc'               => 'nullable|in:1',
                'obs_dni'               => 'nullable|in:1',

                'slug'                  => 'required|string'
            ]);

            // =======================================================
            // 2. NORMALIZAR FECHA
            // =======================================================
            if ($request->filled('date_of_birth')) {
                $request->merge([
                    'date_of_birth' => Carbon::createFromFormat(
                        'd/m/Y',
                        $request->date_of_birth
                    )->format('Y-m-d')
                ]);
            }

            // =======================================================
            // 3. LÓGICA DE PARTICIPANTE (REGLAS DE NEGOCIO)
            // =======================================================
            $action = 'updated';

            // Buscar por doc_number
            $participant = MPParticipant::where('doc_number', $request->doc_number)->first();

            if ($participant) {

                /*
                |------------------------------------------------------
                | CASO 1:
                | doc_number EXISTE
                |------------------------------------------------------
                */

                // Si el RUC en BD es NULL → se puede completar / modificar
                if (is_null($participant->ruc)) {

                    // Validar que el nuevo RUC no esté en otra fila
                    if ($request->filled('ruc')) {
                        $existsRuc = MPParticipant::where('ruc', $request->ruc)
                            ->where('id', '!=', $participant->id)
                            ->exists();

                        if ($existsRuc) {
                            return response()->json([
                                'status'  => 409,
                                'message' => 'El RUC ya se encuentra registrado en otro participante'
                            ], 409);
                        }
                    }

                    $participant->update($request->except('slug'));

                } else {

                    /*
                    |--------------------------------------------------
                    | CASO 2:
                    | doc_number y ruc YA EXISTEN
                    |--------------------------------------------------
                    | → NO sobreescribir datos existentes con NULL
                    */

                    $data = collect($request->except('slug'))
                        ->filter(fn ($value) => !is_null($value))
                        ->toArray();

                    $participant->update($data);
                }

            } else {

                /*
                |------------------------------------------------------
                | CASO 3:
                | doc_number NO EXISTE
                |------------------------------------------------------
                */

                // Validar que el RUC no exista si viene informado
                if ($request->filled('ruc')) {
                    $existsRuc = MPParticipant::where('ruc', $request->ruc)->exists();

                    if ($existsRuc) {
                        return response()->json([
                            'status'  => 409,
                            'message' => 'El RUC ya se encuentra registrado'
                        ], 409);
                    }
                }

                // Crear nuevo participante
                $participant = MPParticipant::create(
                    $request->except('slug')
                );

                $action = 'created';
            }

            // =======================================================
            // 4. BUSCAR EVENTO
            // =======================================================
            $event = MPEvent::where('slug', $request->slug)->first();

            if (!$event) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Evento no encontrado'
                ], 404);
            }

            // =======================================================
            // 5. REGISTRAR ASISTENCIA (SIN DUPLICAR)
            // =======================================================
            MPAttendance::firstOrCreate([
                'event_id'       => $event->id,
                'participant_id' => $participant->id,
            ], [
                'attendance' => null
            ]);

            // =======================================================
            // 6. RESPUESTA FINAL
            // =======================================================
            return response()->json([
                'status'  => 200,
                'message' => $action === 'created'
                    ? 'Participante creado y asistencia registrada'
                    : 'Participante actualizado y asistencia verificada',
                'data'    => $participant
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status'  => 422,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'message' => 'Error interno del servidor',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function registerAttendance(Request $request)
    {
        try {

            $request->validate([
                'ruc'        => 'required|string|max:11',
                'doc_number' => 'required|string|max:12',
                'slug'       => 'required|string',
                'attendance' => 'required|in:1'
            ]);

            // Buscar participante
            $participant = MPParticipant::where('ruc', $request->ruc)
                ->where('doc_number', $request->doc_number)
                ->first();

            if (!$participant) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Participante no existe en el sistema'
                ]);
            }

            // Buscar evento
            $event = MPEvent::where('slug', $request->slug)->first();

            if (!$event) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Evento no encontrado'
                ], 404);
            }

            // Buscar asistencia previa
            $attendance = MPAttendance::where('event_id', $event->id)
                ->where('participant_id', $participant->id)
                ->first();

            // Si ya está registrada
            if ($attendance && $attendance->attendance == 1) {
                return response()->json([
                    'status'  => 409,
                    'message' => 'La asistencia ya fue registrada previamente'
                ]);
            }

            // Registrar asistencia
            $attendance = MPAttendance::updateOrCreate(
                [
                    'event_id'       => $event->id,
                    'participant_id' => $participant->id,
                ],
                [
                    'attendance' => 1
                ]
            );

            return response()->json([
                'status'  => 200,
                'message' => 'Asistencia registrada correctamente',
                'data'    => $attendance
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status'  => 422,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'message' => 'Error interno al procesar la solicitud',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    // PAGINA DE REGISTROS STATUS

    public function infoEventPublic($slug)
    {
        try {

            // Buscar evento con relaciones necesarias
            $event = MPEvent::with(['city', 'province', 'district', 'modality'])
                ->where('slug', $slug)
                ->first();

            if (!$event) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Evento no encontrado.'
                ], 404);
            }

            $today = now()->format('Y-m-d');

            // Determinar si ha finalizado
            $finished = (!empty($event->endDate) && $event->endDate < $today);

            return response()->json([
                'status'   => 200,
                'message'  => $finished
                    ? 'El evento ha finalizado.'
                    : 'El evento no ha finalizado.',
                'data' => [
                    'finished' => $finished,
                    'title'     => $event->title,
                    'city'      => $event->city->name ?? null,
                    'province'  => $event->province->name ?? null,
                    'district'  => $event->district->name ?? null,
                    'modality'  => $event->modality->name ?? null,
                    // 'hours'     => $event->hours,
                    'hourStart' => $event->hourStart,
                    'hourEnd'   => $event->hourEnd,

                    'place'     => $event->place,
                    'link'      => $event->link,
                    'date'      => Carbon::parse($event->date)->format('d/m/Y')
                ]
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'message' => 'Error al procesar la solicitud.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function diagnosticQuestions()
    {
        try {

            // Obtener todas las preguntas del modelo MPDiagnostico
            $questions = MPDiagnostico::orderBy('id', 'ASC')->get();

            return response()->json([
                'status' => 200,
                'data'   => $questions
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'message' => 'Error al obtener preguntas del diagnóstico',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function getQuestionDiagnostic()
    {
        try {

            $questions = MPDiagnostico::with(['options'])
                ->where('status', 1)
                ->orderBy('position', 'ASC')
                ->get()
                ->map(function ($q) {

                    return [
                        'id'    => $q->id,
                        'label' => $q->label,

                        // tipo
                        'type'  => match ($q->type) {
                            't' => 'text',
                            'o' => 'select',
                            'l' => 'title',
                            default => 'text'
                        },

                        // requerido
                        'required' => (bool) $q->required,

                        'model' => $q->id,
                        'status' => true,

                        // 👇 regla fija
                        'md' => $q->type === 'l'
                            ? 12
                            : (mb_strlen($q->label) > 45 ? 12 : 6),

                        // opciones solo select
                        'options' => $q->type === 'o'
                            ? $q->options->map(fn($opt) => [
                                'value' => $opt->id,
                                'label' => $opt->name
                            ])
                            : []
                    ];
                });

            return response()->json([
                'status'  => 200,
                'message' => 'Listado de preguntas obtenido correctamente',
                'data'    => $questions
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'message' => 'Error al obtener las preguntas diagnósticas',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function registerConsulting(Request $request)
    {
        try {

            // =========================
            // 1. VALIDACIÓN
            // =========================
            $request->validate([
                'typedocument_id' => 'required|integer',
                'ruc'             => 'nullable|string|max:20',
                'documentnumber'  => 'required|string|max:20',
            ]);

            // =========================
            // 2. BÚSQUEDA DEL PARTICIPANTE
            // =========================
            $participant = MPParticipant::where('doc_number', $request->documentnumber)
            ->when($request->filled('ruc'), function ($query) use ($request) {
                $query->where('ruc', $request->ruc);
            })
            ->first();

            if (!$participant) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'El participante no existe'
                ]);
            }

            // =========================
            // 3. TRAER RESPUESTAS DEL PARTICIPANTE
            // =========================
            $responses = MPDiagnosticoResponse::where('participant_id', $participant->id)
                ->get()
                ->keyBy('question_id');

            // =========================
            // 4. TRAER TODAS LAS PREGUNTAS ACTIVAS
            // =========================
            $questions = MPDiagnostico::where('status', 1)
                ->orderBy('id', 'ASC')
                ->get();

            // =========================
            // 5. ARMAR QUESTIONNAIRE
            // =========================
            $questionnaire = [];

            foreach ($questions as $question) {

                $response = $responses->get($question->id);

                if ($response) {
                    // existe respuesta
                    $questionnaire[$question->id] = $question->type === 't'
                        ? $response->answer_text
                        : $response->answer_option_id;
                } else {
                    // no existe respuesta
                    $questionnaire[$question->id] = null;
                }
            }

            // =========================
            // 6. RESPUESTA FINAL
            // =========================
            return response()->json([
                'status'  => 200,
                'message' => 'Participante encontrado',
                'data'    => [
                    'id'            => $participant->id,
                    'ruc'           => $participant->ruc,
                    'doc_number'    => $participant->doc_number,
                    'questionnaire' => $questionnaire
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status'  => 422,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'message' => 'Error interno del servidor',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function registerDiagnosticResponse(Request $request)
    {
        try {

            // ===============================
            // 1. VALIDACIÓN BASE
            // ===============================
            $request->validate([
                'ruc'            => 'nullable|string',
                'documentnumber' => 'required|string',
            ]);

            // ===============================
            // 2. BUSCAR PARTICIPANTE
            // ===============================
            $participant = MPParticipant::where('ruc', $request->ruc)
                ->where('doc_number', $request->documentnumber)
                ->first();

            if (!$participant) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Participante no encontrado'
                ], 404);
            }

            // ===============================
            // 3. FILTRAR RESPUESTAS DINÁMICAS
            // ===============================
            $fixedKeys = ['ruc', 'documentnumber', 'typedocument_id'];
            $answers   = collect($request->all())->except($fixedKeys);

            DB::beginTransaction();

            foreach ($answers as $questionId => $value) {

                // Validar que la key sea numérica
                if (!is_numeric($questionId)) {
                    continue;
                }

                // Buscar pregunta
                $question = MPDiagnostico::where('id', $questionId)
                    ->where('status', 1)
                    ->first();

                if (!$question) {
                    continue;
                }

                // ===============================
                // 4. DATA BASE
                // ===============================
                $data = [
                    'answer_text'       => null,
                    'answer_option_id'  => null,
                ];

                if ($question->type === 't') {
                    $data['answer_text'] = (string) $value;
                }

                if ($question->type === 'o') {
                    $data['answer_option_id'] = is_numeric($value) ? (int) $value : null;
                }

                // ===============================
                // 5. CREAR O ACTUALIZAR
                // ===============================
                MPDiagnosticoResponse::updateOrCreate(
                    [
                        'participant_id' => $participant->id,
                        'question_id'    => $question->id,
                    ],
                    $data
                );
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'message' => 'Respuestas registradas / actualizadas correctamente'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status'  => 422,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 500,
                'message' => 'Error interno al registrar respuestas',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function salaLinkMeet(Request $request)
    {
        try {
            $validated = $request->validate([
                'doc_number' => 'required|string',
                'slug'       => 'required|string',
            ]);

            // 1. Validar que el participante exista
            $participant = MPParticipant::where('doc_number', $validated['doc_number'])->first();

            if (!$participant) {
                return response()->json([
                    'status'    => 403,
                    'success'   => false,
                    'message'   => 'No te encuentras registrado.'
                ]);
            }

            // 2. Buscar evento por slug
            $event = MPEvent::where('slug', $validated['slug'])
                ->select('id', 'link')
                ->first();

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'El evento no existe.'
                ], 404);
            }

            // 3. Validar que el evento tenga link
            if (!$event->link) {
                return response()->json([
                    'status'    => 401,
                    'success'   => false,
                    'message'   => 'El enlace de la sala aún no está disponible.'
                ]);
            }

            // 4. Retornar link
            return response()->json([
                'status'    => 200,
                'success'   => true,
                'data'      => [
                    'link' => $event->link
                ]
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al validar el acceso.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function attendanceParticipant(Request $request)
    {
        try {
            // Validación básica
            $request->validate([
                'doc_number' => 'required|string',
                'slug'       => 'required|string',
            ]);

            DB::beginTransaction();

            // Buscar participante por documento
            $participant = MPParticipant::where('doc_number', $request->doc_number)->first();

            if (!$participant) {
                return response()->json([
                    'message' => 'Participante no encontrado'
                ], 404);
            }

            // Buscar evento por slug
            $event = MPEvent::where('slug', $request->slug)->first();

            if (!$event) {
                return response()->json([
                    'message' => 'Evento no encontrado'
                ], 404);
            }

            // Registrar asistencia (evita duplicados)
            $attendance = MPAttendance::updateOrCreate(
                [
                    'participant_id' => $participant->id,
                    'event_id'       => $event->id,
                ],
                [
                    'attendance' => 1
                ]
            );

            DB::commit();

            return response()->json([
                'message' => 'Asistencia registrada correctamente',
                'data'    => $attendance
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al registrar la asistencia',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function updateParticipant(Request $request, $id)
    {
        try {

            // =======================================================
            // 1. VALIDACIÓN
            // =======================================================
            $request->validate([
                'doc_number'            => 'required|string|max:12',
                'ruc'                   => 'nullable|string|max:11',

                'social_reason'         => 'nullable|string|max:255',
                'economic_sector_id'    => 'nullable|exists:economicsectors,id',
                'rubro_id'              => 'nullable|exists:categories,id',
                'comercial_activity_id' => 'nullable|exists:activities,id',
                'city_id'               => 'nullable|exists:cities,id',
                'province_id'           => 'nullable|exists:provinces,id',
                'district_id'           => 'nullable|exists:districts,id',

                't_doc_id'              => 'nullable|exists:typedocuments,id',
                'country_id'            => 'nullable|exists:countries,id',
                'date_of_birth'         => 'nullable|date_format:d/m/Y',
                'names'                 => 'nullable|string|max:100',
                'last_name'             => 'nullable|string|max:100',
                'middle_name'           => 'nullable|string|max:100',
                'civil_status_id'       => 'nullable|exists:civilstatus,id',
                'num_soons'             => 'nullable|max:3',
                'gender_id'             => 'nullable|exists:genders,id',
                'sick'                  => 'nullable|string|max:10',
                'academicdegree_id'     => 'nullable|exists:academicdegree,id',
                'phone'                 => 'nullable|max:9',
                'email'                 => 'nullable|string|max:200',
                'role_company_id'       => 'nullable|exists:role_company,id',
            ]);

            // =======================================================
            // 2. BUSCAR PARTICIPANTE
            // =======================================================
            $participant = MPParticipant::find($id);

            if (!$participant) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Participante no encontrado'
                ], 404);
            }

            // =======================================================
            // 3. PREPARAR DATA A ACTUALIZAR
            // =======================================================
            $data = $request->all();

            // =======================================================
            // 4. VALIDAR RUC (SI EXISTE, NO SE ACTUALIZA)
            // =======================================================
            if ($request->filled('ruc')) {

                $existsRuc = MPParticipant::where('ruc', $request->ruc)
                    ->where('id', '!=', $participant->id)
                    ->exists();

                if ($existsRuc) {
                    // ❌ NO se actualiza el RUC
                    unset($data['ruc']);
                }
            }

            // =======================================================
            // 5. NORMALIZAR FECHA
            // =======================================================
            if ($request->filled('date_of_birth')) {
                $data['date_of_birth'] = Carbon::createFromFormat(
                    'd/m/Y',
                    $request->date_of_birth
                )->format('Y-m-d');
            }

            // =======================================================
            // 6. ACTUALIZAR PARTICIPANTE
            // =======================================================
            $participant->update($data);

            // =======================================================
            // 7. RESPUESTA
            // =======================================================
            return response()->json([
                'status'  => 200,
                'message' => 'Participante actualizado correctamente',
                'data'    => $participant
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status'  => 422,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'message' => 'Error interno del servidor',
                'error'   => $e->getMessage()
            ], 500);
        }
    }




}
