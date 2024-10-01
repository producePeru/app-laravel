<?php

namespace App\Http\Controllers\Formalization;

use App\Http\Controllers\Controller;
use App\Models\ActionPlans;
use App\Models\Advisory;
use App\Models\Formalization10;
use App\Models\Formalization20;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanActionsController extends Controller
{
    public function planActions()
    {
        $advisories = DB::table('advisories')
            ->whereNotNull('ruc')
            ->whereRaw('LENGTH(ruc) = 11')
            ->select('ruc')
            ->distinct()
            ->get();

        $formattedResults = $advisories->map(function ($item) {
            return [
                'name' => null,
                'ruc' => $item->ruc,
            ];
        });

        $response = [
            'data' => $formattedResults,
        ];

        return response()->json($response);
    }

    public function rucFormalizationR20Set()
    {
        $advisories = DB::table('formalizations20')
            ->whereNotNull('ruc')
            ->whereRaw('LENGTH(ruc) = 11')
            ->select('ruc', 'user_id', 'nameMype')
            ->distinct()
            ->get();

        $existingRucs = DB::table('mypes')
            ->pluck('ruc')
            ->toArray();

        $newRucs = $advisories->filter(function ($item) use ($existingRucs) {
            return ! in_array($item->ruc, $existingRucs);
        });

        $dataToInsert = $newRucs->map(function ($item) {
            return [
                'name' => $item->nameMype,
                'ruc' => $item->ruc,
                'user_id' => $item->user_id,
            ];
        })->toArray();

        if (! empty($dataToInsert)) {
            DB::table('mypes')->insert($dataToInsert);
        }

        $response = [
            'data' => $dataToInsert,
        ];

        return response()->json(['message' => 'success', 'status' => 200]);
    }

    public function rucFormalizationR10Set()
    {
        $advisories = DB::table('formalizations10')
            ->whereNotNull('ruc')
            ->whereRaw('LENGTH(ruc) = 11')
            ->select('ruc', 'user_id')
            ->distinct()
            ->get();

        $existingRucs = DB::table('mypes')
            ->pluck('ruc')
            ->toArray();

        $newRucs = $advisories->filter(function ($item) use ($existingRucs) {
            return ! in_array($item->ruc, $existingRucs);
        });

        $dataToInsert = $newRucs->map(function ($item) {
            return [
                'name' => null,
                'ruc' => $item->ruc,
                'user_id' => $item->user_id,
            ];
        })->toArray();

        if (! empty($dataToInsert)) {
            DB::table('mypes')->insert($dataToInsert);
        }

        $response = [
            'data' => $dataToInsert,
        ];

        return response()->json(['message' => 'success', 'status' => 200]);
    }

    public function rucAdvisoriesSet()
    {
        $advisories = DB::table('advisories')
            ->whereNotNull('ruc')
            ->whereRaw('LENGTH(ruc) = 11')
            ->select('ruc', 'user_id')
            ->distinct()
            ->get();

        $existingRucs = DB::table('mypes')
            ->pluck('ruc')
            ->toArray();

        $newRucs = $advisories->filter(function ($item) use ($existingRucs) {
            return ! in_array($item->ruc, $existingRucs);
        });

        $dataToInsert = $newRucs->map(function ($item) {
            return [
                'name' => null,
                'ruc' => $item->ruc,
                'user_id' => $item->user_id,
            ];
        })->toArray();

        if (! empty($dataToInsert)) {
            DB::table('mypes')->insert($dataToInsert);
        }

        $response = [
            'data' => $dataToInsert,
        ];

        return response()->json(['message' => 'success', 'status' => 200]);
    }

    public function listAllServicesAF($ruc)
    {
        $advisories = Advisory::where('ruc', $ruc)->with(['component'])->orderBy('created_at', 'desc')->get();
        $f10 = Formalization10::where('ruc', $ruc)->orderBy('created_at', 'desc')->first();
        $f20 = Formalization20::where('ruc', $ruc)->orderBy('created_at', 'desc')->first();

        $components = [
            'component_1' => null,
            'component_2' => null,
            'component_3' => null,
            'endDate' => null,
        ];

        $addedComponents = []; // Arreglo auxiliar para almacenar componentes ya agregados

        if ($f20) {
            $components['component_1'] = 4;
            $components['endDate'] = $f20->created_at;
        } elseif ($f10) {
            $components['component_1'] = 4;
            $components['endDate'] = $f10->created_at;
        }

        if (! $f20 && ! $f10 && $advisories->isNotEmpty()) {
            $advisoryComponents = $advisories->take(3);
            foreach ($advisoryComponents as $index => $advisory) {
                if ($index == 0 && ! $components['component_1']) {
                    $components['component_1'] = $advisory->component->id;
                    $components['endDate'] = $advisory->created_at;
                    $addedComponents[] = $advisory->component->id;
                } elseif ($index == 1 && ! $components['component_2']) {
                    if (! in_array($advisory->component->id, $addedComponents)) {
                        $components['component_2'] = $advisory->component->id;
                        $addedComponents[] = $advisory->component->id;
                    }
                } elseif ($index == 2 && ! $components['component_3']) {
                    if (! in_array($advisory->component->id, $addedComponents)) {
                        $components['component_3'] = $advisory->component->id;
                        $addedComponents[] = $advisory->component->id;
                    }
                }
            }
        } elseif ($advisories->isNotEmpty()) {
            $advisoryComponents = $advisories->take(2);
            foreach ($advisoryComponents as $index => $advisory) {
                if (! in_array($advisory->component->id, $addedComponents)) {
                    $components['component_'.($index + 2)] = $advisory->component->id;
                    $addedComponents[] = $advisory->component->id;
                }
            }
        }

        return response()->json(['data' => $components, 'status' => 200]);
    }

    public function store(Request $request)
    {
        $user_role = getUserRole();

        // $existingActionPlan = ActionPlans::where('ruc', $request->ruc)->first();

        // if ($existingActionPlan) {
        //     return response()->json(['message' => 'El RUC ya está en uso', 'status' => 400]);
        // }

        $data = [
            'people_id' => $request->people_id,
            'asesor_id' => $user_role['user_id'],
            'cde_id' => $request->cde_id,
            'component_1' => $request->component_1,
            'component_2' => $request->component_2,
            'component_3' => $request->component_3,
            'ruc' => $request->ruc,
            'numberSessions' => (
                ($request->component_1 ? 1 : 0) +
                ($request->component_2 ? 1 : 0) +
                ($request->component_3 ? 1 : 0)
            ),
            'startDate' => $request->startDate,
            'endDate' => $request->endDate,
            'totalDate' => Carbon::parse($request->startDate)->diffInDays(Carbon::parse($request->endDate)),
        ];

        $actionPlan = ActionPlans::create($data);

        if ($actionPlan) {
            return response()->json(['message' => 'Plan de acción creado con éxito', 'status' => 200]);
        } else {
            return response()->json(['message' => 'Error al crear el action plan', 'status' => 500]);
        }
    }

    //INDEX _+++_
    public function index(Request $request)
    {
        $user_role = getUserRole();
        $role_array = $user_role['role_id'];
        $search = $request->input('search');

        $query = ActionPlans::with([
            'user.profile:id,user_id,name,lastname,middlename,notary_id,cde_id,documentnumber',
            'cde',
            'businessman',
            'businessman.city:id,name',
            'businessman.province:id,name',
            'businessman.district:id,name',
            'businessman.gender:id,avr',
            'component1',
            'component2',
            'component3',
        ])->search($search)
            ->orderBy('created_at', 'desc');

        // Filtrar por roles
        if (in_array(2, $role_array) && ! in_array(1, $role_array)) {
            $query->where('asesor_id', $user_role['user_id']);
        }

        $data = $query->paginate(50);

        $data->getCollection()->transform(function ($item) {
            return $this->transformActionPlan($item);
        })->values();

        return response()->json(['data' => $data, 'status' => 200]);
    }

    private function transformActionPlan($item)
    {
        return [
            'id' => $item->id,
            'centro_empresa' => $item->cde->name,
            'asesor' => $this->formatFullName($item->user->profile),
            'asesor_dni' => $item->user->profile->documentnumber,
            'emprendedor_region' => $item->businessman->city->name,
            'emprendedor_provincia' => $item->businessman->province->name,
            'emprendedor_distrito' => $item->businessman->district->name,
            'emprendedor_nombres' => $this->formatFullName($item->businessman),
            'emprendedor_dni' => $item->businessman->documentnumber,
            'ruc' => $item->ruc,
            'genero' => $item->businessman->gender->avr,
            'discapacidad' => $item->businessman->sick,
            'component_1' => $item->component1->name,
            'component_2' => optional($item->component2)->name,
            'component_3' => optional($item->component3)->name,

            'component_1_id' => optional($item->component1)->id,
            'component_2_id' => optional($item->component2)->id,
            'component_3_id' => optional($item->component3)->id,

            'numberSessions' => $item->numberSessions,
            'startDate' => Carbon::parse($item->startDate)->format('d-m-Y'),
            'endDate' => Carbon::parse($item->endDate)->format('d-m-Y'),
            'totalDate' => $item->totalDate,
            'actaCompromiso' => $item->actaCompromiso,
            'envioCorreo' => $item->envioCorreo,
            'status' => $item->status,
            'details' => $item->details,
            'updated_at' => Carbon::parse($item->updated_at)->format('d-m-Y'),
        ];
    }

    private function formatFullName($profile)
    {
        return $profile->name.' '.$profile->lastname.' '.$profile->middlename;
    }

    public function editComponent(Request $request)
    {
        $user_role = getUserRole();
        $role_array = $user_role['role_id'];

        if (in_array(1, $role_array) && in_array(5, $role_array)) {
            $validatedData = $request->validate([
                'idPlan' => 'required|integer',
                'nameComponent' => 'required|string|in:component_1,component_2,component_3',
                'valueComponent' => 'required|integer',
            ]);

            $actionPlan = ActionPlans::find($validatedData['idPlan']);

            // Verificar si el valor ya existe en alguno de los otros componentes

            if (
                ($validatedData['nameComponent'] !== 'component_1' && $actionPlan->component_1 == $validatedData['valueComponent']) ||
                ($validatedData['nameComponent'] !== 'component_2' && $actionPlan->component_2 == $validatedData['valueComponent']) ||
                ($validatedData['nameComponent'] !== 'component_3' && $actionPlan->component_3 == $validatedData['valueComponent'])
            ) {
                return response()->json(['message' => 'El valor ya existe en otro componente. No se permiten valores duplicados en los componentes.', 'status' => 400]);
            }

            $actionPlan->{$validatedData['nameComponent']} = $validatedData['valueComponent'];
            $actionPlan->save();

            return response()->json(['message' => 'Componente actualizado exitosamente.', 'status' => 200]);
        }
    }

    public function updateField(Request $request)
    {
        $user_role = getUserRole();
        $role_array = $user_role['role_id'];

        // if (!in_array(2, $role_array) && !in_array(7, $role_array)) {
        //     return response()->json(['message' => 'No tienes permisos para actualizar este campo', 'status' => 401]);
        // }

        $validatedData = $request->validate([
            'idPlan' => 'required|integer',
            'type' => 'required|string|in:actaCompromiso,envioCorreo',
            'value' => 'required',
        ]);

        $actionPlan = ActionPlans::find($validatedData['idPlan']);

        if (is_null($actionPlan->{$validatedData['type']})) {
            $actionPlan->{$validatedData['type']} = $validatedData['value'];
            $actionPlan->save();

            return response()->json(['message' => ucfirst($validatedData['type']).' actualizado exitosamente.', 'status' => 200]);
        } else {
            return response()->json(['message' => 'El campo '.$validatedData['type'].' ya tiene un valor y no se puede actualizar.', 'status' => 400]);
        }
    }

    public function update(Request $request)
    {
        $user_role = getUserRole();
        $role_array = $user_role['role_id'];

        $validatedData = $request->validate([
            'people_id' => 'required|integer',
            'cde_id' => 'required|integer',
            'component_1' => 'required',
            'component_2' => 'nullable',
            'component_3' => 'nullable',
            'ruc' => 'nullable|string|max:11',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'idItem' => 'required|integer',
        ]);

        $payload = [
            'people_id' => $validatedData['people_id'],
            'cde_id' => $validatedData['cde_id'],
            'component_1' => $validatedData['component_1'],
            'component_2' => $validatedData['component_2'],
            'component_3' => $validatedData['component_3'],
            'ruc' => $validatedData['ruc'],
            'startDate' => $validatedData['startDate'],
            'endDate' => $validatedData['endDate'],
        ];

        if (in_array(1, $role_array) || in_array(5, $role_array)) {

            $actionPlan = ActionPlans::where('id', $validatedData['idItem'])->update($payload);

            if ($actionPlan) {
                return response()->json(['message' => 'Plan de acción actualizado', 'status' => 200]);
            } else {
                return response()->json(['message' => 'Action Plan not found or update failed', 'status' => 404]);
            }
        }
    }

    public function delete($id)
    {
        $user_role = getUserRole();
        $role_array = $user_role['role_id'];

        if (in_array(1, $role_array) || in_array(5, $role_array)) {
            $actionPlan = ActionPlans::find($id);

            if ($actionPlan) {
                $actionPlan->delete();

                return response()->json(['message' => 'Se ha eliminado el registro', 'status' => 200]);
            } else {
                return response()->json(['message' => 'Action Plan not found'], 404);
            }

        }
    }

    public function changeStatus($id, $status)
    {
        if (! in_array($status, ['aprobado', 'observado'])) {
            return response()->json(['error' => 'Estado no válido'], 400);
        }

        $actionPlan = ActionPlans::find($id);

        if ($actionPlan) {
            $actionPlan->status = $status;
            $actionPlan->save();

            return response()->json(['message' => 'Estado actualizado']);
        }

        return response()->json(['error' => 'Plan de acción no encontrado'], 404);
    }

    public function sendMessageDetails(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:actionsplans,id',
            'details' => 'required|string|max:255',
        ]);

        $actionPlan = ActionPlans::find($request->id);

        if ($actionPlan) {
            $actionPlan->details = $request->details;
            $actionPlan->save();

            return response()->json(['message' => 'Se han guardado', 'status' => 200]);
        }

        return response()->json(['error' => 'Plan de acción no encontrado'], 404);
    }
}
