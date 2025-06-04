<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Fair;
use App\Models\FairPostulate;
use App\Models\Mype;
use App\Models\People;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EventsUgseController extends Controller
{

    public function verify(Request $request)
    {
        // Obtener el token de reCAPTCHA desde la solicitud
        $recaptchaToken = $request->input('recaptcha_token');

        // Verificar el token con Google
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $recaptchaToken,
        ]);

        $data = $response->json();

        // Verificar si la validación fue exitosa
        if (!$data['success']) {
            return response()->json(['message' => 'Error de reCAPTCHA'], 400);
        }

        // Si es válido, continúa con el procesamiento
        return response()->json(['message' => 'Formulario validado correctamente', 'status' => 200]);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            // 1. Crear o actualizar Mype
            $mype = Mype::firstOrNew(['ruc' => $data['ruc']]);

            $mype->comercialName = $data['comercialName'];
            $mype->socialReason = $data['socialReason'];
            $mype->economicsector_id = $data['economicsector_id'];
            $mype->comercialactivity_id = $data['comercialactivity_id'];
            $mype->category_id = $data['category_id'];
            $mype->city_id = $data['city_id'];

            // Asignar redes sociales solo si están vacías en BD
            if (empty($mype->facebook) && !empty($data['facebook'])) {
                $mype->facebook = $data['facebook'];
            }
            if (empty($mype->instagram) && !empty($data['instagram'])) {
                $mype->instagram = $data['instagram'];
            }

            $mype->save();

            // 2. Crear o actualizar titular
            $person = People::updateOrCreate(
                ['documentnumber' => $data['documentnumber']],
                [
                    'typedocument_id' => $data['typedocument_id'],
                    'lastname' => $data['lastname'],
                    'middlename' => $data['middlename'],
                    'name' => $data['name'],
                    'gender_id' => $data['gender_id'],
                    'sick' => $data['sick'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'birthday' => $data['birthday'],
                ]
            );

            // 3. Si hay invitado, crear o actualizar
            $invitado = null;
            if (!empty($data['invitado']) && $data['invitado'] === true) {
                $invitado = People::updateOrCreate(
                    ['documentnumber' => $data['invitado_documentnumber']],
                    [
                        'typedocument_id' => $data['invitado_typedocument_id'],
                        'lastname' => $data['invitado_lastname'],
                        'middlename' => $data['invitado_middlename'],
                        'name' => $data['invitado_name'],
                        'gender_id' => $data['invitado_gender_id'],
                        'sick' => $data['invitado_sick'],
                        'phone' => $data['invitado_phone'],
                        'email' => $data['invitado_email'],
                        'birthday' => $data['invitado_birthday'],
                    ]
                );
            }

            // 4. Obtener fair_id desde el slug
            $fair = Fair::where('slug', $data['slug'])->firstOrFail();

            // 5. Registrar en FairPostulate
            FairPostulate::create([
                'ruc' => $data['ruc'],
                'dni' => $data['documentnumber'],
                'email' => $data['email'],
                'fair_id' => $fair->id,
                'mype_id' => $mype->id,
                'person_id' => $person->id,
                'positionUser1' => $data['positionCompany'],
                'invitado_id' => $invitado?->id,
                'positionUser2' => $invitado ? $data['invitado_positionCompany'] : null,
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Registro exitoso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Error al procesar la solicitud.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
