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


    public function index(Request $request)
    {
        $filters = [
            'name'         => $request->input('name'),
            'document'     => $request->input('document'),
            'email'        => $request->input('email'),
            'phone'        => $request->input('phone'),
            'event_id'     => $request->input('event_id'),
            'city_id'      => $request->input('city_id'),
        ];

        $query = UgsePostulante::query();

        $query->withBasicFilters($filters);

        // Eager Loading de relaciones
        $query->with([
            'economicsector',
            // 'comercialactivity',
            'category',
            'city',
            'typedocument',
            'gender',
            'howKnowEvent',
            'event',
        ]);

        $postulantes = $query->paginate(150)->through(function ($item) {
            return $this->mapEvents($item);
        });

        return response()->json([
            'data'   => $postulantes,
            'status' => 200
        ]);
    }

    private function mapEvents($item)
    {
        return [
            'id'               => $item->id,
            'name'             => $item->name,
            'lastname'         => $item->lastname,
            'middlename'       => $item->middlename,
            'documentnumber'   => $item->documentnumber,
            'email'            => $item->email,
            'phone'            => $item->phone,
            'event_id'         => $item->event_id,
            'positionCompany'  => $item->positionCompany,
            'instagram'        => $item->instagram,
            'facebook'         => $item->facebook,
            'web'              => $item->web,
            'ruc'              => $item->ruc,
            'comercialName'    => $item->comercialName,
            'socialReason'     => $item->socialReason,
            'typeAsistente'    => $item->typeAsistente,
            'sick'             => $item->sick,
            'birthday'         => $item->birthday,
            'created_at' => $item->created_at ? Carbon::parse($item->created_at)->format('d/m/Y h:i A') : null,



            // Relaciones
            'economicsector' => $item->economicsector ? [
                'id'   => $item->economicsector->id,
                'name' => $item->economicsector->name
            ] : null,

            'comercialactivity' => $item->comercialactivity ? [
                'id'   => $item->comercialactivity->id,
                'name' => $item->comercialactivity->name
            ] : null,

            'economicsector' => $item->economicsector ? [
                'id'   => $item->economicsector->id,
                'name' => $item->economicsector->name
            ] : null,

            'category' => $item->category ? [
                'id'   => $item->category->id,
                'name' => $item->category->name
            ] : null,

            'city' => $item->city ? [
                'id'   => $item->city->id,
                'name' => $item->city->name
            ] : null,

            'typedocument' => $item->typedocument ? [
                'id'   => $item->typedocument->id,
                'name' => $item->typedocument->name
            ] : null,

            'gender' => $item->gender ? [
                'id'   => $item->gender->id,
                'name' => $item->gender->name
            ] : null,

            'howKnowEvent' => $item->howKnowEvent ? [
                'id'   => $item->howKnowEvent->id,
                'name' => $item->howKnowEvent->name
            ] : null,

            'event' => $item->event ? [
                'id'   => $item->event->id,
                'name' => $item->event->title  // suponiendo que el evento tiene "title"
            ] : null,

        ];
    }




    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'ruc' => 'required|max:11',
            'comercialName' => 'required|string|max:200',
            'socialReason' => 'required|string|max:200',
            'economicsector_id' => 'required|integer',
            'comercialactivity_id' => 'required|integer',
            'category_id' => 'required|integer',
            'city_id' => 'required|integer',
            'typedocument_id' => 'required|integer',
            'documentnumber' => 'required|string|max:12',
            'lastname' => 'required|string|max:100',
            'name' => 'required|string|max:100',
            'gender_id' => 'required|integer',
            'howKnowEvent_id' => 'required|integer',
            'slug' => 'required|string',
            'phone' => 'required|max:9',
            'email' => 'required|email|max:100',
            'birthday' => 'required|date',
            'positionCompany' => 'required|string|max:100',
            'mailer' => 'nullable|string|in:gmail,office365',
        ]);

        $fair = Fair::where('slug', $request->slug)->firstOrFail();

        DB::beginTransaction();

        try {

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


            $mailer = $request->mailer ?? 'gmail';


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
}
