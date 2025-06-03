<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Fair;
use App\Models\FairPostulate;
use App\Models\Mype;
use App\Models\People;
use Illuminate\Http\Request;

class EventsUgseController extends Controller
{
    public function store(Request $request)
    {
        try {
            $data = $request->all();

            // 1. Crear o actualizar Mype
            $mype = Mype::updateOrCreate(
                ['ruc' => $data['ruc']],
                [
                    'comercialName' => $data['comercialName'],
                    'socialReason' => $data['socialReason'],
                    'economicsector_id' => $data['economicsector_id'],
                    'comercialactivity_id' => $data['comercialactivity_id'],
                    'city_id' => $data['city_id'],
                    'category_id' => $data['category_id'],
                    'facebook' => $data['facebook'],
                    'instagram' => $data['instagram'],
                ]
            );

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
            if (isset($data['invitado'])) {
                $inv = $data['invitado'];
                $invitado = People::updateOrCreate(
                    ['documentnumber' => $inv['documentnumber']],
                    [
                        'typedocument_id' => $inv['typedocument_id'],
                        'lastname' => $inv['lastname'],
                        'middlename' => $inv['middlename'],
                        'name' => $inv['name'],
                        'gender_id' => $inv['gender_id'],
                        'sick' => $inv['sick'],
                        'phone' => $inv['phone'],
                        'email' => $inv['email'],
                        'birthday' => $inv['birthday'],
                    ]
                );
            }

            // 4. Obtener fair_id desde el slug
            $fair = Fair::where('slug', $data['slug'])->firstOrFail();

            // 5. Registrar en FairPostulate
            FairPostulate::create([
                'fair_id' => $fair->id,
                'people_id' => $person->id,
                'mype_id' => $mype->id,
                'positionCompany' => $data['positionCompany'],
                'propagandamedia_id' => $data['howKnowEvent_id'],
            ]);

            // Si hay invitado, registrarlo también
            // if ($invitado) {
            //     FairPostulate::create([
            //         'fair_id' => $fair->id,
            //         'people_id' => $invitado->id,
            //         'mype_id' => $mype->id,
            //         'positionCompany' => $inv['positionCompany'], // solo ahora se guarda
            //         'propagandamedia_id' => $data['howKnowEvent_id'],
            //     ]);
            // }

            return response()->json([
                'mype_id' => $mype->id,
                'people_id' => $person->id,
                'invitado_id' => $invitado?->id,
                'fair_id' => $fair->id,
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
