<?php

namespace App\Http\Controllers\Formalization;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormalization20Request;
use Illuminate\Http\Request;
use App\Models\Formalization20;
use App\Models\Mype;
use Illuminate\Support\Facades\Auth;
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
            // $existingRecord = Formalization20::where('codesunarp', $request->codesunarp)->first();

            // if ($existingRecord) {
            //     return response()->json(['message' => 'Ya existe una formalización tipo RUC 20 con el codigo SUNARP', 'status' => 400]);
            // }

            $data = $request->all();
            Formalization20::create($data);

            return response()->json(['message' => 'Formalizacion tipo RUC 20 registrada correctamente', 'status' => 200]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al registrar la formalizacion tipo RUC 20', 'status' => $e]);
        }
    }


    public function ruc20Step2(Request $request, $id)
    {
        try {

            if ($id == 000) {
                $data = $request->all();
                Formalization20::create($data);

                return response()->json(['message' => 'Formalizacion tipo RUC 20 registrada correctamente', 'status' => 200]);
            }


            $existingRecord = Formalization20::where('id', $id)->first();

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

    public function ruc20Step3(Request $request, $id)
    {
        try {
            $existingRecord = Formalization20::where('id', $id)->first();

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

    public function storeRuc20(StoreFormalization20Request $request)
    {
        try {
            $data = $request->validated();

            // Agregar el user_id del usuario autenticado
            $data['user_id'] = auth()->id();

            Formalization20::create($data);

            return response()->json([
                'message' => 'Formalización creada correctamente',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la formalización',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function getDataF20ById($id)
    {
        $f20 = Formalization20::find($id);

        if (!$f20) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        return response()->json(['data' => $f20, 'status' => 200]);
    }

    public function update(Request $request, $id)
    {
        $f20 = Formalization20::find($id);

        if (!$f20) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $request->validate([
            'city_id' => 'required|integer',
            'province_id' => 'required|integer',
            'district_id' => 'required|integer',
            'address' => 'nullable|string',
            'modality_id' => 'required|integer',
            'nameMype' => 'required|string',
            'ruc' => 'nullable',
            'dni' => 'nullable',
            'economicsector_id' => 'required|integer',
            'comercialactivity_id' => 'required|integer',
            'regime_id' => 'required|integer',
            'numbernotary' => 'nullable',
            'notary_id' => 'required|integer',
            'dateReception' => 'nullable|string',
            'dateTramite' => 'nullable|string',

            'userupdated_id' => 'required|integer',
            'typecapital_id' => 'nullable|integer',
            'isbic' => 'nullable|string',
            'montocapital' => 'nullable',
        ]);

        $f20->update($request->only([
            'city_id',
            'province_id',
            'district_id',
            'address',
            'modality_id',
            'nameMype',
            'ruc',
            'dni',
            'economicsector_id',
            'comercialactivity_id',
            'regime_id',
            'numbernotary',
            'notary_id',
            'dateReception',
            'dateTramite',
            'userupdated_id',
            'typecapital_id',
            'isbic',
            'montocapital',
        ]));

        return response()->json(['message' => 'Datos actualizados correctamente', 'status' => 200]);
    }

    public function destroy($id)
    {
        $item = Formalization20::find($id);

        if (!$item) {
            return response()->json(['message' => 'No se encontró este registro'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Registro eliminado correctamente'], 200);
    }


    public function updateValueRuc20(Request $request, $id)
    {
        try {
            // 1) Usuario autenticado
            $userId = Auth::id();
            if (!$userId) {
                return response()->json(['message' => 'No autenticado'], 401);
            }

            // 2) Buscar registro
            $f10 = Formalization20::find($id);
            if (!$f10) {
                return response()->json(['message' => 'No encontrado'], 404);
            }

            // 3) Validación
            $validated = $request->validate([
                'ruc'                  => 'nullable|digits:11',
                'regime_id'            => 'required|integer',
                'nameMype'             => 'required|string|max:255',
                'city_id'              => 'required|integer',
                'province_id'          => 'required|integer',
                'district_id'          => 'required|integer',
                'address'              => 'nullable|string|max:255',
                'modality_id'          => 'required|integer',
                'economicsector_id'    => 'required|integer',
                'comercialactivity_id' => 'required|integer',
                'numbernotary'         => 'nullable|string|max:50',
                'notary_id'            => 'nullable|integer',
                'dateReception'        => 'required|date',
                'dateTramite'          => 'required|date',
                'isbic'                => 'nullable|in:SI,NO',
                'montocapital'         => 'nullable|numeric',
                'typecapital_id'       => 'nullable|integer',
            ]);

            // 4) Preparar data para update
            $data = [
                'ruc'                  => $validated['ruc'] ?? null,
                'regime_id'            => $validated['regime_id'],
                'nameMype'             => $validated['nameMype'],
                'city_id'              => $validated['city_id'],
                'province_id'          => $validated['province_id'],
                'district_id'          => $validated['district_id'],
                'address'              => $validated['address'] ?? null,
                'modality_id'          => $validated['modality_id'],
                'economicsector_id'    => $validated['economicsector_id'],
                'comercialactivity_id' => $validated['comercialactivity_id'],
                'numbernotary'         => $validated['numbernotary'] ?? null,
                'notary_id'            => $validated['notary_id'] ?? null,
                'dateReception'        => $validated['dateReception'],
                'dateTramite'          => $validated['dateTramite'],
                'isbic'                => $validated['isbic'] ?? null,
                'montocapital'         => $validated['montocapital'] ?? null,
                'typecapital_id'       => $validated['typecapital_id'] ?? null,
                'userupdated_id '      => $userId, // 👈 del usuario autenticado
            ];

            // 5) Actualizar registro
            $f10->update($data);

            return response()->json([
                'message' => 'Formalización actualizada correctamente',
                'status'  => 200,
                'data'    => $f10
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
                'status'  => 422,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno en el servidor',
                'error'   => $e->getMessage(),
                'status'  => 500,
            ], 500);
        }
    }
}
