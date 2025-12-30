<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Models\Fair;
use App\Mail\FairSedInfoMail;
use App\Models\CyberwowBrand;
use App\Models\CyberwowLeader;
use App\Models\CyberwowOffer;
use App\Models\CyberwowParticipant;
use App\Models\FairPostulate;
use App\Models\UgsePostulante;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use PDF;
use Illuminate\Support\Str;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Auth;


class UgsePostulanteController extends Controller
{


    public function usersRegisteredList(Request $request, $slug)
    {
        // Buscar la feria por su slug
        $fair = Fair::where('slug', $slug)->firstOrFail();

        $filters = [
            'name'          => $request->input('name'),
            'dateStart'     => $request->input('dateStart'),
            'dateEnd'       => $request->input('dateEnd'),

        ];

        // Crear la consulta base y filtrar por el ID del evento
        $query = UgsePostulante::where('event_id', $fair->id);

        $query->withBasicFilters($filters);

        // Eager Loading de relaciones
        $query->with([
            'economicsector',
            'businessman:id,typedocument_id,documentnumber,name,lastname,middlename,birthday,gender_id',
            'businessman.gender:id,name',
            'category:id,name',
            'comercialactivity:id,name',
            'city:id,name',
            'province:id,name',
            'district:id,name',
            'howKnowEvent:id,name',
            'event',
        ]);

        $postulantes = $query->paginate(150)->through(function ($item) {
            return $this->mapPostulantes($item);
        });

        return response()->json([
            'data'   => $postulantes,
            'event' => [
                'id'   => $fair->id,
                'name' => $fair->title,
            ],
            'status' => 200
        ]);
    }

    private function mapPostulantes($item)
    {
        return [
            'id'                        => $item->id,
            'ruc'                       => $item->ruc ?? null,
            'comercialName'             => $item->comercialName ?? null,
            'economicsector_id'         => $item->economicsector->id ?? null,
            'economicsector_name'       => $item->economicsector->name ?? null,
            'rubro_id'                  => $item->category->id ?? null,
            'rubro_name'                => $item->category->name ?? null,
            'comercialactivity_id'      => $item->comercialactivity->id ?? null,
            'comercialactivity_name'    => $item->comercialactivity->name ?? null,
            'city_id'                   => $item->city->id ?? null,
            'city_name'                 => $item->city->name ?? null,
            'province_id'               => $item->province->id ?? null,
            'province_name'             => $item->province->name ?? null,
            'district_id'               => $item->district->id ?? null,
            'district_name'             => $item->district->name ?? null,
            'address'                   => $item->address ?? null,
            'typedocument_id'           => $item->businessman->typedocument_id ?? null,
            'typedocument_name'         => $item->businessman->typedocument->name ?? null,
            'documentnumber'            => $item->businessman->documentnumber ?? $item->documentnumber,
            'name'                      => $item->businessman->name ?? $item->name,
            'lastname'                  => $item->businessman->lastname ?? $item->lastname,
            'middlename'                => $item->businessman->middlename ?? $item->middlename,
            'email'                     => $item->email,
            'phone'                     => $item->phone,
            'event_id'                  => $item->event_id,
            'positionCompany'           => $item->positionCompany,
            'instagram'                 => $item->instagram,
            'facebook'                  => $item->facebook,
            'web'                       => $item->web,
            'attended'                  => $item->attended ?? null,
            'socialReason'              => $item->socialReason,
            'typeAsistente'             => $item->typeAsistente,
            'sick'                      => $item->sick == 'si' ? 'SI' : 'NO',
            'birthday'                  => $item->businessman->birthday ?? $item->birthday,
            'created_at'                => $item->created_at ? Carbon::parse($item->created_at)->format('d/m/Y h:i A') : null,
            'asistio'                   => $item->attended ? true : false,
            'howKnowEvent_id'           => $item->howKnowEvent->id ?? null,
            'howKnowEvent_name'         => $item->howKnowEvent->name ?? null,
            'gender_id'                 => $item->businessman->gender->id ?? '-',
            'gender_name' => $item->businessman
                ? ($item->businessman->gender->name === 'FEMENINO' ? 'F' : 'M')
                : ($item->gender_id == 1 ? 'M' : 'F'),
            'economicsector_id'         => $item->economicsector->id ?? null,
            'economicsector_name'       => $item->economicsector->name ?? null,
            'comercialactivity_id'      => $item->comercialactivity->id ?? null,
            'comercialactivity_name'    => $item->comercialactivity->name ?? null,
            'category_id'               => $item->category->id ?? null,
            'category_name'             => $item->category->name ?? null,
            'city_id'                   => $item->city->id ?? null,
            'city_name'                 => $item->city->name ?? null,


            'event' => $item->event ? [
                'id'   => $item->event->id ?? null,
                'name' => $item->event->title  // suponiendo que el evento tiene "title"
            ] : null,

        ];
    }

