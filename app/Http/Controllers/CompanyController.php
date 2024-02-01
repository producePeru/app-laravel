<?php

namespace App\Http\Controllers;
use App\Models\Company;
use Illuminate\Support\Facades\Crypt;
use GuzzleHttp\Client;

use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function rucSearch($ruc)
    {
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
            $data['update_by'] = Crypt::decryptString($request->update_by);
            
            $company->update($data);
            
            $mensaje = "Datos actualizados exitosamente.";

        } else {

            $data = array_merge($request->except('update_by'), ['created_by' => Crypt::decryptString($request->created_by)]);
            
            Company::create($data);
            
            $mensaje = "Creado exitosamente.";

        }

        return response()->json(['mensaje' => $mensaje]);

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
