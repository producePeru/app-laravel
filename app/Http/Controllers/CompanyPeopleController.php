<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\People;
use App\Models\Post;
use App\Models\Post_Person;
use Illuminate\Support\Facades\Crypt;

class CompanyPeopleController extends Controller
{
    public function insertUpdateCompany(Request $request)
    {
        $company = Company::where('ruc', $request->ruc)->first();

        if ($company) {

            if(!$request->update_by) {
                $data = $request->except('created_by');
            } else {
                $data['update_by'] = Crypt::decryptString($request->update_by);
            }
            
            $company->update($data);
            
            $mensaje = "Datos actualizados exitosamente.";

        } else {

            if(!$request->created_by) {
                $data = $request->except('update_by'); 
            } else {
                $data = array_merge($request->except('update_by'), ['created_by' => Crypt::decryptString($request->created_by)]);
            }

            Company::create($data);
            
            $mensaje = "Creado exitosamente.";

        }

        return response()->json(['mensaje' => $mensaje]);
    }

    public function insertUpdatePerson(Request $request)
    {
        $post = Post::find($request->post);

        if (!$post) {
            return response()->json(['message' => 'Este rol no existe amiwito'], 404);
        }

        $user = People::where('number_document', $request->number_document)->first();

        if ($user) {

            $data = $request->except(['created_by', 'update_by']);
            
            $user->update($data);

        } else {

            if(!$request->created_by) {
                $data = $request->except('update_by'); 
            } else {
                $data = array_merge($request->except('update_by'), ['created_by' => Crypt::decryptString($request->created_by)]);
            }

            People::create($data);
        }

        $values = [
            'numero' => $request->number_document,
            'post' => $request->post
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

        return response()->json(['mensaje' => "ok"]);
    }

    
}
