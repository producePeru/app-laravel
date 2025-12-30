<?php

namespace App\Http\Controllers\People;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePersonRequest;
use App\Models\From;
use App\Models\People;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Token;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;

class PersonController extends Controller
{

    public function index(Request $request)
    {
        $permission = getPermission('empresarios-ugo');

        // if (!$permission['hasPermission']) {
        //     return response()->json([
        //         'message' => 'No tienes permiso para acceder a esta sección',
        //         'status' => 403
        //     ]);
        // }

        $filters = [
            'asesor'    => $request->input('asesor'),
            'name'      => $request->input('name')
        ];

        $user = Auth::user();


        $query = People::query();

        if ($user->rol == 1) {
            $query->withProfileAndUser($filters);
        }

        if ($user->rol == 2) {
            $query->withProfileAndUser($filters)->where('user_id', $user->id);
        }


        $businessman = $query->paginate(100)->through(function ($item) {
            return $this->mapPeopleRegisters($item);
        });


        return response()->json([
            'data'   => $businessman,
            'rol' => $user->rol,
            'status' => 200
        ]);
    }

    private function mapPeopleRegisters($people)
    {
        return [
            'id'                    => $people->id,
            'typedocument'          => $people->typedocument->avr,
            'documentnumber'        => $people->documentnumber,
            'name'                  => isset($people->name) ? strtoupper($people->name) : null,
            'last_name'             => isset($people->lastname, $people->middlename) ? strtoupper(trim($people->lastname . ' ' . $people->middlename)) : (isset($people->lastname) ? strtoupper($people->lastname) : (isset($people->middlename) ? strtoupper($people->middlename) : null)),
            'city'                  => $people->city->name ?? null,
            'province'              => $people->province->name ?? null,
            'district'              => $people->district->name ?? null,
            'address'               => isset($people->address) ? strtoupper($people->address) : null,
            'phone'                 => $people->phone ?? null,
            'email'                 => $people->email ?? null,
            'gender'                => $people->gender->name ?? null,
            'sick'                  =>  $people->sick == 'yes' ? 'SI' : 'NO',
            'hasSoon'               => $people->hasSoon ?? null,
            'registered'            => strtoupper(trim(
                ($people->user->name ?? '') . ' ' .
                    ($people->user->lastname ?? '') . ' ' .
                    ($people->user->middlename ?? '')
            )),

            //ids
            'typedocument_id'       => $people->typedocument->id,
            'lastname'              => $people->lastname ?? null,
            'middlename'            => $people->middlename ?? null,
            'country_id'            => $people->pais->id ?? null,
            'city_id'               => $people->city->id ?? null,
            'province_id'           => $people->province->id ?? null,
            'district_id'           => $people->district->id ?? null,
            'address'               => $people->address,
            'birthday'              => $people->birthday,
            'gender_id'             => $people->gender->id ?? null,
        ];
    }

    public function registerNewBusinessman(StorePersonRequest $request)
    {
        try {

            $validatedData = $request->validated();

            $validatedData['user_id'] = auth()->id();

            $person = People::create($validatedData);

            return response()->json([
                'data' => $person,
                'message' => 'Persona creada exitosamente',
                'status' => 200
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'message' => 'Por favor verifica los datos ingresados',
                'errors' => $e->errors(), // Esto contiene los mensajes específicos
                'status' => 422
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear la persona',
                'message' => 'Consulta con tu administrador',
                'status' => 500
            ], 500);
        }
    }
























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
        try {
            $user_id = $this->getUserRole()['user_id'];

            $user = People::find($id);

            if (!$user) {
                return response()->json(['message' => 'El usuario no tiene un perfil'], 404);
            }

            $validatedData = $request->validate([

                'typedocument_id' => 'required|integer',
                'documentnumber' => 'required',

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
                'phone' => 'nullable|max:20',
                'email' => 'nullable|string',
                'hasSoon' => 'nullable|string',
            ]);

            $validatedData['updated_by'] = $user_id;

            $user->update($validatedData);

            return response()->json(['message' => 'Perfil actualizado con éxito', 'status' => 200]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al actualizar el perfil.',
                'error' => $e->getMessage()
            ], 500);
        }
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
                            'sick' => null,
                            'phone' => null,
                            'email' => null
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
                        'sick' => $person->sick ?? null,
                        'phone' => $person->phone ?? null,
                        'email' => $person->email ?? null
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


    // cleannds
    public function registerOrUpdateBusinessman(Request $request)
    {
        $authId = Auth::id();

        $data = $request->all();

        // Normalizar campos
        if (!empty($data['birthday'])) {
            $data['birthday'] = Carbon::parse($data['birthday'])->toDateString();
        }

        // Campos que sí vamos a guardar
        $FIELDS = [
            'typedocument_id',
            'documentnumber',
            'lastname',
            'middlename',
            'name',
            'country_id',
            'city_id',
            'province_id',
            'district_id',
            'address',
            'birthday',
            'phone',
            'email',
            'gender_id',
            'sick',
            'hasSoon',
        ];
        $payload = Arr::only($data, $FIELDS);

        // Buscar o crear por documentnumber
        $person = People::firstOrNew([
            'documentnumber' => $data['documentnumber'],
        ]);
        $isNew = !$person->exists;

        // Asignar valores
        $person->fill($payload);
        if ($authId) {
            $isNew ? $person->user_id = $authId : $person->updated_by = $authId;
        }
        $person->save();

        return response()->json([
            'message' => $isNew ? 'Registrada correctamente.' : 'Persona actualizada correctamente.',
            'action'  => $isNew ? 'created' : 'updated',
            'data'    => $person->only(array_merge(['id'], $FIELDS, $isNew ? ['user_id'] : ['updated_by'])),
            'status'  => 200
        ], $isNew ? 201 : 200);
    }

    public function getBusinessmanData($numberDocument)
    {
        try {
            $person = People::where('documentnumber', $numberDocument)
                ->select(
                    'id',
                    'documentnumber',
                    'name',
                    'lastname',
                    'middlename',
                    'address',
                    'phone',
                    'email',
                    'birthday',
                    'sick',
                    'hasSoon',
                    'country_id',
                    'city_id',
                    'province_id',
                    'district_id',
                    'address',
                    'typedocument_id',
                    'gender_id',
                    'user_id'
                )->first();

            if ($person) {
                return response()->json([
                    'status'  => 200,
                    'message' => 'Registro encontrado',
                    'data'    => $person
                ]);
            }

            return response()->json([
                'status'  => 404,
                'message' => 'No se encontró el registro'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Ocurrió un error al obtener la información',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // actualiza los datos
    public function updateDataBusinessman(Request $request, $id)
    {
        try {
            $person = People::find($id);

            if (!$person) {
                return response()->json(['message' => 'Persona no encontrada', 'status' => 404]);
            }

            // Verificar si el documentnumber ya existe
            if (People::where('documentnumber', $request->input('documentnumber'))
                ->where('id', '!=', $id) // Aseguramos que no sea el mismo registro
                ->exists()
            ) {
                return response()->json(['message' => 'El número de documento ya existe. No se puede editar.', 'status' => 409]);
            }

            $person->update($request->all());
            return response()->json(['message' => 'Datos actualizados correctamente', 'status' => 200]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar los datos: ' . $e->getMessage(), 'status' => 500]);
        }
    }
}
