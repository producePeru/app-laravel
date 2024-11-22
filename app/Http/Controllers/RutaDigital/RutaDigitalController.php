<?php

namespace App\Http\Controllers\RutaDigital;

use App\Http\Controllers\Controller;
use App\Models\Digitalroute;
use App\Models\Mype;
use App\Models\People;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RutaDigitalController extends Controller
{
    public function businessman(Request $request)               // existe este usuario  ¿? si existe la editas sino la creas
    {
        $request->validate([
            'documentnumber' => 'required|string|max:255',
            'typedocument_id' => 'required|integer',
        ]);

        $user = getUserRole();
        $user_id = $user['user_id'];

        $requestData = $request->all();
        $requestData['user_id'] = $user_id;

        $person = People::where('documentnumber', $request->documentnumber)
            ->where('typedocument_id', $request->typedocument_id)
            ->first();

        if ($person) {
            $person->update($requestData); // Actualizamos con los datos modificados
        } else {
            $person = People::create($requestData); // Creamos con los datos modificados
        }


        return response()->json([
            'id' => $person->id,
            'dni' => $person->documentnumber,
            'status' => 200
        ]);
    }

    public function mype(Request $request)
    {
        $request->validate([
            'ruc' => 'required|string|max:11',
        ]);

        $user = getUserRole();
        $user_id = $user['user_id'] ?? null;

        if (!$user_id) {
            return response()->json([
                'message' => 'No se pudo identificar al usuario'
            ], 400);
        }

        $allowedFields = [
            'ruc',
            'comercialName',
            'socialReason',
            'city_id',
            'province_id',
            'district_id',
            'address',
            'comercialactivity_id',
            'economicsector_id'
        ];

        // Agregar user_id a los datos recibidos
        $requestData = $request->all();
        $requestData['user_id'] = $user_id;

        try {
            $mype = Mype::where('ruc', $request->ruc)->first();

            if ($mype) {
                $mype->update($request->only($allowedFields));
            } else {
                $mype = Mype::create($requestData);
            }

            return response()->json([
                'id' => $mype->id,
                'ruc' => $mype->ruc,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al procesar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $user = getUserRole();
        $user_id = $user['user_id'];

        if (!$user_id) {
            return response()->json([
                'message' => 'No se pudo identificar al usuario'
            ], 400);
        }

        $requestData = $request->all();
        $requestData['user_id'] = $user_id;


        // Validar si ya existe el registro
        $existingRecord = Digitalroute::where('mype_id', $requestData['mype_id'])
            ->where('person_id', $requestData['person_id'])
            ->first();

        if ($existingRecord) {
            return response()->json([
                'message' => 'ESTOS DATOS YA HAN SIDO REGISTRADOS, IMPOSIBLE DE CREAR',
                'status' => 409
            ]);
        }

        try {

            Digitalroute::create($requestData);

            return response()->json([
                'message' => 'Se ha registrado correctamente',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Hubo un error al registrar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request,)
    {
        $search = $request->input('search');
        $user_role = getUserRole();
        $role_array = $user_role['role_id'];

        $query = Digitalroute::with([
            'profile',
            'profile.cde:id,name',
            'profile.supervisor',

            'user.misupervisor.supervisor.profile',

            'person',
            'person.typedocument:id,name',
            'person.pais:id,name',
            'person.gender:id,avr',
            'mype',
            'mype.region:id,name',
            'mype.province:id,name',
            'mype.district:id,name',
            'mype.comercialactivity:id,name',
            'mype.economicsector:id,name'
        ])->search($search)
            ->orderBy('created_at', 'desc');


        // Filtrado según roles
        if (in_array(1, $role_array) || in_array(5, $role_array)) {
            // Roles 1 y 5 pueden ver todos los registros, no aplicamos filtro adicional
        } elseif (in_array(2, $role_array) || in_array(7, $role_array)) {
            $user_id = $user_role['user_id'];
            $query->where('user_id', $user_id);
        } else {
            return response()->json(['error' => 'Unauthorized', 'status' => 409]);
        }


        $data = $query->paginate(50);

        // return $data;

        $data->getCollection()->transform(function ($item) {

            $supervisador = $item->user->misupervisor?->supervisor->profile
            ? $item->user->misupervisor->supervisor->profile->name . ' ' . $item->user->misupervisor->supervisor->profile->lastname . ' ' . $item->user->misupervisor->supervisor->profile->middlename
            : 'Sin supervisor';

            return [
                'id' => $item->id,

                'date' => Carbon::parse($item->created_at)->format('d/m/Y H:i'),
                'asesor_documentnumber' => $item->profile->documentnumber,
                'asesor_name' => $item->profile->name . ' ' . $item->profile->lastname . ' ' . $item->profile->middlename,
                'asesor_cde' => $item->profile->cde->name,
                'supervisador' => $supervisador,

                'documentnumber' => $item->person->documentnumber,
                'typedocument' => $item->person->typedocument->name,
                'country' => $item->person->pais->name,
                'birthdate' => Carbon::parse($item->person->birthday)->format('d/m/Y'),
                'lastname' => $item->person->lastname . ' ' . $item->person->middlename,
                'name' => $item->person->name,
                'gender' => $item->person->gender->avr,
                'sick' => $item->person->sick == 'yes' ? 'SI' : 'NO',
                'phone' => $item->person->phone,
                'email' => $item->person->email,

                'ruc' => $item->mype->ruc,
                'region' => $item->mype->region->name,
                'province' => $item->mype->province->name,
                'district' => $item->mype->district->name,
                'address' => $item->mype->address,
                'comercialactivity' => $item->mype->comercialactivity->name,
                'economicsector' => $item->mype->economicsector->name
            ];
        });

        return response()->json(['data' => $data]);
    }
}
