<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Models\Fair;
use App\Mail\FairSedInfoMail;
use App\Models\UgsePostulante;
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
            return $this->mapEvents($item);
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

    private function mapEvents($item)
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
            'howKnowEvent_id'           => $item->howKnowEvent->id,
            'howKnowEvent_name'         => $item->howKnowEvent->name,
            'gender_id'                 => $item->businessman->gender->id ?? '-',
            'gender_name' => $item->businessman
                ? ($item->businessman->gender->name === 'FEMENINO' ? 'F' : 'M')
                : ($item->gender_id == 1 ? 'M' : 'F'),
            'economicsector_id'         => $item->economicsector->id,
            'economicsector_name'       => $item->economicsector->name,
            'comercialactivity_id'      => $item->comercialactivity->id ?? null,
            'comercialactivity_name'    => $item->comercialactivity->name ?? null,
            'category_id'               => $item->category->id ?? null,
            'category_name'             => $item->category->name ?? null,
            'city_id'                   => $item->city->id ?? null,
            'city_name'                 => $item->city->name ?? null,


            // 'event' => $item->event ? [
            //     'id'   => $item->event->id,
            //     'name' => $item->event->title  // suponiendo que el evento tiene "title"
            // ] : null,

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
}
