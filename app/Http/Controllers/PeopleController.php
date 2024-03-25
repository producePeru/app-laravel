<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Crypt;
use App\Models\Post;
use App\Models\Departament;
use App\Models\Province;
use App\Models\District;
use App\Models\AdviserSupervisor;
use App\Models\People;
use App\Models\Post_Person;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\Return_;
use Illuminate\Support\Facades\Http;


class PeopleController extends Controller
{
    public function dniSearch(Request $request, $type, $num)
    {

        $person = People::where('number_document', $num)->first();

        if($person) {

            $data = [
                'numeroDocumento' => $person->number_document,
                'apellidoMaterno' => $person->middle_name,
                'apellidoPaterno' => $person->last_name,
                'nombres' => $person->name,
                'department' => $person->department,
                'province' => $person->province,
                'district' => $person->district,
                'email' => $person->email,
                'phone' => $person->phone,
                'document_type' => $person->document_type,
                'birthdate' => $person->birthdate,
                'gender' => $person->gender,
                'lession' => $person->lession
            ];

            return response()->json(['data' => $data]);

        } else {

            if($type === 'dni') {
                $apiUrl = "https://api.apis.net.pe/v2/reniec/dni?numero={$num}";

                try {
                    $client = new Client();
                    $response = $client->request('GET', $apiUrl, [
                        'headers' => [
                            'Authorization' => 'Bearer apis-token-6688.nekxM8GmGEHYD9qosrnbDWNxQlNOzaT5', 
                            'Accept' => 'application/json',
                        ],
                    ]);

                    $data = json_decode($response->getBody(), true);

                    return response()->json(['data' => $data]);
                } catch (\Exception $e) {
                    return response()->json(['status' => 404]);
                }
            } else {
                return response()->json(['status' => 404]);
            }
        }
    }

