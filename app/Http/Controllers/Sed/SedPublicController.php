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
use App\Models\ActividadPnte;
use App\Models\Empresario;
use App\Models\EmpresarioActividad;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\sedQuestionAnswer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SedPublicController extends Controller
{
    public function getSedSurvey(Request $request, $slug)
    {
        try {
            // 👇 default 'sed'
            $table = $request->input('table', 'sed');

            // 1. Buscar el evento

            $fair = ActividadPnte::with(['regionRel', 'provinciaRel', 'distritoRel'])
                ->where('slug', $slug)
                ->firstOrFail();

            // 3. Traer preguntas
            $questions = SedSurvey::where('actividad_pnte_slug', $fair->slug)
                ->with(['question.options'])
                ->get()
                ->map(function ($item) use ($table) {

                    $q = $item->question;

                    // 👇 aquí aplicas dinámico
                    if (!$q || $q->tableName !== $table) {
                        return null;
                    }

                    return [
                        'id' => $q->id,
                        'type' => $q->type,
                        'label' => $q->label,
                        'model' => $q->model,
                        // 'model' => $q->model,
                        'required' => (bool) $q->required,
                        'md' => 12,
                        'visible' => $q->visible == 1 ? true : false,
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

            // 4. Respuesta
            return response()->json([
                'status' => 200,
                'questions' => $questions,
                'sed_title' => $fair->tema,
                'sed_region' => $fair->regionRel?->name,
                'sed_province' => $fair->provinciaRel?->name,
                'sed_distrito' => $fair->distritoRel?->name,
                'sed_lugar' => $fair->lugar

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
        $documentNumber = $request->input('documentNumber');
        $slug = $request->input('slug');

        // 1. Buscar registro en EmpresarioActividad
        $registro = EmpresarioActividad::where('numero_dni', $documentNumber)
            ->where('slug', $slug)
            ->first();

        // 2. No existe registro
        if (!$registro) {
            return response()->json([
                'status' => 404,
                'message' => 'Para hacer el test debió haber estado registrado previamente en la actividad.'
            ], 404);
        }

        // 3. Existe pero no tiene asistencia
        if (is_null($registro->fecha_asistencia)) {
            return response()->json([
                'status' => 403,
                'message' => 'No puedes completar el test porque no se registró tu asistencia.'
            ], 403);
        }

        // 4. Traer datos del empresario
        $empresario = Empresario::select(
            'id',
            'apellido_paterno',
            'apellido_materno',
            'nombres',
            'ruc'
        )
            ->where('numero_dni', $documentNumber)
            ->first();

        return response()->json([
            'status' => 200,
            'message' => 'Usuario autorizado para el test.',
            'empresario' => $empresario,
            'registro' => [
                'slug' => $registro->slug,
                'fecha_asistencia' => $registro->fecha_asistencia,
            ]
        ]);
    }

    public function rucCompany($ruc)
    {
        try {

            $empresa = Empresario::where('ruc', $ruc)

                // ✅ seleccionar solo campos necesarios
                ->select([
                    'id',
                    'ruc',
                    'razon_social',
                    'nombre_comercial',
                    'sector_economico_id',
                    'rubro_id',
                    'actividad_comercial_id',
                    'region_id',
                    'provincia_id',
                    'distrito_id',
                    'direccion',
                ])

                // ✅ priorizar el registro más completo
                ->orderByRaw("
                (
                    IF(ruc IS NOT NULL AND ruc != '', 1, 0) +
                    IF(razon_social IS NOT NULL AND razon_social != '', 1, 0) +
                    IF(nombre_comercial IS NOT NULL AND nombre_comercial != '', 1, 0) +
                    IF(sector_economico_id IS NOT NULL, 1, 0) +
                    IF(rubro_id IS NOT NULL, 1, 0) +
                    IF(actividad_comercial_id IS NOT NULL, 1, 0) +
                    IF(region_id IS NOT NULL, 1, 0) +
                    IF(provincia_id IS NOT NULL, 1, 0) +
                    IF(distrito_id IS NOT NULL, 1, 0) +
                    IF(direccion IS NOT NULL AND direccion != '', 1, 0)
                ) DESC
            ")

                ->first();

            if (!$empresa) {

                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontró información para este RUC.',
                    'data' => null,
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Información encontrada correctamente.',
                'data' => [
                    'ruc'                    => $empresa->ruc,
                    'razon_social'           => $empresa->razon_social,
                    'nombre_comercial'       => $empresa->nombre_comercial,
                    'sector_economico_id'    => $empresa->sector_economico_id,
                    'rubro_id'               => $empresa->rubro_id,
                    'actividad_comercial_id' => $empresa->actividad_comercial_id,
                    'region_id'              => $empresa->region_id,
                    'provincia_id'           => $empresa->provincia_id,
                    'distrito_id'            => $empresa->distrito_id,
                    'direccion'              => $empresa->direccion,
                ]
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Error al consultar el RUC.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function dniBusimessMan($dni)
    {
        try {

            $empresario = Empresario::where('numero_dni', $dni)

                // ✅ seleccionar solo campos necesarios
                ->select([
                    'id',
                    'numero_dni',
                    'apellido_paterno',
                    'apellido_materno',
                    'nombres',
                    'genero_id',
                    'discapacidad',
                    'celular',
                    'correo_electronico',
                    'cargo_empresa_id',
                    'fecha_nacimiento',
                    'edad',
                ])

                // ✅ priorizar el registro más completo
                ->orderByRaw("
                (
                    IF(apellido_paterno IS NOT NULL AND apellido_paterno != '', 1, 0) +
                    IF(apellido_materno IS NOT NULL AND apellido_materno != '', 1, 0) +
                    IF(nombres IS NOT NULL AND nombres != '', 1, 0) +
                    IF(genero_id IS NOT NULL, 1, 0) +
                    IF(discapacidad IS NOT NULL, 1, 0) +
                    IF(celular IS NOT NULL AND celular != '', 1, 0) +
                    IF(cargo_empresa_id IS NOT NULL, 1, 0) +
                    IF(fecha_nacimiento IS NOT NULL, 1, 0) +
                    IF(edad IS NOT NULL, 1, 0)
                ) DESC
            ")

                ->first();

            if (!$empresario) {

                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontró información para este DNI.',
                    'data' => null,
                ]);
            }

            // ✅ ocultar últimos 4 dígitos del celular
            $celular = $empresario->celular;

            if (!empty($celular) && strlen($celular) >= 4) {

                $celular = substr($celular, 0, -4) . '****';
            }

            return response()->json([
                'status' => 200,
                'message' => 'Información encontrada correctamente.',
                'data' => [
                    'apellido_paterno'  => $empresario->apellido_paterno,
                    'apellido_materno'  => $empresario->apellido_materno,
                    'nombres'           => $empresario->nombres,
                    'genero_id'         => $empresario->genero_id,
                    'discapacidad'      => $empresario->discapacidad,
                    'celular'           => $celular,
                    'correo_electronico' => $empresario->correo_electronico,
                    'cargo_empresa_id'  => $empresario->cargo_empresa_id,
                    'fecha_nacimiento'  => $empresario->fecha_nacimiento,
                    'edad'              => $empresario->edad,
                ]
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Error al consultar el DNI.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function sedRegisterMype(Request $request)
    {
        DB::beginTransaction();

        try {

            $validator = Validator::make($request->all(), [

                // empresario
                'ruc'                      => 'required|string|max:11',
                'razon_social'             => 'required|string|max:255',
                'nombre_comercial'         => 'nullable|string|max:255',
                'sector_economico_id'      => 'nullable|integer',
                'rubro_id'                 => 'nullable|integer',
                'actividad_comercial_id'   => 'nullable|integer',
                'region_id'                => 'nullable|integer',
                'provincia_id'             => 'nullable|integer',
                'distrito_id'              => 'nullable|integer',
                'direccion'                => 'nullable|string|max:255',
                'tipo_documento_id'        => 'nullable|integer',
                'numero_dni'               => 'required|string|max:12',
                'apellido_paterno'         => 'nullable|string|max:255',
                'apellido_materno'         => 'nullable|string|max:255',
                'nombres'                  => 'nullable|string|max:255',
                'genero_id'                => 'nullable|integer',
                'discapacidad'             => 'nullable',
                'celular'                  => 'nullable|string|max:20',
                'correo_electronico'       => 'nullable|email|max:255',
                'cargo_empresa_id'         => 'nullable',
                'fecha_nacimiento'         => 'nullable|string',
                'edad'                     => 'nullable',

                // sed question
                'question_1'               => 'nullable|string',
                'question_2'               => 'nullable|string',
                'question_3'               => 'nullable|string',
                'question_4'               => 'nullable|string',
                'question_5'               => 'nullable|string',

                'propagandamedia_id'       => 'nullable|integer',
                'tipo_asistente'           => 'nullable|integer',

                'slug'                     => 'required|string|max:100',

                'cooperativa'              => 'nullable',
                'rucCooperativa'           => 'nullable|string|max:11',
                'rolCooperativa'           => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {

                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors'  => $validator->errors()
                ], 422);
            }

            // =====================================================
            // EMPRESARIO
            // VALIDAR POR RUC + NUMERO DNI
            // =====================================================

            $empresario = Empresario::where('ruc', $request->ruc)
                ->where('numero_dni', $request->numero_dni)
                ->first();

            $fechaNacimiento = null;

            if ($request->fecha_nacimiento) {

                try {

                    $fechaNacimiento = Carbon::createFromFormat(
                        'd/m/Y',
                        $request->fecha_nacimiento
                    )->format('Y-m-d');
                } catch (\Exception $e) {

                    $fechaNacimiento = null;
                }
            }

            $dataEmpresario = [
                'razon_social'           => $request->razon_social,
                'nombre_comercial'       => $request->nombre_comercial,
                'sector_economico_id'    => $request->sector_economico_id,
                'rubro_id'               => $request->rubro_id,
                'actividad_comercial_id' => $request->actividad_comercial_id,
                'region_id'              => $request->region_id,
                'provincia_id'           => $request->provincia_id,
                'distrito_id'            => $request->distrito_id,
                'direccion'              => $request->direccion,
                'tipo_documento_id'      => $request->tipo_documento_id,
                'apellido_paterno'       => $request->apellido_paterno,
                'apellido_materno'       => $request->apellido_materno,
                'nombres'                => $request->nombres,
                'genero_id'              => $request->genero_id,
                'discapacidad'           => $request->discapacidad,
                'celular'                => $request->celular,
                'correo_electronico'     => $request->correo_electronico,
                'cargo_empresa_id'       => $request->cargo_empresa_id,
                'fecha_nacimiento'       => $fechaNacimiento,
                'edad'                   => $request->edad,
            ];

            // =====================================================
            // ACTUALIZAR O CREAR EMPRESARIO
            // =====================================================

            if ($empresario) {

                // NO CAMBIAR ruc NI numero_dni
                $empresario->update($dataEmpresario);
            } else {

                $empresario = Empresario::create(array_merge(
                    $dataEmpresario,
                    [
                        'ruc'         => $request->ruc,
                        'numero_dni'  => $request->numero_dni,
                    ]
                ));
            }

            // =====================================================
            // EMPRESARIO ACTIVIDAD
            // NO DUPLICAR slug + numero_dni
            // =====================================================

            $empresarioActividad = EmpresarioActividad::where('slug', $request->slug)
                ->where(
                    'numero_dni',
                    $request->numero_dni
                )
                ->first();

            if (!$empresarioActividad) {

                EmpresarioActividad::create([
                    'slug'          => $request->slug,
                    'empresario_id' => $empresario->id,
                    'numero_dni'    => $request->numero_dni,
                ]);
            }

            // =====================================================
            // SED QUESTION
            // VALIDAR slug + documentnumber
            // =====================================================

            $sedQuestion = SedQuestion::where(
                'slug',
                $request->slug
            )
                ->where(
                    'documentnumber',
                    $request->numero_dni
                )
                ->first();

            $dataSedQuestion = [
                'question_1'         => $request->question_1,
                'question_2'         => $request->question_2,
                'question_3'         => $request->question_3,
                'question_4'         => $request->question_4,
                'question_5'         => $request->question_5,
                'propagandamedia_id' => $request->propagandamedia_id,
                'tipo_asistente'     => $request->tipo_asistente,
                'cooperativa'        => $request->cooperativa,
                'rucCooperativa'     => $request->rucCooperativa,
                'rolCooperativa'     => $request->rolCooperativa,
            ];

            // =====================================================
            // ACTUALIZAR O CREAR SED QUESTION
            // =====================================================

            if ($sedQuestion) {

                // NO CAMBIAR slug NI documentnumber
                $sedQuestion->update($dataSedQuestion);
            } else {

                $sedQuestion = SedQuestion::create(array_merge(
                    $dataSedQuestion,
                    [
                        'documentnumber' => $request->numero_dni,
                        'slug'           => $request->slug,
                    ]
                ));
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'message' => 'Registro guardado correctamente',
                'data'    => [
                    'empresario_id'  => $empresario->id,
                    'sedquestion_id' => $sedQuestion->id,
                ]
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al registrar',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function qrSendEmailInvitation(Request $request)
    {
        try {

            // ✅ VALIDACIÓN COMPLETA
            $validated = $request->validate([
                'slug'            => 'required|string',
                'documentnumber'  => 'required|string',
                'email'           => 'required|email',
                'name'            => 'required|string',
                'lastname'        => 'required|string',
            ]);

            $mailer = $request->mailer ?? 'digitalizacion';

            // 🔍 EVENTO
            $fair = Fair::where('slug', $validated['slug'])->firstOrFail();

            // 🔍 (OPCIONAL) VALIDAR QUE EXISTA PARTICIPANTE
            $ugsePostulante = UgsePostulante::where('event_id', $fair->id)
                ->where('documentnumber', $validated['documentnumber'])
                ->first();

            if (!$ugsePostulante) {
                return response()->json([
                    'message' => 'Participante no encontrado',
                    'status' => 404
                ], 404);
            }

            // 📛 NOMBRE DESDE PAYLOAD
            $participantName = "{$validated['name']} {$validated['lastname']}";

            // 🖼 LOGO BASE64
            $logoPath = public_path('images/logo/sed.png');
            $logoBase64 = base64_encode(file_get_contents($logoPath));
            $logoMime = mime_content_type($logoPath);
            $logoDataUri = "data:$logoMime;base64,$logoBase64";

            // 🔳 QR
            $qrResult = Builder::create()
                ->writer(new PngWriter())
                ->data($validated['documentnumber'])
                ->size(200)
                ->margin(10)
                ->build();

            $qrBase64 = base64_encode($qrResult->getString());

            // 📄 PDF
            $pdf = PDF::loadView('pdf.ticket_entry', [
                'fair' => $fair,
                'participantName' => $participantName,
                'qrBase64' => $qrBase64,
                'logoDataUri' => $logoDataUri,
            ]);

            $filename = 'entrada_' . Str::random(10) . '.pdf';
            $filepath = storage_path("app/public/entradas/{$filename}");

            Storage::makeDirectory('public/entradas');
            $pdf->save($filepath);

            // 📧 CONTENIDO MENSAJE
            $messageContent = strip_tags($fair->msgSendEmail);

            // 📧 ENVÍO
            Mail::mailer($mailer)
                ->to($validated['email']) // 🔥 correo del payload
                ->send(new FairSedInfoMail(
                    $messageContent,
                    $filepath,
                    $participantName,
                    $fair
                ));

            return response()->json([
                'success' => true,
                'message' => 'Correo enviado correctamente',
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
        DB::beginTransaction();

        try {

            $payload = $request->all();

            // =====================================================
            // VALIDAR DATOS
            // =====================================================

            if (empty($payload['slug'])) {

                return response()->json([
                    'status' => 422,
                    'message' => 'El slug es requerido'
                ], 422);
            }

            if (empty($payload['documentnumber'])) {

                return response()->json([
                    'status' => 422,
                    'message' => 'El documentnumber es requerido'
                ], 422);
            }

            $dni  = $payload['documentnumber'];
            $slug = $payload['slug'];

            // =====================================================
            // VALIDAR DUPLICADOS
            // =====================================================

            $exists = sedQuestionAnswer::where('dni', $dni)
                ->where('slug_sed', $slug)
                ->exists();

            if ($exists) {

                return response()->json([
                    'status' => 409,
                    'message' => 'La encuesta ya fue registrada'
                ], 409);
            }

            // =====================================================
            // GUARDAR RESPUESTAS
            // =====================================================

            foreach ($payload as $key => $value) {

                // Solo questions
                if (!str_starts_with($key, 'question_')) {
                    continue;
                }

                // question_50 => questions_50
                $question = str_replace('question_', 'questions_', $key);

                // Convertir array a JSON
                $answer = is_array($value)
                    ? json_encode($value, JSON_UNESCAPED_UNICODE)
                    : $value;

                sedQuestionAnswer::create([
                    'dni'       => $dni,
                    'slug_sed'  => $slug,
                    'ruc'       => null,
                    'sed_id'    => null,
                    'question'  => $question,
                    'answer'    => $answer,
                    'order'     => (int) filter_var($key, FILTER_SANITIZE_NUMBER_INT)
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Encuesta guardada correctamente'
            ]);
        } catch (\Throwable $th) {

            DB::rollBack();

            return response()->json([
                'status' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }































    public function participantConsultation(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'slug' => 'required|string',
                'dni'  => 'required',
            ]);

            $fair = Fair::where('slug', $validatedData['slug'])->first();

            if (!$fair) {
                return response()->json([
                    'message' => 'Evento no encontrado',
                    'status'  => 404
                ], 404);
            }

            $postulante = UgsePostulante::where('documentnumber', $validatedData['dni'])->first();

            if (!$postulante) {
                return response()->json([
                    'message' => 'no-se-registro',
                    'status'  => 404
                ]);
            }

            $asistencia = SedAsistente::where('sed_id', $fair->id)
                ->where('dni', $postulante->documentnumber)
                ->first();

            if (!$asistencia) {
                return response()->json([
                    'message' => 'no-se-registro',
                    'status'  => 404
                ], 404);
            }

            $nombreCompleto = strtoupper(trim(
                $postulante->name . ' ' . $postulante->lastname . ' ' . $postulante->middlename
            ));


            $status = is_null($asistencia->attendance) ? 'asistio' : 'ya-estas-en-sala';

            return response()->json([
                'data' => [
                    'participant' => $nombreCompleto,
                    'status'      => $status,
                ],
                'status' => 200
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error interno',
                'error'   => $e->getMessage(),
                'status'  => 500
            ], 500);
        }
    }

    public function registerAttendance(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'slug'     => 'required|string',
                'dni'      => 'required|string',
                'attended' => 'required|string',
            ]);

            $fair = Fair::where('slug', $validatedData['slug'])->first();

            if (!$fair) {
                return response()->json([
                    'message' => 'Evento no encontrado',
                    'status'  => 404
                ], 404);
            }

            $postulante = UgsePostulante::where('documentnumber', $validatedData['dni'])->first();

            if (!$postulante) {
                return response()->json([
                    'message' => 'Postulante no encontrado',
                    'status'  => 404
                ], 404);
            }

            $updated = SedAsistente::where('sed_id', $fair->id)
                ->where('dni', $postulante->documentnumber)
                ->update([
                    'attendance' => $validatedData['attended']
                ]);

            if (!$updated) {
                return response()->json([
                    'message' => 'No se encontró registro de asistencia',
                    'status'  => 404
                ], 404);
            }

            return response()->json([
                'message' => 'Asistencia registrada correctamente',
                'status'  => 200
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error interno',
                'error'   => $e->getMessage(),
                'status'  => 500
            ], 500);
        }
    }

    public function markAttendance(Request $request)
    {
        try {
            // 1. Validar que el payload traiga lo necesario
            $request->validate([
                'slug' => 'required|string',
                'dni'  => 'required'
            ]);

            // 2. Buscar el evento por slug para obtener el ID
            $fair = Fair::where('slug', $request->slug)->firstOrFail();

            // 3. Buscar al asistente por sed_id y dni
            $asistente = SedAsistente::where('sed_id', $fair->id)
                ->where('dni', $request->dni)
                ->first();

            // Si no existe el asistente en ese evento
            if (!$asistente) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'El DNI ingresado no está registrado como asistente de este evento.'
                ], 404);
            }

            // 4. Actualizar el campo attendance con el formato "27/03/2026 12:23"
            // Usamos now() de Carbon con el formato específico
            $asistente->attendance = Carbon::now()->format('d/m/Y H:i');
            $asistente->save();

            return response()->json([
                'status'  => 200,
                'message' => 'Asistencia marcada correctamente.',
                'data'    => [
                    'hora_asistencia' => $asistente->attendance,
                    'dni' => $asistente->dni
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => 404,
                'message' => 'Evento (slug) no encontrado.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al marcar asistencia: ' . $e->getMessage());
            return response()->json([
                'status'  => 500,
                'message' => 'Error interno al procesar la asistencia.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
