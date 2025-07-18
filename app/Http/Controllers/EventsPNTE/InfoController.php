<?php

namespace App\Http\Controllers\EventsPNTE;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\StoreEmpresarioRequest;
use App\Models\Empresa;
use App\Models\Empresario;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class InfoController extends Controller
{
    public function createEmpresa(StoreCompanyRequest $request)
    {
        try {

            $existingCompany = Empresa::where('ruc', $request->ruc)->first();

            if ($existingCompany) {

                return response()->json([
                    'message' => 'Empresa ya registrada con este RUC.',
                    'status' => 200
                ]);
            }

            $company = Empresa::create($request->all());

            return response()->json([
                'message' => 'Company created successfully',
                'data' => $company,
                'status' => 200
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Model not found error: ' . $e->getMessage(),
                'status' => 404
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error: ' . $e->getMessage(),
                'status' => 500
            ]);
        }
    }


    public function createEmpresario(StoreEmpresarioRequest $request)
    {
        try {

            $existingEmpresario = Empresario::where('dni', $request->dni)->first();

            if ($existingEmpresario) {
                return response()->json([
                    'message' => 'Empresario ya registrado con este DNI.',
                    'status' => 200
                ]);
            }

            $empresario = Empresario::create($request->all()); // Usa all() para crear con todos los datos

            return response()->json([
                'message' => 'Empresario created successfully',
                'data' => $empresario,
                'status' => 200 // Código HTTP para éxito
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Model not found error: ' . $e->getMessage(),
                'status' => 404
            ]);
        } catch (Exception $e) {
            // Si ocurre otro tipo de error, devolver un error genérico 500
            return response()->json([
                'message' => 'Error: ' . $e->getMessage(),
                'status' => 500
            ]);
        }
    }
}
