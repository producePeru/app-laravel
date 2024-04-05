<?php

namespace App\Http\Controllers\Formalization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Formalization20;
use App\Models\Mype;
use Illuminate\Support\Facades\DB;

class Formalization20Controller extends Controller
{
    public function indexRuc20(Request $request)
    {
        $formalization = Formalization20::withFormalizationAndRelations();

        return response()->json($formalization, 200);
    }

    public function allFormalizationsRuc20ByPersonId($id)
    {
        $resultados = Formalization20::withFormalizationAndRelationsId($id);

        $resultados = $resultados->filter(function ($formalization) {
            return $formalization->task != 3;
        });

        return response()->json($resultados, 200);
    }

    public function ruc20Step1(Request $request)
    {
        try {
            $existingRecord = Formalization20::where('codesunarp', $request->codesunarp)->first();

            if ($existingRecord) {
                return response()->json(['message' => 'Ya existe una formalización tipo RUC 20 con el codigo SUNARP', 'status' => 400]);
            }

            $data = $request->all();
            Formalization20::create($data);

            return response()->json(['message' => 'Formalizacion tipo RUC 20 registrada correctamente', 'status' => 200]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al registrar la formalizacion tipo RUC 20', 'status' => $e]);
        }
    }


    public function ruc20Step2(Request $request, $codesunarp)
    {
        try {
            $existingRecord = Formalization20::where('codesunarp', $codesunarp)->first();

            if ($existingRecord) {

                $existingMype = Mype::where('name', $request->name)->first();

                if (!$existingMype) {
                    $newMype = new Mype;
                    $newMype->name = $request->name;
                    $newMype->user_id = $request->user_id;
                    $newMype->save();
                }

                $existingRecord->task = $request->task;
                $existingRecord->mype_id = $newMype->id;
                $existingRecord->numbernotary = $request->numbernotary;
                $existingRecord->notary_id = $request->notary_id;
                $existingRecord->userupdated_id = $request->userupdated_id;
                $existingRecord->save();

                DB::table('owners')->insert([
                    'mype_id' => $newMype->id,
                    'people_id' => $request->people_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                return response()->json(['message' => 'Formalizacion tipo RUC 20 registrada correctamente', 'status' => 200]);
            }

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al registrar la formalizacion tipo RUC 20', 'status' => $e]);
        }
    }

    public function ruc20Step3(Request $request, $codesunarp)
    {
        try {
            $existingRecord = Formalization20::where('codesunarp', $codesunarp)->first();

            if ($existingRecord) {

                $mype = Mype::where('id', $request->mype_id)->first();

                if ($mype) {
                    $mype->ruc = $request->ruc;
                    $mype->save();
                }

                $existingRecord->task = $request->task;
                $existingRecord->save();

                return response()->json(['message' => 'Formalizacion tipo RUC 20 registrada correctamente', 'status' => 200]);

            }

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al registrar la formalizacion tipo RUC 20', 'status' => $e]);
        }
    }
}
