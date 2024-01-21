<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Agreement;
use App\Models\AgreementPDF;
use App\Models\Commitment;
use App\Models\User;
use App\Http\Requests\StoreAgreementRequest;
use App\Http\Requests\StoreCommitmentRequest;
use Carbon\Carbon;

class AgreementsController extends Controller
{
    public function newAgreement(StoreAgreementRequest $request)
    {
        $data = $request->validated();

        $user = User::where('_id', $request['created_by'])->first();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $dateCarbon1 = Carbon::parse($request->dateIssue);
        $dateCarbon2 = Carbon::parse($request->effectiveDate);
        $dateCarbon3 = Carbon::parse($request->dueDate);
        
        $dateIssue = $dateCarbon1->format('Y-m-d');
        $effectiveDate = $dateCarbon2->format('Y-m-d');
        $dueDate = $dateCarbon3->format('Y-m-d');
  
        $data['created_by'] = $user->id;
        $data['dateIssue'] = $dateIssue;
        $data['effectiveDate'] = $effectiveDate;
        $data['dueDate'] = $dueDate;

        try {
            $agreement = Agreement::create($data);
            return response()->json(['message' => 'Convenio creado correctamente', 'data' => $agreement->id], 200);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error al crear el Convenio. ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al crear el convenio.'. $e], 500);
        }
    }

    public function uploadPdf(Request $request)
    {
        $user = User::where('_id', $request['created'])->first();
   
        try {
            $request->validate([
                'pdf' => 'required|mimes:pdf|max:10240', // PDF y tamaño máximo de 10 MB
            ]);

            $path = $request->file('pdf')->store('conveniospdf');

            AgreementPDF::create([
                'created' => $user->id,
                'name' => $request->file('pdf')->getClientOriginalName(),
                'path' => $path,
            ]);

            return response()->json(['message' => 'Archivo subido correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al subir el archivo: ' . $e->getMessage()], 500);
        }
    }

    public function newCommitments(StoreCommitmentRequest $request)
    {
        $data = $request->validated();

        $user = User::where('_id', $request['created'])->first();
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $data['created'] = $user->id;

        try {
            $commitment = Commitment::create($data);
            return response()->json(['message' => 'Compromiso creado correctamente'], 200);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error al crear el Convenio. ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al crear el convenio.'. $e], 500);
        }
    }

    public function commitments($idAgreement)
    {
        $activeCommitments = Commitment::where('status', 'Active')
            ->where('idAgreement', $idAgreement)
            ->get();

        $groupedCommitments = $activeCommitments->groupBy('entity')->map(function ($group, $key) {
            $entityLabel = $key == 1 ? 'empresa' : 'aliados';
            return [
                $entityLabel => $group
            ];
        });

        return response()->json(['data' => $groupedCommitments->values()], 200);
    }

    public function deleteCommitments($idCommitment)
    {
        $commitment = Commitment::find($idCommitment);

        if ($commitment) {
            $commitment->status = 'Inactive';
            $commitment->save();
            return response()->json(['message' => 'Se ha eliminado con éxito'], 200);
        }
    }
}