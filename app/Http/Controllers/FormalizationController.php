<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\People;
use App\Models\Post_Person;
use App\Models\Notary;
use App\Models\Company;
use App\Models\Gpscde;
use App\Models\FormFormalization;
use App\Models\Formalization20;
use App\Models\ComercialActivity;

use App\Jobs\FormalizationFormPublicWorkshopJob;

class FormalizationController extends Controller
{
    
    public function myFormalizationsRuc20($dni)
    {
        $data = Formalization20::where('dni', $dni)->get();

        return response()->json(['data' => $data]);
    }

    public function chooseFormalizationRuc20($id)
    {
        $data = Formalization20::where('id', $id)->first();
        return response()->json(['data' => $data]);
    }

    public function setPersonPost(Request $request)
    {
        $assign = Post_Person::where('dni_people', $request->number)
            ->where('id_post', $request->id_post)
            ->first();

        if (!$assign) {
            Post_Person::create([
                'dni_people' => $request->number,
                'id_post' => $request->id_post,
                'status' => 1
            ]);
        }

        return response()->json(['message' => 'success']);
    }


    public function formalizationRuc20(Request $request)
    {
        $solicitante = People::where('number_document', $request->dni)
            ->where('id', $request->id_person)
            ->first();

        if($solicitante) {
            $formalization = Formalization20::where('dni', $request->dni)
            ->where('code_sid_sunarp', $request->code_sid_sunarp)
            ->first();

            if($formalization) {
                $formalization->update($request->except('created_by'));
                $message = "Los datos se han actualizado";
            } else {
                Formalization20::create($request->all());
                $message = "Se ha registrado con éxito";
            }

            return response()->json(['message' => $message]);
        }
    }

    public function getAllSelectNotary()
    {
        $notaries = Notary::where('status', 1)->get();

        $data = $notaries->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getAllSelectComercialActivities()
    {
        $categories = ComercialActivity::where('status', 1)->get();
        $data = $categories->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function createComercialActivities(Request $request)
    {
        ComercialActivity::create($request->all());
        return response()->json(['message' => 'Categoría creada']);
    }

    public function formalizationToCompany(Request $request) 
    {
        $company = Company::where('ruc', $request->ruc)->first();

        if(!$company) {

            Company::create($request->all());
            return response()->json(['message' => 'Registro exitoso']);

        } else {
            return response()->json(['message' => 'Este RUC ya se encuentra registrado']);
        }
    }

    public function formalizationPublicForm(Request $request)
    {
        $formalization = FormFormalization::where('dni', $request->dni)->first();
        if ($formalization) {
            $formalization->count += 1;
            $formalization->update($request->all());
        } else {
            FormFormalization::create($request->all());
        }

        // $email = $request->email;

        // $formalizationform = [       
        //     'name_complete' => $request->name
        // ];

        // FormalizationFormPublicWorkshopJob::dispatch($email, $formalizationform);
    
        return response()->json(['message' => 'Un asesor se comunicará contigo, gracias', 'status' => '200']);
    }

    public function gpsCdes()
    {
        $data = Gpscde::select('lat_cde', 'log_cde', 'name_cde')->get()->map(function ($item) {
            return [
                'position' => [
                    'lat' => $item->lat_cde,
                    'lng' => $item->log_cde,
                ],
                'title' => $item->name_cde,
            ];
        });
    
        return response()->json(['data' => $data]);
    }
}



