<?php

namespace App\Http\Controllers\Formalization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\People;
use App\Models\FormalizationDigital;
use Illuminate\Support\Facades\DB;

class FormalizationDigitalController extends Controller
{


    public function index()
    {
        $formalizationDigitals = FormalizationDigital::with(['people.city', 'people.province', 'people.district'])->paginate(10);
        return response()->json(['data' => $formalizationDigitals]);
    }


    public function formalizationDigital(Request $request)
    {
        $documentNumber = $request->input('documentnumber');

        $person = People::where('documentnumber', $documentNumber)->first();

        if ($person) {
            $person->fill($request->all());
            $person->save();
        } else {
            $person = new People();
            $person->fill($request->all());
            $person->save();
        }

        $formalization = FormalizationDigital::where('documentnumber', $documentNumber)->first();

        if ($formalization) {
            $formalization->fill($request->all());
            $formalization->save();
        } else {
            $formalization = new FormalizationDigital();
            $formalization->fill($request->all());
            $formalization->save();
        }

        return response()->json(['message' => 'Operación completada con éxito', 'status' => 200]);
    }

    public function deleteRegister($id)
    {
        DB::table('formalizationdigital')->where('id', $id)->update(['deleted_at' => now()]);
        return response()->json(['message' => 'Se ha eliminado correctamente', 'status' => 200]);
    }

    public function updateAttendedStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:formalizationdigital,id',
            'documentnumber' => 'required|exists:formalizationdigital,documentnumber',
        ]);

        $formalizationDigital = FormalizationDigital::where('id', $request->id)
                                ->where('documentnumber', $request->documentnumber)
                                ->first();
        if ($formalizationDigital) {
            $formalizationDigital->attended = 1;
            $formalizationDigital->save();
            return response()->json(['message' => 'El valor de attended se actualizó correctamente.']);
        } else {
            return response()->json(['error' => 'Los valores proporcionados no existen en la tabla formalizationdigital.'], 404);
        }
    }

    public function getStatusByDocumentNumber(Request $request)
    {
        try {
            $request->validate([
                'documentnumber' => 'required|exists:formalizationdigital,documentnumber',
            ]);

            $formalizationDigital = FormalizationDigital::where('documentnumber', $request->documentnumber)->first();

            if ($formalizationDigital) {
                $status = $formalizationDigital->status;
                $user = $formalizationDigital->people->name . ' '. $formalizationDigital->people->lastname;

                return response()->json(['user' => $user, 'status' => $status]);
            }

        } catch (\Exception $e) {
            return response()->json(['message' => 'Proceso de formalizacion nueva', 'status' => 404]);
        }
    }
}
