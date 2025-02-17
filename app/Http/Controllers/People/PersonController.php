<?php

namespace App\Http\Controllers\People;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use App\Models\From;
use App\Models\People;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Token;
use GuzzleHttp\Client;

class PersonController extends Controller
{
    private function getUserRole()
    {
        $user_id = Auth::user()->id;

        $roleUser = DB::table('role_user')
            ->where('user_id', $user_id)
            ->first();

        if ($user_id != $roleUser->user_id) {
            return response()->json(['message' => 'Este rol no es correcto', 'status' => 404]);
        }

        return [
            "role_id" => $roleUser->role_id,
            'user_id' => $user_id
        ];
    }


    public function index(Request $request)
    {
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];
        $filters = [
            'search' => $request->input('search'),
        ];

        $userRole = getUserRole();
        $roleIdArray = $userRole['role_id'];

        if (in_array(1, $roleIdArray) || in_array(5, $roleIdArray)) {
            $people = People::withProfileAndRelations($filters);
            return response()->json($people, 200);
        }


        if (in_array(2, $roleIdArray) || in_array(7, $roleIdArray)) {
            $people = People::withProfileAndUser($user_id, $filters);
            return response()->json($people, 200);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'documentnumber' => 'required|string',
                'lastname' => 'required|string',
                'middlename' => 'required|string',
                'name' => 'required|string',
                'country_id' => 'required',
                'phone' => 'nullable|string',
                'email' => 'nullable|email|unique:people',
                'birthday' => 'nullable|date',
                'sick' => 'nullable|in:yes,no',
                'facebook' => 'nullable|string',
                'linkedin' => 'nullable|string',
                'instagram' => 'nullable|string',
                'tiktok' => 'nullable|string',
                'city_id' => 'required|integer',
                'province_id' => 'required|integer',
                'district_id' => 'required|integer',
                'address' => 'nullable|string',
                'typedocument_id' => 'required|integer',
                'gender_id' => 'nullable|integer',
                'hasSoon' => 'string',
            ]);

            $person = People::create($data);

            $user = User::find($request->input('people_id'));
            $user->people()->attach($person->id);

            // Attach de la entidad From a la persona
            $person->from()->attach($request->input('from_id'), ['people_id' => $person->id, 'from_id' => $request->input('from_id')]);

            return response()->json(['message' => 'Usuario creado correctamente', 'status' => 200]);
        } catch (\Illuminate\Validation\ValidationException $e) {         // DEVUELVE ERRORES DE LA VALIDACION

            if (isset($e->errors()['email'])) {
                return response()->json(['error' => $e->errors()['email'][0], 'status' => 400]);
            }
            if (isset($e->errors()['documentnumber'])) {
                return response()->json(['status' => 401]);
            }
            return $e;
        } catch (QueryException $e) {
            return response()->json(['message' => 'El usuario se registró pero la relación ha fallado', 'error' => $e], 400);
        }
    }

    public function destroy($id)
    {
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];

        if ($role_id === 1 || $user_id === 1) {
            People::destroy($id);
            return response()->json(['message' => 'Usuario y sus relaciones eliminados correctamente'], 200);
        } else {
            return response()->json(['message' => 'La eliminación de este usuario no es posible. Por favor, busca orientación de tu administrador.', 'status' => 500]);
        }
    }

    public function dniFoundUser($type, $dni)
    {
        $person = People::where('typedocument_id', $type)
            ->where('documentnumber', $dni)
            ->with([
                'idformalization10' => function ($query) {
                    $query->with('detailprocedure', 'modality', 'economicsector');
                },
                'idformalization20' => function ($query) {
                    $query->with('economicsector', 'userupdater.profile');
                },
                'idadvisory' => function ($query) {
                    $query->with('component', 'theme', 'modality');
                }
            ])
            ->first();

        if ($person) {
            return response()->json(['data' => $person, 'status' => 200]);
        } else {
            return response()->json(['message' => 'El usuario no existe', 'status' => 404]);
        }
    }

    public function update(Request $request, $id)
    {
        $user = People::find($id);

        if (!$user) {
            return response()->json(['message' => 'El usuario no tiene un perfil'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'birthday' => 'nullable|date',
            'gender_id' => 'nullable|integer',
            'country_id' => 'required',
            'city_id' => 'required|integer',
            'province_id' => 'required|integer',
            'district_id' => 'required|integer',
            'sick' => 'nullable|in:yes,no',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|string',
            'hasSoon' => 'nullable|string',
        ]);

        $user->update($validatedData);

        return response()->json(['message' => 'Perfil actualizado con éxito', 'status' => 200]);
    }

    public function findUserById($dni)
    {
        $user = People::where('documentnumber', $dni)->first();

        if (!$user) {
            return response()->json(['message' => 'Not Found', 'status' => 404]);
        }

        $data = [
            'id' => $user->id,
            'namePerson' => $user->name . ' ' . $user->lastname . ' ' . $user->middlename,
            'city_id' => $user->city_id,
            'province_id' => $user->province_id,
            'district_id' => $user->district_id,
            'gender_id' => $user->gender_id,
            'sick' => $user->sick
        ];


        return response()->json(['data' => $data, 'status' => 200]);
    }


    // BUSCA POR EL API
    public function apiDNI($numeroDOC)
    {
        try {
            $person = People::where('documentnumber', $numeroDOC)->first();

            if (!$person) {
                $apiUrl = "https://api.apis.net.pe/v2/reniec/dni?numero={$numeroDOC}";

                try {
                    $tokenRecord = Token::where('status', 1)->first();

                    if (!$tokenRecord) {
                        return response()->json(['status' => 404, 'message' => 'Token no encontrado o inactivo']);
                    }

                    $client = new Client();
                    $response = $client->request('GET', $apiUrl, [
                        'headers' => [
                            'Authorization' => $tokenRecord->token,
                            'Accept' => 'application/json',
                        ],
                    ]);

                    $responseData = json_decode($response->getBody(), true);

                    return response()->json([
                        'status' => 200,
                        'message' => 'Información obtenida',
                        'data' => [
                            'name' => $responseData['nombres'] ?? null,
                            'lastname' => $responseData['apellidoPaterno'] ?? null,
                            'middlename' => $responseData['apellidoMaterno'] ?? null,
                            'gender_id' => null,
                            'sick' => null
                        ]
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'error' => 'No se pudo obtener información del MYPE',
                        'status' => 500,
                        'error' => $e->getMessage()
                    ], 500);
                }
            } else {
                // Si se encuentra
                return response()->json([
                    'status' => 200,
                    'message' => 'Usuario',
                    'data' => [
                        'name' => $person->name ?? null,
                        'lastname' => $person->lastname ?? null,
                        'middlename' => $person->middlename ?? null,
                        'gender_id' => $person->gender_id ?? null,
                        'sick' => $person->sick ?? null
                    ]
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al procesar la solicitud',
                'error' => $th->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    // CREA O EDITA
    public function createUpdate(Request $request)
    {
        $person = People::where('documentnumber', $request->documentnumber)
            ->where('typedocument_id', $request->typedocument_id)
            ->first();

        if ($person) {
            $person->update([
                'name' => $request->name,
                'lastname' => $request->lastname,
                'middlename' => $request->middlename,
                'address' => $request->address,
                'birthday' => $request->birthday,
                'gender_id' => $request->gender_id,
                'sick' => $request->sick,
                'email' => $request->email,
                'phone' => $request->phone
            ]);

            return response()->json(['message' => 'Person updated successfully.', 'id_person' => $person->id, 'status' => 200]);
        } else {
            $person = People::create($request->all());

            return response()->json(['message' => 'Person created successfully.', 'id_person' => $person->id, 'status' => 200]);
        }
    }



    // existe este registro
    public function isNewRecord($type, $number)
    {
        $person = People::where('documentnumber', $number)
            ->where('typedocument_id', $type)
            ->first();

        if ($person) {
            return response()->json(
                [
                    'data' => [
                        'typedocument_id' => $person->typedocument_id ?? null,
                        'documentnumber' => $person->documentnumber ?? null,
                        'lastname' => $person->lastname ?? null,
                        'middlename' => $person->middlename ?? null,
                        'name' => $person->name ?? null,
                        'country_id' => $person->country_id ?? null,
                        'city_id' => $person->city_id ?? null,
                        'province_id' => $person->province_id ?? null,
                        'district_id' => $person->district_id ?? null,
                        'address' => $person->address ?? null,
                        'birthday' => $person->birthday ?? null,
                        'phone' => $person->phone ?? null,
                        'email' => $person->email ?? null,
                        'gender_id' => $person->gender_id ?? null,
                        'sick' => $person->sick ?? null,
                        'hasSoon' => $person->hasSoon ?? null
                    ],
                    'status' => 200
                ]
            );
        } else {
            return response()->json(['message' => 'No se encuentra registrado', 'status' => 300]);
        }
    }
}