    public function formalizationRecaptcha(Request $request)
    {   
        
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => '6LcQMYopAAAAAEHqCHDRyjOofIVdcSzxqlHM4mUS', 
            'response' => $request->input('recaptcha_token'),
            'remoteip' => $request->ip()
        ]);

        $responseData = $response->json();

        if ($responseData['success']) {
            return $this->personCreate(new Request($request->data));
        } else {
            return response()->json(['message' => 'Error al validar reCAPTCHA', 'status' => 400]);
        }
    }

    public function personCreate(Request $request)
    {
        $post = Post::find($request->post);

        if (!$post) {
            return response()->json(['message' => 'Este rol no existe amiwito'], 404);
        }

        $user = People::where('number_document', $request->number_document)->first();

        if ($user) {
            
            $data = $request->except('created_by'); 
            // $data['update_by'] = Crypt::decryptString($request->update_by);
            $user->update($data);
            $mensaje = "Usuario actualizado exitosamente.";

        } else {

            $data = array_merge($request->except('update_by'));
            $user = People::create($data);
            $mensaje = "Usuario creado exitosamente.";
        }


        // ASESOR CON SUPERVISOR
        // if ($request->supervisor) {
        //     $super = [
        //         'id_adviser' => $user->id,cccccccc 
        //         'id_supervisor' => $request->supervisor,
        //         'created_by' => $request->created_by
        //     ];
        
        //     $existingRecord = AdviserSupervisor::where('id_adviser', $user->id)->first();
        
        //     if ($existingRecord) {
        //         $existingRecord->update($super);
        //     } else {
        //         AdviserSupervisor::create($super);
        //     }
        // }

        $values = [
            'numero' => $request->number_document,
            'post' => $request->post,
            // 'departament' => $request->department,
            // 'province' => $request->province,
            // 'district' => $request->district,
            'msg' => $mensaje
        ];

        return $this->newTopAssigned($values);
    }

    public function newTopAssigned($request)
    {
        $assign = Post_Person::where('dni_people', $request['numero'])
            ->where('id_post', $request['post'])
            ->first();

        if (!$assign) {
            Post_Person::create([
                'dni_people' => $request['numero'],
                'id_post' => $request['post'],
                'status' => 1
            ]);
        } else {
            $assign->update(['status' => 1]);
        }

        return response()->json(['message' => $request['msg']]);
    }
    
    public function userAsesor(Request $request)
    {
        $existingRecord = AdviserSupervisor::where('id_adviser', $request->id_adviser)->first();

        $user = People::where('number_document', $request->number_document)->first();

        $super = [
            'id_adviser' => $user->id, 
            'id_supervisor' => $request->id_supervisor,
            'created_by' => $request->created_by
        ];

        if ($existingRecord) {
            $existingRecord->update($super);
        } else {
            AdviserSupervisor::create($super);
        }
    }

    public function index($idPost)
    {
        $result = Post_Person::with('people')
        ->where('status', 1)
        ->where('id_post', $idPost)
        ->get();

        $data = $result->map(function ($item) {
            return [
                'document_type' => $item->people->document_type,
                'number_document' => $item->people->number_document,
                'last_name' => ucfirst(strtolower($item->people->last_name)),
                'middle_name' => ucfirst(strtolower($item->people->middle_name)),
                'name' => ucwords(strtolower($item->people->name)),
                'department' => $this->getDepartmentName($item->people->departament),
                'province' => Province::find($item->people->province),
                'district' => District::find($item->people->district),
                'phone' => $item->people->phone,
                'email' => $item->people->email,
                'post' => $item->id_post,
                'created_by' => $item->people->created_by,
                'update_by' => $item->people->update_by
            ];
        });
        
        return response()->json(['data' => $data]);
    }


    function getDepartmentName($id) {
        return $id;
    }

    function show($dniNumber) {
        try {
        
            $person = People::where('number_document', $dniNumber)->first();

            if (!$person) {
                return response()->json(['error' => 'Persona no encontrada'], 404);
            }

            $data = $person->makeHidden(['created_by', 'created_at', 'updated_at', 'update_by', 'id']);
        
            return response()->json(['data' => $data]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
        
    }

    function deleteUser($dniNumber, $rol) {
        try {
        
            $post = Post_Person::where('dni_people', $dniNumber)
                                ->where('id_post', $rol)
                                ->first();

            if (!$post) {
                return response()->json(['error' => 'No encontrado'], 404);
            }
            
            if ($post->status == 1) {
                $post->update(['status' => 0]);
                $data = ['success' => true, 'message' => 'Usuario eliminado correctamente'];
            } else {
                $data = ['success' => false, 'message' => 'No se puede eliminar este usuario'];
            }

            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    function allSupervisores() {
        $result = Post_Person::with('people')
        ->where('status', 1)
        ->where('id_post', 1)
        ->get();

        return $result;

        $data = $result->map(function ($item) {
            return [
                'label' => ucfirst(strtolower($item->people->last_name)) . ' ' .  ucfirst(strtolower($item->people->middle_name)) . ' ' . ucwords(strtolower($item->people->name)),
                'value' => $item->people->id,
            ];
        });
        return $data;
    }

    public function isApplicantNew($dni)
    {
        $result = People::where('number_document', $dni)->first();

        if($result) {
            $data = [
                'id' => $result->id,
                'document_type' => $result->document_type,
                'number_document' => $result->number_document,
                'last_name' => $result->last_name,
                'middle_name' => $result->middle_name,
                'name' => $result->name,
                'email' => $result->email,
                'phone' => $result->phone,
                'department' => $result->department,
                'province' => $result->province,
                'district' => $result->district
            ];
            return $data;
        } else {
            return response()->json(['message' => 'Persona no encontrada', 'status' => 205]);
        }
    }

    public function updateDataUserProfile(Request $request, $dni)
    {
        $query = People::where('number_document', $dni)->first();

        if (!$query) {
            return response()->json(['message' => 'Este usuario no existe'], 404);
        }

        $request->last_name && $query->last_name = $request->input('last_name');
        $request->middle_name && $query->middle_name = $request->input('middle_name');
        $request->name && $query->name = $request->input('name');
        $request->email && $query->email = $request->input('email');
        $request->birthdate && $query->birthdate = $request->input('birthdate');
        $request->gender && $query->gender = $request->input('gender');
        $request->lession && $query->lession = $request->input('lession');
        $request->phone && $query->phone = $request->input('phone');

        $query->save();

        return response()->json(['message' => 'Registrado correctamente', 'status' => 200]);

    }
}