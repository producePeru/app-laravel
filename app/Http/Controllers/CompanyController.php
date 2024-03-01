<?php

namespace App\Http\Controllers;
use App\Models\Company;
use App\Models\CompanyPeople;
use Illuminate\Support\Facades\Crypt;
use GuzzleHttp\Client;

use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function rucSearch($ruc)
    {

        $company = Company::where('ruc', $ruc)->first();

        if($company) {

            $data = [
                'ruc' => $company->ruc,
                'social_reason' => $company->social_reason,
                'category' => $company->category,
                'person_type' => $company->person_type
            ];

            return response()->json(['data' => $data]);

        } else {

            $apiUrl = "https://api.apis.net.pe/v2/sunat/ruc?numero={$ruc}";

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
                return response()->json(['error' => $e->getMessage()], 500);
            }

        }

    }

    public function index()
    {
        $companies = Company::where('status', 1)->get();

        $data = $companies->makeHidden(['created_by', 'created_at', 'updated_at', 'update_by']);

        return response()->json(['data' => $companies]);
    }

    public function companyCreateUpadate(Request $request)
    {

        $company = Company::where('ruc', $request->ruc)->first();

        if ($company) {

            $data = $request->except('created_by'); 
            
            $company->update($data);
            
            $mensaje = "Datos actualizados exitosamente.";

        } else {

            $data = array_merge($request->except('update_by'));
            
            Company::create($data);
            
            $mensaje = "Empresa creada exitosamente.";

        }

        return response()->json(['mensaje' => $mensaje]);

    }

    public function companyPersonRegister(Request $request)
    {
        $percomp = CompanyPeople::where('ruc', $request->ruc)
        ->where('number_document', $request->number_document)
        ->get();

        if ($percomp->isEmpty()) {
            CompanyPeople::create([
                'ruc' => $request->ruc,
                'number_document' => $request->number_document
            ]);
            return response()->json(['message' => 'Registrada correctamente'], 200);
        } else {
            return response()->json(['message' => 'Ya existe una persona con estos datos'], 200);
        }
    }

    public function deleteCompany($id)
    {
        try {
            $company = Company::find($id);

            if (!$company) {
                return response()->json(['error' => 'No encontrado'], 404);
            }

            if ($company->status == 1) {
                $company->update(['status' => 0]);
                $mensaje = "Se ha eliminado el registro";
            } else {
                $mensaje = "No se puede eliminar este registro";
            }

            return response()->json(['mensaje' => $mensaje]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

}
