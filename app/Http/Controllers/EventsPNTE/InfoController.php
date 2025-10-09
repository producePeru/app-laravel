<?php

namespace App\Http\Controllers\EventsPNTE;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\StoreEmpresarioRequest;
use App\Models\Empresario;
use App\Models\Mype;
use App\Models\People;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class InfoController extends Controller
{
    public function createEmpresa(StoreCompanyRequest $request)
    {
        try {

            $existingCompany = Mype::where('ruc', $request->ruc)->first();

            if ($existingCompany) {

                return response()->json([
                    'message' => 'Empresa ya registrada con este RUC.',
                    'status' => 200
                ]);
            }

            $company = Mype::create($request->all());

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
            // Verificar si ya existe empresario
            $existingEmpresario = People::where('documentnumber', $request->documentnumber)->first();

            if ($existingEmpresario) {
                return response()->json([
                    'message' => 'Empresario ya registrado con este DNI.',
                    'status'  => 200
                ]);
            }

            // 🔹 Normalizar fecha a formato YYYY-MM-DD si viene con "/"
            if ($request->filled('birthday')) {
                $birthday = $request->birthday;

                // Detectar formato dd/mm/yyyy
                if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $birthday, $matches)) {
                    $normalized = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
                    $request->merge(['birthday' => $normalized]);
                }
            }

            // Crear empresario
            $empresario = People::create($request->all());

            return response()->json([
                'message' => 'Empresario creado correctamente.',
                'data'    => $empresario,
                'status'  => 200
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Model not found error: ' . $e->getMessage(),
                'status'  => 404
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error: ' . $e->getMessage(),
                'status'  => 500
            ]);
        }
    }
}
