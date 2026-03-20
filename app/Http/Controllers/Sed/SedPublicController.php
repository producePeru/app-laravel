<?php

namespace App\Http\Controllers\Sed;

use App\Http\Controllers\Controller;
use App\Models\Fair;
use App\Models\SedAsistente;
use App\Models\SedQuestion;
use App\Models\SedSurvey;
use App\Models\UgsePostulante;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use PDF;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Mail\FairSedInfoMail;
use App\Models\Question;
use App\Models\sedQuestionAnswer;
use FontLib\Table\Type\name;
use Illuminate\Support\Facades\Mail;

class SedPublicController extends Controller
{
    public function getSedSurvey($slug)
    {
        try {

            $fair = Fair::where('slug', $slug)->firstOrFail();

            $questions = SedSurvey::where('sed_id', $fair->id)
                ->with(['question.options'])
                ->get()
                ->map(function ($item) {

                    $q = $item->question;

                    if (!$q || $q->tableName !== 'sed') {
                        return null;
                    }

                    return [
                        'type' => $q->type,
                        'label' => $q->label,
                        'model' => $q->model,
                        'required' => (bool) $q->required,
                        'md' => 12,
                        'options' => $q->options->map(function ($opt) {
                            return [
                                'label' => $opt->label,
                                'value' => $opt->value
                            ];
                        })->values()
                    ];
                })
                ->filter()
                ->values();

            return response()->json([
                'status' => 200,
                'questions' => $questions
            ]);
        } catch (\Throwable $e) {

            Log::error('Error obteniendo encuesta SED', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => $e->getMessage(),
                'status' => 500,
                'message' => 'Error al obtener la encuesta'
            ], 500);
        }
    }

    public function isRegisterThisUser(Request $request)
    {
        try {

            if (empty($request->slug) || empty($request->documentNumber)) {
                return response()->json([
                    'message' => 'slug y documentNumber son requeridos',
                    'status' => 422
                ], 422);
            }


            $fair = Fair::where('slug', $request->slug)->firstOrFail();
            $postulante = UgsePostulante::where('documentnumber', $request->documentNumber)->first();




            if (!$postulante) {
                return response()->json([
                    'message' => 'No existe este usuario',
                    'status' => 409
                ], 409);
            }



            // 4️⃣ Buscar en sed_asistencias
            $asistencia = SedAsistente::where('sed_id', $fair->id)
                ->where('mype_id', $postulante->id)
                ->first();

            if (!$asistencia) {
                return response()->json([
                    'message' => 'No existe este registro en el evento',
                    'status' => 409
                ]);
            }

            // 5️⃣ Respuesta OK
            return response()->json([
                'status' => 200,
                'data' => [
                    'ruc'        => $postulante->ruc,
                    'name'       => strtoupper($postulante->name),
                    'lastname'   => strtoupper($postulante->lastname),
                    'middlename' => strtoupper($postulante->middlename),
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Evento no encontrado',
                'status' => 404
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function rucCompany($ruc)
    {
        $company = UgsePostulante::select(
            'comercialName',
            'socialReason',
            'economicsector_id',
            'category_id',
            'comercialactivity_id',
            'city_id',
            'province_id',
            'district_id',
            'address'
        )
            ->where('ruc', $ruc)
            ->first();

        if ($company) {
            return response()->json([
                'status' => 200,
                'exists' => true,
                'data' => $company
            ]);
        }

        return response()->json([
            'status' => 404,
            'exists' => false,
            'message' => 'Empresa no encontrada'
        ]);
    }

    public function dniBusimessMan($dni)
    {
        $company = UgsePostulante::select(
            'lastname',
            'middlename',
            'name',
            'gender_id',
            'sick',
            'phone',
            'email',
            'positionCompany',
            'birthday',
            'age'
        )
            ->where('documentnumber', $dni)
            ->first();

        if ($company) {
            return response()->json([
                'status' => 200,
                'exists' => true,
                'data' => $company
            ]);
        }

        return response()->json([
            'status' => 404,
            'exists' => false,
            'message' => 'Empresario no encontrado'
        ]);
    }


    public function sedRegisterMype(Request $request)
    {
        try {

            // 1️⃣ Buscar evento
            $fair = Fair::where('slug', $request->slug)->first();

            if (!$fair) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Evento no encontrado'
                ], 404);
            }

            // 2️⃣ Convertir fecha
            $birthday = null;
            if (!empty($request->birthday)) {
                try {
                    $birthday = Carbon::createFromFormat('d/m/Y', $request->birthday)->format('Y-m-d');
                } catch (\Exception $e) {
                    $birthday = null;
                }
            }

            // 3️⃣ CREATE OR UPDATE (clave compuesta 🔥)
            $postulante = UgsePostulante::updateOrCreate(
                [
                    // 'event_id' => $fair->id,
                    'ruc' => $request->ruc,
                    'documentnumber' => $request->documentnumber
                ],
                [
                    'event_id' => $fair->id, // 👈 AHORA SOLO SE ACTUALIZA
                    'comercialName' => $request->comercialName,
                    'socialReason' => $request->socialReason,
                    'economicsector_id' => $request->economicsector_id,
                    'category_id' => $request->category_id,
                    'comercialactivity_id' => $request->comercialactivity_id,
                    'city_id' => $request->city_id,
                    'province_id' => $request->province_id,
                    'district_id' => $request->district_id,
                    'address' => $request->address,
                    'typedocument_id' => $request->typedocument_id,
                    'lastname' => $request->lastname,
                    'middlename' => $request->middlename,
                    'name' => $request->name,
                    'gender_id' => $request->gender_id,
                    'sick' => $request->sick,
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'positionCompany' => $request->positionCompany,
                    'birthday' => $birthday,
                    'age' => $request->age,
                    'howKnowEvent_id' => $request->howKnowEvent_id,
                    'typeAsistente' => $request->typeAsistente
                ]
            );

            // 4️⃣ Encuesta (igual lógica)
            SedQuestion::updateOrCreate(
                [
                    'documentnumber' => $request->documentnumber,
                    'event_id' => $fair->id
                ],
                [
                    'question_1' => $request->question_1,
                    'question_2' => $request->question_2,
                    'question_3' => $request->question_3,
                    'question_4' => $request->question_4,
                    'question_5' => $request->question_5,
                ]
            );

            // 5️⃣ Asistencia (update si ya existe)
            SedAsistente::updateOrCreate(
                [
                    'sed_id' => $fair->id,
                    'mype_id' => $postulante->id
                ],
                [
                    'attendance' => Carbon::now()->format('d/m/Y h:i a'),
                    'typeAsistente' => $request->typeAsistente
                ]
            );

            return response()->json([
                'status' => 200,
                'message' => 'Registro guardado correctamente',
                'data' => [
                    'postulante_id' => $postulante->id
                ]
            ]);
        } catch (\Exception $e) {

            Log::error('Error obteniendo encuesta SED', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function qrSendEmailInvitation(Request $request)
    {
        try {

            // 1️⃣ Validación mínima manual
            if (empty($request->slug) || empty($request->documentnumber)) {
                return response()->json([
                    'message' => 'slug y documentnumber son requeridos',
                    'status' => 422
                ], 422);
            }

            // 2️⃣ Buscar evento
            $fair = Fair::where('slug', $request->slug)->firstOrFail();

            // 3️⃣ Buscar participante
            $ugsePostulante = UgsePostulante::where('event_id', $fair->id)
                ->where('documentnumber', $request->documentnumber)
                ->first();

            if (!$ugsePostulante) {
                return response()->json([
                    'message' => 'Participante no encontrado',
                    'status' => 404
                ], 404);
            }

            $mailer = $request->mailer ?? 'hostinger';

            // 4️⃣ Logo base64
            $logoPath = public_path('images/logo/sed.png');
            $logoBase64 = base64_encode(file_get_contents($logoPath));
            $logoMime = mime_content_type($logoPath);
            $logoDataUri = "data:$logoMime;base64,$logoBase64";

            // 5️⃣ QR
            $qrResult = Builder::create()
                ->writer(new PngWriter())
                ->data($ugsePostulante->documentnumber)
                ->size(200)
                ->margin(10)
                ->build();

            $qrBase64 = base64_encode($qrResult->getString());

            // 6️⃣ PDF
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

            // 7️⃣ Enviar correo
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
                'success' => true,
                'message' => 'Correo reenviado correctamente',
                'data' => $ugsePostulante,
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function saveSurvey(Request $request)
    {
        try {

            // 1️⃣ Obtener Fair
            $fair = Fair::where('slug', $request->slug)->firstOrFail();

            // 2️⃣ Datos base
            $dni = $request->documentnumber;
            $ruc = $request->ruc;

            // 3️⃣ Obtener TODAS las preguntas con opciones
            $questions = Question::with('options')->get()->keyBy('model');

            foreach ($request->all() as $key => $value) {

                // 🔥 Solo procesar questions
                if (!str_starts_with($key, 'question_')) continue;

                if (!isset($questions[$key])) continue;

                $question = $questions[$key];

                $answerText = null;

                // 4️⃣ Checkbox múltiple
                if (is_array($value)) {

                    $labels = collect($value)->map(function ($val) use ($question) {
                        $option = $question->options->firstWhere('value', $val);
                        return $option ? $option->label : $val;
                    })->filter()->values();

                    // 🔥 FORMATO CON VIÑETAS
                    $answerText = $labels->map(function ($label) {
                        return '- ' . $label;
                    })->implode("\n");
                } else {

                    // 5️⃣ Single (radio/select/text)
                    $option = $question->options->firstWhere('value', $value);

                    $answerText = $option ? $option->label : $value;
                }

                // 6️⃣ Guardar
                sedQuestionAnswer::updateOrCreate(
                    [
                        'dni'      => $dni,
                        'sed_id'   => $fair->id,
                        'question' => $question->label
                    ],
                    [
                        'ruc'    => $ruc,
                        'answer' => $answerText
                    ]
                );
            }

            return response()->json([
                'status' => 200,
                'message' => 'Encuesta guardada correctamente'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Error al guardar encuesta',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
