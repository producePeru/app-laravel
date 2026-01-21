<?php

namespace App\Http\Controllers\MujerProduce;

use App\Http\Controllers\Controller;
use App\Models\MPAttendance;
use App\Models\MPCapacitador;
use App\Models\MPDiagnostico;
use App\Models\MPDiagnosticoOption;
use App\Models\MPDiagnosticoResponse;
use App\Models\MPEvent;
use App\Models\MPParticipant;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MujerProduceController extends Controller
{
    public function registerCapacitador(Request $request)
    {
        try {

            // Validación
            $request->validate([
                'name' => 'required|string|max:255',
                'dni'  => 'nullable|string|max:20|unique:mp_capacitadores,dni',
            ]);

            // Registro
            $capacitador = MPCapacitador::create([
                'name' => $request->name,
                'dni'  => $request->dni,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Capacitador registrado correctamente.',
                'status' => 200
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {

            // Errores de validación
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {

            // Errores inesperados del servidor
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error inesperado.',
                'error'   => $e->getMessage() // puedes quitar esto en producción
            ], 500);
        }
    }

    public function allCapacitadores(Request $request)
    {
        try {

            // Parámetros
            $page = $request->get('page', 1);
            $name = $request->get('name', null);

            // Query base
            $query = MpCapacitador::query();

            // FILTRO POR NOMBRE (LIKE)
            if ($name) {
                $query->where('name', 'LIKE', "%{$name}%");
            }

            // PAGINACIÓN (10 por página)
            $capacitadores = $query->orderBy('id', 'desc')->paginate(50, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'message' => 'Listado obtenido correctamente.',
                'data' => $capacitadores,
                'status' => 200
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener el listado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // EVENTOS

    private function generateSlug($title, $date)
    {
        // Convertir a slug base
        $base = \Illuminate\Support\Str::slug($title);

        $slug = $base;
        $counter = 2;

        // Buscar si existe
        while (MpEvent::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }


    public function mpStoreEvent(Request $request)
    {
        try {

            // Validación
            $request->validate([
                'title'          => 'required|string|max:255',
                'component'      => 'required|integer|in:1,2,3,4,5',
                'capacitador_id' => 'required|exists:mp_capacitadores,id',
                'city_id'        => 'nullable',
                'province_id'    => 'nullable',
                'district_id'    => 'nullable',
                'modality_id'    => 'required|exists:modalities,id',
                'place'          => 'nullable|string|max:255',
                'date'           => 'nullable|date_format:Y-m-d',
                // 'hours'          => 'nullable|string|max:100',
                'startDate'      => 'nullable|date_format:Y-m-d',
                'endDate'        => 'nullable|date_format:Y-m-d',
                // 'training_time'  => 'required',

                'link'           => 'nullable',
                'aliado'         => 'nullable',
                'hourStart'      => 'nullable',
                'hourEnd'        => 'nullable'
            ]);

            // Generar slug
            $slug = $this->generateSlug($request->title, $request->date);

            // Crear
            $evento = MpEvent::create([
                'title'          => $request->title,
                'slug'           => $slug,
                'component'      => $request->component,
                'capacitador_id' => $request->capacitador_id,
                'city_id'        => $request->city_id,
                'province_id'    => $request->province_id,
                'district_id'    => $request->district_id,
                'modality_id'    => $request->modality_id,
                'place'          => $request->place,
                'date'           => $request->date,
                'hours'          => $request->hours,
                'startDate'      => $request->startDate,
                'endDate'        => $request->endDate,
                // 'training_time'  => $request->training_time,

                'link'           => $request->link,
                'aliado'         => $request->aliado,
                'hourStart'      => $request->hourStart,
                'hourEnd'        => $request->hourEnd
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Evento registrado correctamente.',
                'data' => $evento,
                'status' => 200
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function mpIndexEvents(Request $request)
    {
        $filters = [
            'year'      =>  $request->input('year'),
            'startDate' =>  $request->input('startDate'),
            'endDate'   =>  $request->input('endDate'),
            'name'      =>  $request->input('name'),
            'orderby'   =>  $request->input('orderby'),
        ];

        $query = MpEvent::query();

        $query->withItems($filters)
            ->withCount('attendances')
            ->orderBy('created_at', 'DESC');

        $items = $query->paginate(100)->through(function ($item) {
            return $this->mapItems($item);
        });

        return response()->json([
            'data'   => $items,
            'status' => 200
        ]);
    }

    private function mapItems($item)
    {
        return [
            'id'                => $item->id,
            'title'             => $item->title,
            'slug'              => $item->slug,
            'city_id'           => $item->city->id ?? null,
            'city_name'         => $item->city->name ?? '🏷 VIRTUAL',
            'place'             => $item->place ?? 'Plataforma Virtual',
            'date_format'       => $item->date ? Carbon::parse($item->date)->format('d/m/Y') : null,
            'date'              => $item->date,
            // 'hours'             => $item->hours,
            'hourStart'         => $item->hourStart,
            'hourEnd'           => $item->hourEnd,

            'startDate'         => $item->startDate,
            'endDate'           => $item->endDate,
            'component'         => $item->component,
            'capacitador_id'    => $item->capacitador_id,
            'modality_id'       => $item->modality_id,
            'training_time'     => $item->training_time,
            'count'             => $item->attendances_count,

            'link'              => $item->link,
            'aliado'            => $item->aliado,
            'province_id'       => $item->province->id ?? null,
            'province_name'     => $item->province->name ?? null,
            'district_id'       => $item->district->id ?? null,
            'district_name'     => $item->district->name ?? null,

        ];
    }

    public function mpUpdateEvent(Request $request, $id)
    {
        try {

            $evento = MpEvent::findOrFail($id);

            // Validación
            $request->validate([
                'title'          => 'sometimes|string|max:255',
                'component'      => 'sometimes|integer|in:1,2,3,4,5',
                'capacitador_id' => 'sometimes|exists:mp_capacitadores,id',
                'city_id'        => 'sometimes|exists:cities,id',
                'modality_id'    => 'sometimes|exists:modalities,id',
                'place'          => 'nullable|string|max:255',
                'date'           => 'nullable|date_format:Y-m-d',
                'hours'          => 'nullable|string|max:100',
                'startDate'      => 'nullable|date_format:Y-m-d',
                'endDate'        => 'nullable|date_format:Y-m-d',
                'training_time'  => 'sometimes|integer'
            ]);

            // NO actualizar slug
            $data = $request->except(['slug']);

            // Actualizar
            $evento->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Evento actualizado correctamente.',
                'data' => $evento,
                'status' => 200
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            return response()->json([
                "success" => false,
                "message" => "Evento no encontrado."
            ], 404);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al actualizar el evento.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function mpAttendance(Request $request, $slug)
    {
        try {

            // FILTRO ÚNICO MULTICAMPO
            $filters = [
                'search' => $request->input('name'),
            ];

            // 1. Buscar evento
            $event = MPEvent::where('slug', $slug)->first();

            if (!$event) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Evento no encontrado'
                ], 404);
            }

            // 2. Query base
            $query = MPAttendance::where('event_id', $event->id)
                ->with([
                    'event',

                    'participant.city',
                    'participant.province',
                    'participant.dictrict',
                    'participant.typeDocument',
                    'participant.country',
                    'participant.civilStatus',
                    'participant.gender',
                    'participant.degree',
                    'participant.roleCompany',

                    'participant.economicSector',
                    'participant.rubro',
                    'participant.comercialActivity'
                ])
                ->orderBy('created_at', 'DESC');

            // 3. Aplicar filtro multicampo
            if (!empty($filters['search'])) {

                $search = $filters['search'];

                $query->whereHas('participant', function ($q) use ($search) {
                    $q->where('ruc', 'LIKE', "%{$search}%")
                        ->orWhere('social_reason', 'LIKE', "%{$search}%")
                        ->orWhere('doc_number', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            // 4. Paginación + mapeo
            $items = $query->paginate(100)->through(function ($item) {
                return $this->mapAttendance($item);
            });

            return response()->json([
                'data'       => $items,
                'status'     => 200,
                'eventTitle' => $event->title
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'message' => 'Error al procesar la solicitud',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    private function mapAttendance($item)   
    {
        return [
            'id'                => $item->id,
            'ruc'               => $item->participant->ruc ?? null,      
            'city'              => $item->participant->city->name ?? null,
            'province'          => $item->participant?->province->name ?? null,
            'district'          => $item->participant?->dictrict->name ?? null,
            'socialReason'      => $item->participant?->social_reason ?? null,
            'roleCompany'       => $item->participant?->roleCompany->name ?? null,
            'typeDocument'      => $item->participant?->typeDocument->name ?? null,
            'dni'               => $item->participant->doc_number,
            'country'           => $item->participant->country->name ?? null,
            'date_of_birth'     => $item->participant->date_of_birth,
            'name'              => $item->participant->names,
            'last_name'         => $item->participant->last_name,
            'middle_name'       => $item->participant->middle_name,
            'civilStatus'       => $item->participant->civilStatus->name ?? null,
            'numSoons'          => $item->participant->num_soons,
            'gender'            => $item->participant->gender->name ?? null,
            'sick'              => $item->participant->sick,
            'degree'            => $item->participant->degree->name ?? null,
            'phone'             => $item->participant->phone,
            'email'             => $item->participant->email,
            'economicSector'    => $item->participant->economicSector->name ?? null,
            'rubro'             => $item->participant->rubro->name ?? null,
            'comercialActivity' => $item->participant->comercialActivity->name ?? null,
            'event'             => $item->event->component == 1 ? 'GESTIÓN EMPRESARIAL' : 'HABILIDADES PERSONALES',
            'attendance'        => $item->attendance ? true : false,
            'obs_dni'           => $item->participant->obs_dni == 1 ? '🚩' : '✔',
            'obs_ruc'           => is_null($item->participant->ruc) ? ' ' : ($item->participant->obs_ruc == 1 ? '🚩' : '✔'),
            'created_at'        => $item->created_at->format('d/m/Y H:i:s'),

            'participant_id'        => $item->participant->id,
            'economic_sector_id'    => $item->participant->economic_sector_id ?? null,
            'rubro_id'              => $item->participant->rubro_id ?? null,
            'comercial_activity_id' => $item->participant->comercial_activity_id ?? null,
            'country_id'            => $item->participant->country_id ?? null,
            'city_id'               => $item->participant->city_id ?? null,
            'province_id'           => $item->participant->province_id ?? null,
            'district_id'           => $item->participant->district_id ?? null,
            't_doc_id'              => $item->participant->t_doc_id ?? null,
            'gender_id'             => $item->participant->gender_id ?? null,
            'sick'                  => $item->participant->sick ?? null,
            'academicdegree_id'     => $item->participant->academicdegree_id ?? null,
            'civil_status_id'       => $item->participant->civil_status_id ?? null,
            'role_company_id'       => $item->participant->role_company_id ?? null,
        ];
    }

    public function createQuestionDiagnostic(Request $request)
    {
        try {

            // VALIDACIÓN DEL REQUEST
            $request->validate([
                'label'     => 'required|string|max:250',
                'type'      => 'required|in:t,o,l',
                'required'  => 'required|in:s,n',
                'options'   => 'nullable|array',
            ]);

            // =====================================================
            // 1. GENERAR MODEL ÚNICO (slug)
            // =====================================================
            $baseModel = Str::slug($request->label, '_');

            $model = $baseModel;
            $count = 1;

            // Asegurar que el model sea único en BD
            while (MPDiagnostico::where('model', $model)->exists()) {
                $count++;
                $model = $baseModel . '_' . $count;
            }

            // =====================================================
            // 2. CREAR LA PREGUNTA PRINCIPAL
            // =====================================================
            $question = MPDiagnostico::create([
                'label'     => $request->label,
                'type'      => $request->type,
                'model'     => $model,
                'required'  => $request->required === 's' ? 1 : 0,
                'status'    => 1  // siempre activo
            ]);

            // =====================================================
            // 3. SI type = 'o', GUARDAR OPCIONES
            // =====================================================
            if ($request->type === 'o' && !empty($request->options)) {

                foreach ($request->options as $opt) {
                    if (!empty($opt['value'])) {
                        MPDiagnosticoOption::create([
                            'name'             => $opt['value'],
                            'diag_pregunta_id' => $question->id
                        ]);
                    }
                }
            }

            return response()->json([
                'status'  => 200,
                'message' => 'Pregunta registrada correctamente',
                'data'    => $question
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status'  => 422,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'message' => 'Error interno al crear la pregunta',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function getQuestionDiagnostic(Request $request)
    {
        try {
            $questions = MPDiagnostico::with(['options'])
                ->orderBy('position', 'ASC') // orden visual desde 1
                ->get();

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


    public function updateQuestionDiagnostic(Request $request, $id)
    {
        try {

            // =====================================================
            // 0. VALIDACIÓN DEL REQUEST
            // =====================================================
            $request->validate([
                'label'     => 'required|string|max:250',
                'type'      => 'required|in:t,o',
                'required'  => 'required|in:s,n',
                'options'   => 'nullable|array',
            ]);


            // =====================================================
            // 1. BUSCAR LA PREGUNTA
            // =====================================================
            $question = MPDiagnostico::find($id);

            if (!$question) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Pregunta no encontrada'
                ], 404);
            }


            // =====================================================
            // 2. SI CAMBIÓ EL LABEL, GENERAR NUEVO MODEL (único)
            // =====================================================
            $model = $question->model;

            if ($question->label !== $request->label) {

                $baseModel = Str::slug($request->label, '_');
                $model = $baseModel;
                $count = 1;

                while (MPDiagnostico::where('model', $model)->where('id', '!=', $id)->exists()) {
                    $count++;
                    $model = $baseModel . '_' . $count;
                }
            }


            // =====================================================
            // 3. ACTUALIZAR LA PREGUNTA
            // =====================================================
            $question->update([
                'label'     => $request->label,
                'type'      => $request->type,
                'model'     => $model,
                'required'  => $request->required === 's' ? 1 : 0,
            ]);


            // =====================================================
            // 4. MANEJO DE OPCIONES
            // =====================================================

            // Siempre eliminar opciones actuales
            MPDiagnosticoOption::where('diag_pregunta_id', $id)->delete();

            // Si el tipo es "o", volver a insertar
            if ($request->type === 'o' && !empty($request->options)) {

                foreach ($request->options as $opt) {
                    if (!empty($opt['value'])) {
                        MPDiagnosticoOption::create([
                            'name'             => $opt['value'],
                            'diag_pregunta_id' => $question->id
                        ]);
                    }
                }
            }


            return response()->json([
                'status'  => 200,
                'message' => 'Pregunta actualizada correctamente',
                'data'    => $question->load('options')
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
                'message' => 'Error interno al actualizar la pregunta',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus($id)
    {
        try {

            // Buscar la pregunta
            $question = MPDiagnostico::find($id);

            if (!$question) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Pregunta no encontrada'
                ], 404);
            }

            // Actualizar el estado
            $question->update([
                'status' => $question->status === 1 ? 0 : 1
            ]);

            return response()->json([
                'status'  => 200,
                'message' => 'Estado actualizado correctamente',
                'data'    => $question
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'message' => 'Error al actualizar el estado',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function mpIndexParticipantDiagnostic(Request $request)
    {
        $search = trim($request->input('name'));
        $startDate  = $request->input('startDate');
        $endDate    = $request->input('endDate');

        // =========================
        // PREGUNTAS ACTIVAS (1 sola vez)
        // =========================

        $questions = MPDiagnostico::with('options')
            ->where('status', 1)
            ->where('type', '!=', 'l')   // NO mostrar tipo 'l'
            ->orderBy('position', 'ASC') // Ordenar por position
            ->get();

        // =========================
        // PARTICIPANTES PAGINADOS
        // =========================
        $participants = MPParticipant::with([
            'diagnosticoResponses.option',
            'comercialActivity:id,name',
            'rubro:id,name',
            'economicSector:id,name'
        ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('ruc', 'like', "%{$search}%")
                        ->orWhere('doc_number', 'like', "%{$search}%")
                        ->orWhere('names', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereHas('diagnosticoResponses', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [
                        $startDate . ' 00:00:00',
                        $endDate . ' 23:59:59'
                    ]);
                });
            })
            ->orderByRaw('EXISTS (
        SELECT 1
        FROM mp_diag_respuestas r
        WHERE r.participant_id = mp_participantes.id
    ) DESC')
    ->orderBy('id', 'DESC')
    ->paginate(100); 


        // Transformar SOLO el data
        $participants->getCollection()->transform(function ($participant) use ($questions) {
            return $this->mapParticipantResponses($participant, $questions);
        });

        // =========================
        // RESPONSE FINAL
        // =========================
        return response()->json(
            array_merge(
                $participants->toArray(),
                [
                    'questions' => $questions->map(function ($q) {
                        return [
                            'id'       => $q->id,
                            'label'    => $q->label,
                            'model'    => $q->model,
                            'type'     => $q->type === 't' ? 'text' : 'select',
                            'required' => (bool) $q->required,
                            'options'  => $q->options->map(fn($opt) => [
                                'id'    => $opt->id,
                                'label' => $opt->name,
                            ]),
                        ];
                    }),
                ]
            )
        );
    }

    private function mapParticipantResponses(
        MPParticipant $participant,
        $questions
    ) {
        $responses = $participant->diagnosticoResponses
            ->keyBy('question_id');

        $mappedResponses = $questions->map(function ($question) use ($responses) {

            $response = $responses->get($question->id);

            return [
                'question_model' => $question->model,

                // SI es select → name
                // SI es text → answer_text
                'answer' => $response
                    ? ($response->answer_text
                        ?? $response->option?->name)
                    : null,
            ];
        });

        // ✅ Última fecha real por participant_id
        $lastDiagnosticoAt = $participant->diagnosticoResponses
            ->max('created_at');


        return [
            'participant_id'    => $participant->id,

            'nombre_completo'   => $participant->names,
            'apellidos'         => $participant->last_name . ' ' . $participant->middle_name,
            'fecha_nacimiento'  => Carbon::parse($participant->birth_date)->format('d/m/Y'),
            'celular'           => $participant->phone,
            'tipo_documento'    => optional($participant->typeDocument)->avr,
            'doc_number'        => $participant->doc_number,
            'email'             => $participant->email,

            'ruc'               => $participant->ruc ?? null,
            'actividad'         => $participant->comercialActivity->name ?? null,
            'rubro'             => $participant->rubro->name ?? null,
            'economicSector'    => $participant->economicSector->name ?? null,

            'responses'         => $mappedResponses,

            'registrado'        => $lastDiagnosticoAt
            ? Carbon::parse($lastDiagnosticoAt)->format('d/m/Y H:i')
            : null,
        ];
    }


    public function updateOrder(Request $request)
    {
        $request->validate([
            '*.id' => 'required|exists:mp_diag_preguntas,id',
            '*.position' => 'required|integer|min:1|max:999',
        ]);

        foreach ($request->all() as $item) {
            MPDiagnostico::where('id', $item['id'])
                ->update(['position' => $item['position']]);
        }

        return response()->json(['status' => 200]);
    }

    
    public function toggleAttendance(Request $request)
    {
        try {

            // 1. Buscar evento por slug
            $event = MPEvent::where('slug', $request->slug)->first();

            if (!$event) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Evento no encontrado'
                ], 404);
            }

            // 2. Buscar participante por RUC + Documento
            $participant = MPParticipant::where('ruc', $request->ruc)
                ->where('doc_number', $request->doc_number)
                ->first();

            if (!$participant) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Participante no encontrado'
                ], 404);
            }

            // 3. Buscar asistencia
            $attendance = MPAttendance::where('event_id', $event->id)
                ->where('participant_id', $participant->id)
                ->first();

            // 4. Toggle o crear
            if ($attendance) {
                $attendance->attendance = $attendance->attendance ? null : 1;
                $attendance->save();
            } else {
                $attendance = MPAttendance::create([
                    'event_id'       => $event->id,
                    'participant_id' => $participant->id,
                    'attendance'     => 1,
                ]);
            }

            return response()->json([
                'status'     => 200,
                'message'    => 'Asistencia actualizada correctamente',
                'attendance' => (bool) $attendance->attendance,
            ]);

        } catch (\Throwable $e) {

            // Log técnico (backend)
            Log::error('Error toggleAttendance', [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
                'file'  => $e->getFile(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Ocurrió un error al procesar la asistencia'
            ], 500);
        }
    }

    public function deleteAssistant(Request $request)
    {
        $request->validate([
            'event_slug'     => 'required|string',
            'participant_id' => 'required|integer'
        ]);

        // Buscar el evento por slug
        $event = MPEvent::where('slug', $request->event_slug)->first();

        if (!$event) {
            return response()->json([
                'message' => 'Evento no encontrado'
            ], 404);
        }

        // Eliminar la asistencia
        $deleted = MPAttendance::where('event_id', $event->id)
            ->where('participant_id', $request->participant_id)
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'message' => 'Asistencia no encontrada'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Asistencia eliminada correctamente'
        ]);
    }
}
