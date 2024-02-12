<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Crypt;
use App\Models\Post;
use App\Models\Departament;
use App\Models\Province;
use App\Models\District;
use App\Models\People;
use App\Models\Post_Person;
use Illuminate\Support\Facades\DB;


class PeopleController extends Controller
{
    public function dniSearch(Request $request, $type, $num)
    {

        $person = People::where('number_document', $num)->first();

        if($person) {

            $data = [
                'apellidoMaterno' => $person->middle_name,
                'apellidoPaterno' => $person->last_name,
                'nombres' => $person->name,
                'department' => $person->department,
                'province' => $person->province,
                'district' => $person->district,
                'email' => $person->email,
                'phone' => $person->phone,
                'document_type' => $person->document_type
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
            People::create($data);
            $mensaje = "Usuario creado exitosamente.";
        }

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

        return response()->json(['mensaje' => $request['msg']]);
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
                'last_name' => $item->people->last_name,
                'middle_name' => $item->people->middle_name,
                'name' => $item->people->name,
                'department' => $this->getDepartmentName($item->people->departament),
                'province' => Province::find($item->people->province),
                'district' => District::find($item->people->district),
                'phone' => $item->people->phone,
                'email' => $item->people->email,
                'post' => $item->id_post,
                'created_by' => Crypt::encryptString($item->people->created_by),
                'update_by' => Crypt::encryptString($item->people->update_by)
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
    
}