    public function store(Request $request)
    {
        try {

            $validatedData = $request->validate([
                'ruc' => 'required|max:11',
                'comercialName' => 'required|string|max:200',
                'socialReason' => 'required|string|max:200',
                'economicsector_id' => 'required|integer',
                'comercialactivity_id' => 'required|integer',
                'category_id' => 'required|integer',
                'city_id' => 'required|integer',
                'typedocument_id' => 'required|integer',
                'documentnumber' => 'required',
                'lastname' => 'required|string|max:100',
                'name' => 'required|string|max:100',
                'gender_id' => 'required|integer',
                'howKnowEvent_id' => 'required|integer',
                'slug' => 'required|string',
                'phone' => 'required|max:9',
                'email' => 'required',
                'birthday' => 'required',
                'positionCompany' => 'required',
                'mailer' => 'nullable|string',
            ]);



            $fair = Fair::where('slug', $request->slug)->firstOrFail();

            $exists = UgsePostulante::where('event_id', $fair->id)
                ->where('documentnumber', $request->documentnumber)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Ya estás registrado en este evento con este número de documento.',
                    'status' => 409,
                ]);
            }

            DB::beginTransaction();

            $representante = UgsePostulante::create([
                'ruc' => $request->ruc,
                'comercialName' => $request->comercialName,
                'socialReason' => $request->socialReason,
                'economicsector_id' => $request->economicsector_id,
                'comercialactivity_id' => $request->comercialactivity_id,
                'category_id' => $request->category_id,
                'city_id' => $request->city_id,
                'typeAsistente' => 1,
                'typedocument_id' => $request->typedocument_id,
                'documentnumber' => $request->documentnumber,
                'lastname' => $request->lastname,
                'middlename' => $request->middlename,
                'name' => $request->name,
                'gender_id' => $request->gender_id,
                'sick' => $request->sick,
                'phone' => $request->phone,
                'email' => $request->email,
                'birthday' => $request->birthday,
                'positionCompany' => $request->positionCompany,
                'bringsGuest' => $request->registrarInvitado ? 1 : 0,
                'howKnowEvent_id' => $request->howKnowEvent_id,
                'event_id' => $fair->id,
                'instagram' => $request->instagram,
                'facebook' => $request->facebook,
                'web' => $request->web,
            ]);


            $mailer = $request->mailer ?? 'digitalization';


            // ✅ Codificar logo en base64
            // ✅ Generar QR en base64 usando Endroid QR Code
            $logoPath = public_path('images/logo/sed.png');
            $logoBase64 = base64_encode(file_get_contents($logoPath));
            $logoMime = mime_content_type($logoPath);
            $logoDataUri = "data:$logoMime;base64,$logoBase64";
            $qrResult = Builder::create()
                ->writer(new PngWriter())
                ->data($representante->documentnumber)
                ->size(200)
                ->margin(10)
                ->build();

            $qrBase64 = base64_encode($qrResult->getString());


            $qrResult = Builder::create()
                ->writer(new PngWriter())
                ->data($representante->documentnumber)
                ->size(200)
                ->margin(10)
                ->build();

            // Obtener la imagen QR en base64
            $qrBase64 = base64_encode($qrResult->getString());

            $pdf = PDF::loadView('pdf.ticket_entry', [
                'fair' => $fair,
                'participantName' => "{$representante->name} {$representante->lastname}",
                'qrBase64' => $qrBase64,
                'logoDataUri' => $logoDataUri,
            ]);

            $filename = 'entrada_' . Str::random(10) . '.pdf';
            $filepath = storage_path("app/public/entradas/{$filename}");
            Storage::makeDirectory('public/entradas');
            $pdf->save($filepath);

            $participantName = "{$representante->name} {$representante->lastname}";
            $messageContent = strip_tags($fair->msgSendEmail);



            Mail::mailer($mailer)
                ->to($representante->email)
                ->send(new FairSedInfoMail(
                    $messageContent,
                    $filepath,
                    $participantName,
                    $fair
                ));


            // Si el representante trae invitado
            if ($request->has('invitado') && $request->invitado === true) {
                $invitado = UgsePostulante::create([
                    'ruc' => $request->ruc,
                    'comercialName' => $request->comercialName,
                    'socialReason' => $request->socialReason,
                    'economicsector_id' => $request->economicsector_id,
                    'comercialactivity_id' => $request->comercialactivity_id,
                    'category_id' => $request->category_id,
                    'city_id' => $request->city_id,
                    'typeAsistente' => 2,
                    'typedocument_id' => $request->invitado_typedocument_id,
                    'documentnumber' => $request->invitado_documentnumber,
                    'lastname' => $request->invitado_lastname,
                    'middlename' => $request->invitado_middlename,
                    'name' => $request->invitado_name,
                    'gender_id' => $request->invitado_gender_id,
                    'sick' => $request->invitado_sick,
                    'phone' => $request->invitado_phone,
                    'email' => $request->invitado_email,
                    'birthday' => $request->invitado_birthday,
                    'positionCompany' => $request->invitado_positionCompany,
                    'bringsGuest' => 0,
                    'howKnowEvent_id' => $request->howKnowEvent_id,
                    'event_id' => $fair->id,
                    'instagram' => null,
                    'facebook' => null,
                    'web' => null,
                ]);

                $qrInvitado = Builder::create()
                    ->writer(new PngWriter())
                    ->data($invitado->documentnumber)
                    ->size(200)
                    ->margin(10)
                    ->build();
                $qrBase64Invitado = base64_encode($qrInvitado->getString());

                $pdfInvitado = PDF::loadView('pdf.ticket_entry', [
                    'fair' => $fair,
                    'participantName' => "{$invitado->name} {$invitado->lastname}",
                    'qrBase64' => $qrBase64Invitado,
                    'logoDataUri' => $logoDataUri,
                ]);

                $filenameInvitado = 'entrada_' . Str::random(10) . '.pdf';
                $filepathInvitado = storage_path("app/public/entradas/{$filenameInvitado}");
                $pdfInvitado->save($filepathInvitado);

                Mail::mailer($mailer)
                    ->to($invitado->email)
                    ->send(new FairSedInfoMail(
                        $messageContent,
                        $filepathInvitado,
                        "{$invitado->name} {$invitado->lastname}",
                        $fair
                    ));
            }


            DB::commit();


            return response()->json([
                'message' => 'Postulante registrado correctamente y correo enviado.',
                'representante_id' => $representante->id,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al registrar postulante.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // public function store(Request $request)
    // {
    //     try {

    //         $request->validate([
    //             'ruc' => 'required|max:11',
    //             'comercialName' => 'required|string|max:200',
    //             'socialReason' => 'required|string|max:200',
    //             'economicsector_id' => 'required|integer',
    //             'comercialactivity_id' => 'required|integer',
    //             'category_id' => 'required|integer',
    //             'city_id' => 'required|integer',
    //             'typedocument_id' => 'required|integer',
    //             'documentnumber' => 'required',
    //             'lastname' => 'required|string|max:100',
    //             'name' => 'required|string|max:100',
    //             'gender_id' => 'required|integer',
    //             'howKnowEvent_id' => 'required|integer',
    //             'slug' => 'required|string',
    //             'phone' => 'required|max:9',
    //             'email' => 'required',
    //             'birthday' => 'required',
    //             'positionCompany' => 'required',
    //             'mailer' => 'nullable|string',
    //         ]);

    //         $fair = Fair::where('slug', $request->slug)->firstOrFail();

    //         $exists = UgsePostulante::where('event_id', $fair->id)
    //             ->where('documentnumber', $request->documentnumber)
    //             ->exists();

    //         if ($exists) {
    //             return response()->json([
    //                 'message' => 'Ya estás registrado en este evento con este número de documento.',
    //                 'status' => 409,
    //             ]);
    //         }

    //         DB::beginTransaction();

    //         $representante = UgsePostulante::create($request->all());

    //         if (!$representante || !$representante->id) {
    //             DB::rollBack();
    //             return response()->json([
    //                 'message' => 'No se pudo registrar al postulante.',
    //                 'status' => 500
    //             ]);
    //         }

    //         $mailer = $request->mailer ?? 'digitalization';

    //         // ✅ Codificar logo en base64 & ✅ Generar QR en base64 usando Endroid QR Code

    //         $logoPath = public_path('images/logo/sed.png');
    //         $logoBase64 = base64_encode(file_get_contents($logoPath));
    //         $logoMime = mime_content_type($logoPath);
    //         $logoDataUri = "data:$logoMime;base64,$logoBase64";
    //         $qrResult = Builder::create()
    //             ->writer(new PngWriter())
    //             ->data($representante->documentnumber)
    //             ->size(200)
    //             ->margin(10)
    //             ->build();

    //         $qrBase64 = base64_encode($qrResult->getString());

    //         $qrResult = Builder::create()
    //             ->writer(new PngWriter())
    //             ->data($representante->documentnumber)
    //             ->size(200)
    //             ->margin(10)
    //             ->build();

    //         // Obtener la imagen QR en base64
    //         $qrBase64 = base64_encode($qrResult->getString());

    //         $pdf = PDF::loadView('pdf.ticket_entry', [
    //             'fair' => $fair,
    //             'participantName' => "{$representante->name} {$representante->lastname}",
    //             'qrBase64' => $qrBase64,
    //             'logoDataUri' => $logoDataUri,
    //         ]);

    //         $filename = 'entrada_' . Str::random(10) . '.pdf';
    //         $filepath = storage_path("app/public/entradas/{$filename}");
    //         Storage::makeDirectory('public/entradas');
    //         $pdf->save($filepath);

    //         $participantName = "{$representante->name} {$representante->lastname}";
    //         $messageContent = strip_tags($fair->msgSendEmail);

    //         Mail::mailer($mailer)
    //             ->to($representante->email)
    //             ->send(new FairSedInfoMail(
    //                 $messageContent,
    //                 $filepath,
    //                 $participantName,
    //                 $fair
    //             ));


    //         DB::commit();


    //         return response()->json([
    //             'message' => 'Postulante registrado correctamente y correo enviado.',
    //             'representante_id' => $representante->id,
    //             'status' => 200
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'message' => 'Error al registrar postulante.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }


    // devuelve el tipo de evento x el slug que te paso
    public function showFairBySlug($slug)
    {
        try {
            $fair = Fair::where('slug', $slug)->first();

            if (!$fair) {
                return response()->json([
                    'message' => 'Feria no encontrada',
                    'status'  => 404
                ], 404);
            }

            return response()->json([
                'data'   => $fair,
                'status' => 200
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al buscar la feria',
                'error'   => $e->getMessage(),
                'status'  => 500
            ], 500);
        }
    }


    // Actualizamos los datos del registrado
    public function update(Request $request, $id)
    {
        try {
            $postulante = UgsePostulante::findOrFail($id);

            // Solo actualiza los campos que están en $request y son fillables
            $fillable = (new UgsePostulante())->getFillable();
            $data = $request->only($fillable);

            $postulante->fill($data);
            $postulante->save();

            return response()->json([
                'message' => 'Postulante actualizado correctamente',
                'data'    => $postulante,
                'status'  => 200
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al actualizar el postulante',
                'error'   => $e->getMessage(),
                'status'  => 500
            ], 500);
        }
    }

    public function isRegistered(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'slug' => 'required|string',
                'dni' => 'required',
            ]);

            $fair = Fair::where('slug', $validatedData['slug'])->first();

            if (!$fair) {
                return response()->json(['message' => 'Evento no encontrado'], 404);
            }

            $postulante = UgsePostulante::where('event_id', $fair->id)
                ->where('documentnumber', $validatedData['dni'])
                ->first();

            if ($postulante) {
                return response()->json([
                    'name' => $postulante->name . ' ' . $postulante->lastname,
                    'doc' => $postulante->documentnumber,
                    'status' => 200
                ]);
            }

            return response()->json(['message' => 'No se ha registrado', 'status' => 400]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al actualizar el postulante',
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
                'attended' => 'required|string', // formato "DD-MM-YYYY HH:mm a"
            ]);

            $fair = Fair::where('slug', $validatedData['slug'])->first();

            if (!$fair) {
                return response()->json(['message' => 'Evento no encontrado'], 404);
            }

            $postulante = UgsePostulante::where('event_id', $fair->id)
                ->where('documentnumber', $validatedData['dni'])
                ->first();

            if (!$postulante) {
                return response()->json(['message' => 'Postulante no encontrado'], 404);
            }

            if ($postulante->attended !== null) {
                return response()->json([
                    'message' => 'Ya has sido registrado anteriormente',
                    'status'  => 400
                ]);
            }

            $postulante->attended = $validatedData['attended'];
            $postulante->save();

            return response()->json([
                'message' => 'Asistencia registrada correctamente',
                'status'  => 200
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al registrar la asistencia',
                'error'   => $e->getMessage(),
                'status'  => 500
            ], 500);
        }
    }

    public function deleteParticipante($id)
    {
        try {
            $participante = UgsePostulante::findOrFail($id);
            $participante->delete();

            return response()->json([
                'message' => 'Participante eliminado correctamente',
                'status'  => 200
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al eliminar el participante',
                'error'   => $e->getMessage(),
                'status'  => 500
            ], 500);
        }
    }

    public function registerSedEvent(Request $request)
    {
        try {
            $fair = Fair::where('slug', $request->slug)->firstOrFail();

            $data = $request->all();
            $data['event_id'] = $fair->id;
            $data['attended'] = Carbon::now()->format('d/m/Y h:i a');

            // Busca si ya existe el registro
            $postulante = UgsePostulante::where('event_id', $fair->id)
                ->where('documentnumber', $request->documentnumber)
                ->where('ruc', $request->ruc)
                ->first();

            if ($postulante) {
                // Actualiza los datos existentes
                $postulante->update($data);
                return response()->json(['message' => 'Datos actualizados correctamente', 'status' => 200]);
            } else {
                // Crea nuevo registro
                UgsePostulante::create($data);
                return response()->json(['message' => 'Registro exitoso', 'status' => 200]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Ocurrió un error al procesar el registro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateAttendedStatus(Request $request)
    {
        try {
            // 1. Buscar el evento por slug
            $fair = Fair::where('slug', $request->slug)->firstOrFail();

            // 2. Buscar al postulante por event_id, ruc y documentnumber
            $postulante = UgsePostulante::where('event_id', $fair->id)
                ->where('documentnumber', $request->documentnumber)
                ->firstOrFail();

            // 3. Actualizar el campo 'attended' según el valor de check
            if ($request->check) {
                $postulante->attended = $request->date;
            } else {
                $postulante->attended = null;
            }

            $postulante->save();

            return response()->json(['message' => 'Estado de asistencia actualizado correctamente.', 'status' => 200]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar asistencia.',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    // ******** Cyber-wow *************

    // cyberwow registra un nuevo evento
    public function cyberWowRegisterEvent(Request $request)
    {
        // 1) Validar datos mínimos
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'fairtype_id'   => 'required|integer|exists:fairtypes,id',
            'modality_id'   => 'required|integer|exists:modalities,id',
            'startDate'     => 'required|date',
            'endDate'       => 'required|date|after_or_equal:startDate',
            'metaMypes'     => 'nullable|integer|min:0',
            'city_id'       => 'nullable|integer|exists:cities,id',
            'fecha'         => 'nullable|date',
            'place'         => 'nullable|string|max:255',
            'hours'         => 'nullable|string|max:255',
            'msgEndForm'    => 'nullable|string',
            'msgSendEmail'  => 'nullable|string',
            'image_id'      => 'nullable|integer|exists:images,id',
        ]);

        try {
            // 2) Registrar el evento en fairs
            $fair = Fair::create([
                'title'        => $validated['title'],
                'description'  => $validated['description'] ?? null,
                'fairtype_id'  => $validated['fairtype_id'],
                'modality_id'  => $validated['modality_id'],
                'startDate'    => $validated['startDate'],
                'endDate'      => $validated['endDate'],
                'metaMypes'    => $validated['metaMypes'] ?? null,
                'city_id'      => $validated['city_id'] ?? null,
                'fecha'        => $validated['fecha'] ?? null,
                'place'        => $validated['place'] ?? null,
                'hours'        => $validated['hours'] ?? null,
                'msgEndForm'   => $validated['msgEndForm'] ?? null,
                'msgSendEmail' => $validated['msgSendEmail'] ?? null,
                'image_id'     => $validated['image_id'] ?? null,
                'slug'         => Str::slug($validated['title']) . '-' . Str::uuid(), // generar slug único
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Evento CyberWow registrado correctamente',
                'data'    => $fair,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el evento',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // cyberwow listame todos los asistentes

    public function cyberWowListAssistants(Request $request, $slug)
    {
        $user = Auth::user();

        // 1) Buscar la feria por slug
        $fair = Fair::where('slug', $slug)->firstOrFail();

        // 2) Armar query base con las relaciones definidas en tu modelo
        $query = CyberwowParticipant::with([
            'region',
            'provincia',
            'distrito',
            'sectorEconomico',
            'actividadComercial',
            'rubro',
            'tipoDocumento',
            'genero',
            'pais',
            'medioEntero'
        ])->where('event_id', $fair->id)
            ->orderBy('created_at', 'desc');

        // muestra solo la lista x user_id
        if ($request->isAsesor) {
            $query->where('user_id', $user->id);
        }

        // 3) Aplicar filtros dinámicos

        if ($request->filled('name')) {
            $search = trim($request->input('name'));

            $query->where(function ($q) use ($search) {
                $q->where('razonSocial', 'LIKE', "%{$search}%")
                    ->orWhere('nombreComercial', 'LIKE', "%{$search}%")
                    ->orWhere('ruc', 'LIKE', "%{$search}%")
                    ->orWhere('documentnumber', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%")
                    ->orWhere('lastname', 'LIKE', "%{$search}%")
                    ->orWhere('middlename', 'LIKE', "%{$search}%")
                    ->orWhereRaw("CONCAT_WS(' ', name, lastname, middlename) LIKE ?", ["%{$search}%"]);
            });
        }

        if ($request->filled('typeStatus')) {
            $typeStatus = $request->input('typeStatus');

            if ($typeStatus === 'asignados') {
                $query->whereNotNull('user_id');
            } elseif ($typeStatus === 'no_asignados') {
                $query->whereNull('user_id');
            }
            // si es "todos", no se aplica filtro
        }

        // 4) Paginación de 100 en 100 con transformación
        $participants = $query->paginate(100)->through(function ($item) {
            return $this->mapParticipant($item);
        });

        // 5) Respuesta JSON estructurada
        return response()->json([
            'data'   => $participants,
            'event'  => [
                'id'   => $fair->id,
                'name' => $fair->title,
            ],
            'status' => 200,
        ]);
    }

    private function mapParticipant($item)
    {
        return [
            'id' => $item->id,
            'documentnumber' => $item->documentnumber,
            'lastname' => $item->lastname,
            'middlename' => $item->middlename,
            'name' => $item->name,
            'phone' => $item->phone,
            'email' => $item->email,
            'sick' => $item->sick,

            'ruc' => $item->ruc,
            'razonSocial' => $item->razonSocial,
            'nombreComercial' => $item->nombreComercial,
            'socials' => $item->socials, // array gracias al cast

            'sectorEconomico' => $item->sectorEconomico->name ?? null,
            'actividadComercial' => $item->actividadComercial->name ?? null,
            'rubro' => $item->rubro->name ?? null,

            'city' => $item->region->name ?? null,
            'province' => $item->provincia->name ?? null,
            'district' => $item->distrito->name ?? null,

            'tipoDocumento' => $item->tipoDocumento->name ?? null,
            'genero' => $item->genero->name ?? null,
            'pais' => $item->pais->name ?? null,
            'medioEntero' => $item->medioEntero->name ?? null,
            'description' => $item->descripcion,

            'user_id' => $item->user_id,

            'paso1' => $item->paso1,
            'paso2' => $item->paso2,
            'paso3' => $item->paso3,

            'created_at' => $item->created_at->format('d/m/Y H:i'),
        ];
    }


    // eliminar participante del cyber-wow
    public function cyberWowDeleteParticipant($id)
    {
        try {
            $participant = CyberwowParticipant::findOrFail($id);
            $participant->delete();

            return response()->json([
                'message' => 'Participante eliminado correctamente',
                'status'  => 200
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al eliminar el participante',
                'error'   => $e->getMessage(),
                'status'  => 500
            ], 500);
        }
    }

    // Agregar un lider al evento
    public function selectLeaderForThisEvent(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'slug'    => 'required|string|exists:fairs,slug',
        ]);

        // Buscar el evento en Fair por slug
        $fair = Fair::where('slug', $request->input('slug'))->first();

        if (!$fair) {
            return response()->json([
                'message' => 'Evento no encontrado',
                'status'  => 404
            ]);
        }

        // Verificar si ya existe ese líder para el evento
        $exists = CyberwowLeader::where('user_id', $request->input('user_id'))
            ->where('wow_id', $fair->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'El usuario ya está registrado como líder en este evento',
                'status'  => 409 // conflicto
            ]);
        }

        // Crear registro con el modelo
        $leader = CyberwowLeader::create([
            'user_id' => $request->input('user_id'),
            'wow_id'  => $fair->id,
            'status'  => 1
        ]);

        return response()->json([
            'message' => 'Líder asignado correctamente',
            'data'    => $leader,
            'status'  => 200
        ]);
    }

    // asignar un lider a una empresa
    public function aCompanyToLeader(Request $request)
    {
        $request->validate([
            'slug'       => 'required|string|exists:fairs,slug',
            'user_id'    => 'required|integer|exists:users,id',
            'company_id' => 'required|integer'
        ]);

        $fair = Fair::where('slug', $request->input('slug'))->first();

        if (!$fair) {
            return response()->json([
                'message' => 'Evento no encontrado',
                'status'  => 404
            ]);
        }

        $participant = CyberwowParticipant::where('event_id', $fair->id)
            ->where('id', $request->input('company_id'))
            ->first();

        if (!$participant) {
            return response()->json([
                'message' => 'La empresa no está registrada en este evento',
                'status'  => 404
            ]);
        }

        $participant->user_id = $request->input('user_id');
        $participant->save();
        return response()->json([
            'message' => 'Líder asignado a la empresa correctamente',
            'data'    => $participant,
            'status'  => 200
        ]);
    }


    // cyberwow Empresas mini-dashboard
    public function cyberwowCompanyCount($slug)
    {
        $fair = Fair::where('slug', $slug)->first();

        if (!$fair) {
            return response()->json([
                'message' => 'Evento no encontrado',
                'status'  => 404
            ]);
        }

        $total       = CyberwowParticipant::where('event_id', $fair->id)->count();
        $no_asignadas = CyberwowParticipant::where('event_id', $fair->id)
            ->whereNull('user_id')
            ->count();
        $asignadas   = CyberwowParticipant::where('event_id', $fair->id)
            ->whereNotNull('user_id')
            ->count();

        return response()->json([
            'total'        => $total,
            'no_asignadas' => $no_asignadas,
            'asignadas'    => $asignadas,
            'status'       => 200
        ]);
    }

    // pasar al paso 2
    public function cyberwowStep1(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
            'company_id' => 'required|integer'
        ]);

        // Buscamos la feria por slug
        $fair = Fair::where('slug', $request->slug)->first();

        if (!$fair) {
            return response()->json([
                'message' => 'Evento no encontrado',
                'status'  => 404
            ]);
        }

        $participant = CyberwowParticipant::where('event_id', $fair->id)
            ->where('id', $request->company_id)
            ->first();


        if (!$participant) {
            return response()->json([
                'message' => 'Participante no encontrado',
                'status'  => 404
            ]);
        }

        $participant->paso1 = 1;
        $participant->save();

        return response()->json([
            'message' => 'Datos actualizados correctamente',
            'status'  => 200
        ]);
    }


    public function cyberwowStep2(Request $request)
    {
        $request->validate([
            'isService'   => 'required|string|max:2',
            'logo256_id'  => 'required|exists:images,id',
            'logo160_id'  => 'required|exists:images,id',
            'description' => 'nullable|string|max:1000',
            'slug'        => 'required|string|exists:fairs,slug',
            'url'         => 'required|url|max:255',
            'company_id'  => 'required|integer',
            'red'         => 'integer'
        ]);

        try {
            // Buscar la feria
            $fair = Fair::where('slug', $request->slug)->first();

            if (!$fair) {
                return response()->json([
                    'success' => false,
                    'message' => 'Feria no encontrada',
                ], 404);
            }

            // Buscar si ya existe una marca registrada para esa empresa en este evento
            $brand = CyberwowBrand::where('company_id', $request->company_id)
                ->where('wow_id', $fair->id)
                ->first();

            if ($brand) {
                // 🔄 Actualizar registro existente
                $brand->update([
                    'isService'   => $request->isService,
                    'description' => $request->description,
                    'url'         => $request->url,
                    'logo256_id'  => $request->logo256_id,
                    'logo160_id'  => $request->logo160_id,
                    'user_id'     => Auth::id(),
                    'red'         => $request->red,
                ]);

                $action = 'actualizado';
            } else {
                // 🆕 Crear nuevo registro
                $brand = CyberwowBrand::create([
                    'isService'   => $request->isService,
                    'description' => $request->description,
                    'url'         => $request->url,
                    'logo256_id'  => $request->logo256_id,
                    'logo160_id'  => $request->logo160_id,
                    'wow_id'      => $fair->id,
                    'company_id'  => $request->company_id,
                    'user_id'     => Auth::id(),
                    'red'         => $request->red,
                ]);

                $action = 'creado';
            }

            // Actualizar paso 2 del participante
            $participant = CyberwowParticipant::where('event_id', $fair->id)
                ->where('id', $request->company_id)
                ->first();

            if ($participant) {
                $participant->paso2 = 1;
                $participant->save();
            }

            return response()->json([
                'success' => true,
                'message' => "Registro {$action} correctamente",
                'brand'   => $brand,
                'participant' => $participant,
                'status' => 200

            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar: ' . $e->getMessage()
            ], 500);
        }
    }



    public function cyberwowStep3(Request $request)
    {
        try {
            // Validación base del payload
            $validated = $request->validate([
                'days' => 'required|array|min:1',
                'days.*.data.slug' => 'required|string|exists:fairs,slug',
                'days.*.data.company_id' => 'required|integer|exists:cyberwowparticipants,id',
                'days.*.data.imgFull' => 'nullable|integer|exists:images,id',
                'days.*.data.img' => 'nullable|integer|exists:images,id',
                'days.*.data.dia' => 'required|integer|in:1,2,3', // solo se permiten 1, 2 o 3
            ]);

            $processed = [];

            DB::beginTransaction();

            foreach ($request->days as $day) {
                $data = $day['data'];

                // Buscar la feria por slug
                $fair = Fair::where('slug', $data['slug'])->firstOrFail();

                // Buscar si ya existe una oferta con mismo wow_id, company_id y dia
                $offer = CyberwowOffer::where('wow_id', $fair->id)
                    ->where('company_id', $data['company_id'])
                    ->where('dia', $data['dia'])
                    ->first();

                // Si existe → actualizar
                if ($offer) {
                    $offer->update([
                        'imgFull'        => $data['imgFull'] ?? null,
                        'img'            => $data['img'] ?? null,
                        'title'          => $data['title'] ?? '',
                        'link'           => $data['link'] ?? null,
                        'category'       => $data['category'] ?? null,
                        'tipo'           => $data['tipo'] ?? null,
                        'beneficio'      => $data['beneficio'] ?? null,
                        'moneda'         => $data['moneda'] ?: 'S/',
                        'precioAnterior' => $data['precioAnterior'] ?? 0,
                        'precioOferta'   => $data['precioOferta'] ?? 0,
                        'descripcion'    => $data['descripcion'] ?? null,
                    ]);

                    $processed[] = [
                        'action' => 'updated',
                        'dia' => $data['dia'],
                        'offer' => $offer
                    ];
                }
                // Si no existe → crear nueva (solo si no excede 3 por empresa)
                else {
                    $count = CyberwowOffer::where('wow_id', $fair->id)
                        ->where('company_id', $data['company_id'])
                        ->count();

                    if ($count < 3) {
                        $newOffer = CyberwowOffer::create([
                            'wow_id'         => $fair->id,
                            'company_id'     => $data['company_id'],
                            'imgFull'        => $data['imgFull'] ?? null,
                            'img'            => $data['img'] ?? null,
                            'title'          => $data['title'] ?? '',
                            'link'           => $data['link'] ?? null,
                            'category'       => $data['category'] ?? null,
                            'tipo'           => $data['tipo'] ?? null,
                            'beneficio'      => $data['beneficio'] ?? null,
                            'moneda'         => $data['moneda'] ?: 'S/',
                            'precioAnterior' => $data['precioAnterior'] ?? 0,
                            'precioOferta'   => $data['precioOferta'] ?? 0,
                            'descripcion'    => $data['descripcion'] ?? null,
                            'dia'            => $data['dia'],
                        ]);

                        $processed[] = [
                            'action' => 'created',
                            'dia' => $data['dia'],
                            'offer' => $newOffer
                        ];
                    } else {
                        $processed[] = [
                            'action' => 'skipped',
                            'dia' => $data['dia'],
                            'message' => 'Ya tiene 3 ofertas registradas para este evento',
                        ];
                    }
                }
            }

            DB::commit();

            // Actualizar paso3 del participante (solo si hubo creación o edición)
            if (!empty($processed)) {
                $firstData = $request->days[0]['data'];
                $fair = Fair::where('slug', $firstData['slug'])->first();
                $participant = CyberwowParticipant::where('event_id', $fair->id)
                    ->where('id', $firstData['company_id'])
                    ->first();

                if ($participant) {
                    $participant->paso3 = 1;
                    $participant->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Ofertas procesadas correctamente.',
                'resultados' => $processed,
                'status' => 200
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar o actualizar las ofertas.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }



    public function cyberwowCountMyProgress($slug)
    {
        // Buscar la feria
        $fair = Fair::where('slug', $slug)->firstOrFail();

        // Usuario autenticado
        $userId = Auth::id();

        // Query base (filtra por feria y usuario)
        $query = CyberwowParticipant::where('event_id', $fair->id)
            ->where('user_id', $userId);

        // Clones del query
        $asignados   = (clone $query)->count();
        $completados = (clone $query)->where('paso3', 1)->count();
        $pendientes  = (clone $query)->whereNull('paso3')->count();

        // Calcular porcentaje (evitar división por 0)
        $porcentaje = $asignados > 0 ? round(($completados / $asignados) * 100, 2) : 0;

        return response()->json([
            'status'      => 200,
            'asignados'   => $asignados,
            'completados' => $completados,
            'pendientes'  => $pendientes,
            'porcentaje'  => $porcentaje,
        ]);
    }

    public function cyberwowCountPrincipalPanel($slug)
    {
        $fair = Fair::where('slug', $slug)->firstOrFail();
        $userId = auth()->id(); // <-- o pásalo como parámetro si no es Auth

        // Total de empresas (todas por evento)
        $totalEmpresas = CyberwowParticipant::where('event_id', $fair->id)->count();

        // Empresas asignadas (evento + user_id)
        $empresasAsignadas = CyberwowParticipant::where('event_id', $fair->id)
            ->whereNotNull('user_id')
            ->count();

        // Perfiles completados (evento + paso3 no nulo / true)
        $perfilesCompletados = CyberwowParticipant::where('event_id', $fair->id)
            ->whereNotNull('paso3')
            ->count();

        // Líderes activos (evento + user_id únicos)
        $lideresActivos = CyberwowParticipant::where('event_id', $fair->id)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        return response()->json([
            'status' => 200,
            'message' => 'Conteo generado correctamente',
            'data' => [
                'total_empresas' => $totalEmpresas,
                'empresas_asignadas' => $empresasAsignadas,
                'perfiles_completados' => $perfilesCompletados,
                'lideres_activos' => $lideresActivos,
            ]
        ], 200);
    }

    public function resumenPorUsuarios($slug)
    {
        $fair = Fair::where('slug', $slug)->firstOrFail();

        // 1) Sacamos todos los líderes vinculados a este fair
        $leaders = CyberwowLeader::where('wow_id', $fair->id)
            ->pluck('user_id');

        // 2) Obtenemos la info de cada usuario líder ordenados por lastname DESC
        $usuarios = User::whereIn('id', $leaders)
            ->select('id', 'name', 'lastname', 'middlename')
            ->orderBy('lastname', 'desc')
            ->get();

        // Paleta de colores predeterminada (Ant Design)
        $colores = [
            '#722ed1',
            '#eb2f96',
            '#fa541c',
            '#13c2c2',
            '#faad14',
            '#52c41a',
            '#1890ff',
            '#2f54eb',
            '#a0d911',
            '#f5222d',
            '#08979c',
            '#fa8c16'
        ];
        shuffle($colores); // Aleatorio

        $resultados = [];
        $i = 0;

        foreach ($usuarios as $user) {
            // 3) Buscar todos los participantes asignados a este líder en este evento
            $query = CyberwowParticipant::where('event_id', $fair->id)
                ->where('user_id', $user->id);

            $total = $query->count();
            $completados = (clone $query)->whereNotNull('paso3')->count();
            $pendientes = (clone $query)->whereNull('paso3')->count();

            $tasa = $total > 0 ? round(($completados / $total) * 100, 1) : 0;

            // productividad
            if ($tasa >= 70) {
                $productividad = 'Alta';
            } elseif ($tasa >= 40) {
                $productividad = 'Media';
            } else {
                $productividad = 'Baja';
            }

            $tiempo = round(mt_rand(15, 40) / 10, 1) . " días";

            // 4️⃣ Buscar supervisor del líder actual
            $leaderRecord = CyberwowLeader::where('wow_id', $fair->id)
                ->where('user_id', $user->id)
                ->select('supervisor')
                ->first();

            $supervisor = $leaderRecord->supervisor ?? null; // Valor por defecto si no existe

            $resultados[] = [
                'id' => $user->id,
                'nombre' => mb_strtoupper(trim("{$user->name} {$user->lastname} {$user->middlename}")),
                'asignadas' => $total,
                'completadas' => $completados,
                'pendientes' => $pendientes,
                'tasa' => $tasa,
                'tiempo' => $tiempo,
                'productividad' => $productividad,
                'actividad' => now()->subDays(rand(1, 30))->format('Y-m-d H:i'),
                'color' => $colores[$i] ?? sprintf("#%06X", mt_rand(0, 0xFFFFFF)),
                'supervisor' => $supervisor,
            ];

            $i++;
        }

        // Ordenar resultados por 'supervisor' de manera descendente, colocando los null al final
        $resultados = collect($resultados)->sortBy(function ($item) {
            return $item['supervisor'] === null ? PHP_INT_MAX : $item['supervisor'];
        })->values()->all();

        return response()->json([
            'status' => 200,
            'message' => 'Resumen generado correctamente',
            'data' => $resultados
        ], 200);
    }
}
