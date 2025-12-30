<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCyberwowParticipantRequest;
use App\Models\CyberwowParticipant;
use App\Models\Fair;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class CyberwowParticipantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }


    public function store(StoreCyberwowParticipantRequest $request)
    {
        try {
            // Buscar el evento (feria) por slug
            $fair = Fair::where('slug', $request->input('slug_id'))->firstOrFail();

            // Validar si ya existe participante con mismo event_id + documentnumber
            $exists = CyberwowParticipant::where('event_id', $fair->id)
                ->where('ruc', $request->ruc)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'El participante ya está registrado en este evento.',
                    'status' => 409
                ]); // 409 Conflict
            }

            // Crear participante
            $participant = CyberwowParticipant::create([
                'event_id' => $fair->id,
                'ruc' => $request->ruc,
                'razonSocial' => $request->razonSocial,
                'nombreComercial' => $request->nombreComercial,
                'city_id' => $request->city_id,
                'province_id' => $request->province_id,
                'district_id' => $request->district_id,
                'direccion' => $request->direccion,
                'economicsector_id' => $request->economicsector_id,
                'comercialactivity_id' => $request->comercialactivity_id,
                'rubro_id' => $request->rubro_id,
                'descripcion' => $request->descripcion,
                'socials' => $request->socials ?? [],
                'typedocument_id' => $request->typedocument_id,
                'documentnumber' => $request->documentnumber,
                'lastname' => $request->lastname,
                'middlename' => $request->middlename,
                'name' => $request->name,
                'gender_id' => $request->gender_id,
                'sick' => $request->sick ?? 'no',
                'phone' => $request->phone,
                'email' => $request->email,
                'birthday' => $this->normalizeDate($request->birthday),
                'age' => $request->age,
                'country_id' => $request->country_id,
                'cargo' => $request->cargo,
                'question_1' => $request->question_1,
                'question_2' => $request->question_2,
                'question_3' => $request->question_3,
                'question_4' => $request->question_4,
                'question_5' => $request->question_5,
                'question_6' => $request->question_6,
                'question_7' => $request->question_7,
                'howKnowEvent_id' => $request->howKnowEvent_id,
                'autorization' => $request->autorization ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Participante registrado correctamente',
                'data' => $participant,
                'status' => 200
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la feria con el slug proporcionado',
                'status' => 404
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage(),
                'status' => 500
            ]);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateParticipantData(Request $request, $id)
    {
        try {
            // Buscar participante
            $participant = CyberwowParticipant::findOrFail($id);

            // Actualizar datos
            $participant->update([
                'ruc'                => $request->ruc,
                'razonSocial'        => $request->razonSocial,
                'nombreComercial'    => $request->nombreComercial,
                // 'city_id'            => $request->city_id,
                // 'province_id'        => $request->province_id,
                // 'district_id'        => $request->district_id,
                // 'direccion'          => $request->direccion,
                // 'economicsector_id'  => $request->economicsector_id,
                // 'comercialactivity_id' => $request->comercialactivity_id,
                // 'rubro_id'           => $request->rubro_id,
                // 'descripcion'        => $request->descripcion,
                'socials'            => $request->has('socials') ? $request->socials : $participant->socials,
                // 'typedocument_id'    => $request->typedocument_id,
                'documentnumber'     => $request->documentnumber,
                'lastname'           => $request->lastname,
                'middlename'         => $request->middlename,
                'name'               => $request->name,
                // 'gender_id'          => $request->gender_id,
                // 'sick'               => $request->sick ?? $participant->sick,
                // 'phone'              => $request->phone,
                // 'email'              => $request->email,
                // 'birthday'           => $this->normalizeDate($request->birthday),
                // 'age'                => $request->age,
                // 'country_id'         => $request->country_id,
                // 'cargo'              => $request->cargo,
                // 'question_1'         => $request->question_1,
                // 'question_2'         => $request->question_2,
                // 'question_3'         => $request->question_3,
                // 'question_4'         => $request->question_4,
                // 'question_5'         => $request->question_5,
                // 'question_6'         => $request->question_6,
                // 'question_7'         => $request->question_7,
                // 'howKnowEvent_id'    => $request->howKnowEvent_id,
                // 'autorization'       => $request->autorization ?? $participant->autorization,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Participante actualizado correctamente',
                'data'    => $participant,
                'status'  => 200
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el participante con el ID proporcionado',
                'status' => 404
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error inesperado.',
                'error'   => $e->getMessage(),
                'status' => 500
            ]);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    private function normalizeDate($date)
    {
        if (!$date) {
            return null;
        }

        $formats = ['d/m/Y', 'Y-m-d', 'Y/m/d']; // formatos que recibes

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $date)->format('Y-m-d');
            } catch (\Exception $e) {
                continue; // intenta con el siguiente formato
            }
        }

        // último intento: parsear automáticamente
        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null; // si no puede parsear
        }
    }
}
